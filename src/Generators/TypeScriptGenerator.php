<?php

declare(strict_types=1);

namespace OybekDaniyarov\LaravelTrpc\Generators;

use Illuminate\Support\Str;
use OybekDaniyarov\LaravelTrpc\Collections\RouteCollection;
use OybekDaniyarov\LaravelTrpc\Contracts\Generator;
use OybekDaniyarov\LaravelTrpc\Data\Context\GeneratorContext;
use OybekDaniyarov\LaravelTrpc\Data\GeneratorResult;
use OybekDaniyarov\LaravelTrpc\Services\StubRenderer;
use OybekDaniyarov\LaravelTrpc\TrpcConfig;

/**
 * Generator for TypeScript definitions.
 *
 * Generates multiple TypeScript files from route definitions:
 * - types.ts: Core types (HttpMethod, ApiError, PaginationMeta)
 * - routes.ts: Route interfaces, RouteTypeMap, routes constant
 * - helpers.ts: Type helpers (RequestOf, ResponseOf, ParamsOf)
 * - url-builder.ts: url(), UrlOptions
 * - fetch.ts: fetchApi(), FetchOptions, RequestConfig
 * - client.ts: createApiClient(), RequestOptions
 * - api.ts: Grouped API client (api.users.show(), etc.)
 * - queries.ts: React Query hooks per resource
 * - react-query.ts: React Query integration utilities
 * - inertia.ts: Inertia helpers (route, visit, formAction)
 * - index.ts: Barrel exports
 * - README.md: Frontend documentation
 */
final class TypeScriptGenerator implements Generator
{
    /** @var array<string, string> */
    private array $interfaceNameCache = [];

    public function __construct(
        private readonly TrpcConfig $config,
        private readonly StubRenderer $stubRenderer,
    ) {}

    public function generate(RouteCollection $routes, GeneratorContext $context): GeneratorResult
    {
        $routeArray = $this->prepareRouteData($routes);
        $groupedRoutes = $this->groupRoutes($routeArray);

        $outputs = $this->config->getEffectiveOutputs();
        $files = [];

        // Core files (these are dependencies for others)
        if ($outputs['types'] ?? true) {
            $files[$this->getFileName('types', 'types.ts')] = $this->stubRenderer->render('types', [
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        if ($outputs['routes'] ?? true) {
            $files[$this->getFileName('routes', 'routes.ts')] = $this->stubRenderer->render('routes', [
                'routes' => $routeArray,
                'groupedRoutes' => $groupedRoutes,
                'getInterfaceName' => fn (string $name): string => $this->routeNameToInterfaceName($name),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        if ($outputs['helpers'] ?? true) {
            $files[$this->getFileName('helpers', 'helpers.ts')] = $this->stubRenderer->render('helpers', [
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        if ($outputs['url-builder'] ?? true) {
            $files[$this->getFileName('url-builder', 'url-builder.ts')] = $this->stubRenderer->render('url-builder', [
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        if ($outputs['fetch'] ?? true) {
            $files[$this->getFileName('fetch', 'fetch.ts')] = $this->stubRenderer->render('fetch', [
                'timestamp' => now()->toIso8601String(),
                'baseUrl' => $context->baseUrl,
            ]);
        }

        if ($outputs['client'] ?? true) {
            $files[$this->getFileName('client', 'client.ts')] = $this->stubRenderer->render('client', [
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Framework integrations (opt-in/out)
        if ($outputs['react-query'] ?? false) {
            $files[$this->getFileName('react-query', 'react-query.ts')] = $this->stubRenderer->render('react-query', [
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        if ($outputs['inertia'] ?? true) {
            $files[$this->getFileName('inertia', 'inertia.ts')] = $this->stubRenderer->render('inertia', [
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Grouped API client (object-based)
        if ($outputs['grouped-api'] ?? true) {
            $files[$this->getFileName('api', 'api.ts')] = $this->stubRenderer->render('api', [
                'groupedRoutes' => $groupedRoutes,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // React Query hooks per resource (requires grouped-api)
        if ($outputs['queries'] ?? false) {
            $files[$this->getFileName('queries', 'queries.ts')] = $this->stubRenderer->render('queries', [
                'groupedRoutes' => $groupedRoutes,
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // Index and readme
        if ($outputs['index'] ?? true) {
            $files[$this->getFileName('index', 'index.ts')] = $this->stubRenderer->render('index', [
                'timestamp' => now()->toIso8601String(),
                'outputs' => $outputs,
            ]);
        }

        if ($outputs['readme'] ?? true) {
            $files['README.md'] = $this->stubRenderer->render('readme', [
                'timestamp' => now()->toIso8601String(),
                'config' => $this->config,
            ]);
        }

        return new GeneratorResult($files);
    }

    /**
     * Get the output filename, allowing customization via config.
     */
    private function getFileName(string $key, string $default): string
    {
        return $this->config->getFileName($key, $default);
    }

    /**
     * Convert RouteCollection to array format with deduplication.
     *
     * @return array<int, array<string, mixed>>
     */
    private function prepareRouteData(RouteCollection $routes): array
    {
        $routeNameCounts = [];
        $result = [];

        foreach ($routes as $route) {
            $data = $route->toArray();
            $originalName = $data['name'];

            // Handle duplicates by appending counter
            $count = $routeNameCounts[$originalName] ?? 0;
            $routeNameCounts[$originalName] = $count + 1;

            if ($count > 0) {
                $data['name'] = $originalName.'_'.$count;
            }

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Group routes by their group property.
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupRoutes(array $routes): array
    {
        $grouped = [];

        foreach ($routes as $route) {
            $group = $route['group'];
            $grouped[$group] ??= [];
            $grouped[$group][] = $route;
        }

        return $grouped;
    }

    /**
     * Convert route name to interface name with caching.
     */
    private function routeNameToInterfaceName(string $routeName): string
    {
        if (isset($this->interfaceNameCache[$routeName])) {
            return $this->interfaceNameCache[$routeName];
        }

        $parts = explode('.', $routeName);
        $parts = array_map(function ($part) {
            $part = preg_replace('/_(\d+)$/', '$1', $part) ?? $part;

            return ucfirst(Str::camel($part));
        }, $parts);

        return $this->interfaceNameCache[$routeName] = implode('', $parts).'Route';
    }
}
