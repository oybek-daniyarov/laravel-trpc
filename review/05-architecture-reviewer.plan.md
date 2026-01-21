# Architecture Reviewer

## Role Overview
Review the overall architecture, pipeline design, and extensibility of the package.

## Why This Matters
Good architecture enables maintainability, testing, and future enhancements. Poor architecture creates technical debt.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Software Architecture | Advanced | SOLID principles, design patterns, modularity |
| Pipeline Pattern | Advanced | Chain of responsibility, middleware pattern |
| Dependency Injection | Advanced | Inversion of control, interface segregation |
| Laravel Pipeline | Intermediate | Laravel's Pipeline implementation |
| UML / Diagramming | Intermediate | Visualizing architecture |
| Code Review | Advanced | Identifying code smells, coupling issues |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **PHPStorm** | Class diagrams, dependency analysis |
| **dePHPend** | Dependency analysis tool |
| **PhpMetrics** | Code quality metrics |
| **Read Tool** | Read architecture files |
| **Glob Tool** | Map file structure |
| **Grep Tool** | Find cross-cutting concerns |
| **Mermaid / PlantUML** | Generate architecture diagrams |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Map the pipeline structure
Grep: "class.*Pipe|implements Pipe|handle\(" --glob="*Pipe*.php"

# Find all contracts/interfaces
Glob: "src/Contracts/*.php"

# Find dependency injection points
Grep: "__construct\(" --glob="src/**/*.php"

# Find service container usage
Grep: "app\(|resolve\(|make\(" --glob="*.php"

# Find cross-class dependencies
Grep: "use OybekDaniyarov\\\\LaravelTrpc" --glob="src/**/*.php"

# Find the pipeline orchestration
Grep: "Pipeline|->pipe\(|->through\(" --glob="*.php"

# Find payload transformations
Grep: "PipelinePayload|->payload" --glob="*Pipe*.php"

# Map class inheritance
Grep: "extends|implements" --glob="src/**/*.php"

# Find potential god classes (many dependencies)
Grep: "private.*\$|protected.*\$" --glob="src/**/*.php" -C 5
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Laravel pipeline pattern best practices 2026"
WebSearch: "PHP code generation architecture patterns"
WebSearch: "SOLID principles PHP examples"
WebSearch: "Hexagonal architecture Laravel"
WebSearch: "Chain of responsibility pattern PHP"
WebSearch: "PHP package architecture best practices"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://laravel.com/docs/12.x/helpers#pipelines"
Prompt: "Extract key information about Laravel pipeline implementation"

WebFetch: "https://refactoring.guru/design-patterns/chain-of-responsibility/php/example"
Prompt: "Extract information about chain of responsibility pattern implementation"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Laravel pipeline example"
WebSearch: "site:github.com PHP code generator architecture"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Architecture Mapping
```bash
# List all source files to map structure
Glob: "src/**/*.php"

# Count lines per file (identify large files)
Bash: find src -name "*.php" -exec wc -l {} \; | sort -rn

# Find circular dependencies
# (Use dePHPend or manual inspection)
```

---

## Files to Review
- `src/TrpcPipeline.php`
- `src/Pipes/CollectRoutesPipe.php`
- `src/Pipes/ExtractTypesPipe.php`
- `src/Pipes/TransformTypesPipe.php`
- `src/Pipes/GenerateOutputPipe.php`
- `src/Data/PipelinePayload.php`
- `src/Contracts/*.php`

## Review Checklist

### 1. Pipeline Flow
Verify the data flow:
```
Input (Config)
    ↓
CollectRoutesPipe    → Gathers routes from Laravel router
    ↓
ExtractTypesPipe     → Extracts PHP types using reflection
    ↓
TransformTypesPipe   → Converts PHP types to TypeScript types
    ↓
GenerateOutputPipe   → Renders stubs and writes files
    ↓
Output (Generated Files)
```

### 2. Pipeline Payload
- [ ] Contains all necessary data between pipes
- [ ] Immutable or clearly mutable by design
- [ ] Easy to debug/inspect intermediate state
- [ ] Typed properties (no mixed/array abuse)

### 3. Contracts/Interfaces
Review each contract:
- [ ] `Collector` - Route collection abstraction
- [ ] `Transformer` - Type transformation abstraction
- [ ] `Generator` - Output generation abstraction
- [ ] `Pipe` - Pipeline stage abstraction
- [ ] `TypeResolver` - Type resolution abstraction

### 4. Dependency Injection
- [ ] Classes depend on abstractions, not concretions
- [ ] Services are injected, not instantiated inline
- [ ] Easy to swap implementations

### 5. Extensibility Points
- [ ] Can users add custom collectors?
- [ ] Can users add custom transformers?
- [ ] Can users add custom generators?
- [ ] Can users add custom pipes?

### 6. Single Responsibility
Each class should have one job:
- [ ] Pipes only orchestrate, don't do heavy lifting
- [ ] Services handle specific domains
- [ ] Data objects are just data

### 7. Error Handling
- [ ] Exceptions are specific and meaningful
- [ ] Errors bubble up correctly
- [ ] Pipeline can be halted gracefully

### 8. Code Smells to Look For
- [ ] God classes (too many responsibilities)
- [ ] Deep nesting
- [ ] Long methods (>30 lines)
- [ ] Hardcoded values that should be config
- [ ] Tight coupling between unrelated classes

## Questions to Answer
1. Is the pipeline pattern the right choice here?
2. Should there be events/hooks for external integrations?
3. Is the architecture over-engineered or appropriately complex?

## Output
Document findings in `review/reports/05-architecture-report.md`
