# React Query Reviewer

## Role Overview
Review the React Query integration for correctness, best practices, and developer experience.

## Why This Matters
React Query is a popular choice for data fetching. Poor integration undermines the package's utility for React developers.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| React Query (TanStack Query) | Expert | Query keys, options, caching strategies |
| Infinite Queries | Advanced | Pagination, cursor-based fetching |
| Mutations | Advanced | Optimistic updates, invalidation |
| Query Key Management | Advanced | Key factories, hierarchical keys |
| React Hooks | Advanced | Custom hooks, hook composition |
| TypeScript Generics | Advanced | Typing hooks and options |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **React Query DevTools** | Inspect queries, cache, mutations |
| **VS Code + React** | React component testing |
| **Read Tool** | Read React Query integration files |
| **Grep Tool** | Search for query patterns |
| **npm/pnpm** | Install React Query for testing |
| **Vitest / Jest** | Test query hooks |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find query key definitions
Grep: "queryKey|QueryKey" --glob="*.blade.ts"

# Find query options factories
Grep: "queryOptions|createQueryOptions|QueryOptions" --glob="*.blade.ts"

# Find infinite query handling
Grep: "infiniteQuery|getNextPageParam|getPreviousPageParam|pageParam" --glob="*.blade.ts"

# Find mutation options
Grep: "mutationOptions|createMutationOptions|useMutation" --glob="*.blade.ts"

# Find stale time configuration
Grep: "staleTime|cacheTime|gcTime" --glob="*.blade.ts"

# Find query invalidation patterns
Grep: "invalidateQueries|queryClient" --glob="*.blade.ts"

# Check keys factory pattern
Grep: "keys\s*=|\.keys\." --glob="*.blade.ts"

# Read React Query integration
Read: resources/stubs/react-query.blade.ts

# Read resource queries
Read: resources/stubs/queries.blade.ts
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "TanStack Query v5 query key factory pattern 2026"
WebSearch: "React Query infinite query pagination"
WebSearch: "React Query TypeScript best practices"
WebSearch: "React Query mutation patterns optimistic updates"
WebSearch: "React Query SSR Next.js App Router"
WebSearch: "TanStack Query queryOptions function"
WebSearch: "React Query cache invalidation patterns"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://tanstack.com/query/latest/docs/framework/react/overview"
Prompt: "Extract key information about React Query setup and basic usage"

WebFetch: "https://tanstack.com/query/latest/docs/framework/react/guides/query-keys"
Prompt: "Extract information about query key structure and best practices"

WebFetch: "https://tanstack.com/query/latest/docs/framework/react/guides/infinite-queries"
Prompt: "Extract information about infinite query implementation"

WebFetch: "https://tanstack.com/query/latest/docs/framework/react/typescript"
Prompt: "Extract information about TypeScript integration"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com TanStack Query query key factory"
WebSearch: "site:github.com React Query typed API client"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Testing Setup
```bash
# Install React Query for testing
npm install @tanstack/react-query

# Generate output
php artisan trpc:generate

# Type check React Query integration
npx tsc --noEmit resources/js/api/react-query.ts resources/js/api/queries.ts
```

### React Query Version Compatibility
```bash
# Check for v4 vs v5 differences
Grep: "cacheTime|gcTime" --glob="*.ts"  # gcTime is v5
Grep: "useQuery\(\{|useQuery\(options" --glob="*.ts"  # Object syntax
```

---

## Prerequisites
- Strong React Query knowledge (v4/v5)
- Understanding of query keys, stale time, caching
- Experience with infinite queries and mutations

## Files to Review
- `resources/stubs/react-query.blade.ts`
- `resources/stubs/queries.blade.ts`

## Review Checklist

### 1. Query Key Structure
- [ ] Keys are deterministic and predictable
- [ ] Keys include route name
- [ ] Keys include path parameters
- [ ] Keys include query parameters
- [ ] Keys are serializable (no functions/objects with methods)

```typescript
// Good key structure
['users.show', { user: 1 }, undefined]
['users.index', undefined, { page: 1, per_page: 10 }]
```

### 2. Query Options Factory
```typescript
// Should return valid QueryOptions
const options = createQueryOptions('users.show', {
  path: { user: 1 },
  staleTime: 5000,
});

// Type should be inferred
const { data } = useQuery(options); // data: UserData | undefined
```

### 3. Infinite Query Support
For paginated endpoints:
- [ ] `getNextPageParam` extracts next page correctly
- [ ] `getPreviousPageParam` works (if supported)
- [ ] `initialPageParam` is set
- [ ] Page data is flattened correctly

```typescript
const { data, fetchNextPage, hasNextPage } = useInfiniteQuery(
  usersQueries.index({ query: { per_page: 20 } })
);

// data.pages should be PaginatedResponse<UserData>[]
```

### 4. Mutation Options
```typescript
const mutation = useMutation(createMutationOptions('users.store'));
mutation.mutate({ body: { name: 'John', email: 'john@example.com' } });

// onSuccess, onError should be typed
```

### 5. Resource-Based Queries (`queries.ts`)
```typescript
// usersQueries.index should return infinite query options
// usersQueries.show should return standard query options

// Keys object for invalidation
usersQueries.keys.all      // ['users']
usersQueries.keys.index()  // ['users', 'index', ...]
usersQueries.keys.show({ user: 1 }) // ['users', 'show', { user: 1 }]
```

### 6. Type Safety
- [ ] `useQuery` data type matches response type
- [ ] `useMutation` variables type matches request type
- [ ] Error type is properly typed
- [ ] No `any` types in public API

### 7. Default Options
- [ ] Sensible `staleTime` defaults (or none)
- [ ] Sensible `cacheTime`/`gcTime` defaults
- [ ] Retry logic appropriate
- [ ] `refetchOnWindowFocus` consideration

### 8. Query Key Invalidation
```typescript
// Should be easy to invalidate related queries
queryClient.invalidateQueries({ queryKey: usersQueries.keys.all });
queryClient.invalidateQueries({ queryKey: usersQueries.keys.show({ user: 1 }) });
```

### 9. React Query Version Compatibility
- [ ] Works with React Query v4
- [ ] Works with React Query v5 (TanStack Query)
- [ ] Breaking changes between versions handled

### 10. SSR Considerations
- [ ] Works with Next.js App Router
- [ ] Works with Remix
- [ ] Hydration issues addressed

## Test Scenarios
```typescript
// Basic query
const { data, isLoading, error } = useQuery(usersQueries.show({ user: 1 }));

// Infinite query
const { data, fetchNextPage, hasNextPage } = useInfiniteQuery(usersQueries.index());

// Mutation with invalidation
const queryClient = useQueryClient();
const createUser = useMutation({
  ...createMutationOptions('users.store'),
  onSuccess: () => {
    queryClient.invalidateQueries({ queryKey: usersQueries.keys.all });
  },
});

// Prefetching
await queryClient.prefetchQuery(usersQueries.show({ user: 1 }));
```

## Questions to Answer
1. Should optimistic updates be supported out of the box?
2. Should there be suspense-ready query options?
3. How to handle dependent queries (query B depends on query A)?

## Output
Document findings in `review/reports/10-react-query-report.md`
