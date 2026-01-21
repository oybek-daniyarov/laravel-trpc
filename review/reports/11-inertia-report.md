# Inertia.js Integration Review Report

**Package**: Laravel tRPC (laravel-typed-api)
**Reviewer**: Inertia.js Reviewer
**Date**: 2026-01-21
**File Reviewed**: `resources/stubs/inertia.blade.ts`

---

## Executive Summary

The Inertia.js integration provides a type-safe layer for building URLs and creating form actions within Inertia.js applications. The implementation offers three main helpers (`route`, `visit`, `formAction`) that leverage the package's existing URL building infrastructure. While the foundation is solid, there are several issues and missing features that should be addressed to make this a complete Inertia.js integration.

**Overall Rating**: 6/10 - Functional but incomplete

---

## 1. Route Helper Review

### Current Implementation

```typescript
export function route<T extends RouteName>(
    name: T,
    path?: ParamsOf<T> | null,
    query?: QueryParams<T>,
): string {
    return url(name, path, { query: query as UrlOptions['query'] });
}
```

### Assessment

| Criteria | Status | Notes |
|----------|--------|-------|
| Generates correct URLs | PASS | Delegates to `url()` function correctly |
| Path parameter substitution | PASS | Handles `{param}` replacement properly |
| Query string building | PASS | Properly handles arrays, null, undefined |
| Type safety | PASS | Route name autocomplete works |
| Ziggy compatibility | PARTIAL | API is similar but not identical |

### Issues Found

1. **Missing absolute URL support**: The `route()` function only returns relative paths (e.g., `api/users/123`). Inertia.js and Ziggy typically return absolute URLs with the base URL included.

2. **No base URL configuration**: Unlike Ziggy's `route()`, there's no way to configure a base URL or domain.

3. **Parameter naming inconsistency**: The second parameter is named `path` but it represents route parameters, which could be confusing since Ziggy uses `params`.

### Recommendation

```typescript
// Suggested improvement
export function route<T extends RouteName>(
    name: T,
    params?: ParamsOf<T> | null,
    query?: QueryParams<T>,
    options?: { absolute?: boolean }
): string {
    const baseUrl = options?.absolute ? window.location.origin : '';
    const routeUrl = url(name, params, { query: query as UrlOptions['query'] });
    return baseUrl ? `${baseUrl}/${routeUrl}` : routeUrl;
}
```

---

## 2. Visit Helper Review

### Current Implementation

```typescript
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
```

### Assessment

| Criteria | Status | Notes |
|----------|--------|-------|
| URL generation | PASS | Correctly builds URLs |
| Type-safe data | PASS | `RequestOf<T>` types the data properly |
| Returns correct structure | PASS | Returns `{ url, options }` |
| Inertia.js compatibility | PARTIAL | Requires manual `router.visit()` call |

### Issues Found

1. **Does not directly call router**: The helper returns an object that must be destructured and passed to `router.visit()`. This is cumbersome compared to what users might expect.

2. **Missing `preserveState` default**: For many form submissions, `preserveState: true` is commonly desired.

3. **No support for Link component props**: The helper doesn't generate props suitable for the Inertia `<Link>` component.

### Recommendation

Consider adding a direct integration:

```typescript
// For programmatic navigation
export function visit<T extends RouteName>(
    name: T,
    params: ParamsOf<T> | null,
    options: InertiaVisitOptions & {
        data?: RequestOf<T>;
        query?: QueryParams<T>;
    } = {},
): void {
    const { query, ...visitOptions } = options;
    const visitUrl = url(name, params, { query: query as UrlOptions['query'] });

    // Import router at runtime to avoid bundling Inertia
    import('@inertiajs/react').then(({ router }) => {
        router.visit(visitUrl, visitOptions);
    });
}

// For getting visit options (current behavior, renamed)
export function visitOptions<T extends RouteName>(
    name: T,
    params: ParamsOf<T> | null,
    options: InertiaVisitOptions & {
        data?: RequestOf<T>;
        query?: QueryParams<T>;
    } = {},
): { url: string; options: InertiaVisitOptions } {
    // ... current implementation
}
```

---

## 3. Form Action Helper Review

### Current Implementation

```typescript
export function formAction<T extends RouteName>(
    name: T,
    path?: ParamsOf<T> | null,
): { action: string; method: string } {
    const formUrl = url(name, path);
    return {
        action: formUrl,
        method: name.includes('.') ? 'post' : 'get',
    };
}
```

### Assessment

| Criteria | Status | Notes |
|----------|--------|-------|
| Returns action URL | PASS | Correctly generates URL |
| Returns method | FAIL | Logic is incorrect |
| Type safety | PASS | Route name is typed |
| useForm integration | PARTIAL | Missing type-safe defaults |

### Critical Issue: Incorrect Method Detection

**The method detection logic is fundamentally broken.**

```typescript
method: name.includes('.') ? 'post' : 'get'
```

This logic assumes any route with a dot (`.`) is a POST, which is incorrect:
- `users.index` (GET) would return `'post'`
- `users.show` (GET) would return `'post'`
- `users.destroy` (DELETE) would return `'post'`

### Recommended Fix

```typescript
export function formAction<T extends RouteName>(
    name: T,
    params?: ParamsOf<T> | null,
): { action: string; method: string } {
    const formUrl = url(name, params);
    // Use the actual route method from the routes definition
    const routeMethod = routes[name].method;

    return {
        action: formUrl,
        method: routeMethod,
    };
}
```

---

## 4. Method Spoofing for PUT/PATCH/DELETE

### Assessment

| Criteria | Status | Notes |
|----------|--------|-------|
| Support for PUT | MISSING | Not implemented |
| Support for PATCH | MISSING | Not implemented |
| Support for DELETE | MISSING | Not implemented |
| Hidden _method field | MISSING | Not generated |

### Issue

HTML forms only support GET and POST methods. Laravel and Inertia.js use method spoofing via a `_method` hidden field to simulate PUT, PATCH, and DELETE requests. The current implementation does not address this.

### Recommendation

Add method spoofing support:

```typescript
export interface FormActionResult {
    action: string;
    method: 'get' | 'post';  // Only HTML-valid methods
    httpMethod: string;       // The actual HTTP method
    spoofMethod?: string;     // The _method value if needed
}

export function formAction<T extends RouteName>(
    name: T,
    params?: ParamsOf<T> | null,
): FormActionResult {
    const formUrl = url(name, params);
    const httpMethod = routes[name].method;

    // HTML forms only support GET and POST
    const needsSpoofing = ['put', 'patch', 'delete'].includes(httpMethod);

    return {
        action: formUrl,
        method: needsSpoofing ? 'post' : (httpMethod as 'get' | 'post'),
        httpMethod,
        ...(needsSpoofing && { spoofMethod: httpMethod.toUpperCase() }),
    };
}

// Usage with Inertia's Form component:
// const { action, method, spoofMethod } = formAction('users.update', { user: 1 });
// <Form action={action} method={method}>
//   {spoofMethod && <input type="hidden" name="_method" value={spoofMethod} />}
//   ...
// </Form>
```

---

## 5. Type Safety and Route Names Autocomplete

### Assessment

| Criteria | Status | Notes |
|----------|--------|-------|
| Route name autocomplete | PASS | Works via `RouteName` type |
| Path params inference | PASS | `ParamsOf<T>` correctly infers |
| Request body typing | PASS | `RequestOf<T>` works correctly |
| Query params typing | PASS | `QueryParams<T>` is properly typed |
| Return types | PASS | All functions have explicit return types |

### Strengths

The type safety implementation is solid:

```typescript
// Route names autocomplete correctly
route('users.show', { user: 123 });
//     ^-- autocomplete shows all available routes

// Path params are enforced
route('users.show', { user: 123 });  // OK
route('users.show', { id: 123 });    // Error: 'id' not in ParamsOf<'users.show'>
route('users.index', null);           // OK: no params required

// Request body is typed
visit('users.store', null, {
    data: { name: 'John', email: 'john@example.com' }  // Type-checked against RequestOf
});
```

---

## 6. InertiaVisitOptions Type Review

### Current Implementation

```typescript
export interface InertiaVisitOptions {
    readonly method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    readonly data?: Record<string, unknown>;
    readonly replace?: boolean;
    readonly preserveState?: boolean;
    readonly preserveScroll?: boolean;
    readonly only?: readonly string[];
    readonly except?: readonly string[];
    readonly headers?: Readonly<Record<string, string>>;
    readonly errorBag?: string;
    readonly forceFormData?: boolean;
    readonly onCancelToken?: (cancelToken: { cancel: () => void }) => void;
    readonly onCancel?: () => void;
    readonly onBefore?: () => boolean | void;
    readonly onStart?: () => void;
    readonly onProgress?: (progress: { percentage: number }) => void;
    readonly onSuccess?: (page: unknown) => void;
    readonly onError?: (errors: Record<string, string>) => void;
    readonly onFinish?: () => void;
}
```

### Assessment

| Property | Status | Notes |
|----------|--------|-------|
| method | PARTIAL | Missing 'head' method |
| data | PASS | Correctly typed |
| replace | PASS | Correct |
| preserveState | PASS | Correct |
| preserveScroll | PASS | Correct |
| only/except | PASS | Correct |
| headers | PASS | Correct |
| errorBag | PASS | Correct |
| forceFormData | PASS | Correct |
| Event callbacks | PARTIAL | Missing some 2026 Inertia v2 callbacks |

### Missing Properties (Inertia.js v2.x)

Based on current Inertia.js documentation:

```typescript
// Missing properties that should be added:
export interface InertiaVisitOptions {
    // ... existing properties ...

    // Missing in current implementation:
    readonly async?: boolean;           // Async visits (v2)
    readonly prefetch?: boolean;        // Link prefetching (v2)
    readonly showProgress?: boolean;    // Progress bar control
    readonly resetOnSuccess?: boolean;  // Form reset behavior
    readonly onPrefetch?: () => void;   // Prefetch callback (v2)
    readonly onPrefetching?: () => void; // Prefetching state callback (v2)
}
```

---

## 7. Missing Features

### 7.1 Link Component Helper

No helper for generating props for Inertia's `<Link>` component:

```typescript
// Suggested addition
export function linkProps<T extends RouteName>(
    name: T,
    params?: ParamsOf<T> | null,
    query?: QueryParams<T>,
): {
    href: string;
    method?: string;
    data?: Record<string, unknown>;
} {
    return {
        href: route(name, params, query),
        method: routes[name].method,
    };
}
```

### 7.2 useForm Type-Safe Wrapper

No wrapper for Inertia's `useForm` hook:

```typescript
// Suggested addition
export function useTypedForm<T extends RouteName>(
    name: T,
    initialData: RequestOf<T>,
) {
    // This would require Inertia as a peer dependency
    // Return type would be InertiaFormReturn with typed data
}
```

### 7.3 Page Props Type Helpers

No helpers for typing Inertia page props:

```typescript
// Suggested addition
export type PagePropsOf<T extends RouteName> = {
    response: ResponseOf<T>;
    errors: Partial<Record<keyof RequestOf<T>, string>>;
};
```

### 7.4 Prefetching Support

No support for Inertia.js v2 prefetching:

```typescript
// Suggested addition
export function prefetch<T extends RouteName>(
    name: T,
    params?: ParamsOf<T> | null,
    query?: QueryParams<T>,
): void {
    // Implementation would preload the route
}
```

---

## 8. Code Quality

### Strengths

1. **Clean, readable code**: Functions are well-documented with JSDoc and examples
2. **Proper TypeScript usage**: Generics are used correctly for type inference
3. **Consistent API**: Functions follow a similar pattern
4. **Good examples**: Documentation shows practical usage

### Weaknesses

1. **No runtime validation**: Parameters are not validated at runtime
2. **Tight coupling to URL builder**: The `url()` function dependency could make testing harder
3. **No error handling**: What happens if an invalid route name is passed?

---

## 9. Comparison with Ziggy

| Feature | Ziggy | Laravel tRPC Inertia |
|---------|-------|---------------------|
| Route name autocomplete | Via TypeScript plugin | Native TypeScript |
| Absolute URLs | Yes | No |
| Route URL generation | Yes | Yes |
| Query parameters | Yes | Yes |
| Method detection | From config | From routes |
| useForm integration | No | Partial |
| Link component | No | No |
| Bundle size | ~10KB | ~2KB (estimated) |

---

## 10. Recommendations Summary

### Critical (Must Fix)

1. **Fix `formAction` method detection** - The current logic is completely broken
2. **Add method spoofing support** - Essential for PUT/PATCH/DELETE forms

### High Priority

3. **Add absolute URL support** - Many Inertia apps need full URLs
4. **Import actual route method** in `formAction` from routes definition
5. **Update InertiaVisitOptions** for Inertia.js v2 compatibility

### Medium Priority

6. **Add `linkProps` helper** for Link component integration
7. **Consider direct router integration** option
8. **Add prefetching support** for Inertia.js v2

### Low Priority

9. **Add useTypedForm wrapper** (requires Inertia as peer dependency)
10. **Add page props type helpers**
11. **Add runtime validation** for development mode

---

## 11. Security Considerations

1. **URL injection**: The URL builder should sanitize path parameters to prevent injection attacks
2. **CSRF**: The implementation assumes Laravel's CSRF protection is handled separately (correct)
3. **XSS**: No HTML is generated, so XSS risk is minimal

---

## 12. Conclusion

The Inertia.js integration provides a good foundation for type-safe routing in Inertia applications. The type system is well-implemented, with proper generics for route names, parameters, and request bodies.

However, the critical bug in `formAction`'s method detection must be fixed before this can be considered production-ready. Additionally, the lack of method spoofing support significantly limits the usefulness for typical CRUD applications.

With the recommended fixes and enhancements, this integration could become a valuable alternative to Ziggy for TypeScript-heavy Inertia.js applications.

---

## Sources

- [Inertia.js TypeScript Documentation](https://inertiajs.com/docs/v2/advanced/typescript)
- [Inertia.js Forms Documentation](https://inertiajs.com/forms)
- [Type-Safe Shared Data and Page Props in Inertia.js - Laravel News](https://laravel-news.com/type-safe-shared-data-and-page-props-in-inertiajs)
- [Inertia.js Forms (v2)](https://inertiajs.com/docs/v2/the-basics/forms)
