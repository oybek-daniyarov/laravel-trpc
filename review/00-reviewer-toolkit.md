# Reviewer Toolkit - Universal Access Guide

This document defines the universal tools, skills, and knowledge extension methods available to ALL reviewers.

---

## Universal Tool Access

Every reviewer has access to these tools:

### File Operations
| Tool | Command | Purpose |
|------|---------|---------|
| **Read** | `Read: <file_path>` | Read any file in the codebase |
| **Glob** | `Glob: "<pattern>"` | Find files by pattern (e.g., `"**/*.php"`) |
| **Grep** | `Grep: "<pattern>" --glob="*.php"` | Search content with regex |

### Code Execution
| Tool | Command | Purpose |
|------|---------|---------|
| **Bash** | `Bash: <command>` | Run shell commands |
| **PHP Artisan** | `php artisan <command>` | Laravel commands |
| **Composer** | `composer <command>` | PHP dependencies |
| **NPM/PNPM** | `npm <command>` | JS dependencies |

### Web Research
| Tool | Command | Purpose |
|------|---------|---------|
| **WebSearch** | `WebSearch: "<query>"` | Search the web for current information |
| **WebFetch** | `WebFetch: "<url>"` | Fetch and analyze web page content |

### Task Management
| Tool | Command | Purpose |
|------|---------|---------|
| **TodoWrite** | Track review progress | Manage checklist items |
| **Task** | Launch sub-agents | Delegate exploration tasks |

---

## Knowledge Extension Protocol

**IMPORTANT:** Before writing any review report, reviewers MUST extend their knowledge using these steps:

### Step 1: Document Search (Internal)
Search the codebase for patterns, implementations, and tests:

```bash
# Find all related files
Glob: "src/**/*<topic>*.php"

# Search for specific patterns
Grep: "<pattern>" --glob="*.php" --output_mode="content"

# Read key files
Read: <file_path>
```

### Step 2: Web Search (External)
Research current best practices and standards:

```bash
# Search for best practices
WebSearch: "<technology> best practices 2026"

# Search for common patterns
WebSearch: "<framework> <feature> patterns"

# Search for security considerations
WebSearch: "<technology> security vulnerabilities"

# Search for performance optimization
WebSearch: "<technology> performance optimization"
```

### Step 3: Documentation Fetch
Fetch official documentation when needed:

```bash
# Fetch official docs
WebFetch: "https://laravel.com/docs/12.x/<topic>"
WebFetch: "https://www.typescriptlang.org/docs/handbook/<topic>"
WebFetch: "https://spatie.be/docs/laravel-data"
```

### Step 4: GitHub Research
Search for similar implementations and issues:

```bash
# Search GitHub for patterns
WebSearch: "site:github.com <pattern> <language>"

# Search for known issues
WebSearch: "site:github.com <package> issues <topic>"
```

---

## Required Web Searches Per Review

Each reviewer MUST perform these web searches before writing their report:

### For All Reviews
1. `"<main_technology> best practices 2026"`
2. `"<main_technology> common mistakes"`
3. `"<main_technology> security considerations"`

### Technology-Specific Searches

| Review | Required Searches |
|--------|-------------------|
| 01 - PHP Attributes | "PHP 8 attributes best practices", "PHP reflection performance" |
| 02 - Laravel Integration | "Laravel 12 service provider patterns", "Laravel package development" |
| 03 - CLI/UX | "Symfony console UX best practices", "CLI design principles" |
| 04 - Route Collection | "Laravel router internals", "Laravel route performance" |
| 05 - Architecture | "PHP pipeline pattern", "SOLID principles PHP" |
| 06 - Type System | "PHP to TypeScript type mapping", "Spatie typescript transformer" |
| 07 - Templates | "Blade code generation", "TypeScript code generation" |
| 08 - TypeScript | "TypeScript generics best practices", "TypeScript 5 features" |
| 09 - HTTP Client | "Fetch API best practices", "TypeScript HTTP client patterns" |
| 10 - React Query | "TanStack Query v5 patterns", "React Query key factory" |
| 11 - Inertia | "Inertia.js TypeScript", "Inertia.js best practices" |
| 12 - Postman | "Postman collection v2.1 schema", "API documentation generation" |
| 13 - Testing | "Pest PHP best practices", "Laravel package testing" |
| 14 - Documentation | "Technical documentation best practices", "README templates" |
| 15 - Performance | "PHP profiling techniques", "Laravel performance optimization" |
| 16 - Security | "OWASP Top 10 2026", "Laravel security checklist" |

---

## Report Template with Knowledge Sources

Every report MUST include a "Sources & Research" section:

```markdown
## Sources & Research

### Internal Documentation
- [List files reviewed]

### Web Research Conducted
- [Query 1]: [Key findings]
- [Query 2]: [Key findings]

### Official Documentation Referenced
- [URL 1]: [What was learned]
- [URL 2]: [What was learned]

### Industry Best Practices Applied
- [Practice 1]: [How it was applied]
- [Practice 2]: [How it was applied]
```

---

## Exploration Agent Usage

For complex searches, use the Explore agent:

```
Task: subagent_type="Explore"
Prompt: "Find all implementations of <pattern> in the codebase and explain how they work"
```

This is useful for:
- Understanding how a feature is implemented across multiple files
- Finding all usages of a specific class or pattern
- Mapping dependencies between components

---

## Quick Reference Commands

### Search the web
```
WebSearch: "Laravel 12 service provider best practices 2026"
```

### Fetch documentation
```
WebFetch: "https://laravel.com/docs/12.x/packages"
Prompt: "Extract key information about package development"
```

### Search codebase
```
Grep: "class.*ServiceProvider" --glob="*.php" --output_mode="content"
```

### Find files
```
Glob: "src/**/*Provider*.php"
```

### Run commands
```
Bash: php artisan route:list --json
```

---

## Checklist Before Writing Report

- [ ] Searched codebase for all related files
- [ ] Read all files listed in review plan
- [ ] Performed required web searches for this review
- [ ] Fetched relevant official documentation
- [ ] Researched industry best practices
- [ ] Documented all sources in report
