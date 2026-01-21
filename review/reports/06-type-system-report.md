# Type System Review Report

**Package:** Laravel tRPC (laravel-typed-api)
**Review Date:** January 21, 2026
**Reviewer:** Type System Reviewer
**Laravel Version:** 12.x

---

## Executive Summary

This report provides a comprehensive analysis of the PHP to TypeScript type mapping system implemented in the Laravel tRPC package. The type system serves as the bridge between PHP's type system and TypeScript's static typing, enabling fully type-safe API clients. The implementation leverages modern PHP 8.x features (reflection, attributes, enums) and integrates with industry-standard tools like Spatie's `laravel-data` and `typescript-transformer`.

### Overall Assessment: **STRONG with Minor Improvements Needed**

| Category | Score | Status |
|----------|-------|--------|
| Primitive Type Mapping | 4/5 | Good |
| Complex Type Handling | 4/5 | Good |
| Nullable/Union Types | 3/5 | Needs Improvement |
| Generic Types | 4/5 | Good |
| Extensibility | 5/5 | Excellent |
| Documentation | 3/5 | Adequate |

---

## 1. Architecture Overview

### 1.1 Type System Components

The type system consists of several interconnected components:

```
                    +------------------------+
                    |   RouteTypeExtractor   |
                    |   (Static Analysis)    |
                    +------------------------+
                              |
                              v
                    +------------------------+
                    |    RouteTypeInfo       |
                    |   (Type Container)     |
                    +------------------------+
                              |
                              v
                    +------------------------+
                    | TransformerCollection  |
                    |  (Type Conversion)     |
                    +------------------------+
                              |
               +--------------+--------------+
               |              |              |
               v              v              v
        +----------+   +------------+   +-----------+
        |  Data    |   |   Enum     |   |  Carbon   |
        |Transformer|  |Transformer |   |Transformer|
        +----------+   +------------+   +-----------+
                              |
                              v
                    +------------------------+
                    | TypeScriptGenerator    |
                    |   (Output)             |
                    +------------------------+
```

### 1.2 Key Files Analyzed

| File | Purpose |
|------|---------|
| `src/Services/RouteTypeExtractor.php` | Core type extraction via reflection and static analysis |
| `src/Transformers/DataTransformer.php` | Spatie Data class to TypeScript mapping |
| `src/Transformers/EnumTransformer.php` | PHP enum to TypeScript union type mapping |
| `src/Transformers/CarbonTransformer.php` | Date/time type mapping |
| `src/Data/RouteTypeInfo.php` | Type information container |
| `src/Collections/TransformerCollection.php` | Transformer registry and lookup |

---

## 2. Primitive Type Mapping Analysis

### 2.1 Current Implementation

The package delegates primitive type mapping to Spatie's `typescript-transformer` package via the `laravel.d.ts` reference:

```typescript
// Generated routes.blade.ts
/// <reference path="./laravel.d.ts" />
```

**Spatie typescript-transformer standard mappings:**
| PHP Type | TypeScript Type |
|----------|-----------------|
| `string` | `string` |
| `int`, `integer` | `number` |
| `float`, `double` | `number` |
| `bool`, `boolean` | `boolean` |
| `array` | `Array<unknown>` or specific type |
| `object` | `object` |
| `mixed` | `unknown` |
| `null` | `null` |
| `void` | `void` |

### 2.2 Observations

**Strengths:**
- Relies on well-tested Spatie implementation for primitive mappings
- No reinvention of standard type conversions
- Automatic synchronization with Spatie's type definitions

**Concerns:**
- No explicit primitive type handling in this package's transformers
- Tight coupling to Spatie's generated `laravel.d.ts` file
- Missing fallback if Spatie types are unavailable

### 2.3 Recommendation

Consider adding a fallback primitive type resolver for standalone usage:

```php
// Suggested: src/Transformers/PrimitiveTransformer.php
final class PrimitiveTransformer implements Transformer
{
    private const MAPPINGS = [
        'string' => 'string',
        'int' => 'number',
        'integer' => 'number',
        'float' => 'number',
        'double' => 'number',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'array' => 'unknown[]',
        'object' => 'Record<string, unknown>',
        'mixed' => 'unknown',
        'null' => 'null',
        'void' => 'void',
    ];

    public function transform(mixed $value, TransformContext $context): string
    {
        return self::MAPPINGS[$value] ?? 'unknown';
    }

    public function supports(string $type): bool
    {
        return isset(self::MAPPINGS[$type]);
    }
}
```

---

## 3. Complex Type Handling

### 3.1 Spatie Data Classes

**Location:** `src/Transformers/DataTransformer.php`

```php
final class DataTransformer implements Transformer
{
    public function transform(mixed $value, TransformContext $context): string
    {
        if (! is_string($value) || ! class_exists($value)) {
            return 'unknown';
        }

        // Convert namespace to dot notation
        return str_replace('\\', '.', mb_ltrim($value, '\\'));
    }

    public function supports(string $type): bool
    {
        return class_exists($type) && is_subclass_of($type, Data::class);
    }
}
```

**Example Transformation:**
```
App\Data\UserData -> App.Data.UserData
```

**Assessment:**
- Clean namespace-to-dot conversion for TypeScript compatibility
- Proper validation of class existence
- References Spatie's generated types in `laravel.d.ts`

**Concern:** The dot notation relies on matching types in `laravel.d.ts`. If types are generated with different naming, there could be mismatches.

### 3.2 PHP Enums

**Location:** `src/Transformers/EnumTransformer.php`

```php
final class EnumTransformer implements Transformer
{
    public function transform(mixed $value, TransformContext $context): string
    {
        $cases = $value::cases();
        $backingType = (new ReflectionEnum($value))->getBackingType();

        if ($backingType === null) {
            return 'string';
        }

        $isString = $backingType->getName() === 'string';
        $values = array_map(
            fn ($case) => $isString ? "'{$case->value}'" : (string) $case->value,
            $cases
        );

        return implode(' | ', $values);
    }
}
```

**Example Transformations:**
```php
// PHP
enum Status: string {
    case Active = 'active';
    case Inactive = 'inactive';
}

// TypeScript Output
'active' | 'inactive'
```

```php
// PHP
enum Priority: int {
    case Low = 1;
    case High = 10;
}

// TypeScript Output
1 | 10
```

**Assessment: EXCELLENT**
- Properly generates TypeScript literal union types
- Handles both string and integer backed enums
- Correctly quotes string values, leaves numbers unquoted

**Edge Case Concern:** Unit enums (non-backed) default to `'string'` which may not be ideal:

```php
enum Color { case Red; case Blue; } // Returns 'string' instead of 'Red' | 'Blue'
```

### 3.3 Carbon/DateTime Types

**Location:** `src/Transformers/CarbonTransformer.php`

```php
final class CarbonTransformer implements Transformer
{
    private const array SUPPORTED_TYPES = [
        Carbon::class,
        CarbonImmutable::class,
        IlluminateCarbon::class,
        DateTimeInterface::class,
        DateTimeImmutable::class,
    ];

    public function transform(mixed $value, TransformContext $context): string
    {
        return 'string';
    }
}
```

**Assessment:**
- All date/time types correctly map to `string` (ISO 8601 format)
- Comprehensive coverage of Carbon variants
- Matches Spatie's `type_replacements` configuration pattern

**Consideration:** Some applications may prefer a more specific type:

```typescript
// Alternative: branded type for better type safety
type ISODateString = string & { readonly __brand: 'ISODateString' };
```

---

## 4. Nullable and Union Type Handling

### 4.1 Current Implementation

**Location:** `src/Services/RouteTypeExtractor.php`

The type extractor uses `ReflectionNamedType` for type checking:

```php
private function extractRequestTypeFromMethod(string $controller, string $method): ?string
{
    $reflection = new ReflectionMethod($controller, $method);

    foreach ($reflection->getParameters() as $parameter) {
        $type = $parameter->getType();

        if ($type === null || ! $type instanceof ReflectionNamedType) {
            continue;
        }
        // ...
    }
}
```

### 4.2 Analysis: Nullable Types

**Current Handling:**
```php
// RouteTypeInfo.php
public function getTypeScriptRequestType(): string
{
    return $this->requestType ?? 'void';
}

public function getTypeScriptResponseType(): string
{
    if ($this->responseType === null) {
        return 'unknown';
    }
    // ...
}
```

**Issue:** The package does not explicitly handle PHP nullable types (`?string`) at the transformer level.

**Example Gap:**
```php
// PHP Controller
public function show(?UserData $user): JsonResponse

// Current output: UserData (missing null union)
// Expected output: UserData | null
```

### 4.3 Analysis: Union Types (PHP 8.0+)

**Current Implementation:** The code only checks for `ReflectionNamedType`:

```php
if ($type === null || ! $type instanceof ReflectionNamedType) {
    continue;
}
```

This skips `ReflectionUnionType` instances entirely!

**Missing Support:**
```php
// PHP 8.0+ union type
public function getUser(int|string $id): UserData|ErrorData

// Current: Not processed (falls through)
// Expected: Should generate proper TypeScript union
```

### 4.4 Recommendations

**Priority: HIGH** - Add union type support:

```php
// Suggested enhancement to RouteTypeExtractor
private function resolveType(\ReflectionType $type): string
{
    if ($type instanceof ReflectionNamedType) {
        $typeName = $type->getName();
        $result = $this->formatTypeForTypeScript($typeName);

        return $type->allowsNull() && $typeName !== 'null'
            ? "{$result} | null"
            : $result;
    }

    if ($type instanceof ReflectionUnionType) {
        $types = array_map(
            fn($t) => $this->resolveType($t),
            $type->getTypes()
        );
        return implode(' | ', array_unique($types));
    }

    if ($type instanceof ReflectionIntersectionType) {
        $types = array_map(
            fn($t) => $this->resolveType($t),
            $type->getTypes()
        );
        return implode(' & ', $types);
    }

    return 'unknown';
}
```

---

## 5. Generic Type Handling

### 5.1 Collection and Pagination Support

**Location:** `src/Services/RouteTypeExtractor.php`

```php
private function extractDataTypeFromSurveyorType(TypeContract $type): ?array
{
    if ($type instanceof ClassType) {
        $genericTypes = $type->genericTypes();
        if (! empty($genericTypes)) {
            $isDataCollection = str_contains($className, 'DataCollection');
            $isPaginated = str_contains($className, 'PaginatedDataCollection');

            foreach ($genericTypes as $genericType) {
                if ($genericType instanceof ClassType) {
                    $genericClassName = $genericType->resolved();
                    if (is_subclass_of($genericClassName, Data::class)) {
                        return [
                            'type' => $this->formatTypeForTypeScript($genericClassName),
                            'isCollection' => $isDataCollection && ! $isPaginated,
                            'isPaginated' => $isPaginated,
                        ];
                    }
                }
            }
        }
    }
}
```

### 5.2 TypeScript Output

**Location:** `src/Data/RouteTypeInfo.php`

```php
public function getTypeScriptResponseType(): string
{
    if ($this->isPaginated) {
        return "PaginatedResponse<{$this->responseType}>";
    }

    if ($this->isCollection) {
        return "Array<{$this->responseType}>";
    }

    return $this->responseType;
}
```

**Generated Type Definitions:** (`resources/stubs/types.blade.ts`)

```typescript
export interface PaginatedResponse<T> {
    readonly data: readonly T[];
    readonly meta: PaginationMeta;
    readonly links: PaginationLinks;
}
```

### 5.3 Assessment

**Strengths:**
- Proper handling of `DataCollection<T>` and `PaginatedDataCollection<T>`
- Generic type parameter correctly extracted and applied
- TypeScript generics properly utilized in output

**Gaps Identified:**

1. **No support for Laravel's `Collection<T>`** - Only Spatie's DataCollection is handled
2. **No support for `LengthAwarePaginator<T>`** - Raw Laravel paginator not detected
3. **No nested generics** - `Collection<Collection<User>>` not supported

### 5.4 Recommendation

Add support for additional Laravel generic types:

```php
private const COLLECTION_TYPES = [
    'Illuminate\Support\Collection',
    'Illuminate\Database\Eloquent\Collection',
    'Spatie\LaravelData\DataCollection',
];

private const PAGINATOR_TYPES = [
    'Illuminate\Pagination\LengthAwarePaginator',
    'Illuminate\Pagination\Paginator',
    'Spatie\LaravelData\PaginatedDataCollection',
];
```

---

## 6. Transformer Collection System

### 6.1 Implementation

**Location:** `src/Collections/TransformerCollection.php`

```php
final class TransformerCollection
{
    /** @var array<class-string, Transformer> */
    private array $transformers = [];

    public function findForType(string $type): ?Transformer
    {
        // 1. Direct match
        if (isset($this->transformers[$type])) {
            return $this->transformers[$type];
        }

        // 2. Check inheritance
        foreach ($this->transformers as $registeredType => $transformer) {
            if (is_a($type, $registeredType, true)) {
                return $transformer;
            }
        }

        // 3. Check if transformer supports the type
        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($type)) {
                return $transformer;
            }
        }

        return null;
    }
}
```

### 6.2 Assessment: EXCELLENT

The transformer collection implements a robust three-tier lookup:

1. **Direct Match** - O(1) for registered types
2. **Inheritance Check** - Handles subclasses automatically
3. **Support Check** - Allows transformers to claim types dynamically

### 6.3 Configuration Integration

**Location:** `config/trpc.php`

```php
'transformers' => [
    // SomeType::class => CustomTransformer::class,
],

'type_replacements' => [
    Carbon\Carbon::class => 'string',
    Carbon\CarbonImmutable::class => 'string',
    Illuminate\Support\Carbon::class => 'string',
    DateTimeInterface::class => 'string',
],
```

**Extensibility Score: 5/5** - Users can easily add custom transformers for domain-specific types.

---

## 7. TypedRoute Attribute Integration

### 7.1 Type Override Mechanism

**Location:** `src/Attributes/TypedRoute.php`

```php
#[Attribute(Attribute::TARGET_METHOD)]
final class TypedRoute
{
    public function __construct(
        public ?string $request = null,
        public ?string $query = null,
        public ?string $response = null,
        public ?string $errorResponse = null,
        public bool $isCollection = false,
        public bool $isPaginated = false,
    ) {}
}
```

### 7.2 Usage in Type Extraction

```php
// RouteTypeExtractor.php
if ($attribute !== null) {
    return new RouteTypeInfo(
        requestType: $attribute->request !== null
            ? $this->formatTypeForTypeScript($attribute->request)
            : $this->extractRequestTypeFromMethod($controller, $method),
        // ... other types
    );
}
```

**Assessment:**
- Clean priority system: Attribute > Static Analysis
- Allows manual override when static analysis fails
- Supports complex scenarios (collections, pagination, error types)

---

## 8. Comparison with Industry Standards

### 8.1 Spatie typescript-transformer

| Feature | Spatie | Laravel tRPC |
|---------|--------|--------------|
| Primitive Types | Built-in | Delegates to Spatie |
| Data Classes | `@typescript` annotation | Auto-detection of `Data` subclasses |
| Enums | Built-in transformer | Custom `EnumTransformer` |
| Custom Types | Config-based replacement | Transformer collection + config |
| Generics | Partial support | `DataCollection`/`PaginatedDataCollection` |
| Union Types | Supported | **Missing** |

### 8.2 Best Practice Alignment

Based on [Spatie TypeScript Transformer documentation](https://spatie.be/docs/typescript-transformer/v2/introduction):

| Best Practice | Status | Notes |
|---------------|--------|-------|
| Avoid `any` type | Implemented | Uses `unknown` fallback |
| Generate types automatically | Implemented | Auto-runs `typescript:transform` |
| Keep backend/frontend in sync | Implemented | Single source of truth |
| Support custom transformers | Implemented | Extensible config |
| Handle nullable types | **Partial** | Needs improvement |

---

## 9. Security Considerations

### 9.1 Type Validation

The transformers include proper validation:

```php
// DataTransformer
if (! is_string($value) || ! class_exists($value)) {
    return 'unknown';
}

// EnumTransformer
if (! is_subclass_of($value, BackedEnum::class)) {
    return 'unknown';
}
```

**Assessment:** Safe fallback to `unknown` prevents type injection.

### 9.2 Reflection Safety

```php
try {
    $reflection = new ReflectionMethod($controller, $method);
    // ...
} catch (Throwable) {
    return null;
}
```

**Assessment:** Exception handling prevents crashes from invalid reflection targets.

---

## 10. Test Coverage Analysis

### 10.1 Transformer Tests

| Test File | Coverage |
|-----------|----------|
| `EnumTransformerTest.php` | String enum, int enum, edge cases |
| `DataTransformerTest.php` | Data class, namespace handling |
| `CarbonTransformerTest.php` | Carbon, CarbonImmutable, DateTime |
| `TransformerCollectionTest.php` | Collection operations |

### 10.2 Test Quality

```php
// Example: EnumTransformerTest.php
it('transforms string-backed enum to union type', function () {
    $result = $this->transformer->transform(TestStringEnum::class, $this->context);
    expect($result)->toBe("'active' | 'inactive' | 'pending'");
});

it('does not wrap int values in quotes', function () {
    $result = $this->transformer->transform(TestIntEnum::class, $this->context);
    expect($result)->not->toContain("'1'")
        ->and($result)->toContain('1');
});
```

**Assessment:** Good coverage of happy paths and edge cases.

### 10.3 Missing Test Scenarios

1. Union type handling (`string|int`)
2. Nullable type handling (`?string`)
3. Nested generics
4. Invalid class names with special characters
5. Large enum transformation (100+ cases)

---

## 11. Recommendations Summary

### Priority: HIGH

1. **Add Union Type Support**
   - Handle `ReflectionUnionType` in `RouteTypeExtractor`
   - Generate proper TypeScript union types

2. **Fix Nullable Type Handling**
   - Check `allowsNull()` on reflection types
   - Append `| null` to TypeScript output

### Priority: MEDIUM

3. **Add Primitive Type Fallback**
   - Create `PrimitiveTransformer` for standalone usage
   - Reduce tight coupling to Spatie

4. **Extend Generic Type Support**
   - Support Laravel's native `Collection<T>`
   - Support `LengthAwarePaginator<T>`

5. **Improve Unit Enum Handling**
   - Generate `'CaseName'` literals instead of `string`

### Priority: LOW

6. **Add Intersection Type Support** (PHP 8.1+)
   - Handle `ReflectionIntersectionType`

7. **Optional Branded Types**
   - Consider `ISODateString` branded type for dates

---

## 12. Conclusion

The Laravel tRPC package implements a solid type system that successfully bridges PHP and TypeScript types for the common cases. The architecture is clean, extensible, and well-integrated with Spatie's ecosystem.

**Key Strengths:**
- Excellent transformer architecture with three-tier lookup
- Proper enum handling with literal union types
- Good integration with Spatie Data classes
- Extensible configuration system

**Areas for Improvement:**
- Union type support is missing (PHP 8.0+ feature)
- Nullable type handling needs attention
- Generic type support could be broader

The type system provides a strong foundation for type-safe API clients, with the main gaps being in modern PHP type features (unions, nullables) that should be addressed to ensure complete TypeScript representation of PHP's type system.

---

## References

- [Spatie TypeScript Transformer Documentation](https://spatie.be/docs/typescript-transformer/v2/introduction)
- [Spatie TypeScript Transformer GitHub](https://github.com/spatie/typescript-transformer)
- [Spatie Laravel TypeScript Transformer](https://github.com/spatie/laravel-typescript-transformer)
- [PHP Reflection API](https://www.php.net/manual/en/book.reflection.php)
- [TypeScript Handbook - Utility Types](https://www.typescriptlang.org/docs/handbook/utility-types.html)
