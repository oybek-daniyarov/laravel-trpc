# Documentation Reviewer

## Role Overview
Review all documentation for accuracy, completeness, and developer experience.

## Why This Matters
Documentation determines adoption. Developers won't use what they can't understand.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Technical Writing | Advanced | Clear, concise documentation |
| Markdown | Intermediate | Formatting, syntax highlighting |
| Laravel Ecosystem | Intermediate | Understanding user context |
| TypeScript | Intermediate | Validating code examples |
| Developer Experience | Advanced | Empathy for new users |
| API Documentation | Intermediate | Reference documentation patterns |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **VS Code + Markdown** | Edit and preview markdown |
| **markdownlint** | Lint markdown files |
| **Read Tool** | Read documentation files |
| **Grep Tool** | Search documentation |
| **Bash Tool** | Test documented commands |
| **Grammarly / LanguageTool** | Grammar and spelling |
| **Carbon** | Code screenshot generation |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find all documentation files
Glob: "*.md"
Glob: "**/*.md"

# Find README
Read: README.md

# Find generated docs template
Read: resources/stubs/readme.blade.md

# Find code examples in docs
Grep: "\`\`\`php|\`\`\`typescript|\`\`\`bash" --glob="*.md"

# Find broken internal links
Grep: "\]\(#|\]\(\.\/" --glob="*.md"

# Find external links
Grep: "https?://" --glob="*.md"

# Find PHPDoc comments
Grep: "/\*\*" --glob="src/**/*.php"

# Find config comments
Read: config/trpc.php

# Find TODO/FIXME in docs
Grep: "TODO|FIXME|XXX" --glob="*.md"

# Find example commands
Grep: "php artisan|composer|npm|npx" --glob="*.md"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Technical documentation best practices 2026"
WebSearch: "README template open source"
WebSearch: "Laravel package documentation examples"
WebSearch: "API documentation patterns"
WebSearch: "Developer documentation UX"
WebSearch: "documentation as code"
WebSearch: "open source README examples"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-readmes"
Prompt: "Extract key information about GitHub README best practices"

WebFetch: "https://keepachangelog.com/en/1.1.0/"
Prompt: "Extract information about changelog format and best practices"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com spatie Laravel package README"
WebSearch: "site:github.com best README examples"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Documentation Testing
```bash
# Test all documented commands work
php artisan trpc:generate
php artisan trpc:generate --postman
php artisan trpc:generate --all
php artisan vendor:publish --tag=trpc-config
php artisan vendor:publish --tag=trpc-stubs

# Verify code examples compile
# Copy each TypeScript example to a file and run tsc
```

### Link Validation
```bash
# Check external links (manual or use tool)
Grep: "https?://" --glob="*.md"

# Verify each link resolves
# curl -I <url>
```

### New User Simulation
1. Read only README.md
2. Follow installation steps exactly
3. Follow quick start exactly
4. Note every point of confusion
5. Note every missing step
6. Time how long it takes

---

## Files to Review
- `README.md`
- `resources/stubs/readme.blade.md` (generated docs)
- Code comments and PHPDoc
- Config file comments

## Review Checklist

### 1. README Structure
- [ ] Clear package description (what it does)
- [ ] Value proposition (why use it)
- [ ] Requirements listed
- [ ] Installation steps work
- [ ] Quick start gets user running fast
- [ ] Table of contents for longer docs

### 2. Installation Section
Test fresh install:
```bash
composer require spatie/laravel-data spatie/laravel-typescript-transformer
composer require oybek-daniyarov/laravel-trpc
php artisan vendor:publish --tag=trpc-config
```
- [ ] Commands work exactly as documented
- [ ] No missing steps
- [ ] Common errors addressed

### 3. Quick Start Section
- [ ] Minimal working example
- [ ] Copy-paste ready code
- [ ] Expected output shown
- [ ] Can complete in <5 minutes

### 4. API Reference
For each public API:
- [ ] All parameters documented
- [ ] Types specified
- [ ] Default values noted
- [ ] Examples provided

### 5. Configuration Documentation
- [ ] Every config option explained
- [ ] Valid values listed
- [ ] Examples for common configurations
- [ ] Comments in config file itself

### 6. Code Examples
- [ ] Examples are syntactically correct
- [ ] Examples follow best practices
- [ ] Examples cover common use cases
- [ ] Examples are tested/verified

### 7. TypeScript Integration Docs
- [ ] Generated files explained
- [ ] Type helper usage shown
- [ ] IDE setup instructions (if needed)
- [ ] tsconfig requirements noted

### 8. Framework Integration Docs
- [ ] React Query setup complete
- [ ] Inertia.js setup complete
- [ ] Common patterns documented
- [ ] Troubleshooting included

### 9. Advanced Usage
- [ ] Custom transformers documented
- [ ] Stub customization explained
- [ ] Extending the package covered
- [ ] Performance tips included

### 10. Troubleshooting Section
Common issues to document:
- [ ] Types not updating (need to regenerate)
- [ ] Routes not appearing (check route_mode)
- [ ] TypeScript errors (version compatibility)
- [ ] Spatie package conflicts

### 11. Generated Documentation
Review `readme.blade.md`:
- [ ] Explains generated files
- [ ] Usage examples included
- [ ] Customization notes
- [ ] Links to main docs

### 12. Code Comments
- [ ] Public methods have PHPDoc
- [ ] Complex logic explained
- [ ] No outdated comments
- [ ] No commented-out code

### 13. Changelog
- [ ] CHANGELOG.md exists
- [ ] Follows Keep a Changelog format
- [ ] Breaking changes highlighted
- [ ] Upgrade guides for major versions

### 14. Contributing Guide
- [ ] CONTRIBUTING.md exists
- [ ] Development setup explained
- [ ] Testing instructions
- [ ] PR guidelines

### 15. Writing Quality
- [ ] Clear, concise language
- [ ] Consistent terminology
- [ ] No typos or grammar errors
- [ ] Proper markdown formatting
- [ ] Code blocks have syntax highlighting

## Test the Documentation
Pretend you're a new user:
1. Read only the README
2. Try to install and use the package
3. Note every point of confusion
4. Note every missing piece of information

## Questions to Answer
1. Is video documentation needed?
2. Should there be a documentation website?
3. Are there common questions that should be FAQ?

## Output
Document findings in `review/reports/14-documentation-report.md`
