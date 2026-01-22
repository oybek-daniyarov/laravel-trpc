@include('trpc::partials.file-header', ['description' => 'Aggregated Route Definitions'])

/* eslint-disable @typescript-eslint/triple-slash-reference */
/// <reference path="./laravel.d.ts" />

@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
    $groupNamePascal = ucfirst($groupName);
@endphp
import { {!! $groupName !!}Routes, type {!! $groupNamePascal !!}RouteTypeMap } from './{!! $group !!}';
@endforeach

/** Combined route type map */
export interface RouteTypeMap extends
@php
    $groupNames = array_keys($groupedRoutes);
    $lastGroup = end($groupNames);
@endphp
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupNamePascal = ucfirst(\Illuminate\Support\Str::camel($group));
@endphp
    {!! $groupNamePascal !!}RouteTypeMap{!! $group !== $lastGroup ? ',' : '' !!}
@endforeach
{}

/** Combined routes object */
export const routes = {
@foreach(array_keys($groupedRoutes) as $group)
@php
    $groupName = \Illuminate\Support\Str::camel($group);
@endphp
    ...{!! $groupName !!}Routes,
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
