# Architecture Review Report: Laravel tRPC Package

**Review Date:** January 21, 2026
**Laravel Version:** 12.x
**Reviewer:** Architecture Reviewer
**Package:** `oybek-daniyarov/laravel-trpc`

---

## Executive Summary

The Laravel tRPC package demonstrates a well-architected pipeline-based system for generating type-safe API clients from Laravel routes. The architecture follows modern PHP best practices and adheres to SOLID principles effectively. The pipeline design leverages Laravel's native Pipeline class while providing excellent extensibility through well-defined contracts.

**Overall Assessment:** Strong architectural foundation with minor areas for improvement.

| Category | Score | Notes |
|----------|-------|-------|
| Pipeline Flow | 9/10 | Clean data flow with immutable payload |
| Contracts/Interfaces | 8/10 | Well-designed, some minor improvements possible |
| Dependency Injection | 9/10 | Excellent use of Laravel's container |
| Extensibility | 9/10 | Highly extensible via configuration |
| Single Responsibility | 8/10 | Generally well-separated concerns |

---

## 1. Pipeline Flow Analysis

### 1.1 Pipeline Architecture Overview

The package implements the Chain of Responsibility pattern through Laravel's native `Pipeline` class, orchestrated by `TrpcPipeline`:

```
TrpcPipeline::create()
    -> CollectRoutesPipe    (Collect routes from Laravel)
    -> ExtractTypesPipe     (Extract type information using Surveyor)
    -> TransformTypesPipe   (Apply type transformations)
    -> GenerateOutputPipe   (Generate TypeScript/Postman output)
    -> GeneratorResult
```

**File:** `/Users/oybek/Herd/typed-api/src/TrpcPipeline.php`

### 1.2 Data Flow Correctness

**Strengths:**

1. **Immutable Configuration:** `TrpcConfig` is readonly with cached values, preventing accidental mutation.

2. **Payload as Data Carrier:** `PipelinePayload` carries all necessary state through the pipeline:
   ```php
   final class PipelinePayload
   {
       public function __construct(
           public readonly TrpcConfig $config,
           public RouteCollection $routes,
           public GeneratorResult $result,
           public array $metadata = [],
       ) {}
   }
   ```

3. **Metadata System:** The `withMetadata()` pattern allows pipes to communicate without tight coupling:
   ```php
   $payload->withMetadata('routeTypes', $routeTypes);
   $payload->withMetadata('transformers', $transformers);
   ```

4. **Sequential Processing:** Each pipe correctly passes the payload to `$next()`:
   ```php
   public function handle(PipelinePayload $payload, Closure $next): PipelinePayload
   {
       // Process...
       return $next($payload);
   }
   ```

**Concerns:**

1. **Mutable Payload Properties:** While `config` is readonly, `routes` and `result` are mutable. This could lead to unexpected side effects if a pipe modifies them incorrectly.

2. **ExtractTypesPipe Redundancy:** The `ExtractTypesPipe` extracts route types, but `CollectRoutesPipe` also uses `RouteTypeExtractor`. This means types are extracted twice:

   - In `CollectRoutesPipe` (line 40): `$this->routeTypes = $this->typeExtractor->extractRouteTypes();`
   - In `ExtractTypesPipe` (line 26): `$this->typeExtractor->extractRouteTypes();`

**Recommendation:** Consider consolidating type extraction to a single location or caching the results.

### 1.3 Pipeline Customization

The `TrpcPipeline` class provides excellent fluent methods for customization:

```php
TrpcPipeline::create()
    ->through(CustomPipe::class)           // Add to end
    ->firstThrough(LoggingPipe::class)     // Add to beginning
    ->before(ExtractTypesPipe::class, CachePipe::class)
    ->after(CollectRoutesPipe::class, FilterPipe::class)
    ->replace(TypeScriptGenerator::class, CustomGenerator::class)
    ->without(PostmanGenerator::class)
    ->process($config);
```

This follows the [Laravel Pipeline best practices](https://medium.com/insiderengineering/understanding-laravel-pipelines-9717f5d58286) of keeping stages focused and allowing for easy extension.

---

## 2. Contracts/Interfaces Analysis

### 2.1 Contract Overview

The package defines five core contracts in `/Users/oybek/Herd/typed-api/src/Contracts/`:

| Contract | Purpose | Method(s) |
|----------|---------|-----------|
| `Pipe` | Pipeline stage processor | `handle(PipelinePayload, Closure): PipelinePayload` |
| `Collector` | Route discovery | `collect(): RouteCollection` |
| `Transformer` | PHP-to-TypeScript type conversion | `transform(mixed, TransformContext): string`, `supports(string): bool` |
| `Generator` | Output file generation | `generate(RouteCollection, GeneratorContext): GeneratorResult` |
| `TypeResolver` | Reflection type resolution | `resolve(?ReflectionType, ResolverContext): string` |

### 2.2 Interface Design Quality

**Strengths:**

1. **Single Method Interfaces:** Most contracts follow the [Interface Segregation Principle](https://medium.com/@tech.sajjad/understanding-solid-principles-in-php-building-robust-and-maintainable-code-3bb81ee96df0) with focused, single-purpose methods.

2. **Type-Safe Signatures:** All contracts use strict typing with PHP 8.x features:
   ```php
   public function handle(PipelinePayload $payload, Closure $next): PipelinePayload;
   ```

3. **Context Objects:** Contracts receive context objects (`TransformContext`, `GeneratorContext`, `ResolverContext`) rather than raw arrays, providing type safety and IDE support.

**Concerns:**

1. **Transformer Interface Dual Responsibility:** The `Transformer` interface has two methods (`transform` and `supports`), which could be separated:
   ```php
   interface Transformer
   {
       public function transform(mixed $value, TransformContext $context): string;
       public function supports(string $type): bool;
   }
   ```

   Consider splitting into `Transformer` and `TypeChecker` interfaces for stricter ISP adherence.

2. **Missing Return Type Documentation:** The `Pipe` interface closure type hint could be more explicit:
   ```php
   // Current
   @param  Closure(PipelinePayload): PipelinePayload  $next

   // Could be a named type
   @param  \Closure(PipelinePayload): PipelinePayload  $next
   ```

3. **No Base Exception Interface:** The package doesn't define custom exception contracts, making error handling less predictable.

### 2.3 Recommended Contract Improvements

```php
// Consider adding a configuration-aware contract
interface Configurable
{
    public function configure(TrpcConfig $config): void;
}

// Consider adding a result contract
interface GeneratorResultContract
{
    public function files(): array;
    public function merge(self $other): self;
    public function isEmpty(): bool;
}
```

---

## 3. Dependency Injection Analysis

### 3.1 Container Usage

The package demonstrates excellent use of Laravel's service container:

**Service Provider Registration (`TrpcServiceProvider.php`):**
```php
public function register(): void
{
    $this->app->singleton(TrpcConfig::class, function () {
        return TrpcConfig::fromConfig();
    });

    $this->app->singleton(StubRenderer::class);
    $this->app->singleton(RouteTypeExtractor::class);
}
```

**Pipe Resolution via Container:**
```php
// CollectRoutesPipe (line 25)
$collector = app($collectorClass, ['config' => $payload->config]);

// TransformTypesPipe (line 37)
$transformer = app($transformerClass);

// GenerateOutputPipe (line 49)
$generator = app($generatorClass, ['config' => $payload->config]);
```

### 3.2 Dependency Inversion Compliance

**Strengths:**

1. **High-Level Depends on Abstractions:** Pipes depend on contracts, not concrete implementations:
   ```php
   // CollectRoutesPipe depends on Collector contract
   foreach ($collectors as $collectorClass) {
       /** @var Collector $collector */
       $collector = app($collectorClass, ['config' => $payload->config]);
   }
   ```

2. **Constructor Injection:** Services properly use constructor injection:
   ```php
   // RouteTypeExtractor
   public function __construct(
       private readonly Ranger $ranger,
       private readonly Analyzer $analyzer,
   ) {}

   // TypeScriptGenerator
   public function __construct(
       private readonly TrpcConfig $config,
       private readonly StubRenderer $stubRenderer,
   ) {}
   ```

3. **Configuration-Driven Resolution:** Implementations are resolved from configuration, not hardcoded:
   ```php
   // TrpcConfig provides class names
   public function getCollectors(): array
   {
       return $this->get('collectors', [
           Collectors\DefaultRouteCollector::class,
       ]);
   }
   ```

**Concerns:**

1. **Service Locator Pattern:** The use of `app()` helper in pipes is a form of service locator. While acceptable in Laravel context, it reduces testability:
   ```php
   // Current approach
   $collector = app($collectorClass, ['config' => $payload->config]);

   // Alternative: Inject a factory
   $collector = $this->collectorFactory->make($collectorClass, $payload->config);
   ```

2. **`ExtractTypesPipe` Constructor Injection:** This pipe receives `RouteTypeExtractor` via constructor, which is good, but inconsistent with `CollectRoutesPipe` which resolves collectors dynamically.

### 3.3 Dependency Graph

```
TrpcServiceProvider
    |
    +-> TrpcConfig (singleton)
    +-> StubRenderer (singleton)
    +-> RouteTypeExtractor (singleton)
            |
            +-> Laravel\Ranger\Ranger
            +-> Laravel\Surveyor\Analyzer\Analyzer

TrpcPipeline
    |
    +-> CollectRoutesPipe
    |       +-> Collector implementations (from config)
    |       +-> RouteTypeExtractor
    |
    +-> ExtractTypesPipe
    |       +-> RouteTypeExtractor
    |
    +-> TransformTypesPipe
    |       +-> Transformer implementations (from config)
    |
    +-> GenerateOutputPipe
            +-> Generator implementations (from config)
            +-> StubRenderer
```

---

## 4. Extensibility Analysis

### 4.1 Custom Collectors

Users can add custom route collectors by implementing `Collector`:

```php
// config/trpc.php
'collectors' => [
    DefaultRouteCollector::class,
    App\Support\Trpc\CustomApiCollector::class,
],

// Custom implementation
class CustomApiCollector implements Collector
{
    public function collect(): RouteCollection
    {
        // Custom logic
        return new RouteCollection($routes);
    }
}
```

**Assessment:** Excellent extensibility via configuration-driven collector loading.

### 4.2 Custom Transformers

Type transformers can be registered per PHP class:

```php
// config/trpc.php
'transformers' => [
    Carbon::class => CarbonTransformer::class,
    App\Data\CustomData::class => App\Transformers\CustomTransformer::class,
],
```

The `TransformerCollection` supports:
- Direct type matching
- Inheritance-based matching (`is_a()` check)
- Dynamic `supports()` checking

**Assessment:** Highly flexible transformation system with multiple matching strategies.

### 4.3 Custom Generators

New output generators can be added:

```php
// config/trpc.php
'generators' => [
    'typescript' => TypeScriptGenerator::class,
    'postman' => PostmanGenerator::class,
    'openapi' => App\Generators\OpenApiGenerator::class,
],
```

**Assessment:** Clean generator registry pattern.

### 4.4 Custom Pipes

The pipeline can be modified at runtime:

```php
TrpcPipeline::create()
    ->before(GenerateOutputPipe::class, ValidationPipe::class)
    ->after(CollectRoutesPipe::class, CachingPipe::class)
    ->process($config);
```

**Assessment:** Excellent pipeline customization following the [Open/Closed Principle](https://accesto.com/blog/solid-php-solid-principles-in-php/).

### 4.5 Stub Customization

View stubs can be published and customized:

```php
// TrpcServiceProvider
$this->publishes([
    __DIR__.'/../resources/stubs' => resource_path('views/vendor/trpc'),
], 'trpc-stubs');
```

**Assessment:** Standard Laravel pattern for template customization.

---

## 5. Single Responsibility Analysis

### 5.1 Pipe Responsibilities

| Pipe | Responsibility | SRP Compliance |
|------|---------------|----------------|
| `CollectRoutesPipe` | Orchestrate route collection from multiple collectors | Good |
| `ExtractTypesPipe` | Extract type information from routes | Good |
| `TransformTypesPipe` | Build transformer collection | Partial* |
| `GenerateOutputPipe` | Orchestrate output generation | Good |

*`TransformTypesPipe` only builds the collection but doesn't apply transformations. The actual transformation happens in generators, which is fine but the pipe name is misleading.

### 5.2 Service Responsibilities

| Service | Responsibility | SRP Compliance |
|---------|---------------|----------------|
| `RouteTypeExtractor` | Extract PHP types via reflection/Surveyor | Good |
| `StubRenderer` | Render Blade templates | Excellent |
| `TrpcConfig` | Provide typed configuration access | Excellent |
| `DefaultRouteCollector` | Collect routes from Laravel router | Partial* |

*`DefaultRouteCollector` has multiple responsibilities:
- Route filtering logic
- Route name generation
- Group determination
- Type extraction coordination

Consider breaking into:
- `RouteFilter` - shouldCollectRoute logic
- `RouteNamer` - generateRouteName logic
- `RouteGrouper` - determineRouteGroup logic

### 5.3 Collection Responsibilities

| Collection | Responsibility | SRP Compliance |
|------------|---------------|----------------|
| `RouteCollection` | Store and query routes | Excellent |
| `TransformerCollection` | Store and find transformers | Excellent |
| `GeneratorCollection` | Store and retrieve generators | Excellent |

### 5.4 Context Object Responsibilities

All context objects are excellent examples of SRP - they only carry context data:

- `GeneratorContext`: Output path, config, generator-specific flags
- `TransformContext`: Route data, config, metadata
- `ResolverContext`: Type resolution settings, nullability, class context

---

## 6. Identified Issues and Recommendations

### 6.1 High Priority

| Issue | Location | Recommendation |
|-------|----------|----------------|
| Duplicate type extraction | `CollectRoutesPipe` + `ExtractTypesPipe` | Consolidate to single extraction, cache in payload metadata |
| Mutable payload properties | `PipelinePayload` | Consider immutable pattern with `with*` methods |

### 6.2 Medium Priority

| Issue | Location | Recommendation |
|-------|----------|----------------|
| Misleading pipe name | `TransformTypesPipe` | Rename to `PrepareTransformersPipe` |
| Large collector class | `DefaultRouteCollector` | Extract filtering, naming, grouping to separate services |
| No custom exceptions | Throughout | Add `TrpcException` hierarchy |

### 6.3 Low Priority

| Issue | Location | Recommendation |
|-------|----------|----------------|
| Service locator usage | Various pipes | Consider factory injection pattern |
| Transformer dual responsibility | `Transformer` contract | Split into `Transformer` + `TypeChecker` |
| Missing null checks | `GenerateOutputPipe::buildContext()` | Add defensive checks for metadata access |

---

## 7. Architecture Diagram

```
+------------------+     +------------------+
|   TrpcConfig     |---->|  TrpcPipeline    |
| (Configuration)  |     | (Orchestrator)   |
+------------------+     +--------+---------+
                                  |
                    +-------------+-------------+
                    |             |             |
              +-----v-----+ +-----v-----+ +-----v-----+
              |  Collect  | |  Extract  | | Transform |
              |  Routes   | |  Types    | |  Types    |
              |  Pipe     | |  Pipe     | |  Pipe     |
              +-----+-----+ +-----+-----+ +-----+-----+
                    |             |             |
              +-----v-----+ +-----v-----+ +-----v-----+
              | Collector | |  Route    | |Transformer|
              | Contract  | |  Type     | | Contract  |
              |           | | Extractor | |           |
              +-----------+ +-----------+ +-----------+
                    |
              +-----v-----+
              |  Generate |
              |  Output   |
              |  Pipe     |
              +-----+-----+
                    |
              +-----v-----+
              | Generator |
              | Contract  |
              +-----------+
                    |
        +-----------+-----------+
        |                       |
+-------v-------+       +-------v-------+
|  TypeScript   |       |   Postman     |
|  Generator    |       |   Generator   |
+---------------+       +---------------+
```

---

## 8. Best Practices Compliance

### Laravel Pipeline Best Practices (2026)

| Practice | Status | Evidence |
|----------|--------|----------|
| Keep stages simple and focused | Compliant | Each pipe has single transformation |
| Use type-hinting for consistency | Compliant | Strict types throughout |
| Descriptive class names | Mostly | Some names could be clearer |
| Avoid nested pipelines | Compliant | Flat pipeline structure |
| Break down complex operations | Partially | Some services need decomposition |

### SOLID Principles

| Principle | Score | Notes |
|-----------|-------|-------|
| **S**ingle Responsibility | 8/10 | Minor violations in collector |
| **O**pen/Closed | 9/10 | Highly extensible via config |
| **L**iskov Substitution | 10/10 | Contracts properly implemented |
| **I**nterface Segregation | 8/10 | Transformer could be split |
| **D**ependency Inversion | 9/10 | High-level depends on abstractions |

---

## 9. Conclusion

The Laravel tRPC package demonstrates strong architectural design with a well-implemented pipeline pattern, clean contracts, and excellent extensibility. The codebase follows SOLID principles effectively and leverages Laravel's service container appropriately.

### Key Strengths
1. Clean pipeline flow with metadata-based communication
2. Configuration-driven extensibility for collectors, transformers, and generators
3. Strong type safety with readonly classes and context objects
4. Fluent pipeline customization API

### Areas for Improvement
1. Consolidate duplicate type extraction logic
2. Decompose `DefaultRouteCollector` into smaller services
3. Add custom exception hierarchy
4. Consider immutable payload pattern

### Final Verdict

The architecture is **production-ready** and follows industry best practices. The identified issues are minor and can be addressed incrementally without major refactoring.

---

## References

- [Understanding Laravel Pipelines - Medium](https://medium.com/insiderengineering/understanding-laravel-pipelines-9717f5d58286)
- [Laravel Best Practices for 2026 - Smart Logic](https://smartlogiceg.com/en/post/laravel-best-practices-for-2026)
- [SOLID Principles in PHP - Accesto Blog](https://accesto.com/blog/solid-php-solid-principles-in-php/)
- [Understanding SOLID Principles in PHP - Medium](https://medium.com/@tech.sajjad/understanding-solid-principles-in-php-building-robust-and-maintainable-code-3bb81ee96df0)
- [SOLID Principles in PHP - Laracasts](https://laracasts.com/series/solid-principles-in-php)
- [Laravel Pipelines Guide - WP WebInfoTech](https://wpwebinfotech.com/blog/laravel-pipelines/)
