# Performance Reviewer

## Role Overview
Review the package for performance issues in both build-time and generated runtime code.

## Why This Matters
Slow build times frustrate developers. Inefficient runtime code affects end users.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| PHP Profiling | Advanced | Xdebug, Blackfire, profiling interpretation |
| Algorithm Complexity | Advanced | Big O analysis, optimization |
| Memory Management | Intermediate | PHP memory, garbage collection |
| JavaScript Performance | Intermediate | Bundle size, runtime efficiency |
| Database Optimization | Intermediate | Query analysis (if applicable) |
| Caching Strategies | Intermediate | What and when to cache |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Xdebug Profiler** | PHP profiling |
| **Blackfire** | Advanced PHP profiling |
| **Read Tool** | Read source files |
| **Grep Tool** | Search for performance patterns |
| **Bash Tool** | Run benchmarks |
| **time command** | Measure execution time |
| **memory_get_usage()** | PHP memory measurement |
| **webpack-bundle-analyzer** | JS bundle analysis |
| **source-map-explorer** | Bundle composition |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find loops (potential O(n²))
Grep: "foreach|for\s*\(|while\s*\(" --glob="src/**/*.php"

# Find nested loops (O(n²) risk)
Grep: "foreach.*foreach|for.*for" --glob="src/**/*.php" -C 5

# Find reflection calls (expensive)
Grep: "Reflection|getAttributes|getMethods|getProperties" --glob="src/**/*.php"

# Find file operations
Grep: "file_get_contents|file_put_contents|fopen|fwrite" --glob="src/**/*.php"

# Find array operations in loops
Grep: "array_merge|array_push|\[\].*=" --glob="src/**/*.php"

# Find string concatenation in loops
Grep: "\.=|\. " --glob="src/**/*.php"

# Find potential N+1 issues
Grep: "->each\(|->map\(" --glob="src/**/*.php"

# Find caching opportunities
Grep: "cache\(|Cache::|remember\(" --glob="src/**/*.php"

# Find memory-intensive operations
Grep: "json_decode|json_encode|serialize|unserialize" --glob="src/**/*.php"

# Check generated file sizes
Bash: ls -la resources/js/api/
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "PHP reflection performance optimization 2026"
WebSearch: "PHP code generation performance"
WebSearch: "Laravel pipeline performance"
WebSearch: "TypeScript bundle size optimization"
WebSearch: "PHP memory profiling techniques"
WebSearch: "PHP opcache performance"
WebSearch: "Laravel artisan command performance"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://www.php.net/manual/en/book.opcache.php"
Prompt: "Extract key information about PHP OPcache and performance"

WebFetch: "https://laravel.com/docs/12.x/deployment#optimization"
Prompt: "Extract information about Laravel optimization techniques"

WebFetch: "https://blackfire.io/docs/introduction"
Prompt: "Extract information about PHP profiling best practices"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com PHP performance benchmarking"
WebSearch: "site:github.com Laravel performance optimization"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Profiling Commands
```bash
# Time execution
time php artisan trpc:generate

# Profile with Xdebug
php -d xdebug.mode=profile artisan trpc:generate

# Memory usage
php -d memory_limit=-1 artisan trpc:generate 2>&1 | head

# Add memory tracking (in code)
# $start = memory_get_usage();
# ... operation ...
# echo memory_get_usage() - $start;
```

### Benchmark Script
```php
// Create test with varying route counts
// Measure: time, memory, file size

$routeCounts = [10, 50, 100, 500, 1000];
foreach ($routeCounts as $count) {
    // Generate $count routes
    // Time the generation
    // Record memory peak
    // Record output size
}
```

---

## Prerequisites
- PHP profiling tools (Xdebug, Blackfire, or Clockwork)
- JavaScript bundle analysis tools
- Understanding of algorithmic complexity

## Areas to Review

### 1. Build-Time Performance (PHP)

#### Route Collection
File: `src/Collectors/DefaultRouteCollector.php`
- [ ] No N+1 queries to router
- [ ] Efficient filtering (early exit)
- [ ] Minimal reflection calls

#### Type Extraction
File: `src/Services/RouteTypeExtractor.php`
- [ ] Reflection results cached
- [ ] No repeated class loading
- [ ] Efficient attribute reading

#### Transformation
Files: `src/Transformers/*.php`
- [ ] No redundant transformations
- [ ] Circular reference detection is O(n)
- [ ] Type caching for repeated types

#### File Generation
Files: `src/Generators/*.php`, `src/Services/StubRenderer.php`
- [ ] Blade compilation cached
- [ ] Efficient string building (no repeated concatenation)
- [ ] Files written once, not incrementally

### 2. Profiling Commands
```bash
# Profile with Xdebug
php -d xdebug.mode=profile artisan trpc:generate

# Time execution
time php artisan trpc:generate

# Memory usage
php -d memory_limit=-1 artisan trpc:generate
```

### 3. Benchmark Tests
Create test scenarios:
- [ ] Small: 10 routes
- [ ] Medium: 100 routes
- [ ] Large: 500 routes
- [ ] Extra Large: 1000+ routes

Record for each:
- Execution time
- Memory peak usage
- Generated file sizes

### 4. Runtime Performance (TypeScript)

#### Bundle Size
```bash
# Check generated file sizes
ls -la resources/js/api/

# Analyze bundle impact
npx source-map-explorer dist/assets/*.js
```

#### Tree Shaking
- [ ] Unused exports eliminated
- [ ] No side effects in module scope
- [ ] Individual file imports work (`import { api } from '@/api/api'`)

#### URL Builder
File: `resources/stubs/url-builder.blade.ts`
- [ ] String interpolation efficient
- [ ] No unnecessary object creation
- [ ] Query string building optimized

#### Fetch Wrapper
File: `resources/stubs/fetch.blade.ts`
- [ ] Minimal overhead over native fetch
- [ ] No memory leaks
- [ ] Headers not recreated unnecessarily

### 5. Common Performance Issues

#### PHP Side
- [ ] Using `array_merge` in loops (use spread or array_push)
- [ ] Repeated regex compilation
- [ ] Loading unused classes
- [ ] Synchronous file operations without buffering

#### TypeScript Side
- [ ] Creating objects in hot paths
- [ ] Using `Object.keys().forEach()` instead of `for...in`
- [ ] Unnecessary array spreading
- [ ] Regex in frequently called functions

### 6. Memory Analysis
```php
// Add to diagnose memory issues
$startMemory = memory_get_usage();
// ... operation ...
$endMemory = memory_get_usage();
$peak = memory_get_peak_usage();
```

### 7. Caching Opportunities
Identify what can be cached:
- [ ] Route collection results
- [ ] Reflection data
- [ ] Compiled Blade templates
- [ ] Transformed types (across runs)

### 8. Optimization Recommendations
For each issue found, provide:
1. Current behavior
2. Impact (time/memory)
3. Proposed solution
4. Expected improvement

## Benchmarking Template
```
| Scenario    | Routes | Time   | Memory  | Files Size |
|-------------|--------|--------|---------|------------|
| Small       | 10     | Xs     | X MB    | X KB       |
| Medium      | 100    | Xs     | X MB    | X KB       |
| Large       | 500    | Xs     | X MB    | X KB       |
| Extra Large | 1000   | Xs     | X MB    | X KB       |
```

## Questions to Answer
1. Is incremental generation possible (only changed routes)?
2. Should there be a "production" mode with optimizations?
3. Is parallel processing beneficial for large codebases?

## Output
Document findings in `review/reports/15-performance-report.md`
