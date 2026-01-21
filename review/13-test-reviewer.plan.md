# Test Reviewer

## Role Overview
Review the test suite for coverage, quality, and reliability.

## Why This Matters
Tests ensure the package works correctly and prevent regressions. Good tests enable confident refactoring.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Pest PHP | Advanced | Test syntax, datasets, expectations |
| PHPUnit | Intermediate | Underlying test framework |
| Test Design | Advanced | Unit vs integration, test doubles |
| Mocking | Advanced | Mockery, PHPUnit mocks |
| Code Coverage | Intermediate | Line, branch, path coverage |
| TDD/BDD | Intermediate | Test-first development patterns |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Pest PHP** | Run tests |
| **PHPUnit** | Test framework |
| **Xdebug** | Code coverage generation |
| **PCOV** | Faster coverage alternative |
| **Read Tool** | Read test files |
| **Grep Tool** | Search test patterns |
| **Bash Tool** | Run test commands |
| **Infection PHP** | Mutation testing |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find all test files
Glob: "tests/**/*Test.php"

# Find test setup
Grep: "setUp\(\)|beforeEach\(" --glob="tests/**/*.php"

# Find test cases
Grep: "it\(|test\(|function test" --glob="tests/**/*.php"

# Find assertions
Grep: "expect\(|assert|toBe|toEqual|toHave" --glob="tests/**/*.php"

# Find mocks
Grep: "mock\(|Mockery|createMock|getMockBuilder" --glob="tests/**/*.php"

# Find datasets
Grep: "dataset\(|with\(\[" --glob="tests/**/*.php"

# Find skipped tests
Grep: "skip\(|markTestSkipped" --glob="tests/**/*.php"

# Find test helpers
Read: tests/TestCase.php
Read: tests/Pest.php

# Count tests per file
Bash: grep -c "it\(\\|test\(" tests/**/*.php

# Find fixture data
Glob: "tests/Fixtures/**/*"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Pest PHP best practices 2026"
WebSearch: "PHP test coverage strategies"
WebSearch: "Laravel package testing patterns"
WebSearch: "Mutation testing PHP Infection"
WebSearch: "Test doubles vs mocks vs stubs PHP"
WebSearch: "Pest PHP datasets examples"
WebSearch: "PHPUnit architecture testing"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://pestphp.com/docs/writing-tests"
Prompt: "Extract key information about Pest test syntax and best practices"

WebFetch: "https://pestphp.com/docs/expectations"
Prompt: "Extract information about Pest expectations and assertions"

WebFetch: "https://laravel.com/docs/12.x/packages#testing"
Prompt: "Extract information about Laravel package testing"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Pest PHP Laravel package testing"
WebSearch: "site:github.com spatie Laravel package test structure"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Testing Commands
```bash
# Run all tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run with coverage (minimum threshold)
./vendor/bin/pest --coverage --min=80

# Run specific test file
./vendor/bin/pest tests/Unit/Transformers/DataTransformerTest.php

# Run tests matching pattern
./vendor/bin/pest --filter="type extraction"

# Profile slow tests
./vendor/bin/pest --profile

# Run in parallel
./vendor/bin/pest --parallel

# Generate coverage report
./vendor/bin/pest --coverage-html=coverage-report
```

### Coverage Analysis
```bash
# Check coverage gaps
./vendor/bin/pest --coverage

# Areas to verify coverage:
# - src/Attributes/
# - src/Collectors/
# - src/Transformers/
# - src/Pipes/
# - src/Generators/
# - src/Services/
```

---

## Files to Review
- `tests/Pest.php`
- `tests/TestCase.php`
- `tests/Unit/**/*.php`
- `tests/Feature/**/*.php`

## Review Checklist

### 1. Test Coverage
Run coverage report:
```bash
./vendor/bin/pest --coverage
```

Target coverage areas:
- [ ] Attributes (`TypedRoute`, `ApiRoute`)
- [ ] Collectors (`DefaultRouteCollector`)
- [ ] Transformers (`DataTransformer`, `EnumTransformer`, `CarbonTransformer`)
- [ ] Pipes (`CollectRoutes`, `ExtractTypes`, `TransformTypes`, `GenerateOutput`)
- [ ] Generators (`TypeScriptGenerator`, `PostmanGenerator`)
- [ ] Data classes (`RouteData`, `RouteTypeInfo`, `PipelinePayload`)
- [ ] Services (`RouteTypeExtractor`, `StubRenderer`)
- [ ] Collections (`RouteCollection`, `TransformerCollection`, `GeneratorCollection`)

### 2. Test Types Present
- [ ] Unit tests (isolated class testing)
- [ ] Integration tests (multiple classes together)
- [ ] Feature tests (full pipeline execution)
- [ ] Snapshot tests (generated output verification)

### 3. Test Quality Checklist
For each test file:
- [ ] Tests are focused (one assertion per test ideally)
- [ ] Test names describe behavior (`it('extracts response type from attribute')`)
- [ ] Arrange-Act-Assert pattern followed
- [ ] No test interdependencies
- [ ] Mocks used appropriately (not over-mocked)

### 4. Edge Cases Covered
- [ ] Empty inputs (no routes, no types)
- [ ] Invalid inputs (malformed data)
- [ ] Boundary conditions (max limits)
- [ ] Null/undefined handling
- [ ] Error conditions

### 5. Specific Areas to Verify

#### Attribute Tests
```php
// Test cases needed:
- Attribute with all parameters
- Attribute with minimal parameters
- Attribute on method without return type
- Multiple attributes (if supported)
```

#### Transformer Tests
```php
// Test cases needed:
- Each PHP type → TypeScript mapping
- Nullable types
- Union types
- Nested Data classes
- Circular references
```

#### Route Collection Tests
```php
// Test cases needed:
- Controller routes
- Closure routes
- Resource routes
- Named routes
- Routes with parameters
- Excluded routes
```

#### Pipeline Tests
```php
// Test cases needed:
- Full pipeline execution
- Each pipe in isolation
- Payload transformation at each stage
- Error handling mid-pipeline
```

### 6. Test Fixtures
- [ ] Fixture controllers exist for testing
- [ ] Fixture Data classes exist
- [ ] Fixtures are minimal but complete
- [ ] Fixtures are documented

### 7. Test Performance
```bash
./vendor/bin/pest --profile
```
- [ ] No slow tests (>1s)
- [ ] No database-dependent tests (or properly isolated)
- [ ] Tests can run in parallel

### 8. CI/CD Integration
- [ ] Tests run in GitHub Actions
- [ ] Tests run on multiple PHP versions
- [ ] Tests run on multiple Laravel versions
- [ ] Coverage reported

### 9. Missing Tests
Identify gaps:
- [ ] List untested public methods
- [ ] List untested edge cases
- [ ] List untested error paths

## Test Commands
```bash
# Run all tests
./vendor/bin/pest

# Run with coverage
./vendor/bin/pest --coverage

# Run specific test file
./vendor/bin/pest tests/Unit/Transformers/DataTransformerTest.php

# Run tests matching name
./vendor/bin/pest --filter="extracts response type"

# Profile slow tests
./vendor/bin/pest --profile
```

## Questions to Answer
1. Is mutation testing used (Infection PHP)?
2. Are there architectural tests (class dependencies)?
3. Should there be browser tests for generated TypeScript?

## Output
Document findings in `review/reports/13-test-report.md`
