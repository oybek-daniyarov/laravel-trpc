@include('trpc::partials.file-header', ['description' => ucfirst($group) . ' API Client'])

import { fetchApi, type ApiClientConfig, type RequestOptions } from '../core';
import { {!! \Illuminate\Support\Str::camel($group) !!}Routes, type {!! ucfirst(\Illuminate\Support\Str::camel($group)) !!}RouteTypeMap } from './routes';

@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
@endphp
/**
 * Create {!! $group !!} API client bound to a configuration.
 *
 * @example
 * ```typescript
 * const config: ApiClientConfig = { baseUrl: process.env.NEXT_PUBLIC_API_URL! };
 * const {!! $groupName !!}Api = create{!! $groupNamePascal !!}Api(config);
 *
 * // Use the API
@foreach(array_slice($routes, 0, 2) as $route)
@php
    $parts = explode('.', $route['name']);
    $routeGroup = $parts[0] ?? '';
    if ($routeGroup === $group && count($parts) > 1) {
        array_shift($parts);
    }
    $action = \Illuminate\Support\Str::camel(implode('_', $parts));
    if (empty($action)) {
        $action = 'index';
    }
    $hasPathParams = !empty($route['pathParams']);
@endphp
 * const result = await {!! $groupName !!}Api.{!! $action !!}({!! $hasPathParams ? '{ ' . implode(', ', array_map(fn($p) => "$p: 1", $route['pathParams'])) . ' }' : '' !!});
@endforeach
 * ```
 */
export function create{!! $groupNamePascal !!}Api(config: ApiClientConfig) {
    return {
@foreach($routes as $route)
@php
    // Extract action from route name: users.show -> show, users.show.details -> showDetails
    $parts = explode('.', $route['name']);
    $routeGroup = $parts[0] ?? '';

    // Only remove first part if route name starts with the group name
    if ($routeGroup === $group && count($parts) > 1) {
        array_shift($parts);
    }

    $action = \Illuminate\Support\Str::camel(implode('_', $parts));
    if (empty($action)) {
        $action = 'index';
    }

    $hasPathParams = !empty($route['pathParams']);
    $isGetOrDelete = in_array($route['method'], ['get', 'delete']);
    $hasBody = $route['hasRequest'] && !$isGetOrDelete;
    $hasQuery = $route['hasQuery'];
    $isPaginated = $route['isPaginated'] ?? false;

    // Build parameter parts
    $paramParts = [];
    if ($hasPathParams) {
        foreach ($route['pathParams'] as $param) {
            $paramParts[] = "$param: string | number";
        }
    }
    if ($hasBody) {
        $paramParts[] = "body: {$groupNamePascal}RouteTypeMap['{$route['name']}']['request']";
    }
    if ($hasQuery) {
        $paramParts[] = "query?: {$groupNamePascal}RouteTypeMap['{$route['name']}']['query']";
    } elseif ($isPaginated) {
        // Paginated routes always need query support for page parameter
        $paramParts[] = "query?: { page?: number } & Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>";
    }

    // Determine if we need a params object or individual params
    $needsParams = $hasPathParams || $hasBody || $hasQuery || $isPaginated;
    $paramsType = $needsParams ? '{ ' . implode('; ', $paramParts) . ' }' : '';

    // For queries we need to track if we actually have query params
    $hasAnyQuery = $hasQuery || $isPaginated;
@endphp
        /**
         * {!! ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::snake($action))) !!} - {!! strtoupper($route['method']) !!} /{!! $route['path'] !!}
         */
        {!! $action !!}: ({!! $needsParams ? "params: $paramsType, " : '' !!}options?: RequestOptions): Promise<{!! $groupNamePascal !!}RouteTypeMap['{!! $route['name'] !!}']['response']> => {
            return fetchApi(
                {!! $groupName !!}Routes['{!! $route['name'] !!}'],
                {
@if($hasPathParams)
                    path: { {!! implode(', ', array_map(fn($p) => "$p: params.$p", $route['pathParams'])) !!} },
@else
                    path: null,
@endif
@if($hasBody)
                    body: params.body,
@endif
@if($hasAnyQuery)
                    query: params.query,
@endif
                    clientConfig: config,
                    requestOptions: options,
                }
            );
        },

@endforeach
    };
}

export type {!! $groupNamePascal !!}Api = ReturnType<typeof create{!! $groupNamePascal !!}Api>;
