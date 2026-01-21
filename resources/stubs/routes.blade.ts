@include('trpc::partials.file-header', ['description' => 'Route Definitions & Type Map'])

/* eslint-disable @typescript-eslint/triple-slash-reference */
/// <reference path="./laravel.d.ts" />

import type { NoBody, PaginatedResponse, ValidationError } from './types';

@foreach($groupedRoutes as $group => $groupRoutes)
// {!! $group !!} routes
@foreach($groupRoutes as $route)
@php
    $interface = $getInterfaceName($route['name']);
    $requestType = $route['hasRequest'] ? $route['requestType'] : 'NoBody';
    $queryType = $route['hasQuery'] ? $route['queryType'] : 'NoBody';
    $responseType = $route['responseType'] ?? 'unknown';
    $errorType = $route['errorType'] ?? 'ValidationError';
@endphp
export interface {!! $interface !!} {
    readonly request: {!! $requestType !!};
    readonly query: {!! $queryType !!};
    readonly response: {!! $responseType !!};
    readonly error: {!! $errorType !!};
}

@endforeach
@endforeach

/** Map route names to their type definitions */
export interface RouteTypeMap {
@foreach($routes as $route)
@php
    $interface = $getInterfaceName($route['name']);
@endphp
    '{!! $route['name'] !!}': {!! $interface !!};
@endforeach
}

/** Route definitions */
export const routes = {
@foreach($groupedRoutes as $group => $groupRoutes)
    // {!! $group !!} routes
@foreach($groupRoutes as $route)
@php
    $pathParamsArray = $route['pathParams'];
    $pathParamsTuple = empty($pathParamsArray) ? '[]' : "['" . implode("', '", $pathParamsArray) . "']";
    $middlewareArray = $route['middleware'] ?? [];
    $middlewareTuple = empty($middlewareArray) ? '[]' : "['" . implode("', '", $middlewareArray) . "']";
    $isAuthenticated = collect($middlewareArray)->contains(fn($m) => str_starts_with($m, 'auth') || $m === 'sanctum');
@endphp
    '{!! $route['name'] !!}': {
        path: '{!! $route['path'] !!}' as const,
        method: '{!! $route['method'] !!}' as const,
        params: {!! $pathParamsTuple !!} as const,
        middleware: {!! $middlewareTuple !!} as const,
        authenticated: {!! $isAuthenticated ? 'true' : 'false' !!},
    },
@endforeach

@endforeach
} as const;

/** All available route names */
export type RouteName = keyof typeof routes;

/** Route configuration object type */
export type Routes = typeof routes;

/** Extract routes by HTTP method */
export type GetRoutes = {
    [K in RouteName]: Routes[K]['method'] extends 'get' ? K : never
}[RouteName];

export type PostRoutes = {
    [K in RouteName]: Routes[K]['method'] extends 'post' ? K : never
}[RouteName];

export type PutRoutes = {
    [K in RouteName]: Routes[K]['method'] extends 'put' ? K : never
}[RouteName];

export type PatchRoutes = {
    [K in RouteName]: Routes[K]['method'] extends 'patch' ? K : never
}[RouteName];

export type DeleteRoutes = {
    [K in RouteName]: Routes[K]['method'] extends 'delete' ? K : never
}[RouteName];

/** Extract routes that require authentication */
export type AuthenticatedRoutes = {
    [K in RouteName]: Routes[K]['authenticated'] extends true ? K : never
}[RouteName];

/** Extract public routes (no authentication required) */
export type PublicRoutes = {
    [K in RouteName]: Routes[K]['authenticated'] extends false ? K : never
}[RouteName];
