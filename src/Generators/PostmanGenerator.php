<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Generators;

use BackedEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Generator;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanAuthData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanBodyData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanCollectionData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanEventData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanFolderData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanFormDataItemData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanHeaderData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanInfoData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanItemData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanQueryParamData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanRequestData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanUrlData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanUrlVariableData;
use OybekDaniyarov\LaravelTrpc\Data\Postman\PostmanVariableData;
use OybekDaniyarov\LaravelTrpc\Data\RouteData;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Spatie\LaravelData\Data;
use Throwable;

/**
 * Generator for Postman collections.
 *
 * Generates comprehensive Postman collections with:
 * - Route grouping by configured categories
 * - Request body examples from Spatie Data classes
 * - File upload detection (formdata mode for UploadedFile fields)
 * - Path parameter variable syntax
 * - Authentication configuration
 */
final class PostmanGenerator implements Generator
{
    public function __construct(
        private readonly TrpcConfig $config,
    ) {}

    public function generate(RouteCollection $routes, GeneratorContext $context): GeneratorResult
    {
        $collection = $this->buildCollection($routes);
        $jsonEncoded = json_encode($collection->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $json = $jsonEncoded !== false ? $jsonEncoded : '{}';

        $files = [
            'collection.json' => $json,
        ];

        if ($context->postmanEnv) {
            $environment = $this->buildEnvironment();
            $envJson = json_encode($environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $files['environment.json'] = $envJson !== false ? $envJson : '{}';
        }

        return new GeneratorResult($files);
    }

    private function buildCollection(RouteCollection $routes): PostmanCollectionData
    {
        $items = $this->buildNestedFolders($routes);

        return new PostmanCollectionData(
            info: new PostmanInfoData(
                name: $this->config->getPostmanCollectionName(),
                description: 'Auto-generated API collection from Laravel routes',
            ),
            item: $items,
            variable: $this->buildVariables(),
            auth: $this->buildAuth(),
            event: $this->buildCollectionEvents(),
        );
    }

    /**
     * Build nested folder structure from routes.
     *
     * @return Collection<int, PostmanFolderData|PostmanItemData>
     */
    private function buildNestedFolders(RouteCollection $routes): Collection
    {
        $tree = [];

        foreach ($routes as $route) {
            $parts = explode('.', $route->name);
            $this->insertIntoTree($tree, $parts, $route);
        }

        return $this->treeToFolders($tree);
    }

    /**
     * Insert a route into the tree structure.
     *
     * @param  array<string, mixed>  $tree
     * @param  array<int, string>  $parts
     */
    private function insertIntoTree(array &$tree, array $parts, RouteData $route): void
    {
        if (empty($parts)) {
            return;
        }

        if (count($parts) === 1) {
            $tree['_items'] ??= [];
            $tree['_items'][] = [
                'name' => Str::title(str_replace(['_', '-'], ' ', $parts[0])),
                'route' => $route,
            ];

            return;
        }

        $key = $parts[0];
        $tree[$key] ??= ['_children' => [], '_items' => []];
        $this->insertIntoTree($tree[$key]['_children'], array_slice($parts, 1), $route);
    }

    /**
     * Convert tree structure to Postman folders.
     *
     * @param  array<string, mixed>  $tree
     * @return Collection<int, PostmanFolderData|PostmanItemData>
     */
    private function treeToFolders(array $tree): Collection
    {
        $items = collect();

        $folderKeys = array_filter(array_keys($tree), fn ($key) => $key !== '_items' && $key !== '_children');
        sort($folderKeys);

        foreach ($folderKeys as $name) {
            $node = $tree[$name];
            $children = $node['_children'] ?? [];
            $nodeItems = $node['_items'] ?? [];

            $childItems = $this->treeToFolders($children);

            foreach ($nodeItems as $item) {
                $childItems->push($this->buildRequestItem($item['route'], $item['name']));
            }

            if ($childItems->isNotEmpty()) {
                $items->push(new PostmanFolderData(
                    name: Str::title(str_replace(['_', '-'], ' ', $name)),
                    item: $childItems,
                ));
            }
        }

        $rootItems = $tree['_items'] ?? [];
        foreach ($rootItems as $item) {
            $items->push($this->buildRequestItem($item['route'], $item['name']));
        }

        return $items;
    }

    /**
     * Build a Postman request item from route data.
     */
    private function buildRequestItem(RouteData $route, ?string $customName = null): PostmanItemData
    {
        $body = $this->buildRequestBody($route->requestClass);
        $url = $this->buildUrl($route->path, $route->pathParams, $route->queryClass);
        $auth = $this->buildRequestAuth($route);
        $events = $this->buildRequestEvents($route);

        return new PostmanItemData(
            name: $customName ?? $this->formatRequestName($route->name),
            request: new PostmanRequestData(
                method: mb_strtoupper($route->method),
                url: $url,
                header: $this->buildHeaders($body),
                body: $body,
                auth: $auth,
            ),
            event: $events,
        );
    }

    private function buildRequestBody(?string $dataClass): ?PostmanBodyData
    {
        if ($dataClass === null || ! class_exists($dataClass)) {
            return null;
        }

        try {
            $fileFields = $this->detectFileFields($dataClass);

            if (! empty($fileFields)) {
                return $this->buildFormDataBody($dataClass, $fileFields);
            }

            $example = $this->generateExampleFromDataClass($dataClass);

            if (empty($example)) {
                return null;
            }

            $exampleJson = json_encode($example, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return PostmanBodyData::raw(
                $exampleJson !== false ? $exampleJson : '{}'
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array{key: string, description: string|null}>
     */
    private function detectFileFields(string $dataClass): array
    {
        $fileFields = [];

        try {
            /** @phpstan-ignore argument.type */
            $reflection = new ReflectionClass($dataClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return [];
            }

            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();

                if ($type instanceof ReflectionNamedType) {
                    $typeName = $type->getName();

                    if ($typeName === UploadedFile::class) {
                        $fileFields[] = [
                            'key' => $param->getName(),
                            'description' => $this->getFileFieldDescription($dataClass, $param->getName()),
                        ];
                    }
                }
            }
        } catch (Throwable) {
            // Ignore
        }

        return $fileFields;
    }

    private function getFileFieldDescription(string $dataClass, string $fieldName): ?string
    {
        $rules = $this->extractValidationRules($dataClass);
        $fieldRules = $rules[$fieldName] ?? [];

        $descriptions = [];

        foreach ($fieldRules as $rule) {
            if (is_string($rule)) {
                if (Str::startsWith($rule, 'mimes:')) {
                    $mimes = Str::after($rule, 'mimes:');
                    $descriptions[] = "Allowed: {$mimes}";
                }

                if (Str::startsWith($rule, 'max:')) {
                    $maxKb = (int) Str::after($rule, 'max:');
                    $maxMb = round($maxKb / 1024, 1);
                    $descriptions[] = "Max: {$maxMb}MB";
                }
            }
        }

        return empty($descriptions) ? null : implode(', ', $descriptions);
    }

    /**
     * @param  array<int, array{key: string, description: string|null}>  $fileFields
     */
    private function buildFormDataBody(string $dataClass, array $fileFields): PostmanBodyData
    {
        $formdata = collect();

        foreach ($fileFields as $field) {
            $formdata->push(new PostmanFormDataItemData(
                key: $field['key'],
                type: 'file',
                description: $field['description'],
            ));
        }

        $example = $this->generateExampleFromDataClass($dataClass);
        $fileKeys = array_column($fileFields, 'key');

        foreach ($example as $key => $value) {
            if (in_array($key, $fileKeys, true)) {
                continue;
            }

            $jsonValue = is_array($value) ? json_encode($value) : null;
            $formdata->push(new PostmanFormDataItemData(
                key: $key,
                type: 'text',
                value: $jsonValue !== false && $jsonValue !== null ? $jsonValue : (string) $value,
            ));
        }

        return PostmanBodyData::formdata($formdata);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateExampleFromDataClass(string $dataClass): array
    {
        $example = [];

        try {
            /** @phpstan-ignore argument.type */
            $reflection = new ReflectionClass($dataClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return [];
            }

            $rules = $this->extractValidationRules($dataClass);

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                $type = $param->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === UploadedFile::class) {
                    continue;
                }

                $example[$name] = $this->generateExampleValue($param, $rules[$name] ?? []);
            }
        } catch (Throwable) {
            // Ignore
        }

        return $example;
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function generateExampleValue(ReflectionParameter $param, array $rules): mixed
    {
        $type = $param->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;
        $isNullable = $type?->allowsNull() ?? true;
        $hasDefault = $param->isDefaultValueAvailable();

        foreach ($rules as $rule) {
            if (is_string($rule) && Str::startsWith($rule, 'in:')) {
                $options = explode(',', Str::after($rule, 'in:'));

                return $options[0] !== '' ? $options[0] : '';
            }
        }

        if ($typeName !== null && class_exists($typeName) && is_subclass_of($typeName, BackedEnum::class)) {
            $cases = $typeName::cases();

            return ! empty($cases) ? $cases[0]->value : '';
        }

        if ($hasDefault) {
            return $param->getDefaultValue();
        }

        return match ($typeName) {
            'string' => $this->generateStringExample($param->getName(), $rules),
            'int' => 1,
            'float' => 1.0,
            'bool' => true,
            'array' => [],
            default => $isNullable ? null : '',
        };
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function generateStringExample(string $fieldName, array $rules): string
    {
        foreach ($rules as $rule) {
            if ($rule === 'email' || (is_string($rule) && Str::contains($rule, 'email'))) {
                return 'user@example.com';
            }
        }

        return match (true) {
            Str::contains($fieldName, 'email') => 'user@example.com',
            Str::contains($fieldName, 'password') => 'password123',
            Str::contains($fieldName, 'phone') => '+1234567890',
            Str::contains($fieldName, 'url') => 'https://example.com',
            Str::contains($fieldName, 'name') => 'Example Name',
            Str::contains($fieldName, 'title') => 'Example Title',
            Str::contains($fieldName, 'description') => 'Example description',
            default => 'example',
        };
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function extractValidationRules(string $dataClass): array
    {
        try {
            if (method_exists($dataClass, 'rules')) {
                return $dataClass::rules();
            }
        } catch (Throwable) {
            // Ignore
        }

        return [];
    }

    /**
     * @param  array<int, string>  $pathParams
     */
    private function buildUrl(string $path, array $pathParams, ?string $queryClass = null): PostmanUrlData
    {
        $baseUrl = $this->config->getPostmanBaseUrl();

        $postmanPath = preg_replace('/\{(\w+)\}/', ':$1', $path) ?? $path;
        $rawUrl = $baseUrl.'/'.$postmanPath;

        $queryParams = $this->buildQueryParams($queryClass);

        if ($queryParams !== null && $queryParams->isNotEmpty()) {
            $queryString = $queryParams->map(fn (PostmanQueryParamData $p) => "{$p->key}={$p->value}")->implode('&');
            $rawUrl .= '?'.$queryString;
        }

        $pathSegments = collect(explode('/', $postmanPath))
            ->filter(fn ($segment) => $segment !== '');

        $variables = collect($pathParams)->map(
            fn (string $param) => new PostmanUrlVariableData(
                key: $param,
                value: '',
                description: "The {$param} parameter",
            )
        );

        return new PostmanUrlData(
            raw: $rawUrl,
            host: collect([$baseUrl]),
            path: $pathSegments->values(),
            variable: $variables->isEmpty() ? null : $variables,
            query: $queryParams?->isEmpty() ? null : $queryParams,
        );
    }

    /**
     * @return Collection<int, PostmanQueryParamData>|null
     */
    private function buildQueryParams(?string $dataClass): ?Collection
    {
        if ($dataClass === null || ! class_exists($dataClass)) {
            return null;
        }

        try {
            $reflection = new ReflectionClass($dataClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null) {
                return null;
            }

            $rules = $this->extractValidationRules($dataClass);
            $params = collect();

            foreach ($constructor->getParameters() as $param) {
                $name = $param->getName();
                $type = $param->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === UploadedFile::class) {
                    continue;
                }

                $exampleValue = $this->generateExampleValue($param, $rules[$name] ?? []);
                $jsonValue = is_array($exampleValue) ? json_encode($exampleValue) : null;

                $params->push(new PostmanQueryParamData(
                    key: $name,
                    value: $jsonValue !== false && $jsonValue !== null ? $jsonValue : (string) ($exampleValue ?? ''),
                ));
            }

            return $params;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return Collection<int, PostmanHeaderData>
     */
    private function buildHeaders(?PostmanBodyData $body): Collection
    {
        $headers = collect([
            new PostmanHeaderData(key: 'Accept', value: 'application/json'),
        ]);

        if ($body !== null && $body->mode === 'raw') {
            $headers->push(new PostmanHeaderData(key: 'Content-Type', value: 'application/json'));
        }

        $customHeaders = $this->config->getPostmanDefaultHeaders();
        foreach ($customHeaders as $key => $value) {
            if (! $headers->contains(fn (PostmanHeaderData $h) => $h->key === $key)) {
                $headers->push(new PostmanHeaderData(key: $key, value: $value));
            }
        }

        return $headers;
    }

    private function buildRequestAuth(RouteData $route): ?PostmanAuthData
    {
        $requiresAuth = $this->routeRequiresAuth($route->middleware);

        if (! $requiresAuth) {
            return PostmanAuthData::noauth();
        }

        return null;
    }

    /**
     * @param  array<int, string>  $middleware
     */
    private function routeRequiresAuth(array $middleware): bool
    {
        foreach ($middleware as $m) {
            if (Str::contains($m, 'auth:') || Str::contains($m, 'Authenticate')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, PostmanEventData>|null
     */
    private function buildRequestEvents(RouteData $route): ?Collection
    {
        if (! Str::contains($route->name, 'login')) {
            return null;
        }

        $script = [
            '// Auto-save token on successful login',
            'if (pm.response.code === 200) {',
            '    const response = pm.response.json();',
            '    const token = response.token || response.access_token || response.data?.token || response.data?.access_token;',
            '    if (token) {',
            "        pm.environment.set('token', token);",
            "        console.log('Token saved to token');",
            '    }',
            '}',
        ];

        return collect([
            PostmanEventData::test($script),
        ]);
    }

    /**
     * @return Collection<int, PostmanVariableData>
     */
    private function buildVariables(): Collection
    {
        $baseUrl = config('app.url', 'https://localhost');

        return collect([
            new PostmanVariableData(
                key: 'base_url',
                value: $baseUrl,
                description: 'Base URL for the API',
            ),
            new PostmanVariableData(
                key: 'token',
                value: '',
                description: 'Authentication token (auto-saved on login)',
            ),
        ]);
    }

    private function buildAuth(): ?PostmanAuthData
    {
        $authType = $this->config->getPostmanAuthType();

        return match ($authType) {
            'bearer' => PostmanAuthData::bearer(),
            'apikey' => PostmanAuthData::apikey(),
            default => null,
        };
    }

    /**
     * @return Collection<int, PostmanEventData>
     */
    private function buildCollectionEvents(): Collection
    {
        $prerequest = [
            '// Collection-level pre-request script',
            '// Checks for valid authentication token before making requests',
            '',
            'const noAuthRequests = [',
            "    'login', 'register', 'forgot-password', 'reset-password'",
            '];',
            '',
            '// Skip token check for auth endpoints',
            'const requestName = pm.info.requestName.toLowerCase();',
            'const isAuthEndpoint = noAuthRequests.some(name => requestName.includes(name));',
            '',
            'if (!isAuthEndpoint) {',
            "    const token = pm.environment.get('token');",
            '    if (!token) {',
            "        console.warn('Warning: token is not set. You may need to login first.');",
            '    }',
            '}',
        ];

        return collect([
            PostmanEventData::prerequest($prerequest),
        ]);
    }

    private function formatRequestName(string $routeName): string
    {
        $parts = explode('.', $routeName);
        $parts = array_map(fn ($part) => Str::title(str_replace('_', ' ', $part)), $parts);

        return implode(' > ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEnvironment(): array
    {
        return [
            'name' => config('app.name', 'API').' Environment',
            'values' => [
                [
                    'key' => 'base_url',
                    'value' => config('app.url', 'https://localhost'),
                    'enabled' => true,
                    'type' => 'default',
                ],
                [
                    'key' => 'token',
                    'value' => '',
                    'enabled' => true,
                    'type' => 'secret',
                ],
            ],
        ];
    }
}
