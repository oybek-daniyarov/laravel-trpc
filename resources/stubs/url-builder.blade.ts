@include('trpc::partials.file-header', ['description' => 'URL Builder'])

import { routes, type RouteName, type Routes } from './routes';

/** Options for URL building */
export interface UrlOptions {
    readonly query?: Readonly<Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>>;
}

/** Extract path param names as a tuple */
type PathParams<T extends RouteName> = Routes[T]['params'];

/** Extract path param names as a union */
type PathParamNames<T extends RouteName> = PathParams<T>[number];

/** Build path params object type from route */
type ParamsOf<T extends RouteName> = PathParamNames<T> extends never
    ? Record<string, never>
    : { readonly [K in PathParamNames<T>]: string | number };

/**
 * Build a URL for a route with type-safe path params.
 *
 * @example
 * // With path params
 * url('users.show', { id: 123 })
 * // => 'api/users/123'
 *
 * // Without path params but with query
 * url('users.index', null, { query: { page: 1 } })
 * // => 'api/users?page=1'
 *
 * // With both path params and query
 * url('users.show', { id: 123 }, { query: { include: 'posts' } })
 * // => 'api/users/123?include=posts'
 */
export function url<T extends RouteName>(
    name: T,
    path?: ParamsOf<T> | null,
    options?: UrlOptions,
): string {
    let result: string = routes[name].path;

    if (path && typeof path === 'object') {
        for (const [key, value] of Object.entries(path)) {
            result = result.replace(`{${key}}`, encodeURIComponent(String(value)));
        }
    }

    if (options?.query) {
        const searchParams = new URLSearchParams();
        for (const [key, value] of Object.entries(options.query)) {
            if (value === null || value === undefined) continue;
            if (Array.isArray(value)) {
                for (const v of value) {
                    searchParams.append(`${key}[]`, String(v));
                }
            } else {
                searchParams.append(key, String(value));
            }
        }
        const queryString = searchParams.toString();
        if (queryString) {
            result += '?' + queryString;
        }
    }

    return result;
}

// ============================================
// Route Metadata Helpers (standalone usage)
// ============================================

/**
 * Get route metadata by name.
 *
 * @example
 * const routeInfo = getRoute('users.index');
 * // { path: 'api/users', method: 'get', params: [] }
 */
export function getRoute<T extends RouteName>(name: T): (typeof routes)[T] {
    return routes[name];
}

/**
 * Check if a route requires path parameters.
 *
 * @example
 * requiresPath('users.show') // true (has {id} param)
 * requiresPath('users.index') // false
 */
export function requiresPath<T extends RouteName>(name: T): boolean {
    return routes[name].params.length > 0;
}

/**
 * Get the HTTP method for a route.
 *
 * @example
 * getMethod('users.store') // 'post'
 */
export function getMethod<T extends RouteName>(name: T): (typeof routes)[T]['method'] {
    return routes[name].method;
}

/**
 * Get the path template for a route.
 *
 * @example
 * getPath('users.show') // 'api/users/{id}'
 */
export function getPath<T extends RouteName>(name: T): (typeof routes)[T]['path'] {
    return routes[name].path;
}
