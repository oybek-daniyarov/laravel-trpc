# Inertia.js Reviewer

## Role Overview
Review the Inertia.js integration for correctness and compatibility with Inertia workflows.

## Why This Matters
Inertia.js is popular in the Laravel ecosystem. Proper integration benefits a large user base.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Inertia.js | Advanced | Router, Links, Forms, shared data |
| Laravel + Inertia | Advanced | Inertia responses, middleware |
| React or Vue | Intermediate | Component patterns with Inertia |
| TypeScript | Intermediate | Typing Inertia props and forms |
| Form Handling | Intermediate | useForm hook, validation errors |
| SSR Concepts | Intermediate | Server-side rendering considerations |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **VS Code + Inertia snippets** | Inertia development |
| **Read Tool** | Read Inertia integration files |
| **Grep Tool** | Search for Inertia patterns |
| **Laravel + Inertia project** | Test integration in real app |
| **Browser DevTools** | Inspect Inertia requests |
| **Vue/React DevTools** | Component inspection |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find route helper implementation
Grep: "route\s*=|function route\(" --glob="*.blade.ts"

# Find visit helper
Grep: "visit\s*=|function visit\(" --glob="*.blade.ts"

# Find form action helper
Grep: "formAction|form.*action" --glob="*.blade.ts"

# Find Inertia router usage
Grep: "router\.|Inertia\." --glob="*.blade.ts"

# Find method spoofing (_method)
Grep: "_method|method.*PUT|method.*DELETE" --glob="*.blade.ts"

# Find Inertia imports
Grep: "@inertiajs" --glob="*.blade.ts"

# Find Link component usage examples
Grep: "<Link|Link href" --glob="*.blade.ts"

# Read Inertia integration
Read: resources/stubs/inertia.blade.ts
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Inertia.js TypeScript types 2026"
WebSearch: "Inertia.js form handling patterns"
WebSearch: "Inertia.js route helper implementation"
WebSearch: "Laravel Inertia SSR setup"
WebSearch: "Inertia.js React vs Vue differences"
WebSearch: "Inertia.js useForm TypeScript"
WebSearch: "Inertia.js partial reloads"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://inertiajs.com/the-protocol"
Prompt: "Extract key information about Inertia protocol and how it works"

WebFetch: "https://inertiajs.com/links"
Prompt: "Extract information about Inertia Link component and navigation"

WebFetch: "https://inertiajs.com/forms"
Prompt: "Extract information about Inertia form handling and useForm hook"

WebFetch: "https://inertiajs.com/manual-visits"
Prompt: "Extract information about manual visits and router methods"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Inertia.js TypeScript route helper"
WebSearch: "site:github.com Laravel Inertia typed routes"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Testing with Real Inertia App
```bash
# Test route generation
php artisan trpc:generate

# Verify in Inertia component
# import { route, visit } from '@/api/inertia';
# <Link href={route('users.show', { user: 1 })}>View</Link>
```

### Compatibility Testing
```bash
# Check React adapter compatibility
Grep: "@inertiajs/react" --glob="*.ts"

# Check Vue adapter compatibility
Grep: "@inertiajs/vue3" --glob="*.ts"

# Check for SSR-safe code (no window/document)
Grep: "window\.|document\." --glob="inertia.blade.ts"
```

---

## Prerequisites
- Familiarity with Inertia.js (React and/or Vue)
- Understanding of Inertia's router and forms
- Knowledge of server-side rendering considerations

## Files to Review
- `resources/stubs/inertia.blade.ts`

## Review Checklist

### 1. Route Helper
```typescript
import { route } from '@/api/inertia';

// Should generate correct URLs
route('users.index'); // '/api/users'
route('users.show', { user: 123 }); // '/api/users/123'
route('users.index', null, { query: { page: 2 } }); // '/api/users?page=2'
```

### 2. Visit Helper
```typescript
import { visit } from '@/api/inertia';

// Should call Inertia router with correct options
visit('users.show', { user: 123 });
visit('users.index', null, { query: { page: 2 } });
visit('users.index', null, {
  preserveState: true,
  preserveScroll: true,
});
```

### 3. Form Action Helper
```typescript
import { formAction } from '@/api/inertia';

// Should return form props
<form {...formAction('users.store')}>
// Results in:
// <form action="/api/users" method="POST">

<form {...formAction('users.update', { user: 123 })}>
// Results in:
// <form action="/api/users/123" method="POST">
// with _method="PUT" hidden input
```

### 4. Type Safety
- [ ] Route names autocomplete correctly
- [ ] Path parameters are type-checked
- [ ] Query parameters are type-checked
- [ ] Invalid route names cause TypeScript errors

### 5. Inertia Router Integration
- [ ] `router.visit()` options passed correctly
- [ ] `router.get()`, `router.post()`, etc. work
- [ ] `router.reload()` works with route helper

### 6. Form Handling
- [ ] Works with Inertia's `useForm` hook
- [ ] Form submission types match route expectations
- [ ] Validation errors typed correctly

```typescript
import { useForm } from '@inertiajs/react';

const form = useForm<RequestOf<'users.store'>>({
  name: '',
  email: '',
});

form.post(route('users.store'));
```

### 7. Method Spoofing
Laravel uses `_method` for PUT/PATCH/DELETE in forms:
- [ ] PUT routes generate `_method="PUT"`
- [ ] PATCH routes generate `_method="PATCH"`
- [ ] DELETE routes generate `_method="DELETE"`

### 8. Framework Compatibility
- [ ] Works with `@inertiajs/react`
- [ ] Works with `@inertiajs/vue3`
- [ ] Works with `@inertiajs/svelte` (if applicable)

### 9. SSR Considerations
- [ ] Works in SSR context (no window references)
- [ ] Hydration works correctly

### 10. Link Component Integration
```typescript
import { Link } from '@inertiajs/react';
import { route } from '@/api/inertia';

<Link href={route('users.show', { user: 123 })}>
  View User
</Link>
```

## Test Scenarios
```typescript
// Navigation
visit('users.index');
visit('users.show', { user: 1 });
visit('posts.index', null, { query: { category: 'tech', page: 2 } });

// Forms
<form {...formAction('users.store')}>
<form {...formAction('users.update', { user: 1 })}>
<form {...formAction('users.destroy', { user: 1 })}>

// With useForm
const form = useForm({ name: '', email: '' });
form.post(route('users.store'));
form.put(route('users.update', { user: 1 }));
form.delete(route('users.destroy', { user: 1 }));
```

## Questions to Answer
1. Should there be a typed `useForm` wrapper?
2. How to handle partial reloads with type safety?
3. Should shared data (props) be typed?

## Output
Document findings in `review/reports/11-inertia-report.md`
