# CLI/UX Reviewer

## Role Overview
Review the artisan command interface for usability, clarity, and robustness.

## Why This Matters
The CLI is the primary interaction point. Poor UX leads to frustration, support requests, and abandonment.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Laravel Artisan | Advanced | Command creation, arguments, options, output |
| Symfony Console | Intermediate | Underlying console component |
| CLI/UX Design | Intermediate | Progress bars, colors, formatting, error messages |
| Error Handling | Intermediate | Graceful failure, helpful error messages |
| User Experience | Intermediate | Intuitive interfaces, discoverability |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Terminal / iTerm2** | Test command output and formatting |
| **Bash Tool** | Execute artisan commands for testing |
| **Read Tool** | Read command source files |
| **script command** | Record terminal sessions for review |
| **CI Environment** | Test non-TTY output (no colors/progress) |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find command definitions
Grep: "class.*Command|extends Command" --glob="*Command.php"

# Find command signature
Grep: "signature\s*=|protected.*signature" --glob="*Command.php"

# Find output methods
Grep: "->info\(|->error\(|->warn\(|->line\(|->table\(" --glob="*Command.php"

# Find progress indicators
Grep: "withProgressBar|createProgressBar|advance\(\)" --glob="*.php"

# Find option/argument handling
Grep: "->option\(|->argument\(|hasOption|hasArgument" --glob="*Command.php"

# Find error handling in commands
Grep: "try|catch|throw|fail\(" --glob="*Command.php"

# Check help text
Bash: php artisan trpc:generate --help
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Laravel artisan command best practices 2026"
WebSearch: "Symfony console component UX patterns"
WebSearch: "CLI design principles user experience"
WebSearch: "Terminal progress indicators PHP best practices"
WebSearch: "command line interface error messages"
WebSearch: "artisan command output formatting"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://laravel.com/docs/12.x/artisan"
Prompt: "Extract key information about artisan command creation, output methods, and options"

WebFetch: "https://symfony.com/doc/current/console.html"
Prompt: "Extract information about console output formatting and progress indicators"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Laravel artisan command progress bar"
WebSearch: "site:github.com Symfony console best practices"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Manual Testing Commands
```bash
# Test all command variations
php artisan trpc:generate
php artisan trpc:generate --help
php artisan trpc:generate --postman
php artisan trpc:generate --all
php artisan trpc:generate --output=/invalid/path  # Error case
php artisan trpc:generate -v  # Verbose
php artisan trpc:generate -vvv  # Very verbose
```

---

## Files to Review
- `src/Commands/GenerateTrpcCommand.php`

## Review Checklist

### 1. Command Signature
- [ ] Command name is memorable (`trpc:generate`)
- [ ] Options are intuitive (`--postman`, `--all`, `--output`)
- [ ] Short aliases available for common options
- [ ] Help text is comprehensive (`php artisan trpc:generate --help`)

### 2. Command Options to Test
```bash
php artisan trpc:generate                    # Default behavior
php artisan trpc:generate --postman          # Postman only
php artisan trpc:generate --all              # Everything
php artisan trpc:generate --output=custom/path
php artisan trpc:generate --skip-typescript-transform
```

### 3. Output & Feedback
- [ ] Progress indication for long operations
- [ ] Clear success message with summary
- [ ] File paths shown (what was generated where)
- [ ] Count of routes processed
- [ ] Warnings for skipped routes (with reasons)

### 4. Error Handling
- [ ] Invalid output path (not writable)
- [ ] No routes found (helpful message)
- [ ] Missing dependencies (spatie packages)
- [ ] Config file missing
- [ ] Invalid config values

### 5. Idempotency
- [ ] Running twice produces same output
- [ ] No orphaned files left behind
- [ ] Clean overwrites of existing files

### 6. Edge Cases
- [ ] Empty route collection
- [ ] Very large route collection (500+)
- [ ] Routes with special characters in names
- [ ] Running in CI environment (no TTY)

### 7. Suggested Output Format
```
Laravel tRPC Generator
======================

Collecting routes... 47 routes found
Extracting types... Done
Transforming... Done
Generating TypeScript...
  ✓ types.ts
  ✓ routes.ts
  ✓ helpers.ts
  ✓ fetch.ts
  ✓ client.ts
  ✓ api.ts
  ✓ index.ts

Generated 7 files in resources/js/api
```

## Questions to Answer
1. Should there be a `--dry-run` option?
2. Should there be a `--watch` mode for development?
3. Should there be a `--diff` to show changes?

## Output
Document findings in `review/reports/03-cli-ux-report.md`
