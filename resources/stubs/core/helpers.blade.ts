@include('trpc::partials.file-header', ['description' => 'Type Helpers'])

import type { NoBody } from './types';
import type { FetchOptions } from './fetch';
import type { RouteName, RouteTypeMap } from '../routes';

// ============================================
// Generic Type Helpers
// ============================================

/** Build path params object type from param names */
export type BuildParams<TParams extends readonly string[]> = TParams['length'] extends 0
    ? Record<string, never>
    : { readonly [K in TParams[number]]: string | number };

/** Check if params are required */
export type RequiresParams<TParams extends readonly string[]> = TParams['length'] extends 0
    ? false
    : true;

/** Query params type - generic for routes without typed query */
export type GenericQueryParams = Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>;

// ============================================
// Route Type Map Helpers
// ============================================

/** Extract request type from a route name */
export type RequestOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['request']
    : never;

/** Extract query type from a route name */
export type QueryOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['query']
    : never;

/** Extract response type from a route name */
export type ResponseOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['response']
    : never;

/** Extract error type from a route name */
export type ErrorOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['error']
    : never;

/** Query params - uses typed query if available, otherwise generic */
export type QueryParams<TQuery> = TQuery extends NoBody
    ? GenericQueryParams
    : Partial<TQuery>;

// ============================================
// Method-specific option types
// ============================================

/** Base options for all method calls (no body) */
export interface MethodOptions<TParams extends readonly string[], TQuery> extends FetchOptions {
    readonly path?: BuildParams<TParams>;
    readonly query?: QueryParams<TQuery>;
}

/** Options for mutation methods (with body) */
export interface MutationOptions<TParams extends readonly string[], TQuery, TRequest> extends MethodOptions<TParams, TQuery> {
    readonly body?: TRequest;
}
