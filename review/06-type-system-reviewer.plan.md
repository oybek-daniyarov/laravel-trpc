# Type System Reviewer

## Role Overview
Review how PHP types are extracted, transformed, and mapped to TypeScript types.

## Why This Matters
Type accuracy is the core value proposition. Incorrect types defeat the purpose of the package.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| PHP Type System | Expert | Union types, intersection, nullable, generics |
| TypeScript Type System | Expert | Generics, mapped types, conditional types |
| PHP Reflection | Advanced | ReflectionType, ReflectionNamedType, ReflectionUnionType |
| Spatie Laravel Data | Advanced | Data class structure, casts, transformers |
| Spatie TypeScript Transformer | Advanced | Type transformation pipeline |
| PHP Enums | Intermediate | Backed enums, unit enums |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **PHPStan / Psalm** | Static type analysis |
| **TypeScript Compiler (tsc)** | Validate generated types |
| **Read Tool** | Read transformer source files |
| **Grep Tool** | Search for type handling patterns |
| **PHP REPL (Tinker)** | Test reflection interactively |
| **VS Code + TypeScript** | Test generated type inference |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find type extraction logic
Grep: "ReflectionType|getReturnType\(\)|getType\(\)" --glob="*.php"

# Find transformer implementations
Grep: "class.*Transformer|implements Transformer|transform\(" --glob="*Transformer*.php"

# Find type mapping definitions
Grep: "string|int|bool|float|array|null|void|mixed" --glob="*Transformer*.php"

# Find nullable handling
Grep: "allowsNull\(\)|isNullable|\?|null" --glob="*Transformer*.php"

# Find union type handling
Grep: "ReflectionUnionType|getTypes\(\)|union" --glob="*.php"

# Find generic type handling
Grep: "Collection|DataCollection|Paginator|array<" --glob="*.php"

# Find enum handling
Grep: "enum|BackedEnum|UnitEnum|cases\(\)" --glob="*Transformer*.php"

# Find Carbon/DateTime handling
Grep: "Carbon|DateTime|DateTimeInterface" --glob="*Transformer*.php"

# Find Spatie Data integration
Grep: "Data::class|extends Data|LaravelData" --glob="*.php"

# Check RouteTypeInfo structure
Read: src/Data/RouteTypeInfo.php
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "PHP reflection types complete guide 2026"
WebSearch: "PHP to TypeScript type mapping best practices"
WebSearch: "Spatie typescript transformer custom transformer"
WebSearch: "PHP 8.1 intersection types reflection"
WebSearch: "Spatie Laravel Data nested types"
WebSearch: "PHP union types reflection API"
WebSearch: "TypeScript generics from PHP"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://www.php.net/manual/en/language.types.declarations.php"
Prompt: "Extract key information about PHP type declarations and reflection"

WebFetch: "https://spatie.be/docs/laravel-data/v4/introduction"
Prompt: "Extract information about Laravel Data type handling and transformations"

WebFetch: "https://spatie.be/docs/typescript-transformer/v2/introduction"
Prompt: "Extract information about TypeScript transformer configuration and custom transformers"

WebFetch: "https://www.typescriptlang.org/docs/handbook/2/generics.html"
Prompt: "Extract information about TypeScript generics best practices"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com spatie typescript-transformer custom transformer"
WebSearch: "site:github.com PHP TypeScript type generation"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Type Mapping Verification
```bash
# Generate and check TypeScript output
php artisan trpc:generate
tsc --noEmit resources/js/api/types.ts

# Check for 'any' types in output
Grep: ": any" --glob="resources/js/api/*.ts"
```

---

## Files to Review
- `src/Services/RouteTypeExtractor.php`
- `src/Transformers/DataTransformer.php`
- `src/Transformers/EnumTransformer.php`
- `src/Transformers/CarbonTransformer.php`
- `src/Collections/TransformerCollection.php`
- `src/Data/RouteTypeInfo.php`
- `src/Contracts/TypeResolver.php`

## Review Checklist

### 1. Primitive Type Mapping
Verify PHP → TypeScript mappings:
| PHP Type | TypeScript Type |
|----------|-----------------|
| `string` | `string` |
| `int` | `number` |
| `float` | `number` |
| `bool` | `boolean` |
| `array` | `unknown[]` or typed array |
| `null` | `null` |
| `mixed` | `unknown` |
| `void` | `void` |

### 2. Complex Type Mapping
- [ ] `Spatie\LaravelData\Data` subclasses → interfaces
- [ ] PHP Enums (backed) → TypeScript enums or union types
- [ ] `Carbon` / `DateTime` → `string` (ISO format)
- [ ] `Collection` → `Array<T>`
- [ ] `LengthAwarePaginator` → `PaginatedResponse<T>`

### 3. Nullable and Union Types
- [ ] `?string` → `string | null`
- [ ] `string|int` → `string | number`
- [ ] `string|null` → `string | null`

### 4. Generic Types
- [ ] `Collection<User>` → `Array<User>`
- [ ] `DataCollection<UserData>` → `Array<UserData>`

### 5. Nested Types
- [ ] Nested Data classes resolved correctly
- [ ] Circular references handled (prevent infinite loops)
- [ ] Deep nesting works (3+ levels)

### 6. Edge Cases
- [ ] No return type declared → `unknown` or inferred
- [ ] Return type is `Response` or `JsonResponse`
- [ ] Multiple return types (union)
- [ ] Intersection types
- [ ] `never` return type

### 7. Transformer Priority
- [ ] Custom transformers can override defaults
- [ ] Transformer order is deterministic
- [ ] Fallback transformer exists for unknown types

### 8. Test Matrix
```php
// Test each of these return types
public function a(): string {}
public function b(): int {}
public function c(): array {}
public function d(): ?UserData {}
public function e(): UserData|ErrorData {}
public function f(): Collection {}
public function g(): LengthAwarePaginator {}
public function h(): UserStatus {} // Enum
public function i(): Carbon {}
public function j() {} // No return type
```

## Questions to Answer
1. How are recursive/self-referencing types handled?
2. Should `stdClass` or anonymous classes be supported?
3. Is there a way to add custom type mappings?

## Output
Document findings in `review/reports/06-type-system-report.md`
