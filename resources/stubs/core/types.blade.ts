@include('trpc::partials.file-header', ['description' => 'Core API Types'])

/** HTTP methods supported by the API */
export type HttpMethod = 'get' | 'post' | 'put' | 'patch' | 'delete';

/** Pagination metadata from Laravel */
export interface PaginationMeta {
    readonly current_page: number;
    readonly last_page: number;
    readonly per_page: number;
    readonly total: number;
    readonly from: number | null;
    readonly to: number | null;
}

/** Pagination links from Laravel */
export interface PaginationLinks {
    readonly first: string;
    readonly last: string;
    readonly prev: string | null;
    readonly next: string | null;
}

/** Paginated response wrapper from Laravel */
export interface PaginatedResponse<T> {
    readonly data: readonly T[];
    readonly meta: PaginationMeta;
    readonly links: PaginationLinks;
}

/** Laravel API error response */
export interface ApiError {
    readonly message: string;
    readonly status: number;
    readonly statusText?: string;
    readonly errors?: Readonly<Record<string, readonly string[]>>;
}

/** Empty request body marker */
export type NoBody = undefined;

// ============================================
// Standard Error Types
// ============================================

/** Laravel validation error response (422) */
export interface ValidationError {
    readonly message: string;
    readonly errors: Readonly<Record<string, readonly string[]>>;
}

/** Not found error response (404) */
export interface NotFoundError {
    readonly message: string;
}

/** Unauthorized error response (401) */
export interface UnauthorizedError {
    readonly message: string;
}

/** Forbidden error response (403) */
export interface ForbiddenError {
    readonly message: string;
}

/** Server error response (500) */
export interface ServerError {
    readonly message: string;
    readonly exception?: string;
    readonly file?: string;
    readonly line?: number;
    readonly trace?: readonly { file: string; line: number; function: string }[];
}
