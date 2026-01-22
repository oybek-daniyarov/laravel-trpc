@include('trpc::partials.file-header', ['description' => 'API Client Factory'])

import {
    routes,
    type RouteName,
    type Routes,
    type RouteTypeMap,
    type GetRoutes,
    type PostRoutes,
    type PutRoutes,
    type PatchRoutes,
    type DeleteRoutes,
} from './routes';
import { fetchApi, type ApiClientConfig, type FetchOptions } from './core';

// ============================================
// Local Type Helpers
// ============================================

/** Extract path param names as a tuple */
type PathParams<T extends RouteName> = Routes[T]['params'];

/** Extract path param names as a union */
type PathParamNames<T extends RouteName> = PathParams<T>[number];

/** Build path params object type from route */
type ParamsOf<T extends RouteName> = PathParamNames<T> extends never
    ? Record<string, never>
    : { readonly [K in PathParamNames<T>]: string | number };

/** Check if route requires path params */
type RequiresPathParams<T extends RouteName> = Routes[T]['params']['length'] extends 0
    ? false
    : true;

/** Extract request type from a route */
type RequestOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['request']
    : never;

/** Extract response type from a route */
type ResponseOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['response']
    : never;

/** Extract query type from a route */
type QueryOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['query']
    : never;

/** Empty body marker */
type NoBody = undefined;

/** Query params type - uses typed query if available, otherwise generic */
type QueryParams<T extends RouteName> = QueryOf<T> extends NoBody
    ? Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>
    : Partial<QueryOf<T>>;

/** Base options for all method calls (no body) */
interface MethodOptions<T extends RouteName> extends FetchOptions {
    readonly path?: ParamsOf<T>;
    readonly query?: QueryParams<T>;
}

/** Options for mutation methods (with body) */
interface MutationOptions<T extends RouteName> extends MethodOptions<T> {
    readonly body?: RequestOf<T>;
}

/** GET method options - conditionally require path params */
type GetOptions<T extends GetRoutes> = RequiresPathParams<T> extends true
    ? MethodOptions<T> & { readonly path: ParamsOf<T> }
    : MethodOptions<T>;

/** POST method options - conditionally require path params */
type PostOptions<T extends PostRoutes> = RequiresPathParams<T> extends true
    ? MutationOptions<T> & { readonly path: ParamsOf<T> }
    : MutationOptions<T>;

/** PUT method options - conditionally require path params */
type PutOptions<T extends PutRoutes> = RequiresPathParams<T> extends true
    ? MutationOptions<T> & { readonly path: ParamsOf<T> }
    : MutationOptions<T>;

/** PATCH method options - conditionally require path params */
type PatchOptions<T extends PatchRoutes> = RequiresPathParams<T> extends true
    ? MutationOptions<T> & { readonly path: ParamsOf<T> }
    : MutationOptions<T>;

/** DELETE method options - conditionally require path params */
type DeleteOptions<T extends DeleteRoutes> = RequiresPathParams<T> extends true
    ? MethodOptions<T> & { readonly path: ParamsOf<T> }
    : MethodOptions<T>;

/** Type-safe API client with method-specific calls */
export interface ApiClient {
    /** Make a GET request - autocomplete shows only GET routes */
    get<T extends GetRoutes>(
        name: T,
        ...args: RequiresPathParams<T> extends true
            ? [options: GetOptions<T>]
            : [options?: GetOptions<T>]
    ): Promise<ResponseOf<T>>;

    /** Make a POST request - autocomplete shows only POST routes */
    post<T extends PostRoutes>(
        name: T,
        ...args: RequiresPathParams<T> extends true
            ? [options: PostOptions<T>]
            : [options?: PostOptions<T>]
    ): Promise<ResponseOf<T>>;

    /** Make a PUT request - autocomplete shows only PUT routes */
    put<T extends PutRoutes>(
        name: T,
        ...args: RequiresPathParams<T> extends true
            ? [options: PutOptions<T>]
            : [options?: PutOptions<T>]
    ): Promise<ResponseOf<T>>;

    /** Make a PATCH request - autocomplete shows only PATCH routes */
    patch<T extends PatchRoutes>(
        name: T,
        ...args: RequiresPathParams<T> extends true
            ? [options: PatchOptions<T>]
            : [options?: PatchOptions<T>]
    ): Promise<ResponseOf<T>>;

    /** Make a DELETE request - autocomplete shows only DELETE routes */
    delete<T extends DeleteRoutes>(
        name: T,
        ...args: RequiresPathParams<T> extends true
            ? [options: DeleteOptions<T>]
            : [options?: DeleteOptions<T>]
    ): Promise<ResponseOf<T>>;
}

/**
 * Create a configured API client.
 *
 * @example
 * const api = createApiClient({
 *   baseUrl: '/api',
 *   headers: { Authorization: `Bearer ${token}` },
 * });
 *
 * // Method-specific calls with autocomplete filtering
 * const users = await api.get('users.index', { query: { page: 1 } });
 * const user = await api.get('users.show', { path: { id: 123 } });
 * const newUser = await api.post('users.store', { body: { name: 'John' } });
 * await api.put('users.update', { path: { id: 1 }, body: { name: 'Jane' } });
 * await api.delete('users.destroy', { path: { id: 1 } });
 */
export function createApiClient(clientConfig: ApiClientConfig): ApiClient {
    return {
        get: <T extends GetRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: GetOptions<T>]
                : [options?: GetOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(
                routes[name],
                {
                    path: (options as GetOptions<T> | undefined)?.path ?? null,
                    query: options?.query as Record<string, string | number | boolean | readonly (string | number)[] | null | undefined> | undefined,
                    clientConfig,
                    requestOptions: {
                        headers: options?.headers,
                        next: options?.next,
                        mobile: options?.mobile,
                        signal: options?.signal,
                    },
                }
            );
        },

        post: <T extends PostRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: PostOptions<T>]
                : [options?: PostOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(
                routes[name],
                {
                    path: (options as PostOptions<T> | undefined)?.path ?? null,
                    body: (options as PostOptions<T> | undefined)?.body,
                    query: options?.query as Record<string, string | number | boolean | readonly (string | number)[] | null | undefined> | undefined,
                    clientConfig,
                    requestOptions: {
                        headers: options?.headers,
                        next: options?.next,
                        mobile: options?.mobile,
                        signal: options?.signal,
                    },
                }
            );
        },

        put: <T extends PutRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: PutOptions<T>]
                : [options?: PutOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(
                routes[name],
                {
                    path: (options as PutOptions<T> | undefined)?.path ?? null,
                    body: (options as PutOptions<T> | undefined)?.body,
                    query: options?.query as Record<string, string | number | boolean | readonly (string | number)[] | null | undefined> | undefined,
                    clientConfig,
                    requestOptions: {
                        headers: options?.headers,
                        next: options?.next,
                        mobile: options?.mobile,
                        signal: options?.signal,
                    },
                }
            );
        },

        patch: <T extends PatchRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: PatchOptions<T>]
                : [options?: PatchOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(
                routes[name],
                {
                    path: (options as PatchOptions<T> | undefined)?.path ?? null,
                    body: (options as PatchOptions<T> | undefined)?.body,
                    query: options?.query as Record<string, string | number | boolean | readonly (string | number)[] | null | undefined> | undefined,
                    clientConfig,
                    requestOptions: {
                        headers: options?.headers,
                        next: options?.next,
                        mobile: options?.mobile,
                        signal: options?.signal,
                    },
                }
            );
        },

        delete: <T extends DeleteRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: DeleteOptions<T>]
                : [options?: DeleteOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(
                routes[name],
                {
                    path: (options as DeleteOptions<T> | undefined)?.path ?? null,
                    query: options?.query as Record<string, string | number | boolean | readonly (string | number)[] | null | undefined> | undefined,
                    clientConfig,
                    requestOptions: {
                        headers: options?.headers,
                        next: options?.next,
                        mobile: options?.mobile,
                        signal: options?.signal,
                    },
                }
            );
        },
    };
}
