@php
    $timestamp = now()->toIso8601String();
    $hasReactQuery = $config->shouldGenerateOutput('react-query') || $config->shouldGenerateOutput('queries');
    $hasInertia = $config->shouldGenerateOutput('inertia');
    $hasGroupedApi = $config->shouldGenerateOutput('grouped-api');
    $hasQueries = $config->shouldGenerateOutput('queries');
@endphp
# Generated API Client

> Auto-generated on {{ $timestamp }}
> **Do not edit manually** - Regenerate with: `php artisan trpc:generate`

## Generated Files

| File | Description |
|------|-------------|
| `types.ts` | Core types (HttpMethod, ApiError, PaginatedResponse, ValidationError) |
| `routes.ts` | Route definitions, RouteTypeMap, method-filtered route types |
| `helpers.ts` | Type helpers (RequestOf, ResponseOf, ParamsOf, QueryOf) |
| `url-builder.ts` | Type-safe URL builder with query string support |
| `fetch.ts` | Low-level fetch wrapper with full type safety |
| `client.ts` | Configurable API client factory with method-specific calls |
@if($hasGroupedApi)
| `api.ts` | Grouped API client (`api.users.show()`) |
@endif
@if($hasReactQuery)
| `react-query.ts` | React Query utilities (queryKey, createQueryOptions) |
@endif
@if($hasQueries)
| `queries.ts` | React Query hooks organized by resource |
@endif
@if($hasInertia)
| `inertia.ts` | Inertia.js helpers (route, visit, formAction) |
@endif
| `index.ts` | Barrel exports for easy importing |

## Quick Start
@if($hasGroupedApi)

### Grouped API (Recommended)

```typescript
import { api } from './api';

// GET requests
const users = await api.users.index();
const user = await api.users.show({ user: 1 });

// POST requests
const newUser = await api.users.store({
    name: 'John',
    email: 'john@example.com'
});

// PUT/PATCH requests
await api.users.update({ user: 1 }, { name: 'Jane' });

// DELETE requests
await api.users.destroy({ user: 1 });

// With query parameters
const filtered = await api.users.index({ query: { page: 2, per_page: 20 } });
```
@endif

### Method-Specific Client

```typescript
import { createApiClient } from './client';

const client = createApiClient({
    baseUrl: '/api',
    headers: { Authorization: `Bearer ${token}` },
});

// Only GET routes appear in autocomplete
const users = await client.get('users.index');
const user = await client.get('users.show', { path: { user: 1 } });

// Only POST routes appear in autocomplete
const newUser = await client.post('users.store', {
    body: { name: 'John', email: 'john@example.com' }
});

// Only PUT routes appear in autocomplete
await client.put('users.update', {
    path: { user: 1 },
    body: { name: 'Jane' }
});

// Only DELETE routes appear in autocomplete
await client.delete('users.destroy', { path: { user: 1 } });
```

### Low-Level Fetch

```typescript
import { fetchApi } from './fetch';

const user = await fetchApi('users.show', {
    path: { user: 1 },
    config: {
        headers: { 'X-Custom-Header': 'value' },
    },
});
```

## Type Helpers

Extract types from route names:

```typescript
import type {
    RequestOf,
    ResponseOf,
    ParamsOf,
    QueryOf,
    ErrorOf
} from './helpers';

// Request body type for a route
type CreateUserPayload = RequestOf<'users.store'>;

// Response type for a route
type UserResponse = ResponseOf<'users.show'>;

// URL parameters type (e.g., { user: number })
type UserParams = ParamsOf<'users.show'>;

// Query string parameters type
type UserQuery = QueryOf<'users.index'>;

// Error response type
type UserError = ErrorOf<'users.store'>;
```

### Available Type Helpers

| Helper | Description |
|--------|-------------|
| `RequestOf<T>` | Request body type for route T |
| `ResponseOf<T>` | Response type for route T |
| `ParamsOf<T>` | URL path parameters type |
| `QueryOf<T>` | Query string parameters type |
| `ErrorOf<T>` | Error response type |
| `RequiresPathParams<T>` | Boolean - does route require path params? |

### Route Filter Types

```typescript
import type {
    GetRoutes,
    PostRoutes,
    PutRoutes,
    PatchRoutes,
    DeleteRoutes
} from './routes';

// Only GET route names
type ReadRoutes = GetRoutes; // 'users.index' | 'users.show' | ...

// Only mutation route names
type MutationRoutes = PostRoutes | PutRoutes | DeleteRoutes;
```

## URL Building

```typescript
import { url, getRoute, requiresPath, getMethod } from './url-builder';

// Build URLs with type safety
const userUrl = url('users.show', { user: 123 });
// Result: 'api/users/123'

// With query parameters
const listUrl = url('users.index', null, { query: { page: 2 } });
// Result: 'api/users?page=2'

// Get route metadata
const route = getRoute('users.show');
// { path: 'api/users/{user}', method: 'get', params: ['user'] }

// Check if route requires path params
if (requiresPath('users.show')) {
    // Route needs { user: number }
}

// Get HTTP method
const method = getMethod('users.store'); // 'post'
```
@if($hasReactQuery)

## React Query Integration

### Basic Usage

```typescript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { createQueryOptions, createMutationOptions, queryKey } from './react-query';
import { api } from './api';

function UserProfile({ userId }: { userId: number }) {
    // Query with type-safe options
    const { data, isLoading } = useQuery(
        createQueryOptions('users.show', { path: { user: userId } })
    );

    const queryClient = useQueryClient();

    // Mutation with cache invalidation
    const updateUser = useMutation({
        ...createMutationOptions('users.update'),
        onSuccess: () => {
            queryClient.invalidateQueries({
                queryKey: queryKey('users.show', { user: userId })
            });
        },
    });

    if (isLoading) return <div>Loading...</div>;

    return (
        <div>
            <h1>{data?.name}</h1>
            <button onClick={() => updateUser.mutate({
                path: { user: userId },
                body: { name: 'New Name' }
            })}>
                Update
            </button>
        </div>
    );
}
```
@endif
@if($hasQueries)

### Resource-Based Queries

```typescript
import { useQuery, useMutation, useInfiniteQuery } from '@tanstack/react-query';
import { usersQueries } from './queries';

// Pre-configured query options by resource
const { data } = useQuery(usersQueries.index());
const { data: user } = useQuery(usersQueries.show({ user: 1 }));

// Query keys for cache management
usersQueries.keys.all           // ['users']
usersQueries.keys.index({})     // ['users', 'index', {}]
usersQueries.keys.show({ user: 1 }) // ['users', 'show', { user: 1 }]

// Infinite queries for pagination
const {
    data,
    fetchNextPage,
    hasNextPage,
} = useInfiniteQuery(usersQueries.index({ query: { per_page: 20 } }));

// Invalidation patterns
queryClient.invalidateQueries({ queryKey: usersQueries.keys.all });
```
@endif
@if($hasInertia)

## Inertia.js Integration

```typescript
import { router } from '@inertiajs/react';
import { route, visit, formAction } from './inertia';

// Generate type-safe URLs
const userUrl = route('users.show', { user: 123 });

// Navigate with Inertia
visit('users.index');
visit('users.show', { user: 123 });
visit('users.index', null, { query: { page: 2 } });

// With Inertia options
visit('users.show', { user: 123 }, {
    preserveState: true,
    preserveScroll: true,
});

// Form helper
function CreateUserForm() {
    const { action, method } = formAction('users.store');

    return (
        <form action={action} method={method}>
            <input name="name" />
            <input name="email" type="email" />
            <button type="submit">Create</button>
        </form>
    );
}

// Or spread directly
<form {...formAction('users.store')}>
```
@endif

## Error Handling

```typescript
import type { ValidationError, ApiError } from './types';

try {
    await api.users.store({ name: '', email: 'invalid' });
} catch (error) {
    if (isValidationError(error)) {
        // error.errors: { name?: string[], email?: string[] }
        console.log(error.errors.email?.[0]);
    }
}

function isValidationError(error: unknown): error is ValidationError {
    return (
        typeof error === 'object' &&
        error !== null &&
        'errors' in error
    );
}
```

## Configuration

### Default Usage

```typescript
import { api } from './api';

// Uses relative URLs, works with Laravel session/cookie auth
const users = await api.users.index();
```

### Custom Base URL

```typescript
import { createApi } from './api';

const api = createApi({
    baseUrl: import.meta.env.VITE_API_URL,  // or process.env.NEXT_PUBLIC_API_URL
    headers: {
        Authorization: `Bearer ${token}`,
    },
    onError: (error) => {
        if (error.status === 401) {
            window.location.href = '/login';
        }
    },
});

const users = await api.users.index();
```

## Regenerate

```bash
php artisan trpc:generate
```
