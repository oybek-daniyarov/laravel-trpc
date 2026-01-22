@include('trpc::partials.file-header', ['description' => 'Core Fetch Function'])

import type { ApiError } from './types';

/** Next.js cache/revalidation options */
export interface NextCacheOptions {
    readonly cache?: RequestCache;
    readonly revalidate?: number | false;
    readonly tags?: readonly string[];
}

/** Mobile/React Native specific options */
export interface MobileOptions {
    readonly timeout?: number;
    readonly retry?: {
        readonly count?: number;
        readonly delay?: number;
        readonly backoff?: 'linear' | 'exponential';
    };
    readonly signal?: AbortSignal;
}

/** Base fetch options */
export interface FetchOptions {
    readonly headers?: Readonly<Record<string, string>>;
    readonly next?: NextCacheOptions;
    readonly mobile?: MobileOptions;
    readonly signal?: AbortSignal;
}

/** Per-request options for API calls */
export interface RequestOptions {
    /** Custom headers for this request */
    readonly headers?: Readonly<Record<string, string>>;
    /** Next.js cache configuration */
    readonly next?: NextCacheOptions;
    /** Mobile/React Native specific options */
    readonly mobile?: MobileOptions;
    /** Abort signal */
    readonly signal?: AbortSignal;
}

/** CSRF configuration options */
export interface CsrfConfig {
    /** Static CSRF token to use */
    readonly token?: string;
    /** Cookie name to read token from (e.g., 'XSRF-TOKEN') */
    readonly cookie?: string;
    /** Header name to send token in (defaults to 'X-XSRF-TOKEN') */
    readonly header?: string;
}

/** Configuration for the API client */
export interface ApiClientConfig {
    readonly baseUrl: string;
    readonly headers?: Readonly<Record<string, string>>;
    readonly onRequest?: (url: string, init: RequestInit) => RequestInit | Promise<RequestInit>;
    readonly onResponse?: <T>(response: Response, data: T) => T | Promise<T>;
    readonly onError?: (error: ApiError) => void;
    readonly next?: NextCacheOptions;
    readonly mobile?: MobileOptions;
    readonly csrf?: CsrfConfig;
}

/** Route definition for type-safe API calls */
export interface RouteDefinition {
    readonly path: string;
    readonly method: 'get' | 'post' | 'put' | 'patch' | 'delete';
    readonly params: readonly string[];
}

/**
 * Extract CSRF token from configuration or cookies.
 */
function getCsrfToken(config: CsrfConfig | undefined): string | undefined {
    if (!config) return undefined;
    if (config.token) return config.token;
    if (config.cookie && typeof document !== 'undefined') {
        const match = document.cookie.match(new RegExp(`${config.cookie}=([^;]+)`));
        return match?.[1] ? decodeURIComponent(match[1]) : undefined;
    }
    return undefined;
}

/**
 * Calculate delay for retry with backoff.
 */
function calculateRetryDelay(
    attempt: number,
    baseDelay: number,
    backoff: 'linear' | 'exponential'
): number {
    if (backoff === 'exponential') {
        return baseDelay * Math.pow(2, attempt);
    }
    return baseDelay * (attempt + 1);
}

/**
 * Sleep for a given number of milliseconds.
 */
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Check if an error is retryable (network errors, 5xx, 429).
 */
function isRetryableError(error: unknown): boolean {
    if (error instanceof TypeError) {
        // Network errors
        return true;
    }
    if (typeof error === 'object' && error !== null && 'status' in error) {
        const status = (error as { status: number }).status;
        // Retry on 5xx server errors and 429 rate limit
        return status >= 500 || status === 429;
    }
    return false;
}

/**
 * Build URL with path parameters and query string.
 */
export function buildUrl(
    route: RouteDefinition,
    path: Record<string, string | number> | null,
    query?: Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>
): string {
    let result = route.path;

    if (path && typeof path === 'object') {
        for (const [key, value] of Object.entries(path)) {
            result = result.replace(`{${key}}`, encodeURIComponent(String(value)));
        }
    }

    if (query) {
        const searchParams = new URLSearchParams();
        for (const [key, value] of Object.entries(query)) {
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

/**
 * Low-level fetch function for direct use or integration with any library.
 */
export async function fetchApi<TResponse>(
    route: RouteDefinition,
    options: {
        path?: Record<string, string | number> | null;
        body?: unknown;
        query?: Record<string, string | number | boolean | null | undefined | readonly (string | number)[]>;
        clientConfig: ApiClientConfig;
        requestOptions?: RequestOptions;
    }
): Promise<TResponse> {
    const { path, body, query, clientConfig, requestOptions = {} } = options;
    const url = clientConfig.baseUrl + buildUrl(route, path ?? null, query);

    const nextOptions = { ...clientConfig.next, ...requestOptions.next };
    const mobileOptions = { ...clientConfig.mobile, ...requestOptions.mobile };
    const csrfToken = getCsrfToken(clientConfig.csrf);

    let init: RequestInit = {
        method: route.method.toUpperCase(),
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(csrfToken && { [clientConfig.csrf?.header ?? 'X-XSRF-TOKEN']: csrfToken }),
            ...clientConfig.headers,
            ...requestOptions.headers,
        },
        ...(nextOptions.cache && { cache: nextOptions.cache }),
        ...(requestOptions.signal && { signal: requestOptions.signal }),
    };

    if (body !== undefined && route.method !== 'get') {
        init.body = JSON.stringify(body);
    }

    if (clientConfig.onRequest) {
        init = await clientConfig.onRequest(url, init);
    }

    // Mobile timeout handling
    let timeoutId: ReturnType<typeof setTimeout> | undefined;
    if (mobileOptions.timeout && !init.signal) {
        const controller = new AbortController();
        init.signal = controller.signal;
        timeoutId = setTimeout(() => controller.abort(), mobileOptions.timeout);
    }

    // Retry configuration
    const maxRetries = mobileOptions.retry?.count ?? 0;
    const retryDelay = mobileOptions.retry?.delay ?? 1000;
    const retryBackoff = mobileOptions.retry?.backoff ?? 'exponential';

    let lastError: unknown;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
        try {
            const response = await fetch(url, init);

            if (timeoutId) clearTimeout(timeoutId);

            if (!response.ok) {
                let error: ApiError;
                try {
                    const data = await response.json();
                    error = {
                        ...data,
                        status: response.status,
                        statusText: response.statusText,
                    };
                } catch {
                    error = {
                        message: `HTTP ${response.status}: ${response.statusText}`,
                        status: response.status,
                        statusText: response.statusText,
                    };
                }

                // Check if we should retry this error
                if (attempt < maxRetries && isRetryableError(error)) {
                    lastError = error;
                    await sleep(calculateRetryDelay(attempt, retryDelay, retryBackoff));
                    continue;
                }

                clientConfig.onError?.(error);
                throw error;
            }

            // Handle empty responses (204 No Content)
            if (response.status === 204 || response.headers.get('content-length') === '0') {
                if (clientConfig.onResponse) {
                    return await clientConfig.onResponse(response, undefined as TResponse);
                }
                return undefined as TResponse;
            }

            let data = await response.json();

            if (clientConfig.onResponse) {
                data = await clientConfig.onResponse(response, data);
            }

            return data as TResponse;
        } catch (error) {
            if (timeoutId) clearTimeout(timeoutId);

            // Check if we should retry this error
            if (attempt < maxRetries && isRetryableError(error)) {
                lastError = error;
                await sleep(calculateRetryDelay(attempt, retryDelay, retryBackoff));
                continue;
            }

            throw error;
        }
    }

    // This should never be reached, but TypeScript needs it
    throw lastError;
}
