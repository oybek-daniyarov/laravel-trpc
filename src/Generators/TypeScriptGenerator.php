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
 * Generator for TypeScript definitions with folder-based organization.
 *
 * Generates the following folder structure:
 * - core/types.ts: Core types (HttpMethod, ApiError, PaginationMeta)
 * - core/fetch.ts: fetchApi(), ApiClientConfig, RequestOptions
 * - core/helpers.ts: Type helpers (RequestOf, ResponseOf, etc.)
 * - core/index.ts: Core barrel exports
 * - {group}/routes.ts: Per-group route definitions and types
 * - {group}/api.ts: Per-group API factory (createUsersApi, etc.)
 * - {group}/queries.ts: Per-group React Query hooks
 * - {group}/index.ts: Per-group barrel exports
 * - routes.ts: Aggregated routes from all groups
 * - api.ts: createApi() - combined API factory
 * - queries.ts: createQueries() - combined query factory
 * - index.ts: Main barrel exports
 * - url-builder.ts: URL building utilities
 * - client.ts: Method-based API client
 * - inertia.ts: Inertia.js helpers
 * - react-query.ts: React Query utilities
 * - README.md: Documentation
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

        // 1. Generate core files
        $files = array_merge($files, $this->generateCoreFiles($outputs));

        // 2. Generate per-group files
        foreach ($groupedRoutes as $group => $groupRouteList) {
            $files = array_merge($files, $this->generateGroupFiles($group, $groupRouteList, $outputs));
        }

        // 3. Generate root aggregation files
        $files = array_merge($files, $this->generateRootFiles($routeArray, $groupedRoutes, $outputs));

        // 4. Generate utility files at root level
        $files = array_merge($files, $this->generateUtilityFiles($routeArray, $groupedRoutes, $outputs));

        return new GeneratorResult($files);
    }

    /**
     * Generate core infrastructure files.
     *
     * @param  array<string, bool>  $outputs
     * @return array<string, string>
     */
    private function generateCoreFiles(array $outputs): array
    {
        $files = [];
        $timestamp = now()->toIso8601String();

        if ($outputs['types'] ?? true) {
            $files['core/types.ts'] = $this->stubRenderer->render('core.types', [
                'timestamp' => $timestamp,
            ]);
        }

        if ($outputs['fetch'] ?? true) {
            $files['core/fetch.ts'] = $this->stubRenderer->render('core.fetch', [
                'timestamp' => $timestamp,
            ]);
        }

        if ($outputs['helpers'] ?? true) {
            $files['core/helpers.ts'] = $this->stubRenderer->render('core.helpers', [
                'timestamp' => $timestamp,
            ]);
        }

        // Core index is always generated
        $files['core/index.ts'] = $this->stubRenderer->render('core.index', [
            'timestamp' => $timestamp,
        ]);

        return $files;
    }

    /**
     * Generate per-group files.
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @param  array<string, bool>  $outputs
     * @return array<string, string>
     */
    private function generateGroupFiles(string $group, array $routes, array $outputs): array
    {
        $files = [];
        $timestamp = now()->toIso8601String();
        $getInterfaceName = fn (string $name): string => $this->routeNameToInterfaceName($name);
        $hasQueries = ($outputs['queries'] ?? false) && count(array_filter($routes, fn ($r) => $r['method'] === 'get')) > 0;

        // Routes file - always generated
        if ($outputs['routes'] ?? true) {
            $files["{$group}/routes.ts"] = $this->stubRenderer->render('group.routes', [
                'group' => $group,
                'routes' => $routes,
                'getInterfaceName' => $getInterfaceName,
                'timestamp' => $timestamp,
            ]);
        }

        // API file
        if ($outputs['grouped-api'] ?? true) {
            $files["{$group}/api.ts"] = $this->stubRenderer->render('group.api', [
                'group' => $group,
                'routes' => $routes,
                'timestamp' => $timestamp,
            ]);
        }

        // Queries file (only for groups with GET routes)
        if ($hasQueries) {
            $files["{$group}/queries.ts"] = $this->stubRenderer->render('group.queries', [
                'group' => $group,
                'routes' => $routes,
                'timestamp' => $timestamp,
            ]);
        }

        // Group index
        if ($outputs['index'] ?? true) {
            $files["{$group}/index.ts"] = $this->stubRenderer->render('group.index', [
                'group' => $group,
                'routes' => $routes,
                'getInterfaceName' => $getInterfaceName,
                'hasQueries' => $hasQueries,
                'timestamp' => $timestamp,
            ]);
        }

        return $files;
    }

    /**
     * Generate root aggregation files.
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @param  array<string, array<int, array<string, mixed>>>  $groupedRoutes
     * @param  array<string, bool>  $outputs
     * @return array<string, string>
     */
    private function generateRootFiles(array $routes, array $groupedRoutes, array $outputs): array
    {
        $files = [];
        $timestamp = now()->toIso8601String();
        $getInterfaceName = fn (string $name): string => $this->routeNameToInterfaceName($name);

        // Aggregated routes
        if ($outputs['routes'] ?? true) {
            $files['routes.ts'] = $this->stubRenderer->render('root.routes', [
                'routes' => $routes,
                'groupedRoutes' => $groupedRoutes,
                'getInterfaceName' => $getInterfaceName,
                'timestamp' => $timestamp,
            ]);
        }

        // Combined API factory
        if ($outputs['grouped-api'] ?? true) {
            $files['api.ts'] = $this->stubRenderer->render('root.api', [
                'groupedRoutes' => $groupedRoutes,
                'timestamp' => $timestamp,
            ]);
        }

        // Combined queries factory
        if ($outputs['queries'] ?? false) {
            $files['queries.ts'] = $this->stubRenderer->render('root.queries', [
                'groupedRoutes' => $groupedRoutes,
                'timestamp' => $timestamp,
            ]);
        }

        // Main index
        if ($outputs['index'] ?? true) {
            $files['index.ts'] = $this->stubRenderer->render('root.index', [
                'groupedRoutes' => $groupedRoutes,
                'outputs' => $outputs,
                'timestamp' => $timestamp,
            ]);
        }

        // README
        if ($outputs['readme'] ?? true) {
            $files['README.md'] = $this->stubRenderer->render('readme', [
                'timestamp' => $timestamp,
                'config' => $this->config,
            ]);
        }

        return $files;
    }

    /**
     * Generate utility files (url-builder, client, inertia, react-query).
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @param  array<string, array<int, array<string, mixed>>>  $groupedRoutes
     * @param  array<string, bool>  $outputs
     * @return array<string, string>
     */
    private function generateUtilityFiles(array $routes, array $groupedRoutes, array $outputs): array
    {
        $files = [];
        $timestamp = now()->toIso8601String();

        if ($outputs['url-builder'] ?? true) {
            $files[$this->getFileName('url-builder', 'url-builder.ts')] = $this->stubRenderer->render('url-builder', [
                'timestamp' => $timestamp,
            ]);
        }

        if ($outputs['client'] ?? true) {
            $files[$this->getFileName('client', 'client.ts')] = $this->stubRenderer->render('client', [
                'timestamp' => $timestamp,
            ]);
        }

        if ($outputs['inertia'] ?? true) {
            $files[$this->getFileName('inertia', 'inertia.ts')] = $this->stubRenderer->render('inertia', [
                'timestamp' => $timestamp,
            ]);
        }

        if ($outputs['react-query'] ?? false) {
            $files[$this->getFileName('react-query', 'react-query.ts')] = $this->stubRenderer->render('react-query', [
                'timestamp' => $timestamp,
            ]);
        }

        return $files;
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
