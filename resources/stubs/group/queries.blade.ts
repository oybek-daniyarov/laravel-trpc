@include('trpc::partials.file-header', ['description' => ucfirst($group) . ' React Query Hooks'])

@php
    $hasPaginatedRoutes = count(array_filter($routes, fn($r) => ($r['isPaginated'] ?? false) && $r['method'] === 'get')) > 0;
    $getRoutes = array_filter($routes, fn($r) => $r['method'] === 'get');
    $hasQueryRoutes = count(array_filter($getRoutes, fn($r) => $r['hasQuery'])) > 0;
@endphp
import { queryOptions{{ $hasPaginatedRoutes ? ', infiniteQueryOptions' : '' }} } from '@tanstack/react-query';
import type { RequestOptions } from '../core';
import type { {!! ucfirst(\Illuminate\Support\Str::camel($group)) !!}Api } from './api';
@if($hasQueryRoutes)
import type { {!! ucfirst(\Illuminate\Support\Str::camel($group)) !!}RouteTypeMap } from './routes';
@endif

@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
    $getRoutes = array_filter($routes, fn($r) => $r['method'] === 'get');
@endphp
/** Query keys for {!! $group !!} */
export const {!! $groupName !!}Keys = {
    all: ['{!! $group !!}'] as const,
@foreach($getRoutes as $route)
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
    $hasQuery = $route['hasQuery'];

    // Build param types
    $paramParts = [];
    if ($hasPathParams) {
        foreach ($route['pathParams'] as $param) {
            $paramParts[] = "$param: string | number";
        }
    }
    if ($hasQuery) {
        $paramParts[] = "query?: {$groupNamePascal}RouteTypeMap['{$route['name']}']['query']";
    }
    $needsParams = !empty($paramParts);
    $paramsType = $needsParams ? '{ ' . implode('; ', $paramParts) . ' }' : '';
@endphp
    {!! $action !!}: ({!! $needsParams ? "params: $paramsType" : '' !!}) =>
        ['{!! $route['name'] !!}'{!! $needsParams ? ', params' : '' !!}] as const,
@endforeach
};

/**
 * Create query options for {!! $group !!} bound to a specific API instance.
 *
 * @example
 * ```typescript
 * const api = create{!! $groupNamePascal !!}Api({ baseUrl: process.env.NEXT_PUBLIC_API_URL! });
 * const queries = create{!! $groupNamePascal !!}Queries(api);
 *
 * // Use in React Query
 * const { data } = useQuery(queries.show({ {!! !empty($getRoutes) && !empty(array_values($getRoutes)[0]['pathParams']) ? array_values($getRoutes)[0]['pathParams'][0] . ': 1' : '' !!} }));
 * ```
 */
export function create{!! $groupNamePascal !!}Queries(api: {!! $groupNamePascal !!}Api) {
    return {
        keys: {!! $groupName !!}Keys,
@foreach($getRoutes as $route)
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
    $hasQuery = $route['hasQuery'];
    $isPaginated = $route['isPaginated'] ?? false;

    $paramParts = [];
    if ($hasPathParams) {
        foreach ($route['pathParams'] as $param) {
            $paramParts[] = "$param: string | number";
        }
    }
    if ($hasQuery) {
        $paramParts[] = "query?: {$groupNamePascal}RouteTypeMap['{$route['name']}']['query']";
    }
    $needsParams = !empty($paramParts);
    $paramsType = $needsParams ? '{ ' . implode('; ', $paramParts) . ' }' : '';
@endphp
        {!! $action !!}: ({!! $needsParams ? "params: $paramsType, " : '' !!}options?: RequestOptions) =>
@if($isPaginated)
            infiniteQueryOptions({
                queryKey: {!! $groupName !!}Keys.{!! $action !!}({!! $needsParams ? 'params' : '' !!}),
                queryFn: ({ pageParam = 1 }) => api.{!! $action !!}({!! $needsParams ? '{ ...params, query: { ...params.query, page: pageParam } as ' . $groupNamePascal . 'RouteTypeMap[\'' . $route['name'] . '\'][\'query\'] }' : '{ query: { page: pageParam } }' !!}, options),
                initialPageParam: 1,
                getNextPageParam: (lastPage) => {
                    const meta = (lastPage as { meta?: { current_page: number; last_page: number } }).meta;
                    return meta && meta.current_page < meta.last_page ? meta.current_page + 1 : undefined;
                },
            }),
@else
            queryOptions({
                queryKey: {!! $groupName !!}Keys.{!! $action !!}({!! $needsParams ? 'params' : '' !!}),
                queryFn: () => api.{!! $action !!}({!! $needsParams ? 'params, ' : '' !!}options),
            }),
@endif
@endforeach
    };
}
