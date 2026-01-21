@include('trpc::partials.file-header', ['description' => 'React Query Hooks by Resource'])

import { queryOptions, infiniteQueryOptions } from '@tanstack/react-query';
import { api } from './api';
import type { RouteTypeMap } from './routes';

@foreach($groupedRoutes as $group => $groupRoutes)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $queriesName = $groupName . 'Queries';
    $getRoutes = array_filter($groupRoutes, fn($r) => $r['method'] === 'get');
@endphp
@if(!empty($getRoutes))
/** Query options for {!! $group !!} endpoints */
export const {!! $queriesName !!} = {
    /** Query keys for {!! $group !!} - uses route name as first element for consistency */
    keys: {
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
        $paramParts[] = "query?: RouteTypeMap['{$route['name']}']['query']";
    }
    $needsParams = !empty($paramParts);
    $paramsType = $needsParams ? '{ ' . implode('; ', $paramParts) . ' }' : '';
@endphp
        {!! $action !!}: ({!! $needsParams ? "params: $paramsType" : '' !!}) =>
            ['{!! $route['name'] !!}'{!! $needsParams ? ', params' : '' !!}] as const,
@endforeach
    },

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
    $isPaginated = $route['isPaginated'] ?? false;

    // Build param types
    $paramParts = [];
    if ($hasPathParams) {
        foreach ($route['pathParams'] as $param) {
            $paramParts[] = "$param: string | number";
        }
    }
    if ($hasQuery) {
        $paramParts[] = "query?: RouteTypeMap['{$route['name']}']['query']";
    }
    $needsParams = !empty($paramParts);
    $paramsType = $needsParams ? '{ ' . implode('; ', $paramParts) . ' }' : '';

    // Build the api call arguments
    $apiCallArgs = [];
    if ($hasPathParams || $hasQuery) {
        $apiCallArgs[] = 'params';
    }
    $apiCallArgsStr = empty($apiCallArgs) ? '' : implode(', ', $apiCallArgs);
@endphp
    /** {!! ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::snake($action))) !!} query options */
    {!! $action !!}: ({!! $needsParams ? "params: $paramsType" : '' !!}) =>
@if($isPaginated)
        infiniteQueryOptions({
            queryKey: {!! $queriesName !!}.keys.{!! $action !!}({!! $needsParams ? 'params' : '' !!}),
            queryFn: ({ pageParam = 1 }) => api.{!! $groupName !!}.{!! $action !!}({!! $needsParams ? '{ ...params, query: { ...params.query, page: pageParam } as RouteTypeMap[\'' . $route['name'] . '\'][\'query\'] }' : '{ query: { page: pageParam } }' !!}),
            initialPageParam: 1,
            getNextPageParam: (lastPage) => {
                const meta = (lastPage as { meta?: { current_page: number; last_page: number } }).meta;
                return meta && meta.current_page < meta.last_page ? meta.current_page + 1 : undefined;
            },
        }),
@else
        queryOptions({
            queryKey: {!! $queriesName !!}.keys.{!! $action !!}({!! $needsParams ? 'params' : '' !!}),
            queryFn: () => api.{!! $groupName !!}.{!! $action !!}({!! $needsParams ? 'params' : '' !!}),
        }),
@endif

@endforeach
};

@endif
@endforeach
