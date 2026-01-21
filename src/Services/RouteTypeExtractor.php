<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Services;

use Laravel\Ranger\Components\Route;
use Laravel\Ranger\Ranger;
use Laravel\Surveyor\Analyzed\MethodResult;
use Laravel\Surveyor\Analyzer\Analyzer;
use Laravel\Surveyor\Types\ClassType;
use Laravel\Surveyor\Types\Contracts\MultiType;
use Laravel\Surveyor\Types\Contracts\Type as TypeContract;
use OybekDaniyarov\LaravelTrpc\Attributes\ApiRoute;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;
use OybekDaniyarov\LaravelTrpc\Data\RouteTypeInfo;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Spatie\LaravelData\Data;
use Throwable;

/**
 * Extracts request and response type information from API routes.
 *
 * Uses Laravel Ranger to iterate over routes and Laravel Surveyor for static
 * analysis to detect Spatie Data class types from:
 * - Controller method parameters (request types)
 * - Return type declarations (response types)
 * - Service method return types (response types via chain following)
 *
 * The #[TypedRoute] attribute takes priority when explicitly declared.
 */
final class RouteTypeExtractor
{
    /** @var array<string, RouteTypeInfo> */
    private array $routeTypes = [];

    /** @var array<string, bool> */
    private array $classExistsCache = [];

    /** @var array<string, bool> */
    private array $methodExistsCache = [];

    /** @var array<string, ReflectionMethod|null> */
    private array $reflectionMethodCache = [];

    /** @var array<string, ReflectionClass<object>|null> */
    private array $reflectionClassCache = [];

    public function __construct(
        private readonly Ranger $ranger,
        private readonly Analyzer $analyzer,
    ) {}

    /**
     * Extract type information for all routes.
     *
     * @return array<string, RouteTypeInfo> Map of "method:uri" to type info
     */
    public function extractRouteTypes(): array
    {
        $this->ranger->onRoute(function (Route $route) {
            if (! $route->hasController()) {
                return;
            }

            $controller = mb_ltrim($route->controller(), '\\');
            $method = $route->method();

            if (! $this->classExists($controller) || ! $this->methodExists($controller, $method)) {
                return;
            }

            // Normalize URI by removing leading slash to match Laravel's route collection
            $uri = mb_ltrim($route->uri(), '/');

            // Key by HTTP method + URI to handle same URI with different methods
            $verb = $route->verbs()->first();
            $httpMethod = $verb !== null ? $verb->actual : 'get';
            $key = "{$httpMethod}:{$uri}";

            $this->routeTypes[$key] = $this->extractTypesForRoute($controller, $method, $route);
        });

        $this->ranger->walk();

        return $this->routeTypes;
    }

    private function classExists(string $class): bool
    {
        return $this->classExistsCache[$class] ??= class_exists($class);
    }

    private function methodExists(string $class, string $method): bool
    {
        $key = "{$class}::{$method}";

        return $this->methodExistsCache[$key] ??= method_exists($class, $method);
    }

    /**
     * Get a cached ReflectionMethod instance.
     */
    private function getReflectionMethod(string $class, string $method): ?ReflectionMethod
    {
        $key = "{$class}::{$method}";
        if (! isset($this->reflectionMethodCache[$key])) {
            try {
                $this->reflectionMethodCache[$key] = new ReflectionMethod($class, $method);
            } catch (Throwable) {
                $this->reflectionMethodCache[$key] = null;
            }
        }

        return $this->reflectionMethodCache[$key];
    }

    /**
     * Get a cached ReflectionClass instance.
     *
     * @param  class-string  $class
     * @return ReflectionClass<object>
     */
    private function getReflectionClass(string $class): ReflectionClass
    {
        return $this->reflectionClassCache[$class] ??= new ReflectionClass($class);
    }

    private function extractTypesForRoute(string $controller, string $method, Route $route): RouteTypeInfo
    {
        // Priority 0: Check #[TypedRoute] attribute first
        $attribute = $this->getTypedRouteAttribute($controller, $method);

        if ($attribute !== null) {
            $requestInfo = $attribute->request !== null
                ? ['type' => $this->formatTypeForTypeScript($attribute->request), 'isNullable' => false]
                : $this->extractRequestTypeWithNullable($controller, $method);

            return new RouteTypeInfo(
                requestType: $requestInfo['type'],
                queryType: $attribute->query !== null
                    ? $this->formatTypeForTypeScript($attribute->query)
                    : null,
                responseType: $attribute->response !== null
                    ? $this->formatTypeForTypeScript($attribute->response)
                    : $this->extractResponseTypeFromSurveyor($controller, $method),
                errorType: $attribute->errorResponse !== null
                    ? $this->formatTypeForTypeScript($attribute->errorResponse)
                    : null,
                isCollection: $attribute->isCollection,
                isPaginated: $attribute->isPaginated,
                isRequestNullable: $requestInfo['isNullable'],
            );
        }

        // Fallback: Use static analysis
        $requestInfo = $this->extractRequestTypeWithNullable($controller, $method);
        $responseInfo = $this->extractResponseTypeWithMeta($controller, $method);

        return new RouteTypeInfo(
            requestType: $requestInfo['type'],
            queryType: null,
            responseType: $responseInfo['type'],
            isCollection: $responseInfo['isCollection'],
            isPaginated: $responseInfo['isPaginated'],
            isRequestNullable: $requestInfo['isNullable'],
            isResponseNullable: $responseInfo['isNullable'],
        );
    }

    private function getTypedRouteAttribute(string $controller, string $method): TypedRoute|ApiRoute|null
    {
        $reflection = $this->getReflectionMethod($controller, $method);
        if ($reflection === null) {
            return null;
        }

        // Check for TypedRoute first (preferred)
        $attributes = $reflection->getAttributes(TypedRoute::class);
        if (! empty($attributes)) {
            return $attributes[0]->newInstance();
        }

        // Fall back to ApiRoute for backwards compatibility
        $attributes = $reflection->getAttributes(ApiRoute::class);
        if (! empty($attributes)) {
            return $attributes[0]->newInstance();
        }

        return null;
    }

    /** @phpstan-ignore method.unused */
    private function extractRequestTypeFromMethod(string $controller, string $method): ?string
    {
        return $this->extractRequestTypeWithNullable($controller, $method)['type'];
    }

    /**
     * Extract request type with nullable information.
     *
     * @return array{type: ?string, isNullable: bool}
     */
    private function extractRequestTypeWithNullable(string $controller, string $method): array
    {
        $result = ['type' => null, 'isNullable' => false];

        $reflection = $this->getReflectionMethod($controller, $method);
        if ($reflection !== null) {
            foreach ($reflection->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type === null) {
                    continue;
                }

                // Handle union types (PHP 8.0+)
                if ($type instanceof ReflectionUnionType) {
                    $hasNull = false;
                    foreach ($type->getTypes() as $unionType) {
                        if ($unionType instanceof ReflectionNamedType) {
                            if ($unionType->getName() === 'null') {
                                $hasNull = true;
                            } elseif (! $unionType->isBuiltin()) {
                                $typeName = $unionType->getName();
                                if ($this->classExists($typeName) && is_subclass_of($typeName, Data::class)) {
                                    $result['type'] = $this->formatTypeForTypeScript($typeName);
                                }
                            }
                        }
                    }
                    if ($result['type'] !== null) {
                        $result['isNullable'] = $hasNull;

                        return $result;
                    }

                    continue;
                }

                if (! $type instanceof ReflectionNamedType) {
                    continue;
                }

                $typeName = $type->getName();

                // Check if parameter is a Spatie Data class
                if ($this->classExists($typeName) && is_subclass_of($typeName, Data::class)) {
                    return [
                        'type' => $this->formatTypeForTypeScript($typeName),
                        'isNullable' => $type->allowsNull(),
                    ];
                }
            }
        }

        // Try Surveyor analysis for more detailed type info
        $surveyorType = $this->extractRequestTypeFromSurveyor($controller, $method);

        return ['type' => $surveyorType, 'isNullable' => false];
    }

    private function extractRequestTypeFromSurveyor(string $controller, string $method): ?string
    {
        $methodResult = $this->getMethodResult($controller, $method);

        if ($methodResult === null) {
            return null;
        }

        foreach ($methodResult->parameters() as $name => $type) {
            if ($type instanceof ClassType) {
                $className = $type->resolved();
                if ($this->classExists($className) && is_subclass_of($className, Data::class)) {
                    return $this->formatTypeForTypeScript($className);
                }
            }
        }

        return null;
    }

    /**
     * @return array{type: ?string, isCollection: bool, isPaginated: bool, isNullable: bool}
     */
    private function extractResponseTypeWithMeta(string $controller, string $method): array
    {
        $result = [
            'type' => null,
            'isCollection' => false,
            'isPaginated' => false,
            'isNullable' => false,
        ];

        // First check method return type directly
        $reflection = $this->getReflectionMethod($controller, $method);
        if ($reflection !== null) {
            $returnType = $reflection->getReturnType();

            // Handle union types (PHP 8.0+)
            if ($returnType instanceof ReflectionUnionType) {
                $hasNull = false;
                foreach ($returnType->getTypes() as $unionType) {
                    if ($unionType instanceof ReflectionNamedType) {
                        if ($unionType->getName() === 'null') {
                            $hasNull = true;
                        } elseif (! $unionType->isBuiltin()) {
                            $typeName = $unionType->getName();
                            if ($this->classExists($typeName) && is_subclass_of($typeName, Data::class)) {
                                $result['type'] = $this->formatTypeForTypeScript($typeName);
                            }
                        }
                    }
                }
                if ($result['type'] !== null) {
                    $result['isNullable'] = $hasNull;

                    return $result;
                }
            }

            if ($returnType instanceof ReflectionNamedType) {
                $typeName = $returnType->getName();
                if ($this->classExists($typeName) && is_subclass_of($typeName, Data::class)) {
                    $result['type'] = $this->formatTypeForTypeScript($typeName);
                    $result['isNullable'] = $returnType->allowsNull();

                    return $result;
                }
            }
        }

        // Use Surveyor to analyze return types more deeply
        /** @phpstan-ignore argument.type */
        return $this->extractResponseTypeFromSurveyorWithMeta($controller, $method);
    }

    private function extractResponseTypeFromSurveyor(string $controller, string $method): ?string
    {
        /** @phpstan-ignore argument.type */
        $meta = $this->extractResponseTypeFromSurveyorWithMeta($controller, $method);

        return $meta['type'];
    }

    /**
     * @param  class-string  $controller
     * @return array{type: ?string, isCollection: bool, isPaginated: bool, isNullable: bool}
     */
    private function extractResponseTypeFromSurveyorWithMeta(string $controller, string $method): array
    {
        $result = [
            'type' => null,
            'isCollection' => false,
            'isPaginated' => false,
            'isNullable' => false,
        ];

        $methodResult = $this->getMethodResult($controller, $method);

        if ($methodResult === null) {
            return $result;
        }

        $returnType = $methodResult->returnType();
        /** @phpstan-ignore property.notFound */
        $types = $returnType instanceof MultiType ? $returnType->types : [$returnType];

        foreach ($types as $type) {
            $extracted = $this->extractDataTypeFromSurveyorType($type);
            if ($extracted !== null) {
                return $extracted;
            }
        }

        // If controller returns JsonResponse, try to follow service method calls
        if ($returnType instanceof ClassType && $returnType->resolved() === 'Illuminate\\Http\\JsonResponse') {
            $serviceResult = $this->extractResponseTypeFromServiceChain($controller, $method);
            if ($serviceResult !== null) {
                return $serviceResult;
            }
        }

        return $result;
    }

    /**
     * Follow service method calls to find the response Data type.
     *
     * @param  class-string  $controller
     * @return array{type: ?string, isCollection: bool, isPaginated: bool, isNullable: bool}|null
     */
    private function extractResponseTypeFromServiceChain(string $controller, string $method): ?array
    {
        $reflection = $this->getReflectionClass($controller);

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return null;
        }

        // Build map of property name => service class
        $services = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $services[$param->getName()] = $type->getName();
            }
        }

        if (empty($services)) {
            return null;
        }

        // Look at each service and check methods with similar names
        foreach ($services as $propertyName => $serviceClass) {
            if (! $this->classExists($serviceClass)) {
                continue;
            }

            // Check if service has a method with the same name as the controller method
            if ($this->methodExists($serviceClass, $method)) {
                $result = $this->extractResponseFromServiceMethod($serviceClass, $method);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Extract response type from a service method.
     *
     * @return array{type: ?string, isCollection: bool, isPaginated: bool, isNullable: bool}|null
     */
    private function extractResponseFromServiceMethod(string $serviceClass, string $method): ?array
    {
        $methodResult = $this->getMethodResult($serviceClass, $method);

        if ($methodResult === null) {
            return null;
        }

        $returnType = $methodResult->returnType();
        /** @phpstan-ignore property.notFound */
        $types = $returnType instanceof MultiType ? $returnType->types : [$returnType];

        foreach ($types as $type) {
            $extracted = $this->extractDataTypeFromSurveyorType($type);
            if ($extracted !== null) {
                return $extracted;
            }
        }

        return null;
    }

    /**
     * @return array{type: ?string, isCollection: bool, isPaginated: bool, isNullable: bool}|null
     */
    private function extractDataTypeFromSurveyorType(TypeContract $type): ?array
    {
        if ($type instanceof ClassType) {
            $className = $type->resolved();

            // Check if it's a Data class directly
            if ($this->classExists($className) && is_subclass_of($className, Data::class)) {
                return [
                    'type' => $this->formatTypeForTypeScript($className),
                    'isCollection' => false,
                    'isPaginated' => false,
                    'isNullable' => false,
                ];
            }

            // Check for generic types (e.g., DataCollection<SomeData>)
            $genericTypes = $type->genericTypes();
            if (! empty($genericTypes)) {
                // Check if it's a DataCollection or PaginatedDataCollection
                $isDataCollection = str_contains($className, 'DataCollection');
                $isPaginated = str_contains($className, 'PaginatedDataCollection');

                foreach ($genericTypes as $genericType) {
                    if ($genericType instanceof ClassType) {
                        $genericClassName = $genericType->resolved();
                        if ($this->classExists($genericClassName) && is_subclass_of($genericClassName, Data::class)) {
                            return [
                                'type' => $this->formatTypeForTypeScript($genericClassName),
                                'isCollection' => $isDataCollection && ! $isPaginated,
                                'isPaginated' => $isPaginated,
                                'isNullable' => false,
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    private function getMethodResult(string $controller, string $method): ?MethodResult
    {
        try {
            $analyzed = $this->analyzer->analyzeClass($controller)->result();

            if (! $analyzed->hasMethod($method)) {
                return null;
            }

            $result = $analyzed->getMethod($method);

            return $result instanceof MethodResult ? $result : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function formatTypeForTypeScript(string $className): string
    {
        // Convert App\Features\Auth\Data\Payload\LoginPayload
        // to App.Features.Auth.Data.Payload.LoginPayload
        return str_replace('\\', '.', mb_ltrim($className, '\\'));
    }
}
