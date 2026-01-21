# Route Collection Reviewer

## Role Overview
Review how routes are collected and filtered from Laravel's router.

## Why This Matters
Incorrect route collection means missing APIs or including internal routes that shouldn't be exposed.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Laravel Routing | Advanced | Route registration, groups, resources, model binding |
| Laravel Router Internals | Advanced | RouteCollection, Route class, CompiledRoute |
| Regular Expressions | Intermediate | Pattern matching for route filtering |
| Collections | Intermediate | Laravel collections for filtering/mapping |
| PHP Reflection | Intermediate | Extracting controller/method info |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Laravel Tinker** | Inspect routes interactively |
| **php artisan route:list** | View all registered routes |
| **Read Tool** | Read collector source files |
| **Grep Tool** | Search for route handling patterns |
| **Bash Tool** | Run route commands |
| **Xdebug** | Debug route collection process |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find route collection logic
Grep: "getRoutes\(\)|Route::|\$router->" --glob="*Collector*.php"

# Find route filtering
Grep: "filter\(|reject\(|where\(" --glob="*Collector*.php"

# Find route data extraction
Grep: "getAction\(\)|getName\(\)|getMethods\(\)|uri\(\)" --glob="*.php"

# Find middleware handling
Grep: "middleware\(|getMiddleware\(\)" --glob="*Collector*.php"

# Find parameter extraction
Grep: "parameterNames\(\)|parameters\(\)|bindingFields\(\)" --glob="*.php"

# Find exclude pattern logic
Grep: "exclude|pattern|preg_match" --glob="*Collector*.php"

# Check RouteData structure
Read: src/Data/RouteData.php

# Find route mode handling
Grep: "route_mode|api|web|named|attributed" --glob="*.php"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Laravel router internals 2026"
WebSearch: "Laravel route collection performance optimization"
WebSearch: "Laravel route model binding reflection"
WebSearch: "Laravel API resource routes structure"
WebSearch: "Laravel route caching mechanism"
WebSearch: "Laravel route parameters extraction"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://laravel.com/docs/12.x/routing"
Prompt: "Extract key information about route registration, groups, parameters, and model binding"

WebFetch: "https://laravel.com/docs/12.x/controllers"
Prompt: "Extract information about resource controllers and route reflection"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Laravel route collection iteration"
WebSearch: "site:github.com Laravel route introspection"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Manual Testing Commands
```bash
# List all routes
php artisan route:list

# List API routes only
php artisan route:list --path=api

# List routes as JSON
php artisan route:list --json

# Check specific route
php artisan route:list --name=users.show
```

---

## Files to Review
- `src/Collectors/DefaultRouteCollector.php`
- `src/Collections/RouteCollection.php`
- `src/Contracts/Collector.php`
- `src/Data/RouteData.php`

## Review Checklist

### 1. Route Discovery
- [ ] Controller-based routes collected correctly
- [ ] Closure routes handled (or explicitly skipped with warning)
- [ ] Resource routes expanded properly
- [ ] Nested route groups work
- [ ] Route model binding parameters detected

### 2. Route Modes
Test each `route_mode` option:
- [ ] `api` - Only routes with `api` prefix/middleware
- [ ] `web` - Only routes with `web` middleware
- [ ] `all` - All routes
- [ ] `named` - Only named routes
- [ ] `attributed` - Only routes with `#[TypedRoute]`

### 3. Filtering
- [ ] `api_prefix` filter works correctly
- [ ] `exclude_patterns` removes matching routes
- [ ] Middleware-based filtering (if supported)

### 4. Route Data Extraction
For each route, verify these are captured:
- [ ] `name` - Route name (e.g., `users.show`)
- [ ] `uri` - URI pattern (e.g., `/api/users/{user}`)
- [ ] `methods` - HTTP methods (GET, POST, etc.)
- [ ] `controller` - Controller class name
- [ ] `action` - Method name
- [ ] `parameters` - URL parameters with types
- [ ] `middleware` - Applied middleware

### 5. Edge Cases
- [ ] Routes without names
- [ ] Routes with optional parameters `{user?}`
- [ ] Routes with regex constraints `{id:[0-9]+}`
- [ ] Routes with multiple parameters
- [ ] Fallback routes
- [ ] Redirect routes

### 6. Test Route Definitions
```php
// Test these patterns
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::apiResource('posts', PostController::class);
Route::get('/search', fn() => ...); // Closure
Route::get('/legacy/{any}', ...)->where('any', '.*');
```

## Questions to Answer
1. How should closure routes be handled?
2. Should vendor package routes be auto-excluded?
3. Is the `RouteData` structure extensible for custom metadata?

## Output
Document findings in `review/reports/04-route-collection-report.md`
