@include('trpc::partials.file-header', ['description' => ucfirst($group) . ' Route Definitions'])

/* eslint-disable @typescript-eslint/triple-slash-reference */
/// <reference path="../laravel.d.ts" />

import type { NoBody, ValidationError, PaginatedResponse } from '../core';

@foreach($routes as $route)
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

/** Type map for {!! $group !!} routes */
export interface {!! ucfirst(\Illuminate\Support\Str::camel($group)) !!}RouteTypeMap {
@foreach($routes as $route)
@php
    $interface = $getInterfaceName($route['name']);
@endphp
    '{!! $route['name'] !!}': {!! $interface !!};
@endforeach
}

/** Route definitions for {!! $group !!} */
export const {!! \Illuminate\Support\Str::camel($group) !!}Routes = {
@foreach($routes as $route)
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
} as const;

/** Route names for {!! $group !!} */
export type {!! ucfirst(\Illuminate\Support\Str::camel($group)) !!}RouteName = keyof typeof {!! \Illuminate\Support\Str::camel($group) !!}Routes;
