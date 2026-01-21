# PHP Attributes Reviewer

## Role Overview
Review the core attribute mechanism that powers type extraction from Laravel controllers.

## Why This Matters
The `#[TypedRoute]` attribute is the primary interface developers use to declare API types. Poor design here affects every user of the package.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| PHP 8 Attributes | Advanced | Understanding of attribute syntax, targets, and repetition |
| PHP Reflection API | Advanced | ReflectionClass, ReflectionMethod, ReflectionAttribute |
| Laravel Controllers | Intermediate | Controller structure, dependency injection, route binding |
| PHPDoc/PHPStan | Intermediate | Type annotations, generics syntax |
| Design Patterns | Intermediate | Understanding of metadata patterns |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **PHPStorm / VS Code + Intelephense** | PHP IDE with attribute support |
| **PHP CS Fixer** | Code style analysis |
| **PHPStan / Psalm** | Static analysis for type checking |
| **Xdebug** | Step-through debugging of reflection |
| **Grep / Ripgrep** | Search for attribute usage patterns |
| **Read Tool** | Read source files for analysis |
| **Glob Tool** | Find all PHP files with attributes |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find all attribute definitions
Grep: "#\[Attribute" --glob="*.php"

# Find TypedRoute usage across codebase
Grep: "#\[TypedRoute" --glob="*.php"

# Find reflection usage
Grep: "ReflectionAttribute|getAttributes" --glob="*.php"

# Find attribute parameter handling
Grep: "->newInstance\(\)|->getArguments\(\)" --glob="*.php"

# Search for attribute-related tests
Grep: "TypedRoute|ApiRoute" --path="tests/"

# Find all classes using attributes
Grep: "use.*Attributes\\\\TypedRoute" --glob="*.php"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "PHP 8 attributes best practices 2026"
WebSearch: "PHP reflection attributes performance optimization"
WebSearch: "Laravel custom attributes pattern examples"
WebSearch: "PHP attribute validation patterns"
WebSearch: "PHP 8.1 attribute target constants"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://www.php.net/manual/en/language.attributes.php"
Prompt: "Extract key information about PHP attributes syntax, targets, and best practices"

WebFetch: "https://laravel.com/docs/12.x/routing"
Prompt: "Extract information about route reflection and controller inspection"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com PHP attribute reflection example"
WebSearch: "site:github.com Laravel route type extraction"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

## Files to Review
- `src/Attributes/TypedRoute.php`
- `src/Attributes/ApiRoute.php`
- `src/Services/RouteTypeExtractor.php`

## Review Checklist

### 1. Attribute Design
- [ ] Are all parameters intuitive and well-named?
- [ ] Do parameter types make sense (`class-string`, `bool`, etc.)?
- [ ] Is the attribute repeatable if needed?
- [ ] Are default values sensible?

### 2. Parameter Coverage
- [ ] `request` - body data for POST/PUT/PATCH
- [ ] `query` - URL query parameters for GET
- [ ] `response` - response data class
- [ ] `errorResponse` - custom error response type
- [ ] `isCollection` - array response flag
- [ ] `isPaginated` - paginated response flag

### 3. Reflection Usage
- [ ] Is reflection used efficiently (not repeated unnecessarily)?
- [ ] Are edge cases handled (missing attributes, invalid classes)?
- [ ] Does it work with inheritance (parent class attributes)?

### 4. Edge Cases to Test
- [ ] Controller with no `#[TypedRoute]` attribute
- [ ] Multiple attributes on same method (if supported)
- [ ] Invalid class references in parameters
- [ ] Abstract classes or interfaces as types
- [ ] Closure routes (no controller)

### 5. Developer Experience
- [ ] Are error messages clear when attribute is misconfigured?
- [ ] Is IDE autocomplete working for attribute parameters?
- [ ] Is documentation in docblocks accurate?

## Questions to Answer
1. Should `#[TypedRoute]` support method-level overrides of class-level defaults?
2. Is there a need for a `#[TypedIgnore]` attribute to skip routes?
3. Should validation rules be extractable from request classes automatically?

## Output
Document findings in `review/reports/01-php-attributes-report.md`
