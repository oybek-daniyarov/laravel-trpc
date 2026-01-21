@include('trpc::partials.file-header', ['description' => 'Barrel Exports'])

// ============================================
// Essential Exports (most common usage)
// ============================================

// Routes and URL building
export { routes } from './routes';
export { url, getRoute, requiresPath, getMethod, getPath } from './url-builder';
export type { RouteName, Routes, RouteTypeMap } from './routes';
export type { UrlOptions } from './url-builder';

// Type helpers
export type {
    RequestOf,
    ResponseOf,
    QueryOf,
    ErrorOf,
    ParamsOf,
    PathParams,
    PathParamNames,
    QueryParams,
    RequiresPathParams,
} from './helpers';

// Core types
export type {
    HttpMethod,
    PaginatedResponse,
    PaginationMeta,
    PaginationLinks,
    ApiError,
    NoBody,
    ValidationError,
    NotFoundError,
    UnauthorizedError,
} from './types';

// ============================================
// Route Filter Types
// ============================================
export type {
    GetRoutes,
    PostRoutes,
    PutRoutes,
    PatchRoutes,
    DeleteRoutes,
    AuthenticatedRoutes,
    PublicRoutes,
} from './routes';

// Method-specific options
export type {
    MethodOptions,
    MutationOptions,
    GetOptions,
    PostOptions,
    PutOptions,
    PatchOptions,
    DeleteOptions,
} from './helpers';

// ============================================
// API Client (for SPA/API usage)
// ============================================
export { createApiClient } from './client';
export type { RequestOptions, ApiClient } from './client';

// Fetch function
export { fetchApi } from './fetch';
export type {
    NextCacheOptions,
    MobileOptions,
    FetchOptions,
    ApiClientConfig,
    RequestConfig,
} from './fetch';
@if(($outputs['grouped-api'] ?? true))

// Grouped API client (object-based)
export { api, createApi, createServerApi } from './api';
export type { Api } from './api';
@endif

// ============================================
// Framework Integrations
// ============================================
@if(($outputs['react-query'] ?? false))

// React Query integration
export {
    queryKey,
    baseQueryKey,
    mutationKey,
    createQueryKeys,
    createQueryOptions,
    createInfiniteQueryOptions,
    createMutationOptions,
} from './react-query';
export type { ReactQueryOptions, MutationVariables } from './react-query';
@endif
@if(($outputs['queries'] ?? false))

// React Query hooks by resource
export * from './queries';
@endif
@if(($outputs['inertia'] ?? true))

// Inertia integration
export { route, visit, formAction } from './inertia';
export type { InertiaVisitOptions } from './inertia';
@endif
