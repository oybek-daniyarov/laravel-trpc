@include('trpc::partials.file-header', ['description' => 'API Client Factory'])

import type { RouteName, GetRoutes, PostRoutes, PutRoutes, PatchRoutes, DeleteRoutes } from './routes';
import type {
    ResponseOf,
    RequiresPathParams,
    GetOptions,
    PostOptions,
    PutOptions,
    PatchOptions,
    DeleteOptions,
    RequestOf,
    QueryParams,
} from './helpers';
import { fetchApi, type ApiClientConfig, type FetchOptions } from './fetch';

/** Request options for the API client */
export interface RequestOptions<T extends RouteName> extends FetchOptions {
    readonly body?: RequestOf<T>;
    readonly query?: QueryParams<T>;
}

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
export function createApiClient(clientConfig: ApiClientConfig = {}): ApiClient {
    return {
        get: <T extends GetRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: GetOptions<T>]
                : [options?: GetOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(name, {
                path: (options as GetOptions<T> | undefined)?.path ?? null,
                config: {
                    query: options?.query,
                    headers: options?.headers,
                    next: options?.next,
                    mobile: options?.mobile,
                    signal: options?.signal,
                },
                clientConfig,
            }) as Promise<ResponseOf<T>>;
        },

        post: <T extends PostRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: PostOptions<T>]
                : [options?: PostOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(name, {
                path: (options as PostOptions<T> | undefined)?.path ?? null,
                config: {
                    body: (options as PostOptions<T> | undefined)?.body,
                    query: options?.query,
                    headers: options?.headers,
                    next: options?.next,
                    mobile: options?.mobile,
                    signal: options?.signal,
                },
                clientConfig,
            }) as Promise<ResponseOf<T>>;
        },

        put: <T extends PutRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: PutOptions<T>]
                : [options?: PutOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(name, {
                path: (options as PutOptions<T> | undefined)?.path ?? null,
                config: {
                    body: (options as PutOptions<T> | undefined)?.body,
                    query: options?.query,
                    headers: options?.headers,
                    next: options?.next,
                    mobile: options?.mobile,
                    signal: options?.signal,
                },
                clientConfig,
            }) as Promise<ResponseOf<T>>;
        },

        patch: <T extends PatchRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: PatchOptions<T>]
                : [options?: PatchOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(name, {
                path: (options as PatchOptions<T> | undefined)?.path ?? null,
                config: {
                    body: (options as PatchOptions<T> | undefined)?.body,
                    query: options?.query,
                    headers: options?.headers,
                    next: options?.next,
                    mobile: options?.mobile,
                    signal: options?.signal,
                },
                clientConfig,
            }) as Promise<ResponseOf<T>>;
        },

        delete: <T extends DeleteRoutes>(
            name: T,
            ...args: RequiresPathParams<T> extends true
                ? [options: DeleteOptions<T>]
                : [options?: DeleteOptions<T>]
        ): Promise<ResponseOf<T>> => {
            const [options] = args;
            return fetchApi(name, {
                path: (options as DeleteOptions<T> | undefined)?.path ?? null,
                config: {
                    query: options?.query,
                    headers: options?.headers,
                    next: options?.next,
                    mobile: options?.mobile,
                    signal: options?.signal,
                },
                clientConfig,
            }) as Promise<ResponseOf<T>>;
        },
    };
}
