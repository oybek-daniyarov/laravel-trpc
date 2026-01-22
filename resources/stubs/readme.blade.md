@php
    $timestamp = now()->toIso8601String();
    $hasReactQuery = $config->shouldGenerateOutput('react-query') || $config->shouldGenerateOutput('queries') || $config->shouldGenerateOutput('mutations');
    $hasInertia = $config->shouldGenerateOutput('inertia');
    $hasGroupedApi = $config->shouldGenerateOutput('grouped-api');
    $hasQueries = $config->shouldGenerateOutput('queries');
    $hasMutations = $config->shouldGenerateOutput('mutations');
@endphp
# Generated API Client

> Auto-generated on {{ $timestamp }}
> **Do not edit manually** - Regenerate with: `php artisan trpc:generate`

## Generated Files

```
./
├── core/                 # Core infrastructure
│   ├── types.ts          # HttpMethod, ApiError, PaginatedResponse, ValidationError
│   ├── fetch.ts          # Low-level fetch wrapper with full type safety
│   ├── helpers.ts        # Type helpers (RequestOf, ResponseOf, ParamsOf, QueryOf)
│   └── index.ts          # Core barrel exports
├── {group}/              # Per-resource folders (users/, posts/, etc.)
│   ├── routes.ts         # Group-specific route definitions
│   ├── api.ts            # create{Group}Api() factory
@if($hasQueries)
│   ├── queries.ts        # create{Group}Queries() factory
@endif
@if($hasMutations)
│   ├── mutations.ts      # create{Group}Mutations() factory
@endif
│   └── index.ts          # Group barrel exports
├── routes.ts             # Aggregated route definitions
@if($hasGroupedApi)
├── api.ts                # createApi() factory combining all groups
@endif
@if($hasQueries)
├── queries.ts            # createQueries() factory
@endif
@if($hasMutations)
├── mutations.ts          # createMutations() factory
@endif
├── url-builder.ts        # Type-safe URL builder
├── client.ts             # Method-specific client (client.get(), etc.)
@if($hasReactQuery)
├── react-query.ts        # React Query utilities (queryKey, createQueryOptions)
@endif
@if($hasInertia)
├── inertia.ts            # Inertia.js helpers (route, visit, formAction)
@endif
└── index.ts              # Main barrel exports
```

### Tree-Shaking

Import only what you need for optimal bundle size:

```typescript
// Per-resource import (tree-shakeable) - only imports users code
import { createUsersApi } from './users';

// Or combined API - imports all resources
import { createApi } from './';
```

## Quick Start
@if($hasGroupedApi)

### Grouped API (Recommended)

```typescript
import { createApi } from './';

// Configure once
const api = createApi({
    baseUrl: process.env.NEXT_PUBLIC_API_URL ?? '',
    headers: { 'X-App-Version': '1.0.0' },
});

// GET requests
const users = await api.users.index();
const user = await api.users.show({ user: 1 });

// POST requests
const newUser = await api.users.store({ body: { name: 'John', email: 'john@example.com' } });

// PUT/PATCH requests
await api.users.update({ user: 1 }, { body: { name: 'Jane' } });

// DELETE requests
await api.users.destroy({ user: 1 });

// With query parameters
const filtered = await api.users.index({ query: { page: 2, per_page: 20 } });

// With per-request options (headers, Next.js cache, etc.)
const cached = await api.users.index({ next: { tags: ['users'], revalidate: 60 } });
const user = await api.users.show({ user: 1 }, { headers: { 'X-Custom': 'value' } });
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
import { useQuery } from '@tanstack/react-query';
import { createQueryOptions, queryKey } from './react-query';

function UserProfile({ userId }: { userId: number }) {
    // Query with type-safe options
    const { data, isLoading } = useQuery(
        createQueryOptions('users.show', { path: { user: userId } })
    );

    if (isLoading) return <div>Loading...</div>;

    return (
        <div>
            <h1>{data?.name}</h1>
        </div>
    );
}

// Query keys for cache management
const key = queryKey('users.show', { path: { user: 1 } });
// ['users.show', { path: { user: 1 } }]
```
@endif
@if($hasQueries)

### Resource-Based Queries

#### With Configured API (Recommended)

```typescript
import { useQuery, useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { createApi, createQueries } from './';

// Configure API and queries once
const api = createApi({
    baseUrl: process.env.NEXT_PUBLIC_API_URL ?? '',
});
const queries = createQueries(api);

// Use in components
const { data } = useQuery(queries.users.index());
const { data: user } = useQuery(queries.users.show({ user: 1 }));

// Query keys for cache management
queries.users.keys.all              // ['users']
queries.users.keys.show({ user: 1 }) // ['users.show', { user: 1 }]

// Infinite queries for pagination
const { data, fetchNextPage, hasNextPage } = useInfiniteQuery(
    queries.users.index({ query: { per_page: 20 } })
);

// Invalidation patterns
const queryClient = useQueryClient();
queryClient.invalidateQueries({ queryKey: queries.users.keys.all });
```

@endif
@if($hasMutations)

### Resource-Based Mutations

```typescript
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createApi, createMutations, createQueries } from './';

// Configure API and mutations once
const api = createApi({
    baseUrl: process.env.NEXT_PUBLIC_API_URL ?? '',
});
const mutations = createMutations(api);
const queries = createQueries(api);

// Use in components
function CreateUserButton() {
    const queryClient = useQueryClient();

    const createUser = useMutation({
        ...mutations.users.store(),
        onSuccess: () => {
            // Invalidate users list after creating
            queryClient.invalidateQueries({ queryKey: queries.users.keys.all });
        },
    });

    // TypeScript enforces body is REQUIRED for store route
    return (
        <button
            onClick={() => createUser.mutate({
                body: { name: 'John', email: 'john@example.com' }
            })}
            disabled={createUser.isPending}
        >
            {createUser.isPending ? 'Creating...' : 'Create User'}
        </button>
    );
}

function DeleteUserButton({ userId }: { userId: number }) {
    const queryClient = useQueryClient();

    const deleteUser = useMutation({
        ...mutations.users.destroy(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queries.users.keys.all });
        },
    });

    // TypeScript allows NO body for destroy route (only path params)
    return (
        <button
            onClick={() => deleteUser.mutate({ user: userId })}
            disabled={deleteUser.isPending}
        >
            Delete
        </button>
    );
}

// Mutation keys for cache management
mutations.users.keys.all                // ['users', 'mutation']
mutations.users.keys.store()            // ['users.store']
mutations.users.keys.update()           // ['users.update']
mutations.users.keys.destroy()          // ['users.destroy']
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

### Basic Setup

```typescript
// lib/api.ts
import { createApi } from './';

export const api = createApi({
    baseUrl: process.env.NEXT_PUBLIC_API_URL ?? '',
    headers: {
        Authorization: `Bearer ${token}`,
    },
    onError: (error) => {
        if (error.status === 401) {
            window.location.href = '/login';
        }
    },
});
```

### Per-Request Options

All API methods accept optional per-request options as the last parameter:

```typescript
// Custom headers for this request
const users = await api.users.index({ headers: { 'X-Custom': 'value' } });

// Next.js cache configuration
const users = await api.users.index({ next: { tags: ['users'], revalidate: 60 } });

// With path params and options
const user = await api.users.show({ user: 1 }, { next: { tags: ['user-1'] } });
```

### Next.js Server Components

```typescript
// lib/api.server.ts
import { createApi } from '@/api';
import { cookies } from 'next/headers';
import { cache } from 'react';

export const getServerApi = cache(async () => {
    const cookieStore = await cookies();
    const token = cookieStore.get('token')?.value;

    return createApi({
        baseUrl: process.env.API_URL!,
        headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
});
```

## Regenerate

```bash
php artisan trpc:generate
```
