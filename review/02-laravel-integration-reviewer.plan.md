# Laravel Integration Reviewer

## Role Overview
Review how the package integrates with Laravel's service container, configuration system, and ecosystem.

## Why This Matters
Poor Laravel integration leads to installation issues, conflicts with other packages, and maintenance burden.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Laravel Service Providers | Advanced | Registration, booting, deferred providers |
| Laravel Service Container | Advanced | Bindings, singletons, contextual binding |
| Laravel Config System | Intermediate | Config merging, publishing, environment variables |
| Composer | Intermediate | Package discovery, autoloading, scripts |
| Laravel Package Development | Advanced | Publishing assets, migrations, commands |
| Dependency Injection | Advanced | Interface binding, auto-resolution |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Laravel Tinker** | Interactive testing of container bindings |
| **Laravel Debugbar** | Inspect loaded providers and bindings |
| **Composer** | Dependency analysis and autoload verification |
| **PHPStorm + Laravel Idea** | Laravel-aware IDE support |
| **Read Tool** | Read configuration and provider files |
| **Bash Tool** | Run artisan commands for testing |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find service provider registration
Grep: "class.*ServiceProvider|register\(\)|boot\(\)" --glob="*ServiceProvider.php"

# Find container bindings
Grep: "->bind\(|->singleton\(|->instance\(" --glob="*.php"

# Find config usage
Grep: "config\(|Config::get" --glob="*.php"

# Find publishable assets
Grep: "publishes\(|->publish" --glob="*ServiceProvider.php"

# Find command registration
Grep: "commands\(|Artisan::command" --glob="*.php"

# Check composer.json for auto-discovery
Read: composer.json (check extra.laravel section)

# Find all config references
Grep: "trpc\." --glob="*.php"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Laravel 12 service provider best practices 2026"
WebSearch: "Laravel package development guide 2026"
WebSearch: "Laravel deferred service providers performance"
WebSearch: "Spatie Laravel package patterns"
WebSearch: "Laravel 12 package auto-discovery"
WebSearch: "Laravel service container binding patterns"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://laravel.com/docs/12.x/packages"
Prompt: "Extract key information about Laravel package development, service providers, and publishing assets"

WebFetch: "https://laravel.com/docs/12.x/providers"
Prompt: "Extract information about service provider registration, booting, and deferred loading"

WebFetch: "https://laravel.com/docs/12.x/container"
Prompt: "Extract information about service container bindings and dependency injection"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com spatie laravel package service provider"
WebSearch: "site:github.com Laravel package config publishing"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

## Files to Review
- `src/TrpcServiceProvider.php`
- `config/trpc.php`
- `composer.json` (dependencies)

## Review Checklist

### 1. Service Provider
- [ ] Services are properly bound to container
- [ ] Deferred loading used where appropriate
- [ ] Config file is merged correctly
- [ ] Commands are registered properly
- [ ] Publishable assets tagged correctly (`trpc-config`, `trpc-stubs`)

### 2. Configuration
- [ ] All options have sensible defaults
- [ ] Config keys are consistent and well-named
- [ ] Validation of config values (where critical)
- [ ] Environment variable support where needed
- [ ] Comments explain each option

### 3. Config Options to Verify
```php
'output_path'       // Valid directory path
'api_prefix'        // String or null
'route_mode'        // Enum: api, web, all, named, attributed
'exclude_patterns'  // Array of valid patterns
'outputs'           // Boolean flags for each output type
```

### 4. Dependencies
- [ ] Minimum PHP version appropriate (8.2+)
- [ ] Laravel version constraints correct (12.x, 12.x)
- [ ] Spatie package versions compatible
- [ ] No unnecessary dependencies

### 5. Package Discovery
- [ ] Auto-discovery works (`extra.laravel` in composer.json)
- [ ] Manual registration documented as fallback

### 6. Testing Install Flow
```bash
# Test fresh install
composer require oybek-daniyarov/laravel-trpc

# Test config publish
php artisan vendor:publish --tag=trpc-config

# Test stub publish
php artisan vendor:publish --tag=trpc-stubs
```

## Questions to Answer
1. Should the package auto-run `typescript:transform` or leave it to user?
2. Is the config structure future-proof for new features?
3. Are there any circular dependency risks?

## Output
Document findings in `review/reports/02-laravel-integration-report.md`
