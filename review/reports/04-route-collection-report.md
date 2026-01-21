# Route Collection Review Report

**Package:** Laravel tRPC
**Review Date:** January 21, 2026
**Laravel Version:** 12.x
**Reviewer:** Route Collection Reviewer

---

## Executive Summary

The route collection system in Laravel tRPC is well-designed with a clean separation of concerns. It effectively leverages Laravel 12's routing internals through a combination of Laravel Ranger for type-safe route iteration and the native Route facade for route metadata. The system supports multiple route modes, pattern-based filtering, and comprehensive route data extraction.

**Overall Assessment:** Good architectural foundation with minor gaps in closure route handling and resource route support.

---

## 1. Architecture Overview

### 1.1 Core Components

| Component | File | Purpose |
|-----------|------|---------|
| `Collector` | `src/Contracts/Collector.php` | Contract defining the `collect(): RouteCollection` method |
| `DefaultRouteCollector` | `src/Collectors/DefaultRouteCollector.php` | Primary implementation for route discovery |
| `RouteCollection` | `src/Collections/RouteCollection.php` | Immutable collection with filtering, grouping, and sorting |
| `RouteData` | `src/Data/RouteData.php` | Readonly DTO containing all route metadata |
| `RouteTypeInfo` | `src/Data/RouteTypeInfo.php` | Type information for request/response |
| `RouteTypeExtractor` | `src/Services/RouteTypeExtractor.php` | Static analysis for type extraction |
| `CollectRoutesPipe` | `src/Pipes/CollectRoutesPipe.php` | Pipeline stage that orchestrates collection |

### 1.2 Collection Flow

```
CollectRoutesPipe
    |
    +-> TrpcConfig.getCollectors()
    |
    +-> For each collector:
    |       |
    |       +-> DefaultRouteCollector.collect()
    |               |
    |               +-> RouteTypeExtractor.extractRouteTypes() [via Ranger]
    |               +-> RouteFacade::getRoutes() [Laravel Router]
    |               +-> shouldCollectRoute() [filtering]
    |               +-> processRoute() [data extraction]
    |
    +-> RouteCollection.merge()
    +-> RouteCollection.sortByName()
```

---

## 2. Route Discovery Analysis

### 2.1 Controller Routes

**Status:** Fully Supported

The collector properly handles controller-based routes via Laravel's route action metadata:

```php
// DefaultRouteCollector.php:275-289
private function hasTypedRouteAttribute(Route $route): bool
{
    $action = $route->getAction();

    if (! isset($action['controller'])) {
        return false;
    }

    $controller = $action['controller'];

    if (! is_string($controller) || ! str_contains($controller, '@')) {
        return false;
    }

    [$class, $method] = explode('@', $controller);
    // ...
}
```

Controller routes are fully supported with:
- Method resolution
- Attribute detection (`#[TypedRoute]`)
- Type extraction via Surveyor

### 2.2 Closure Routes

**Status:** Partially Supported (with limitations)

Closure routes are collected from Laravel's router but have limited type information:

```php
// RouteTypeExtractor.php:57-60
$this->ranger->onRoute(function (Route $route) {
    if (! $route->hasController()) {
        return;  // Closure routes skip type extraction
    }
    // ...
});
```

**Current Behavior:**
- Closure routes ARE collected in the RouteCollection
- Type information defaults to `unknown` response type
- Cannot use `#[TypedRoute]` attribute on closures

**Recommendation:** Consider supporting closure type hints or PHPDoc annotations for type extraction.

### 2.3 Resource Routes

**Status:** Fully Supported

Laravel resource routes (`Route::resource()`) are automatically expanded by Laravel's router before collection. The collector processes each expanded route (index, create, store, show, edit, update, destroy) individually.

**Verification:** Resource routes appear as separate entries in `RouteFacade::getRoutes()`:
- `GET /api/users` -> `users.index`
- `POST /api/users` -> `users.store`
- `GET /api/users/{user}` -> `users.show`
- etc.

**Route Name Generation:** The collector intelligently generates names for unnamed routes:

```php
// DefaultRouteCollector.php:148-176
if (count($parts) === 2) {
    $lastPart = $parts[1];
    if (preg_match('/\{(\w+)\}/', $lastPart)) {
        $name = "{$resource}.show";
    } else {
        $name = "{$resource}.{$parts[1]}";
    }
}
```

### 2.4 Invokable Controllers

**Status:** Supported

Invokable controllers use the `__invoke` method format (`Controller@__invoke`) which is properly parsed.

---

## 3. Route Modes Analysis

### 3.1 Configuration

Route modes are configured in `config/trpc.php`:

```php
'route_mode' => env('TRPC_ROUTE_MODE', 'api'),
```

### 3.2 Mode Implementation

```php
// DefaultRouteCollector.php:215-222
$passesMode = match ($mode) {
    'all' => true,
    'api' => $this->isApiRoute($uri, $apiPrefix),
    'web' => ! $this->isApiRoute($uri, $apiPrefix),
    'named' => $routeName !== null && $routeName !== '',
    'attributed' => $this->hasTypedRouteAttribute($route),
    default => $this->isApiRoute($uri, $apiPrefix),
};
```

### 3.3 Mode Analysis

| Mode | Status | Description |
|------|--------|-------------|
| `api` | Working | Filters routes starting with `api_prefix` (default: `api`) |
| `web` | Working | Inverse of `api` - routes NOT starting with prefix |
| `all` | Working | Collects all routes (use with exclude patterns) |
| `named` | Working | Only routes with explicit names |
| `attributed` | Working | Only routes with `#[TypedRoute]` attribute |

### 3.4 API Prefix Detection

```php
// DefaultRouteCollector.php:241-248
private function isApiRoute(string $uri, string $apiPrefix): bool
{
    if ($apiPrefix === '') {
        return true;
    }

    return Str::startsWith($uri, $apiPrefix.'/') || $uri === $apiPrefix;
}
```

**Edge Case Handling:**
- Empty prefix (`''`) treats all routes as API routes
- Exact match (`api`) is handled
- Prefix with trailing segment (`api/`) is handled

---

## 4. Filtering Analysis

### 4.1 Exclude Patterns

**Status:** Working Correctly

Default exclude patterns in config:

```php
'exclude_patterns' => [
    'debugbar.*',
    'horizon.*',
    'telescope.*',
    'sanctum.*',
    'ignition.*',
],
```

Implementation:

```php
// DefaultRouteCollector.php:207-209
if ($this->matchesPatterns($routeName, $uri, $this->config->getExcludePatterns())) {
    return false;
}
```

### 4.2 Include Patterns

**Status:** Working Correctly

```php
// DefaultRouteCollector.php:229-233
$includePatterns = $this->config->getIncludePatterns();

if (! empty($includePatterns)) {
    return $this->matchesPatterns($routeName, $uri, $includePatterns);
}
```

**Behavior:**
- If `include_patterns` is empty, all routes passing mode check are included
- If `include_patterns` is specified, route must match at least one pattern

### 4.3 Pattern Matching

```php
// DefaultRouteCollector.php:255-270
private function matchesPatterns(?string $routeName, string $uri, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        // Check route name pattern (uses dot notation like 'users.*')
        if ($routeName !== null && Str::is($pattern, $routeName)) {
            return true;
        }

        // Check URI pattern (uses path notation like 'api/*')
        if (Str::is($pattern, $uri)) {
            return true;
        }
    }

    return false;
}
```

**Supported Pattern Formats:**
- Route names: `users.*`, `auth.login`, `api.*`
- URI paths: `api/*`, `api/v1/*`, `*/users/*`

Uses Laravel's `Str::is()` for glob-style matching.

### 4.4 Method Exclusion

```php
// DefaultRouteCollector.php:56-59
if (in_array($method, $excludeMethods, true)) {
    continue;
}
```

Default excluded methods: `['options', 'head']`

---

## 5. Data Extraction Analysis

### 5.1 RouteData Properties

| Property | Source | Status |
|----------|--------|--------|
| `method` | `$route->methods()[0]` | Working |
| `path` | `$route->uri()` | Working |
| `name` | `$route->getName()` or generated | Working |
| `group` | Determined from name/config | Working |
| `pathParams` | Extracted from URI `{param}` | Working |
| `requestType` | TypedRoute or Surveyor | Working |
| `queryType` | TypedRoute only | Working |
| `responseType` | TypedRoute or Surveyor | Working |
| `errorType` | TypedRoute only | Limited |
| `hasRequest` | Computed | Working |
| `hasQuery` | Computed | Working |
| `hasResponse` | Computed | Working |
| `isCollection` | TypedRoute or Surveyor | Working |
| `isPaginated` | TypedRoute or Surveyor | Working |
| `middleware` | `$route->gatherMiddleware()` | Working |
| `requestClass` | Original class name | Working |
| `queryClass` | Original class name | Working |

### 5.2 Path Parameter Extraction

```php
// DefaultRouteCollector.php:121-126
private function extractPathParameters(string $path): array
{
    preg_match_all('/\{(\w+)\}/', $path, $matches);

    return $matches[1] ?? [];
}
```

**Supported Formats:**
- `{user}` -> `['user']`
- `{user}/{post}` -> `['user', 'post']`

**Limitation:** Does not capture optional parameters `{user?}` differently from required ones.

### 5.3 Route Grouping

```php
// DefaultRouteCollector.php:179-198
private function determineRouteGroup(string $routeName): string
{
    $routeGroups = $this->config->getRouteGroups();

    foreach ($routeGroups as $group => $patterns) {
        foreach ($patterns as $pattern) {
            if (Str::contains($routeName, $pattern)) {
                return $this->routeGroupCache[$routeName] = $group;
            }
        }
    }

    $parts = explode('.', $routeName);

    return $this->routeGroupCache[$routeName] = $parts[0] ?? 'default';
}
```

**Priority:**
1. Explicit group mappings from config
2. First segment of dot-notation route name
3. Fallback to `'default'`

### 5.4 Authentication Detection

```php
// RouteData.php:39-48
public function isAuthenticated(): bool
{
    foreach ($this->middleware as $middleware) {
        if (str_starts_with($middleware, 'auth') || $middleware === 'sanctum') {
            return true;
        }
    }

    return false;
}
```

**Detected Middleware:**
- `auth`, `auth:api`, `auth:sanctum`
- `sanctum`

**Missing Detection:**
- Custom authentication middleware
- `auth.basic`
- Token-based authentication via headers

---

## 6. RouteCollection Features

### 6.1 Collection Operations

| Method | Status | Description |
|--------|--------|-------------|
| `add(RouteData)` | Working | Adds route and indexes by name |
| `get(string)` | Working | O(1) lookup by name |
| `has(string)` | Working | Check existence by name |
| `filter(callable)` | Working | Returns new filtered collection |
| `map(callable)` | Working | Maps to array |
| `groupBy(callable)` | Working | Groups into array of collections |
| `sortByName()` | Working | Returns sorted collection |
| `merge(RouteCollection)` | Working | Merges, skipping duplicates by name |
| `isEmpty()` | Working | Check if empty |
| `count()` | Working | Countable interface |
| `toArray()` | Working | Converts to array format |

### 6.2 Name Index

```php
// RouteCollection.php:24-25
/** @var array<string, int> */
private array $nameIndex = [];
```

Provides O(1) route lookup by name. Duplicate names are overwritten (last wins).

### 6.3 Immutability Pattern

All mutation methods (`filter`, `sortByName`, `merge`) return new instances:

```php
// RouteCollection.php:83-86
public function filter(callable $callback): self
{
    return new self(array_filter($this->routes, $callback));
}
```

---

## 7. Performance Considerations

### 7.1 Caching Mechanisms

The collector implements caching for:

```php
// DefaultRouteCollector.php:28-31
/** @var array<string, string> */
private array $routeGroupCache = [];

/** @var array<string, string> */
private array $routeNameCache = [];
```

```php
// RouteTypeExtractor.php:39-43
/** @var array<string, bool> */
private array $classExistsCache = [];

/** @var array<string, bool> */
private array $methodExistsCache = [];
```

### 7.2 Laravel Route Caching Compatibility

The collector uses `RouteFacade::getRoutes()` which works with Laravel's route cache:

```bash
php artisan route:cache
```

**Important:** Route caching requires controller-based routes. Closure routes cannot be cached by Laravel.

### 7.3 Complexity Analysis

| Operation | Complexity |
|-----------|------------|
| Route collection | O(n) where n = number of routes |
| Pattern matching | O(n * p) where p = patterns |
| Name lookup | O(1) via index |
| Type extraction | O(n) with caching |

---

## 8. Integration with Laravel 12

### 8.1 Router Integration

The package correctly integrates with Laravel 12's routing system:

```php
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
```

### 8.2 Laravel Ranger Usage

```php
// RouteTypeExtractor.php:7-8
use Laravel\Ranger\Components\Route;
use Laravel\Ranger\Ranger;
```

Laravel Ranger provides type-safe route iteration and is used for static analysis integration with Surveyor.

### 8.3 Middleware Gathering

```php
// DefaultRouteCollector.php:88
$middleware = $route->gatherMiddleware();
```

Uses Laravel's native middleware resolution which handles:
- Route-level middleware
- Controller middleware
- Group middleware
- Global middleware

---

## 9. Issues and Recommendations

### 9.1 Critical Issues

None identified.

### 9.2 Moderate Issues

#### Issue 1: Closure Routes Lack Type Information

**Current Behavior:** Closure routes skip type extraction entirely.

**Impact:** Generated TypeScript types default to `unknown`.

**Recommendation:** Consider supporting PHPDoc annotations or a fluent API for typing closure routes:

```php
// Possible enhancement
Route::get('/health', fn() => ['status' => 'ok'])
    ->typed(response: HealthCheckResponse::class);
```

#### Issue 2: Optional Route Parameters Not Distinguished

**Current Behavior:** `{user?}` extracted as `user` same as `{user}`.

**Impact:** Generated TypeScript doesn't know parameter is optional.

**Recommendation:** Capture optional flag in path parameter extraction:

```php
// Enhanced extraction
preg_match_all('/\{(\w+)(\?)?\}/', $path, $matches);
// Returns: [['user', null], ['comment', '?']]
```

### 9.3 Minor Issues

#### Issue 3: Route Name Collisions

**Current Behavior:** Last route with same name wins in merge.

**Recommendation:** Log warning when name collision occurs.

#### Issue 4: Limited Error Type Support

**Current Behavior:** Error types only supported via `#[TypedRoute]` attribute.

**Recommendation:** Consider inferring error types from exception handlers or validation rules.

#### Issue 5: Hardcoded Route Name Generation Rules

**Current Behavior:** Route name generation has hardcoded patterns:

```php
$name = match ($subResource) {
    'country' => "{$resource}.byCountry",
    'city' => "{$resource}.byCity",
    'search' => "{$resource}.search",
    default => "{$resource}.{$subResource}",
};
```

**Recommendation:** Make this configurable or remove hardcoded mappings.

---

## 10. Test Coverage Assessment

### 10.1 Existing Tests

From `tests/Unit/Collectors/DefaultRouteCollectorTest.php`:

| Test | Coverage |
|------|----------|
| API prefix configuration | Covered |
| Exclude methods configuration | Covered |
| Route groups configuration | Covered |
| Route name mappings | Covered |
| Default values | Covered |
| RouteCollection filtering | Covered |

### 10.2 Missing Test Coverage

| Area | Status |
|------|--------|
| Route mode switching (api/web/all/named/attributed) | Not tested |
| Exclude pattern matching | Not tested |
| Include pattern matching | Not tested |
| Closure route handling | Not tested |
| Resource route expansion | Not tested |
| Path parameter extraction | Not tested |
| Route grouping logic | Not tested |

**Note:** Full integration tests require Laravel application context with Surveyor/Ranger dependencies, which is noted in the test file:

```php
// Note: Tests requiring Route facade and DefaultRouteCollector moved to Feature tests
// since they need full Laravel app context with Surveyor/Ranger dependencies.
```

---

## 11. Configuration Reference

### 11.1 Complete Configuration Options

```php
// config/trpc.php

// Route filtering
'route_mode' => 'api',           // 'api', 'web', 'all', 'named', 'attributed'
'api_prefix' => 'api',           // Prefix for 'api' mode filtering
'include_patterns' => [],        // Whitelist patterns
'exclude_patterns' => [          // Blacklist patterns
    'debugbar.*',
    'horizon.*',
    'telescope.*',
    'sanctum.*',
    'ignition.*',
],
'exclude_methods' => [           // HTTP methods to skip
    'options',
    'head',
],

// Route naming
'route_name_mappings' => [       // Override generated names
    // 'api/v1/users/{user}' => 'users.show',
],
'route_groups' => [              // Group assignment patterns
    'auth' => ['login', 'register', 'logout', 'password', 'auth'],
    'users' => ['users', 'profile'],
],

// Collectors
'collectors' => [
    \OybekDaniyarov\LaravelTrpc\Collectors\DefaultRouteCollector::class,
],
```

---

## 12. Conclusion

The route collection system demonstrates solid engineering with:

- Clean contract-based architecture enabling custom collectors
- Comprehensive route mode filtering
- Efficient caching for repeated operations
- Proper integration with Laravel 12's routing system
- Immutable collection patterns

**Key Strengths:**
1. Flexible route mode system
2. Pattern-based include/exclude filtering
3. Automatic route name generation
4. Full middleware extraction
5. Type-safe route iteration via Ranger

**Areas for Improvement:**
1. Enhanced closure route support
2. Optional parameter distinction
3. Expanded test coverage
4. Configurable name generation rules

**Final Verdict:** Production-ready with minor enhancements recommended.

---

## Sources

- [Routing - Laravel 12.x Documentation](https://laravel.com/docs/12.x/routing)
- [Controllers - Laravel 12.x Documentation](https://laravel.com/docs/12.x/controllers)
- [Laravel Route Collection Performance Analysis](https://how.dev/answers/how-to-optimize-route-loading-in-laravel)
- [Illuminate Routing RouteCollection Source](https://github.com/illuminate/routing/blob/master/RouteCollection.php)
- [Laravel Route Caching Guide](https://voltagead.com/laravel-route-caching-for-improved-performance/)
