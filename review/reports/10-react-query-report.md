# React Query Integration Review Report

**Reviewer:** React Query Reviewer
**Date:** 2026-01-21
**Files Reviewed:**
- `resources/stubs/react-query.blade.ts`
- `resources/stubs/queries.blade.ts`
- `resources/stubs/api.blade.ts`
- `resources/stubs/fetch.blade.ts`
- `resources/stubs/helpers.blade.ts`
- `resources/stubs/types.blade.ts`

---

## Executive Summary

The React Query integration provides a comprehensive, type-safe API for integrating Laravel tRPC-generated types with TanStack Query v5. The implementation includes query key factories, query options helpers, infinite query support, and mutation options - all following modern React Query patterns.

**Overall Rating:** 7.5/10

The implementation demonstrates good understanding of TanStack Query patterns but has several areas where it can better align with v5 best practices and improve type safety.

---

## Checklist Results

### 1. Query Key Structure

| Item | Status | Notes |
|------|--------|-------|
| Deterministic keys | ✅ Pass | Keys are computed from route name and params |
| Includes path params | ✅ Pass | Path params included in key tuple position 1 |
| Includes query params | ✅ Pass | Query params included in key tuple position 2 |
| Hierarchical structure | ✅ Pass | `createQueryKeys` follows TanStack factory pattern |
| Serializable | ✅ Pass | All values are JSON-serializable |

**Query Key Implementation:**
```typescript
// Generated key structure: [routeName, pathParams, queryParams]
queryKey('users.show', { path: { id: 123 }, query: { include: 'posts' } })
// Result: ['users.show', { id: 123 }, { include: 'posts' }]
```

### 2. Query Options Factory

| Item | Status | Notes |
|------|--------|-------|
| Returns valid options object | ✅ Pass | Returns `queryKey`, `queryFn`, `enabled`, and spread options |
| Type-safe query key | ✅ Pass | Uses `RouteName` generic constraint |
| Type-safe response | ✅ Pass | `fetchApi<T>` returns `ResponseOf<T>` |
| Configurable staleTime | ✅ Pass | Accepts `staleTime` in options |
| Works with useQuery | ✅ Pass | Compatible with TanStack Query API |

### 3. Infinite Query Support

| Item | Status | Notes |
|------|--------|-------|
| Pagination works | ✅ Pass | `createInfiniteQueryOptions` handles page params |
| getNextPageParam typed | ⚠️ Partial | Uses `unknown` return type instead of proper typing |
| getPreviousPageParam supported | ✅ Pass | Included in options |
| initialPageParam set | ✅ Pass | Defaults to 1 |
| maxPages option | ❌ Missing | v5 `maxPages` feature not exposed |

### 4. Mutation Options

| Item | Status | Notes |
|------|--------|-------|
| Properly typed variables | ✅ Pass | `MutationVariables<T>` interface |
| Mutation key present | ✅ Pass | Returns `mutationKey` |
| mutationFn typed | ✅ Pass | Returns `ResponseOf<T>` |
| onSuccess/onError hooks | ❌ Missing | Not exposed in factory |

---

## Detailed Findings

### Strengths

#### 1. Comprehensive Query Key Factory Pattern

The `createQueryKeys` function follows the [TkDodo query key factory pattern](https://tkdodo.eu/blog/effective-react-query-keys):

```typescript
export function createQueryKeys<TBase extends string>(base: TBase) {
    return {
        all: [base] as const,
        lists: () => [base, 'list'] as const,
        list: <TFilters>(filters: TFilters) => [base, 'list', filters] as const,
        details: () => [base, 'detail'] as const,
        detail: <TId>(id: TId) => [base, 'detail', id] as const,
    };
}
```

This enables:
- Easy cache invalidation at any level
- Type-safe key composition
- IDE autocomplete support

#### 2. Route-Aware Query Keys

The `queryKey` function creates deterministic keys based on route metadata:

```typescript
export function queryKey<T extends RouteName>(
    name: T,
    options?: { path?: ParamsOf<T>; query?: QueryParams<T> }
): readonly [T, ParamsOf<T> | null | undefined, QueryParams<T> | undefined]
```

This ensures:
- Same route + params = same cache entry
- Automatic cache updates when params change
- No manual key management required

#### 3. Dual Implementation Strategy

Two complementary approaches are provided:

1. **Manual helpers** (`react-query.blade.ts`) - For flexible, custom usage
2. **Auto-generated queries** (`queries.blade.ts`) - For zero-config setup

This supports both simple and complex use cases.

#### 4. Resource-Based Query Organization

The `queries.blade.ts` generates resource-grouped query factories:

```typescript
export const usersQueries = {
    keys: {
        all: ['users'] as const,
        index: (params: { ... }) => ['users', 'index', params] as const,
        show: (params: { id: string | number }) => ['users', 'show', params] as const,
    },
    index: (params) => queryOptions({ ... }),
    show: (params) => queryOptions({ ... }),
};
```

This follows the [recommended TanStack Query v5 pattern](https://tanstack.com/query/v5/docs/react/guides/query-options).

---

### Issues Found

#### Issue 1: Deprecated `cacheTime` Option (Severity: Medium)

**Location:** `resources/stubs/react-query.blade.ts:114`

```typescript
export interface ReactQueryOptions<T extends RouteName> {
    // ...
    readonly cacheTime?: number;  // DEPRECATED in v5
    // ...
}
```

**Impact:** TanStack Query v5 renamed `cacheTime` to `gcTime`. Using `cacheTime` will have no effect and may cause confusion.

**Recommendation:** Rename to `gcTime`:
```typescript
readonly gcTime?: number;
```

**Reference:** [TanStack Query v5 Migration Guide](https://tanstack.com/query/latest/docs/framework/react/guides/migrating-to-v5)

---

#### Issue 2: Missing v5 `queryOptions` Import Usage (Severity: Medium)

**Location:** `resources/stubs/react-query.blade.ts:131-149`

The `createQueryOptions` function returns a plain object instead of using TanStack Query's `queryOptions` helper:

```typescript
// Current implementation
export function createQueryOptions<T extends RouteName>(...) {
    return {
        queryKey: queryKey(name, { path, query }),
        queryFn: () => fetchApi<T>(name, ...),
        enabled,
        ...queryOptions,
    };
}
```

**Impact:** Loses automatic type inference that `queryOptions` provides. This is especially important for `getQueryData` and `setQueryData` type inference.

**Recommendation:** Use the official `queryOptions` helper:
```typescript
import { queryOptions } from '@tanstack/react-query';

export function createQueryOptions<T extends RouteName>(...) {
    return queryOptions({
        queryKey: queryKey(name, { path, query }),
        queryFn: () => fetchApi<T>(name, ...),
        enabled,
        ...options,
    });
}
```

**Reference:** [TanStack Query Options API](https://tanstack.com/query/v5/docs/react/guides/query-options)

---

#### Issue 3: Infinite Query Type Safety Issues (Severity: Medium)

**Location:** `resources/stubs/react-query.blade.ts:162-197`

Several type safety concerns in `createInfiniteQueryOptions`:

1. **`getNextPageParam` returns `unknown`:**
```typescript
getNextPageParam?: (lastPage: ResponseOf<T>, allPages: ResponseOf<T>[]) => unknown;
```

2. **`pageParam` is typed as `unknown`:**
```typescript
queryFn: ({ pageParam }: { pageParam: unknown }) => ...
```

3. **Unsafe type assertion:**
```typescript
query: { ...query, page: pageParam } as unknown as QueryParams<T>,
```

**Impact:** TypeScript won't catch pagination type errors. The `unknown` types bypass type checking.

**Recommendation:** Use proper generics:
```typescript
export function createInfiniteQueryOptions<
    T extends RouteName,
    TPageParam = number
>(
    name: T,
    options: ReactQueryOptions<T> & {
        getNextPageParam?: (lastPage: ResponseOf<T>, allPages: ResponseOf<T>[]) => TPageParam | undefined;
        getPreviousPageParam?: (firstPage: ResponseOf<T>, allPages: ResponseOf<T>[]) => TPageParam | undefined;
        initialPageParam: TPageParam;
    },
    clientConfig?: ApiClientConfig
) {
    // ...
    queryFn: ({ pageParam }: { pageParam: TPageParam }) => ...
}
```

---

#### Issue 4: Missing `maxPages` Option for Infinite Queries (Severity: Low)

**Location:** `resources/stubs/react-query.blade.ts:162-197`

TanStack Query v5 introduced a `maxPages` option to limit stored pages and improve performance for infinite lists.

**Impact:** Users cannot limit memory usage for large infinite queries.

**Recommendation:** Add `maxPages` support:
```typescript
options: ReactQueryOptions<T> & {
    // ...existing options
    maxPages?: number;
}
```

**Reference:** [TanStack Query v5 Infinite Queries](https://tanstack.com/query/v5/docs/react/reference/useInfiniteQuery)

---

#### Issue 5: Mutation Options Missing Lifecycle Hooks (Severity: Medium)

**Location:** `resources/stubs/react-query.blade.ts:217-233`

The `createMutationOptions` factory doesn't expose mutation lifecycle hooks:

```typescript
export function createMutationOptions<T extends RouteName>(
    name: T,
    clientConfig: ApiClientConfig = {}
) {
    return {
        mutationKey: mutationKey(name),
        mutationFn: (variables: MutationVariables<T>) => ...,
        // Missing: onSuccess, onError, onSettled, onMutate
    };
}
```

**Impact:** Users cannot add optimistic updates or cache invalidation within the factory pattern.

**Recommendation:** Accept and pass through lifecycle hooks:
```typescript
interface MutationFactoryOptions<T extends RouteName> {
    onMutate?: (variables: MutationVariables<T>) => Promise<unknown> | unknown;
    onSuccess?: (data: ResponseOf<T>, variables: MutationVariables<T>, context: unknown) => void;
    onError?: (error: ErrorOf<T>, variables: MutationVariables<T>, context: unknown) => void;
    onSettled?: (data: ResponseOf<T> | undefined, error: ErrorOf<T> | null, variables: MutationVariables<T>, context: unknown) => void;
}

export function createMutationOptions<T extends RouteName>(
    name: T,
    options?: MutationFactoryOptions<T>,
    clientConfig?: ApiClientConfig
) {
    return {
        mutationKey: mutationKey(name),
        mutationFn: ...,
        ...options,
    };
}
```

---

#### Issue 6: Inconsistent Query Key Tuple Structure (Severity: Low)

**Location:** `resources/stubs/react-query.blade.ts:31-39` vs `resources/stubs/queries.blade.ts:43-44`

Two different key structures are used:

**react-query.blade.ts:**
```typescript
// [routeName, pathParams, queryParams]
['users.show', { id: 123 }, { include: 'posts' }]
```

**queries.blade.ts:**
```typescript
// [resource, action, params]
['users', 'show', { id: 123, query: { include: 'posts' } }]
```

**Impact:** Cannot invalidate queries created by one approach using keys from the other.

**Recommendation:** Standardize on a single key structure or document the incompatibility.

---

#### Issue 7: Missing Suspense Query Options (Severity: Low)

**Location:** `resources/stubs/react-query.blade.ts`

TanStack Query v5 promotes suspense as a first-class feature with dedicated hooks (`useSuspenseQuery`, `useSuspenseInfiniteQuery`). No suspense-specific options factory exists.

**Impact:** Users must manually handle suspense integration.

**Recommendation:** Add suspense-aware factories:
```typescript
export function createSuspenseQueryOptions<T extends RouteName>(
    name: T,
    options: Omit<ReactQueryOptions<T>, 'enabled'> = {}, // enabled not allowed in suspense
    clientConfig?: ApiClientConfig
) {
    const { path, query, ...queryOptions } = options;
    return {
        queryKey: queryKey(name, { path, query }),
        queryFn: () => fetchApi<T>(name, ...),
        ...queryOptions,
    };
}
```

**Reference:** [TanStack Query v5 Suspense](https://tanstack.com/query/v5/docs/framework/react/guides/suspense)

---

#### Issue 8: Paginated Response Type Assumption (Severity: Low)

**Location:** `resources/stubs/queries.blade.ts:87-90`

The infinite query assumes a specific Laravel pagination structure:

```typescript
getNextPageParam: (lastPage) => {
    const meta = (lastPage as { meta?: { current_page: number; last_page: number } }).meta;
    return meta && meta.current_page < meta.last_page ? meta.current_page + 1 : undefined;
},
```

**Impact:** Won't work with cursor-based pagination or non-standard pagination responses.

**Recommendation:** Make pagination extraction configurable or use the already-defined `PaginatedResponse<T>` type:
```typescript
import type { PaginatedResponse } from './types';

getNextPageParam: (lastPage: PaginatedResponse<unknown>) => {
    return lastPage.meta.current_page < lastPage.meta.last_page
        ? lastPage.meta.current_page + 1
        : undefined;
},
```

---

## Code Quality Analysis

### Type Safety Score: 7/10

| Aspect | Score | Notes |
|--------|-------|-------|
| Generic constraints | 9/10 | Good use of `RouteName` constraints |
| Return types | 6/10 | Some `unknown` types that should be narrower |
| Utility types | 8/10 | Good use of `ResponseOf<T>`, `ParamsOf<T>`, etc. |
| Type inference | 6/10 | Missing `queryOptions`/`infiniteQueryOptions` helpers |

### API Design Score: 8/10

| Aspect | Score | Notes |
|--------|-------|-------|
| Consistency | 7/10 | Two different key structures |
| Discoverability | 8/10 | Good JSDoc examples |
| Flexibility | 9/10 | Options spread pattern allows customization |
| Simplicity | 8/10 | Clean function signatures |

### TanStack Query v5 Compliance: 7/10

| Feature | Status |
|---------|--------|
| `queryOptions` helper | ❌ Not used in manual helpers |
| `infiniteQueryOptions` helper | ✅ Used in auto-generated |
| `gcTime` (not `cacheTime`) | ❌ Uses deprecated name |
| Suspense support | ❌ No dedicated factories |
| `maxPages` | ❌ Not exposed |
| Proper TypeScript | ⚠️ Some `unknown` types |

---

## Comparison with Query Key Factory Libraries

### vs [@lukemorales/query-key-factory](https://github.com/lukemorales/query-key-factory)

| Feature | This Package | query-key-factory |
|---------|--------------|-------------------|
| Type-safe keys | ✅ | ✅ |
| Auto-generated from routes | ✅ | ❌ |
| Hierarchical invalidation | ✅ | ✅ |
| IDE autocomplete | ✅ | ✅ |
| `queryOptions` integration | ⚠️ Partial | ✅ |
| Custom scope support | ❌ | ✅ |

The package provides similar functionality with the added benefit of automatic generation from Laravel routes.

---

## Test Recommendations

Missing test coverage for React Query integration:

| Test Case | Priority |
|-----------|----------|
| `queryKey` produces deterministic keys | High |
| `createQueryOptions` returns valid options object | High |
| `createInfiniteQueryOptions` handles pagination | High |
| Keys include all params for cache busting | Medium |
| `baseQueryKey` enables partial invalidation | Medium |
| `createMutationOptions` types variables correctly | Medium |
| Generated queries match manual helpers | Low |

---

## Recommendations Summary

| Priority | Recommendation |
|----------|----------------|
| **High** | Replace `cacheTime` with `gcTime` for v5 compatibility |
| **High** | Use `queryOptions`/`infiniteQueryOptions` helpers from TanStack |
| **High** | Fix `unknown` types in infinite query options |
| **Medium** | Add lifecycle hooks to mutation options factory |
| **Medium** | Standardize query key structure across both approaches |
| **Medium** | Add suspense query options factory |
| **Low** | Add `maxPages` support for infinite queries |
| **Low** | Make pagination extraction configurable |

---

## Conclusion

The React Query integration provides a solid foundation for type-safe data fetching with TanStack Query v5. The query key factory pattern, resource-based organization, and automatic code generation are well-implemented.

**Key improvements needed:**

1. Update to v5 terminology (`gcTime` vs `cacheTime`)
2. Use official `queryOptions`/`infiniteQueryOptions` helpers for better type inference
3. Fix type safety issues in infinite query options
4. Add mutation lifecycle hooks to the factory pattern

The architecture is sound and these improvements can be made without breaking changes.

---

## References

- [TanStack Query v5 Overview](https://tanstack.com/query/v5/docs/framework/react/overview)
- [Query Options API](https://tanstack.com/query/v5/docs/react/guides/query-options)
- [infiniteQueryOptions Reference](https://tanstack.com/query/latest/docs/framework/react/reference/infiniteQueryOptions)
- [Effective React Query Keys - TkDodo](https://tkdodo.eu/blog/effective-react-query-keys)
- [The Query Options API - TkDodo](https://tkdodo.eu/blog/the-query-options-api)
- [Query Key Factory Pattern](https://github.com/lukemorales/query-key-factory)
- [TanStack Query v5 Migration Guide](https://tanstack.com/query/latest/docs/framework/react/guides/migrating-to-v5)
- [TanStack Query TypeScript Guide](https://tanstack.com/query/v5/docs/framework/react/typescript)
