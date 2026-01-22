@include('trpc::partials.file-header', ['description' => 'Main Barrel Exports'])

// ============================================
// Core Exports
// ============================================
export * from './core';

// ============================================
// Aggregated Routes
// ============================================
export { routes } from './routes';
export type {
    RouteName,
    Routes,
    RouteTypeMap,
    GetRoutes,
    PostRoutes,
    PutRoutes,
    PatchRoutes,
    DeleteRoutes,
    AuthenticatedRoutes,
    PublicRoutes,
} from './routes';

// ============================================
// Combined API Client
// ============================================
export { createApi, type Api } from './api';
@if($outputs['queries'] ?? false)

// ============================================
// Combined React Query Queries
// ============================================
export { createQueries } from './queries';
@endif
@if($outputs['mutations'] ?? false)

// ============================================
// Combined React Query Mutations
// ============================================
export { createMutations } from './mutations';
@endif

// ============================================
// Per-Resource Exports (for tree-shaking)
// ============================================
@foreach(array_keys($groupedRoutes) as $group)
export * from './{!! $group !!}';
@endforeach
@if($outputs['url-builder'] ?? true)

// ============================================
// URL Builder
// ============================================
export { url, getRoute, requiresPath, getMethod, getPath } from './url-builder';
export type { UrlOptions } from './url-builder';
@endif
@if($outputs['client'] ?? true)

// ============================================
// Method-based API Client
// ============================================
export { createApiClient, type ApiClient } from './client';
@endif
@if($outputs['inertia'] ?? true)

// ============================================
// Inertia.js Integration
// ============================================
export { route, visit, formAction, linkProps, typedFormData } from './inertia';
export type { InertiaVisitOptions, FormActionResult, LinkProps } from './inertia';
@endif
@if($outputs['react-query'] ?? false)

// ============================================
// React Query Utilities
// ============================================
export {
    queryKey,
    baseQueryKey,
    mutationKey,
    createQueryKeys,
    createQueryOptions,
    createInfiniteQueryOptions,
    createMutationOptions,
} from './react-query';
export type { QueryKeyParams, ReactQueryOptions, MutationVariables } from './react-query';
@endif
