@include('trpc::partials.file-header', ['description' => 'React Query Integration'])

import { routes, type RouteName, type Routes, type RouteTypeMap } from './routes';
import { fetchApi, type ApiClientConfig } from './core';

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

/** Extract request type from a route */
type RequestOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['request']
    : never;

/** Extract response type from a route */
type ResponseOf<T extends RouteName> = T extends keyof RouteTypeMap
    ? RouteTypeMap[T]['response']
    : never;

/** Query params type */
type QueryParams<_T extends RouteName> = Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>;

// ============================================
// Query Key Helpers
// ============================================

/** Merged params object for query keys */
export type QueryKeyParams<T extends RouteName> = {
    path?: ParamsOf<T> | null;
    query?: QueryParams<T>;
};

/**
 * Create a query key for a route.
 * Uses a standardized 2-part structure: [routeName, params?]
 * This enables easy cache invalidation by route name prefix.
 *
 * @example
 * // Simple key
 * queryKey('users.index')
 * // ['users.index']
 *
 * // With path params
 * queryKey('users.show', { path: { id: 123 } })
 * // ['users.show', { path: { id: 123 } }]
 *
 * // With query params
 * queryKey('users.index', { query: { page: 1 } })
 * // ['users.index', { query: { page: 1 } }]
 *
 * // With both
 * queryKey('users.show', { path: { id: 123 }, query: { include: 'posts' } })
 * // ['users.show', { path: { id: 123 }, query: { include: 'posts' } }]
 */
export function queryKey<T extends RouteName>(
    name: T,
    params?: QueryKeyParams<T>
): readonly [T] | readonly [T, QueryKeyParams<T>] {
    if (params && (params.path !== undefined || params.query !== undefined)) {
        return [name, params] as const;
    }
    return [name] as const;
}

/**
 * Create a base query key for a route (without params).
 * Useful for invalidating all queries for a route.
 *
 * @example
 * // Invalidate all user queries
 * queryClient.invalidateQueries({ queryKey: baseQueryKey('users.index') })
 * queryClient.invalidateQueries({ queryKey: baseQueryKey('users.show') })
 */
export function baseQueryKey<T extends RouteName>(name: T): readonly [T] {
    return [name] as const;
}

/**
 * Create a mutation key for a route.
 *
 * @example
 * mutationKey('users.store')
 * // ['users.store']
 */
export function mutationKey<T extends RouteName>(name: T): readonly [T] {
    return [name] as const;
}

/**
 * Create query keys object for a resource.
 * Follows TanStack Query best practices for query key organization.
 *
 * @example
 * const userKeys = createQueryKeys('users');
 *
 * // All user queries
 * userKeys.all
 * // ['users']
 *
 * // List queries
 * userKeys.lists()
 * // ['users', 'list']
 *
 * // Filtered list
 * userKeys.list({ page: 1, search: 'john' })
 * // ['users', 'list', { page: 1, search: 'john' }]
 *
 * // Detail queries
 * userKeys.details()
 * // ['users', 'detail']
 *
 * // Single detail
 * userKeys.detail(123)
 * // ['users', 'detail', 123]
 */
export function createQueryKeys<TBase extends string>(base: TBase) {
    return {
        all: [base] as const,
        lists: () => [base, 'list'] as const,
        list: <TFilters extends Record<string, unknown>>(filters: TFilters) =>
            [base, 'list', filters] as const,
        details: () => [base, 'detail'] as const,
        detail: <TId extends string | number>(id: TId) =>
            [base, 'detail', id] as const,
    };
}

// ============================================
// Query Options Helpers
// ============================================

/** Options for React Query hooks */
export interface ReactQueryOptions<T extends RouteName, TSelected = ResponseOf<T>> {
    readonly path?: ParamsOf<T> | null;
    readonly query?: QueryParams<T>;
    readonly enabled?: boolean;
    readonly staleTime?: number;
    /** Time in ms before inactive queries are garbage collected (TanStack Query v5+) */
    readonly gcTime?: number;
    readonly refetchOnWindowFocus?: boolean;
    readonly refetchOnMount?: boolean;
    readonly retry?: number | boolean;
    /** Transform the data returned by the query function */
    readonly select?: (data: ResponseOf<T>) => TSelected;
    /** Placeholder data to use while the query is loading */
    readonly placeholderData?: ResponseOf<T> | ((previousData: ResponseOf<T> | undefined) => ResponseOf<T> | undefined);
}

/**
 * Create query options for React Query's useQuery.
 *
 * @example
 * const queryOptions = createQueryOptions('users.show', {
 *   path: { id: 123 },
 *   staleTime: 5000,
 * }, apiConfig);
 *
 * const { data, isLoading } = useQuery(queryOptions);
 */
export function createQueryOptions<T extends RouteName, TSelected = ResponseOf<T>>(
    name: T,
    options: ReactQueryOptions<T, TSelected> = {},
    clientConfig: ApiClientConfig
) {
    const { path, query, enabled = true, select, placeholderData, ...queryOptions } = options;
    const params: QueryKeyParams<T> = {};
    if (path !== undefined) params.path = path;
    if (query !== undefined) params.query = query;

    return {
        queryKey: queryKey(name, Object.keys(params).length > 0 ? params : undefined),
        queryFn: () =>
            fetchApi<ResponseOf<T>>(
                routes[name],
                {
                    path: path ?? null,
                    query,
                    clientConfig,
                }
            ),
        enabled,
        ...(select && { select }),
        ...(placeholderData && { placeholderData }),
        ...queryOptions,
    };
}

/**
 * Create infinite query options for React Query's useInfiniteQuery.
 *
 * @example
 * const infiniteOptions = createInfiniteQueryOptions('users.index', {
 *   query: { per_page: 20 },
 *   getNextPageParam: (lastPage) => lastPage.meta.current_page + 1,
 * }, apiConfig);
 *
 * const { data, fetchNextPage } = useInfiniteQuery(infiniteOptions);
 */
export function createInfiniteQueryOptions<T extends RouteName>(
    name: T,
    options: ReactQueryOptions<T> & {
        getNextPageParam?: (lastPage: ResponseOf<T>, allPages: ResponseOf<T>[]) => unknown;
        getPreviousPageParam?: (firstPage: ResponseOf<T>, allPages: ResponseOf<T>[]) => unknown;
        initialPageParam?: unknown;
    } = {},
    clientConfig: ApiClientConfig
) {
    const {
        path,
        query,
        enabled = true,
        getNextPageParam,
        getPreviousPageParam,
        initialPageParam = 1,
        ...queryOptions
    } = options;

    const params: QueryKeyParams<T> = {};
    if (path !== undefined) params.path = path;
    if (query !== undefined) params.query = query;

    return {
        queryKey: queryKey(name, Object.keys(params).length > 0 ? params : undefined),
        queryFn: ({ pageParam }: { pageParam: unknown }) =>
            fetchApi<ResponseOf<T>>(
                routes[name],
                {
                    path: path ?? null,
                    query: { ...query, page: pageParam } as QueryParams<T>,
                    clientConfig,
                }
            ),
        enabled,
        getNextPageParam,
        getPreviousPageParam,
        initialPageParam,
        ...queryOptions,
    };
}

// ============================================
// Mutation Options Helpers
// ============================================

/** Variables for mutation functions */
export interface MutationVariables<T extends RouteName> {
    path?: ParamsOf<T> | null;
    body?: RequestOf<T>;
    query?: QueryParams<T>;
}

/**
 * Create mutation options for React Query's useMutation.
 *
 * @example
 * const mutation = useMutation(createMutationOptions('users.store', apiConfig));
 * mutation.mutate({ body: { name: 'John', email: 'john@example.com' } });
 */
export function createMutationOptions<T extends RouteName>(
    name: T,
    clientConfig: ApiClientConfig
) {
    return {
        mutationKey: mutationKey(name),
        mutationFn: (variables: MutationVariables<T>) =>
            fetchApi<ResponseOf<T>>(
                routes[name],
                {
                    path: variables.path ?? null,
                    body: variables.body,
                    query: variables.query,
                    clientConfig,
                }
            ),
    };
}
