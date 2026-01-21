# Template Reviewer

## Role Overview
Review Blade stub templates that generate TypeScript output files.

## Why This Matters
Templates directly produce user-facing code. Bugs here appear in every project using the package.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Laravel Blade | Advanced | Directives, components, escaping, raw output |
| TypeScript | Advanced | Valid syntax, module systems, type declarations |
| Code Generation | Intermediate | Template patterns, escaping, formatting |
| ESLint / Prettier | Intermediate | Code style consistency |
| String Manipulation | Intermediate | Proper escaping and interpolation |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **VS Code + Blade Extension** | Blade syntax highlighting |
| **VS Code + TypeScript** | Validate generated TypeScript |
| **ESLint** | Lint generated TypeScript |
| **Prettier** | Format check generated code |
| **Read Tool** | Read stub templates |
| **Glob Tool** | Find all stub files |
| **Bash Tool** | Generate and validate output |
| **tsc** | TypeScript compilation check |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find all stub templates
Glob: "resources/stubs/*.blade.ts"
Glob: "resources/stubs/*.blade.md"

# Find Blade directives in stubs
Grep: "@foreach|@if|@else|@endif|@include" --glob="*.blade.ts"

# Find raw output (potential escaping issues)
Grep: "\{\!\!.*\!\!\}" --glob="*.blade.ts"

# Find variable interpolation
Grep: "\{\{.*\}\}" --glob="*.blade.ts"

# Find partial includes
Grep: "@include\(" --glob="*.blade.ts"

# Check StubRenderer implementation
Read: src/Services/StubRenderer.php

# Find data passed to templates
Grep: "render\(|view\(|with\(" --glob="*Generator*.php"

# Find generator implementations
Glob: "src/Generators/*.php"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Laravel Blade code generation patterns 2026"
WebSearch: "TypeScript code generation best practices"
WebSearch: "Blade template escaping rules"
WebSearch: "Generated code formatting standards"
WebSearch: "code generation template patterns"
WebSearch: "TypeScript module generation"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://laravel.com/docs/12.x/blade"
Prompt: "Extract key information about Blade syntax, escaping, and raw output"

WebFetch: "https://www.typescriptlang.org/docs/handbook/modules.html"
Prompt: "Extract information about TypeScript module syntax and exports"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Blade TypeScript code generation"
WebSearch: "site:github.com Laravel stub generation"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Validation Commands
```bash
# Generate all output
php artisan trpc:generate

# Check TypeScript syntax
npx tsc --noEmit resources/js/api/*.ts

# Lint generated code
npx eslint resources/js/api/*.ts

# Format check
npx prettier --check resources/js/api/*.ts
```

### Template Comparison
```bash
# Compare stub to generated output
Read: resources/stubs/types.blade.ts
Read: resources/js/api/types.ts  # (after generation)
```

---

## Files to Review
- `resources/stubs/types.blade.ts`
- `resources/stubs/routes.blade.ts`
- `resources/stubs/helpers.blade.ts`
- `resources/stubs/url-builder.blade.ts`
- `resources/stubs/fetch.blade.ts`
- `resources/stubs/client.blade.ts`
- `resources/stubs/api.blade.ts`
- `resources/stubs/queries.blade.ts`
- `resources/stubs/react-query.blade.ts`
- `resources/stubs/inertia.blade.ts`
- `resources/stubs/index.blade.ts`
- `resources/stubs/readme.blade.md`
- `resources/stubs/partials/file-header.blade.ts`
- `src/Services/StubRenderer.php`

## Review Checklist

### 1. Template Syntax
- [ ] Valid Blade syntax throughout
- [ ] Proper escaping (`{!! !!}` vs `{{ }}`)
- [ ] No PHP errors in templates
- [ ] Partials included correctly

### 2. Generated Code Quality
For each generated file:
- [ ] Valid TypeScript syntax
- [ ] Passes `tsc --strict --noEmit`
- [ ] No unused variables/imports
- [ ] Consistent code style (indentation, quotes, semicolons)
- [ ] Proper file header with generation notice

### 3. File-by-File Review

#### `types.blade.ts`
- [ ] `HttpMethod` type is complete
- [ ] `ApiError` type covers all error scenarios
- [ ] `PaginatedResponse<T>` matches Laravel pagination
- [ ] `ValidationError` matches Laravel validation response

#### `routes.blade.ts`
- [ ] Route definitions are accurate
- [ ] `RouteTypeMap` is well-structured
- [ ] Method-filtered types (`GetRoutes`, `PostRoutes`, etc.)

#### `helpers.blade.ts`
- [ ] `RequestOf<T>` extracts request type correctly
- [ ] `ResponseOf<T>` extracts response type correctly
- [ ] `ParamsOf<T>` extracts URL params correctly
- [ ] `QueryOf<T>` extracts query params correctly

#### `url-builder.blade.ts`
- [ ] Parameter interpolation works
- [ ] Query string building is correct
- [ ] Handles missing optional params
- [ ] URL encoding applied where needed

#### `fetch.blade.ts`
- [ ] Error handling is robust
- [ ] Content-Type headers set correctly
- [ ] Supports all HTTP methods
- [ ] Handles empty responses (204)

#### `client.blade.ts`
- [ ] Factory pattern implemented correctly
- [ ] Default config is sensible
- [ ] `onError` callback works
- [ ] Custom headers merge correctly

#### `api.blade.ts`
- [ ] Grouped structure matches route names
- [ ] All CRUD methods available
- [ ] Autocomplete-friendly structure

### 4. Customizability
- [ ] Users can publish and modify stubs
- [ ] Modifications survive package updates
- [ ] Clear comments in templates for customization

### 5. Edge Cases
- [ ] Empty route collection (no routes)
- [ ] Routes with special characters in names
- [ ] Very long route names
- [ ] Route names with deep nesting (`api.v1.users.posts.comments.show`)

## Questions to Answer
1. Should there be a "minimal" template option?
2. Are templates tree-shakeable when unused?
3. Should templates include source maps?

## Output
Document findings in `review/reports/07-template-report.md`
