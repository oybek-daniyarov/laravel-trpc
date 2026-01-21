@include('trpc::partials.file-header', ['description' => 'Type Helpers'])

import type { NoBody } from './types';
import type {
    RouteTypeMap,
    RouteName,
    Routes,
    GetRoutes,
    PostRoutes,
    PutRoutes,
    PatchRoutes,
    DeleteRoutes,
} from './routes';
import type { FetchOptions } from './fetch';

// ============================================
// Core Type Helpers
// ============================================

/** Extract request type from a route */
export type RequestOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['request']
    : never;

/** Extract query type from a route */
export type QueryOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['query']
    : never;

/** Extract response type from a route */
export type ResponseOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['response']
    : never;

/** Extract error response type from a route */
export type ErrorOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['error']
    : never;

/** Extract path param names as a tuple */
export type PathParams<T extends RouteName> = Routes[T]['params'];

/** Extract path param names as a union */
export type PathParamNames<T extends RouteName> = PathParams<T>[number];

/** Build path params object type from route */
export type ParamsOf<T extends RouteName> = PathParamNames<T> extends never
    ? Record<string, never>
    : { readonly [K in PathParamNames<T>]: string | number };

/** Check if route requires path params */
export type RequiresPathParams<T extends RouteName> = Routes[T]['params']['length'] extends 0
    ? false
    : true;

/** Query params type - uses QueryOf if defined, otherwise generic */
export type QueryParams<T extends RouteName> = QueryOf<T> extends NoBody
    ? Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>
    : Partial<QueryOf<T>>;

// ============================================
// Method-specific option types
// ============================================

/** Base options for all method calls (no body) */
export interface MethodOptions<T extends RouteName> extends FetchOptions {
    readonly path?: ParamsOf<T>;
    readonly query?: QueryParams<T>;
}

/** Options for mutation methods (with body) */
export interface MutationOptions<T extends RouteName> extends MethodOptions<T> {
    readonly body?: RequestOf<T>;
}

/** GET method options - conditionally require path params */
export type GetOptions<T extends GetRoutes> = RequiresPathParams<T> extends true
    ? MethodOptions<T> & { readonly path: ParamsOf<T> }
    : MethodOptions<T>;

/** POST method options - conditionally require path params */
export type PostOptions<T extends PostRoutes> = RequiresPathParams<T> extends true
    ? MutationOptions<T> & { readonly path: ParamsOf<T> }
    : MutationOptions<T>;

/** PUT method options - conditionally require path params */
export type PutOptions<T extends PutRoutes> = RequiresPathParams<T> extends true
    ? MutationOptions<T> & { readonly path: ParamsOf<T> }
    : MutationOptions<T>;

/** PATCH method options - conditionally require path params */
export type PatchOptions<T extends PatchRoutes> = RequiresPathParams<T> extends true
    ? MutationOptions<T> & { readonly path: ParamsOf<T> }
    : MutationOptions<T>;

/** DELETE method options - conditionally require path params */
export type DeleteOptions<T extends DeleteRoutes> = RequiresPathParams<T> extends true
    ? MethodOptions<T> & { readonly path: ParamsOf<T> }
    : MethodOptions<T>;
