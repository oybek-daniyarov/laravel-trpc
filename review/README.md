# Package Review Guide

This directory contains review plans for the Laravel tRPC package. Each plan is assigned to a specific reviewer role with detailed instructions.

## IMPORTANT: Before Any Review

**Read the [Reviewer Toolkit](./00-reviewer-toolkit.md) first!**

All reviewers have access to:
- **WebSearch** - Search the web for best practices and documentation
- **WebFetch** - Fetch and analyze web pages and official docs
- **Grep/Glob/Read** - Search and read codebase files
- **Bash** - Run commands, tests, and tools
- **Task (Explore agent)** - Delegate complex exploration tasks

## Knowledge Extension Protocol

Before writing ANY report, reviewers MUST:
1. Search the codebase for related patterns
2. Perform web searches for best practices
3. Fetch official documentation
4. Document all sources in the report

## Plan Structure

Each review plan contains:

| Section | Description |
|---------|-------------|
| **Role Overview** | What the reviewer does |
| **Why This Matters** | Importance of the review area |
| **Required Skills** | Skills and experience levels needed |
| **Tools & Plugins Access** | Tools the reviewer should have access to |
| **Knowledge Extension** | Web searches and docs to research BEFORE review |
| **Document Search Instructions** | Grep/Glob patterns for detailed analysis |
| **Files to Review** | Specific files to examine |
| **Review Checklist** | Step-by-step items to verify |
| **Questions to Answer** | Strategic considerations |

## Review Order

Follow this order for a systematic review:

| # | Plan File | Role | Priority |
|---|-----------|------|----------|
| 01 | `01-php-attributes-reviewer.plan.md` | PHP Attributes Reviewer | High |
| 02 | `02-laravel-integration-reviewer.plan.md` | Laravel Integration Reviewer | High |
| 03 | `03-cli-ux-reviewer.plan.md` | CLI/UX Reviewer | Medium |
| 04 | `04-route-collection-reviewer.plan.md` | Route Collection Reviewer | High |
| 05 | `05-architecture-reviewer.plan.md` | Architecture Reviewer | High |
| 06 | `06-type-system-reviewer.plan.md` | Type System Reviewer | Critical |
| 07 | `07-template-reviewer.plan.md` | Template Reviewer | High |
| 08 | `08-typescript-reviewer.plan.md` | TypeScript Reviewer | Critical |
| 09 | `09-http-client-reviewer.plan.md` | HTTP Client Reviewer | High |
| 10 | `10-react-query-reviewer.plan.md` | React Query Reviewer | Medium |
| 11 | `11-inertia-reviewer.plan.md` | Inertia Reviewer | Medium |
| 12 | `12-postman-reviewer.plan.md` | Postman Reviewer | Low |
| 13 | `13-test-reviewer.plan.md` | Test Reviewer | High |
| 14 | `14-documentation-reviewer.plan.md` | Documentation Reviewer | Medium |
| 15 | `15-performance-reviewer.plan.md` | Performance Reviewer | Medium |
| 16 | `16-security-reviewer.plan.md` | Security Reviewer | Critical |

## How to Use

1. **Read the plan** for your assigned role
2. **Review the listed files** following the checklist
3. **Document findings** in `reports/{role}-report.md`
4. **Raise issues** for critical findings

## Reports Directory

Place completed review reports in `reports/`:
- `reports/01-php-attributes-report.md`
- `reports/02-laravel-integration-report.md`
- ... etc.

## Priority Levels

- **Critical**: Core functionality, must be correct
- **High**: Important for quality, should be reviewed thoroughly
- **Medium**: Enhances user experience, review time permitting
- **Low**: Nice to have, review if resources available

## Quick Reference

### Core Files
```
src/
├── Attributes/         # TypedRoute, ApiRoute
├── Collectors/         # Route collection logic
├── Pipes/              # Pipeline stages
├── Transformers/       # PHP → TypeScript type conversion
├── Generators/         # Output generation
├── Services/           # Core services
├── Data/               # Data transfer objects
└── Contracts/          # Interfaces
```

### Generated Output
```
resources/js/api/
├── types.ts            # Core types
├── routes.ts           # Route definitions
├── helpers.ts          # Type helpers
├── url-builder.ts      # URL construction
├── fetch.ts            # HTTP client
├── client.ts           # API client factory
├── api.ts              # Grouped API
├── queries.ts          # React Query hooks
├── react-query.ts      # React Query utilities
├── inertia.ts          # Inertia helpers
└── index.ts            # Barrel exports
```

### Commands
```bash
# Generate TypeScript
php artisan trpc:generate

# Generate Postman
php artisan trpc:generate --postman

# Run tests
./vendor/bin/pest

# Run tests with coverage
./vendor/bin/pest --coverage
```
