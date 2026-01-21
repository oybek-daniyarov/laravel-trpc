# Security Reviewer

## Role Overview
Review the package for security vulnerabilities in both PHP code and generated TypeScript.

## Why This Matters
Security flaws can expose user data, enable attacks, or compromise systems. This package touches routing and HTTP layers.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Web Security | Advanced | OWASP Top 10, common vulnerabilities |
| PHP Security | Advanced | Injection, XSS, CSRF in PHP |
| JavaScript Security | Advanced | XSS, prototype pollution, CORS |
| Laravel Security | Intermediate | Laravel's security features |
| HTTP Security | Intermediate | Headers, cookies, authentication |
| Code Auditing | Advanced | Identifying security patterns |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **composer audit** | PHP dependency vulnerabilities |
| **npm audit** | JS dependency vulnerabilities |
| **Read Tool** | Read source files for audit |
| **Grep Tool** | Search for security patterns |
| **Bash Tool** | Run security checks |
| **Burp Suite** | HTTP request manipulation (testing) |
| **OWASP ZAP** | Automated security scanning |
| **Snyk** | Dependency vulnerability scanning |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find user input handling
Grep: "\$_GET|\$_POST|\$_REQUEST|request\(" --glob="src/**/*.php"

# Find file path operations (path traversal risk)
Grep: "file_get_contents|file_put_contents|fopen|include|require" --glob="src/**/*.php"

# Find shell execution (command injection risk)
Grep: "exec\(|shell_exec|system\(|passthru|popen" --glob="src/**/*.php"

# Find output without escaping
Grep: "echo|print|->content\(" --glob="src/**/*.php"

# Find URL building (injection risk)
Grep: "http://|https://|url\(" --glob="*.blade.ts"

# Find eval or dynamic code
Grep: "eval\(|create_function|preg_replace.*e" --glob="src/**/*.php"

# Find SQL (injection risk)
Grep: "DB::raw|whereRaw|selectRaw" --glob="src/**/*.php"

# Find serialization (object injection)
Grep: "unserialize|serialize" --glob="src/**/*.php"

# Find credential handling
Grep: "password|secret|token|key|auth" --glob="src/**/*.php"

# Find CORS configuration
Grep: "Access-Control|cors|CORS" --glob="*.php"

# Find CSRF handling in generated code
Grep: "csrf|XSRF|X-CSRF" --glob="*.blade.ts"

# Check for sensitive data in output
Grep: "env\(|config\(" --glob="*Generator*.php"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "OWASP Top 10 2026"
WebSearch: "PHP code generation security vulnerabilities"
WebSearch: "JavaScript fetch security best practices"
WebSearch: "Laravel package security checklist"
WebSearch: "TypeScript code injection prevention"
WebSearch: "CSRF protection SPA"
WebSearch: "prototype pollution JavaScript prevention"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://owasp.org/Top10/"
Prompt: "Extract key information about OWASP Top 10 vulnerabilities"

WebFetch: "https://laravel.com/docs/12.x/csrf"
Prompt: "Extract information about Laravel CSRF protection"

WebFetch: "https://laravel.com/docs/12.x/sanctum"
Prompt: "Extract information about Sanctum security considerations"

WebFetch: "https://cheatsheetseries.owasp.org/cheatsheets/AJAX_Security_Cheat_Sheet.html"
Prompt: "Extract information about AJAX/Fetch security best practices"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com security audit PHP package"
WebSearch: "site:github.com Laravel security best practices"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Security Audit Commands
```bash
# PHP dependency audit
composer audit

# NPM dependency audit (if applicable)
npm audit

# Check for known vulnerable patterns
Grep: "md5\(|sha1\(|rand\(" --glob="src/**/*.php"

# Generate and check for sensitive data
php artisan trpc:generate
Grep: "password|secret|key" --glob="resources/js/api/*.ts"
```

### Penetration Testing
```bash
# Test path traversal
# Try route params like: ../../../etc/passwd

# Test XSS in generated code
# Check if special characters are escaped

# Test CSRF
# Verify tokens are required for mutations

# Test header injection
# Check for newlines in header values
```

---

## Areas to Review

### 1. Input Validation (PHP)

#### Configuration
File: `config/trpc.php`
- [ ] Path traversal in `output_path` prevented
- [ ] Invalid patterns in `exclude_patterns` handled
- [ ] No code execution via config values

#### Route Collection
- [ ] No sensitive routes exposed accidentally
- [ ] Debug/internal routes filtered
- [ ] Route parameters sanitized

### 2. Generated Code Security (TypeScript)

#### URL Building
File: `resources/stubs/url-builder.blade.ts`
- [ ] Path parameters properly encoded
- [ ] No URL injection possible
- [ ] Query parameters escaped correctly

```typescript
// Vulnerable:
const url = `/users/${userId}`; // userId could be "1/../../admin"

// Safe:
const url = `/users/${encodeURIComponent(userId)}`;
```

#### Fetch Wrapper
File: `resources/stubs/fetch.blade.ts`
- [ ] No credentials leaked in URLs
- [ ] Error messages don't expose internals
- [ ] Response validation prevents prototype pollution

### 3. CSRF Protection
- [ ] CSRF token included in state-changing requests
- [ ] Works with Laravel Sanctum
- [ ] Works with Laravel's default CSRF protection

```typescript
// Should include CSRF token
headers: {
  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
}
```

### 4. Authentication Headers
- [ ] Auth tokens not logged
- [ ] Auth tokens not included in error reports
- [ ] Secure transmission (HTTPS recommended in docs)

### 5. Sensitive Data Exposure
- [ ] No secrets in generated code
- [ ] No internal paths exposed
- [ ] No database structure leaked
- [ ] Environment variables not exposed

#### Check Generated Files For:
```typescript
// Should NOT contain:
- API keys
- Database credentials
- Internal server paths
- Debug information
- Stack traces
```

### 6. Error Handling
- [ ] Server errors don't leak stack traces to client
- [ ] Validation errors don't expose field existence
- [ ] 404 responses are generic

### 7. Postman Collection Security
File: `src/Generators/PostmanGenerator.php`
- [ ] No hardcoded credentials
- [ ] Auth tokens use variables, not values
- [ ] Sensitive headers not pre-filled

### 8. Dependency Security
```bash
# Check for known vulnerabilities
composer audit
npm audit
```

- [ ] No vulnerable dependencies
- [ ] Dependencies actively maintained
- [ ] Minimal dependency footprint

### 9. Code Injection Vectors

#### Blade Templates
- [ ] User input not directly interpolated
- [ ] Proper escaping in generated code
- [ ] No `{!! !!}` with untrusted data

#### Route Names
- [ ] Special characters in route names handled
- [ ] No code execution via route names
- [ ] XSS via route names prevented

### 10. CORS Considerations
- [ ] Documentation covers CORS setup
- [ ] No wildcard CORS recommendations
- [ ] Credentials mode documented correctly

### 11. Rate Limiting Awareness
- [ ] Documentation mentions rate limiting
- [ ] Generated client handles 429 responses
- [ ] No retry loops that could cause DOS

### 12. Security Headers
Generated client should support/document:
- [ ] `Authorization` header handling
- [ ] `X-Requested-With` for AJAX detection
- [ ] Content Security Policy compatibility

## Security Test Cases
```typescript
// Test these attack vectors:

// Path traversal
api.users.show({ user: '../../../etc/passwd' });

// XSS in parameters
api.search.index({ query: '<script>alert(1)</script>' });

// SQL injection (should be handled by Laravel, but verify)
api.users.index({ filter: "'; DROP TABLE users; --" });

// CSRF bypass attempt
// (verify token is required for mutations)

// Prototype pollution in response
// { "__proto__": { "admin": true } }
```

## Security Checklist Summary
- [ ] No code injection possible
- [ ] No path traversal possible
- [ ] No XSS vectors in generated code
- [ ] No sensitive data exposure
- [ ] CSRF protection documented/implemented
- [ ] Authentication handled securely
- [ ] Dependencies are secure
- [ ] Error handling is safe

## Questions to Answer
1. Should the package integrate with Laravel Sanctum automatically?
2. Should there be security headers by default?
3. Should there be a security audit in CI?

## Output
Document findings in `review/reports/16-security-report.md`
