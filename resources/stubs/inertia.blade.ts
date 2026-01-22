@include('trpc::partials.file-header', ['description' => 'Inertia.js Integration'])

import { routes, type RouteName, type Routes, type RouteTypeMap } from './routes';
import { url, type UrlOptions } from './url-builder';
import type { VisitOptions } from '@inertiajs/core';

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

/** Query params type */
type QueryParams<T extends RouteName> = Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>;

/** Re-export Inertia's VisitOptions for convenience */
export type InertiaVisitOptions = VisitOptions;

/**
 * Build a route URL with type-safe params (Ziggy-compatible).
 *
 * @example
 * import { router } from '@inertiajs/react';
 * import { route } from './inertia';
 *
 * // Simple navigation
 * router.visit(route('users.show', { id: 123 }));
 *
 * // With query params
 * router.visit(route('users.index', null, { page: 1 }));
 */
export function route<T extends RouteName>(
    name: T,
    path?: ParamsOf<T> | null,
    query?: QueryParams<T>,
): string {
    return url(name, path, { query: query as UrlOptions['query'] });
}

/**
 * Build Inertia visit options with type-safe data.
 *
 * @example
 * import { router } from '@inertiajs/react';
 * import { visit } from './inertia';
 *
 * const { url, options } = visit('users.store', null, {
 *   data: { name: 'John', email: 'john@example.com' },
 * });
 * router.visit(url, options);
 */
export function visit<T extends RouteName>(
    name: T,
    path: ParamsOf<T> | null,
    options: InertiaVisitOptions & {
        data?: RequestOf<T>;
        query?: QueryParams<T>;
    } = {},
): { url: string; options: InertiaVisitOptions } {
    const { query, ...visitOptions } = options;
    const visitUrl = url(name, path, { query: query as UrlOptions['query'] });

    return {
        url: visitUrl,
        options: visitOptions,
    };
}

/** Form action result with method spoofing support */
export interface FormActionResult {
    action: string;
    method: 'get' | 'post';
    _method?: string;
}

/**
 * Create form props for Inertia's useForm hook.
 * Supports method spoofing for PUT/PATCH/DELETE requests.
 *
 * @example
 * import { useForm } from '@inertiajs/react';
 * import { formAction } from './inertia';
 *
 * const { action, method, _method } = formAction('users.store');
 * const form = useForm({ name: '', email: '' });
 *
 * <form onSubmit={(e) => {
 *   e.preventDefault();
 *   form.post(action);
 * }}>
 *
 * // For PUT/PATCH/DELETE, _method is provided for method spoofing
 * const updateAction = formAction('users.update', { user: 1 });
 * // { action: '/api/users/1', method: 'post', _method: 'PUT' }
 */
export function formAction<T extends RouteName>(
    name: T,
    path?: ParamsOf<T> | null,
): FormActionResult {
    const formUrl = url(name, path);
    const routeMethod = routes[name].method;

    // HTML forms only support GET and POST
    // Use method spoofing for PUT/PATCH/DELETE
    if (routeMethod === 'get') {
        return { action: formUrl, method: 'get' };
    }

    return {
        action: formUrl,
        method: 'post',
        ...(routeMethod !== 'post' && { _method: routeMethod.toUpperCase() }),
    };
}

/** Props for Inertia Link component */
export interface LinkProps {
    href: string;
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    preserveState?: boolean;
    preserveScroll?: boolean;
}

/**
 * Create props for Inertia Link component.
 *
 * @example
 * import { Link } from '@inertiajs/react';
 * import { linkProps } from './inertia';
 *
 * <Link {...linkProps('users.show', { user: 1 })}>View User</Link>
 *
 * // With options
 * <Link {...linkProps('users.show', { user: 1 }, { preserveScroll: true })}>
 *   View User
 * </Link>
 */
export function linkProps<T extends RouteName>(
    name: T,
    path?: ParamsOf<T> | null,
    options?: { method?: 'get' | 'post' | 'put' | 'patch' | 'delete'; preserveState?: boolean; preserveScroll?: boolean }
): LinkProps {
    const href = url(name, path);
    const routeMethod = routes[name].method;

    return {
        href,
        method: options?.method ?? (routeMethod !== 'get' ? routeMethod : undefined),
        ...(options?.preserveState !== undefined && { preserveState: options.preserveState }),
        ...(options?.preserveScroll !== undefined && { preserveScroll: options.preserveScroll }),
    };
}

/**
 * Type helper for Inertia useForm with route-based typing.
 * Returns the initial data with proper typing for the route's request body.
 *
 * @example
 * import { useForm } from '@inertiajs/react';
 * import { typedFormData } from './inertia';
 *
 * const form = useForm(typedFormData('users.store', {
 *     name: '',
 *     email: '',
 * }));
 *
 * // The form.data is now typed based on the route's request type
 * form.post(route('users.store'));
 */
export function typedFormData<T extends RouteName>(
    _name: T,
    initialData: RequestOf<T> extends object ? RequestOf<T> : Record<string, unknown>
): RequestOf<T> extends object ? RequestOf<T> : Record<string, unknown> {
    return initialData;
}
