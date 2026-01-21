@include('trpc::partials.file-header', ['description' => 'Grouped API Client'])

import { fetchApi, type ApiClientConfig } from './fetch';
import type { RouteTypeMap } from './routes';

@foreach($groupedRoutes as $group => $groupRoutes)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
@endphp
/** {!! ucfirst($group) !!} API endpoints */
export const {!! $groupName !!} = {
@foreach($groupRoutes as $route)
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
        $paramParts[] = "body: RouteTypeMap['{$route['name']}']['request']";
    }
    if ($hasQuery) {
        $paramParts[] = "query?: RouteTypeMap['{$route['name']}']['query']";
    } elseif ($isPaginated) {
        // Paginated routes always need query support for page parameter
        $paramParts[] = "query?: { page?: number; [key: string]: unknown }";
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
    {!! $action !!}: ({!! $needsParams ? "params: $paramsType, " : '' !!}config?: ApiClientConfig): Promise<RouteTypeMap['{!! $route['name'] !!}']['response']> => {
        return fetchApi('{!! $route['name'] !!}', {
@if($hasPathParams)
            path: { {!! implode(', ', array_map(fn($p) => "$p: params.$p", $route['pathParams'])) !!} },
@else
            path: null,
@endif
@if($hasBody || $hasAnyQuery)
            config: {
@if($hasBody)
                body: params.body,
@endif
@if($hasAnyQuery)
                query: params.query,
@endif
            },
@endif
            clientConfig: config ?? {},
        }) as Promise<RouteTypeMap['{!! $route['name'] !!}']['response']>;
    },

@endforeach
};

@endforeach
/** Combined API object with all resource groups */
export const api = {
@foreach($groupedRoutes as $group => $groupRoutes)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
@endphp
    {!! $groupName !!},
@endforeach
};

export type Api = typeof api;

/**
 * Create a configured API client with custom base URL, headers, etc.
 *
 * Use this to configure the API client for different environments:
 * - **Client-side**: Point to staging/production APIs
 * - **Server-side**: Use in Next.js Server Components with absolute URLs
 *
 * @example
 * ```typescript
 * // Client-side: Configure for different environments
 * import { createApi } from '@/api';
 *
 * const api = createApi({
 *     baseUrl: import.meta.env.VITE_API_URL, // or process.env.NEXT_PUBLIC_API_URL
 *     headers: { Authorization: `Bearer ${token}` },
 * });
 *
 * const users = await api.users.index();
 * ```
 *
 * @example
 * ```typescript
 * // Server-side: Next.js Server Components
 * import { createApi } from '@/api';
 *
 * export const serverApi = createApi({
 *     baseUrl: process.env.API_URL!,
 *     headers: { Authorization: `Bearer ${process.env.API_TOKEN}` },
 * });
 *
 * // In a Server Component
 * const user = await serverApi.users.show({ user: 1 });
 * ```
 */
export function createApi(defaultConfig: ApiClientConfig): Api {
    return {
@foreach($groupedRoutes as $group => $groupRoutes)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
@endphp
        {!! $groupName !!}: {
@foreach($groupRoutes as $route)
@php
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
    $needsParams = $hasPathParams || $hasBody || $hasQuery || $isPaginated;
@endphp
            {!! $action !!}: ({!! $needsParams ? 'params, ' : '' !!}config) => {!! $groupName !!}.{!! $action !!}({!! $needsParams ? 'params, ' : '' !!}{ ...defaultConfig, ...config }),
@endforeach
        },
@endforeach
    };
}

/** @deprecated Use createApi instead */
export const createServerApi = createApi;
