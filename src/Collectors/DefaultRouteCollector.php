<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Collectors;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use OybekDaniyarov\LaravelTrpc\Attributes\TypedRoute;
use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Collector;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\Data\RouteTypeInfo;
use OybekDaniyarov\LaravelTrpc\Services\RouteTypeExtractor;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;
use ReflectionMethod;

/**
 * Default route collector that discovers routes from Laravel's route collection.
 */
final class DefaultRouteCollector implements Collector
{
    /** @var array<string, RouteTypeInfo> */
    private array $routeTypes = [];

    /** @var array<string, string> */
    private array $routeGroupCache = [];

    /** @var array<string, string> */
    private array $routeNameCache = [];

    public function __construct(
        private readonly TrpcConfig $config,
        private readonly RouteTypeExtractor $typeExtractor,
    ) {}

    /**
     * @param  array<string, RouteTypeInfo>  $routeTypes
     */
    public function collect(array $routeTypes = []): RouteCollection
    {
        // Use provided route types or extract them if not provided
        $this->routeTypes = $routeTypes ?: $this->typeExtractor->extractRouteTypes();

        $apiPrefix = $this->config->getApiPrefix();
        $excludeMethods = $this->config->getExcludeMethods();
        $routes = RouteFacade::getRoutes();
        $collection = new RouteCollection;

        /** @var iterable<Route> $routes */
        foreach ($routes as $route) {
            $uri = $route->uri();

            if (! $this->shouldCollectRoute($route, $uri, $apiPrefix)) {
                continue;
            }

            $methods = $route->methods();
            $method = mb_strtolower($methods[0]);

            if (in_array($method, $excludeMethods, true)) {
                continue;
            }

            $routeData = $this->processRoute($route, $uri, $method);
            $collection->add($routeData);
        }

        return $collection;
    }

    private function processRoute(Route $route, string $uri, string $method): RouteData
    {
        $name = $route->getName();
        $mappedName = $this->getMappedRouteName($uri);

        if ($mappedName !== null) {
            $name = $mappedName;
        }

        if (empty($name)) {
            $name = $this->generateRouteName($uri, $method);
        }

        $pathParams = $this->extractPathParameters($uri);
        // Key by HTTP method + URI to match RouteTypeExtractor
        $typeKey = "{$method}:{$uri}";
        $typeInfo = $this->routeTypes[$typeKey] ?? new RouteTypeInfo;
        $group = $this->determineRouteGroup($name);
        $middleware = $route->gatherMiddleware();

        return new RouteData(
            method: $method,
            path: $uri,
            name: $name,
            group: $group,
            pathParams: $pathParams,
            requestType: $typeInfo->getTypeScriptRequestType(),
            queryType: $typeInfo->getTypeScriptQueryType(),
            responseType: $typeInfo->getTypeScriptResponseType(),
            errorType: $typeInfo->getTypeScriptErrorType(),
            hasRequest: $typeInfo->requestType !== null,
            hasQuery: $typeInfo->queryType !== null,
            hasResponse: $typeInfo->responseType !== null,
            isCollection: $typeInfo->isCollection,
            isPaginated: $typeInfo->isPaginated,
            middleware: $middleware,
            requestClass: $typeInfo->requestType,
            queryClass: $typeInfo->queryType,
        );
    }

    private function getMappedRouteName(string $path): ?string
    {
        $mappings = $this->config->getRouteNameMappings();

        return $mappings[$path] ?? null;
    }

    /**
     * @return array<int, string>
     */
    private function extractPathParameters(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        return $matches[1];
    }

    private function generateRouteName(string $path, string $method): string
    {
        $cacheKey = "{$path}:{$method}";

        if (isset($this->routeNameCache[$cacheKey])) {
            return $this->routeNameCache[$cacheKey];
        }

        $parts = explode('/', mb_trim($path, '/'));
        array_shift($parts);

        if (isset($parts[0]) && Str::startsWith($parts[0], 'v')) {
            array_shift($parts);
        }

        if (isset($parts[0]) && preg_match('/^[a-z]{2}$/', $parts[0])) {
            array_shift($parts);
        }

        $name = '';

        if (count($parts) >= 2) {
            $resource = $parts[0];

            if (count($parts) === 2) {
                $lastPart = $parts[1];
                if (preg_match('/\{(\w+)\}/', $lastPart)) {
                    $name = "{$resource}.show";
                } else {
                    $name = "{$resource}.{$parts[1]}";
                }
            } else {
                $subResource = $parts[1];
                $name = match ($subResource) {
                    'country' => "{$resource}.byCountry",
                    'city' => "{$resource}.byCity",
                    'search' => "{$resource}.search",
                    default => "{$resource}.{$subResource}",
                };
            }

            if ($method !== 'get') {
                $name = "{$name}.{$method}";
            }
        } else {
            $name = implode('.', $parts);
        }

        return $this->routeNameCache[$cacheKey] = $name;
    }

    private function determineRouteGroup(string $routeName): string
    {
        if (isset($this->routeGroupCache[$routeName])) {
            return $this->routeGroupCache[$routeName];
        }

        $routeGroups = $this->config->getRouteGroups();

        foreach ($routeGroups as $group => $patterns) {
            foreach ($patterns as $pattern) {
                if (Str::contains($routeName, $pattern)) {
                    return $this->routeGroupCache[$routeName] = $group;
                }
            }
        }

        $parts = explode('.', $routeName);

        return $this->routeGroupCache[$routeName] = $parts[0] !== '' ? $parts[0] : 'default';
    }

    /**
     * Determine if a route should be collected based on route mode and patterns.
     */
    private function shouldCollectRoute(Route $route, string $uri, string $apiPrefix): bool
    {
        $routeName = $route->getName();

        // Always check exclude patterns first
        if ($this->matchesPatterns($routeName, $uri, $this->config->getExcludePatterns())) {
            return false;
        }

        // Check route mode
        $mode = $this->config->getRouteMode();

        $passesMode = match ($mode) {
            'all' => true,
            'api' => $this->isApiRoute($uri, $apiPrefix),
            'web' => ! $this->isApiRoute($uri, $apiPrefix),
            'named' => $routeName !== null && $routeName !== '',
            'attributed' => $this->hasTypedRouteAttribute($route),
            default => $this->isApiRoute($uri, $apiPrefix),
        };

        if (! $passesMode) {
            return false;
        }

        // Check include patterns (if specified, route must match at least one)
        $includePatterns = $this->config->getIncludePatterns();

        if (! empty($includePatterns)) {
            return $this->matchesPatterns($routeName, $uri, $includePatterns);
        }

        return true;
    }

    /**
     * Check if route is an API route (matches api prefix).
     */
    private function isApiRoute(string $uri, string $apiPrefix): bool
    {
        if ($apiPrefix === '') {
            return true;
        }

        return Str::startsWith($uri, $apiPrefix.'/') || $uri === $apiPrefix;
    }

    /**
     * Check if route name or URI matches any of the given patterns.
     *
     * @param  array<int, string>  $patterns
     */
    private function matchesPatterns(?string $routeName, string $uri, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            // Check route name pattern (uses dot notation like 'users.*')
            if ($routeName !== null && Str::is($pattern, $routeName)) {
                return true;
            }

            // Check URI pattern (uses path notation like 'api/*')
            if (Str::is($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if route controller method has #[TypedRoute] attribute.
     */
    private function hasTypedRouteAttribute(Route $route): bool
    {
        $action = $route->getAction();

        if (! isset($action['controller'])) {
            return false;
        }

        $controller = $action['controller'];

        if (! is_string($controller) || ! str_contains($controller, '@')) {
            return false;
        }

        [$class, $method] = explode('@', $controller);

        if (! class_exists($class) || ! method_exists($class, $method)) {
            return false;
        }

        try {
            $reflection = new ReflectionMethod($class, $method);
            $attributes = $reflection->getAttributes(TypedRoute::class);

            return ! empty($attributes);
        } catch (\ReflectionException) {
            return false;
        }
    }
}
