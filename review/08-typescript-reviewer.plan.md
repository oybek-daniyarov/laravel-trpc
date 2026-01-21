# TypeScript Reviewer

## Role Overview
Review the generated TypeScript code for correctness, type safety, and developer experience.

## Why This Matters
TypeScript developers expect high-quality types. Poor types or DX issues undermine the package's value.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| TypeScript Generics | Expert | Generic constraints, inference, defaults |
| Mapped Types | Expert | `keyof`, `in`, index signatures |
| Conditional Types | Expert | `extends`, `infer`, distributive conditionals |
| Template Literal Types | Advanced | String manipulation at type level |
| TypeScript Compiler | Advanced | tsconfig options, strict mode |
| IDE Tooling | Advanced | VS Code IntelliSense, type hover |
| Module Systems | Intermediate | ESM, CommonJS, barrel exports |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **VS Code + TypeScript** | Primary IDE for type validation |
| **TypeScript Compiler (tsc)** | Type checking with various configs |
| **TypeScript Playground** | Isolated type testing |
| **Read Tool** | Read generated TypeScript files |
| **Grep Tool** | Search for type patterns |
| **Bash Tool** | Run TypeScript compiler |
| **@typescript-eslint** | TypeScript-specific linting |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# First, generate the TypeScript output
Bash: php artisan trpc:generate

# Find generic type definitions
Grep: "<[A-Z].*>" --glob="resources/js/api/*.ts"

# Find mapped types
Grep: "keyof|in keyof|\[.*\]:" --glob="resources/js/api/*.ts"

# Find conditional types
Grep: "extends.*\?|infer " --glob="resources/js/api/*.ts"

# Find template literal types
Grep: "\`\$\{" --glob="resources/js/api/*.ts"

# Find 'any' usage (should be avoided)
Grep: ": any|as any" --glob="resources/js/api/*.ts"

# Find 'unknown' usage (preferred over any)
Grep: ": unknown" --glob="resources/js/api/*.ts"

# Find type assertions
Grep: " as [A-Z]| as \{" --glob="resources/js/api/*.ts"

# Find exports
Grep: "^export " --glob="resources/js/api/*.ts"

# Check for circular imports
Grep: "import.*from '\\./" --glob="resources/js/api/*.ts"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "TypeScript advanced generics patterns 2026"
WebSearch: "TypeScript mapped types tutorial"
WebSearch: "TypeScript conditional types examples"
WebSearch: "TypeScript 5 new features"
WebSearch: "TypeScript strict mode best practices"
WebSearch: "TypeScript template literal types"
WebSearch: "TypeScript infer keyword examples"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://www.typescriptlang.org/docs/handbook/2/generics.html"
Prompt: "Extract key information about TypeScript generics patterns"

WebFetch: "https://www.typescriptlang.org/docs/handbook/2/mapped-types.html"
Prompt: "Extract information about mapped types and index signatures"

WebFetch: "https://www.typescriptlang.org/docs/handbook/2/conditional-types.html"
Prompt: "Extract information about conditional types and infer"

WebFetch: "https://www.typescriptlang.org/docs/handbook/2/template-literal-types.html"
Prompt: "Extract information about template literal types"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com TypeScript API client types"
WebSearch: "site:github.com TypeScript route type safety"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Type Testing Commands
```bash
# Strict type check
npx tsc --strict --noEmit resources/js/api/*.ts

# Check with noUncheckedIndexedAccess
npx tsc --strict --noUncheckedIndexedAccess --noEmit resources/js/api/*.ts

# Generate declaration files to verify types
npx tsc --declaration --emitDeclarationOnly resources/js/api/*.ts
```

### IDE Testing
1. Open `resources/js/api/` in VS Code
2. Test autocomplete on route names
3. Test autocomplete on request bodies
4. Verify hover shows correct types
5. Check for red squiggles (errors)

---

## Prerequisites
- Strong TypeScript knowledge (generics, mapped types, conditional types)
- Familiarity with TypeScript compiler options
- Experience with IDE tooling (VS Code, WebStorm)

## Files to Review
Generated output files (after running `php artisan trpc:generate`):
- `resources/js/api/types.ts`
- `resources/js/api/routes.ts`
- `resources/js/api/helpers.ts`
- `resources/js/api/url-builder.ts`
- `resources/js/api/fetch.ts`
- `resources/js/api/client.ts`
- `resources/js/api/api.ts`
- `resources/js/api/index.ts`

## Review Checklist

### 1. Type Correctness
- [ ] All types compile without errors (`tsc --strict --noEmit`)
- [ ] No `any` types (use `unknown` instead)
- [ ] No unnecessary type assertions (`as`)
- [ ] Generics constrained appropriately

### 2. IDE Experience
Test in VS Code:
- [ ] Autocomplete for route names
- [ ] Autocomplete for request body properties
- [ ] Autocomplete for URL parameters
- [ ] Hover shows meaningful type information
- [ ] Go to definition works
- [ ] Errors shown inline for type mismatches

### 3. Generic Quality

#### Route Type Map
```typescript
// Should provide autocomplete for all routes
type Routes = keyof RouteTypeMap; // 'users.index' | 'users.show' | ...
```

#### Type Helpers
```typescript
// Should infer correctly
type Req = RequestOf<'users.store'>; // CreateUserData
type Res = ResponseOf<'users.show'>; // UserData
type Params = ParamsOf<'users.show'>; // { user: number }
```

#### Template Literal Types
```typescript
// Should construct route patterns
type UsersRoutes = `users.${string}`;
```

### 4. Strictness Compatibility
Test with various tsconfig settings:
```json
{
  "strict": true,
  "noImplicitAny": true,
  "strictNullChecks": true,
  "noUncheckedIndexedAccess": true
}
```

### 5. Module Compatibility
- [ ] Works with ESM (`"type": "module"`)
- [ ] Works with CommonJS (if needed)
- [ ] Tree-shakeable (unused exports eliminated)
- [ ] No circular imports

### 6. Type Inference
```typescript
// Types should be inferred, not require annotation
const user = await api.users.show({ user: 1 }); // user: UserData
const users = await api.users.index(); // users: PaginatedResponse<UserData>
```

### 7. Error Types
```typescript
// Error handling should be typed
try {
  await api.users.store({ ... });
} catch (e) {
  if (e instanceof ApiError) {
    e.status; // number
    e.data; // ValidationError (if 422)
  }
}
```

### 8. Common TypeScript Patterns
Verify these patterns work:
- [ ] Discriminated unions for responses
- [ ] Conditional types for request/response mapping
- [ ] Mapped types for route definitions
- [ ] Index signatures where appropriate

## Questions to Answer
1. What's the minimum TypeScript version required?
2. Are there performance issues with complex route maps (1000+ routes)?
3. Should there be a "loose" mode for gradual adoption?

## Output
Document findings in `review/reports/08-typescript-report.md`
