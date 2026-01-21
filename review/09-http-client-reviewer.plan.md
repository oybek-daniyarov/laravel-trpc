# HTTP Client Reviewer

## Role Overview
Review the generated fetch wrapper and API client for correctness and robustness.

## Why This Matters
The HTTP client is runtime code executed in users' browsers. Bugs here cause real production issues.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Fetch API | Advanced | Request/Response, headers, body handling |
| HTTP Protocol | Advanced | Methods, status codes, content types |
| Error Handling | Advanced | Network errors, HTTP errors, timeouts |
| URL Construction | Intermediate | Path params, query strings, encoding |
| CORS | Intermediate | Cross-origin requests, credentials |
| TypeScript | Advanced | Typing async functions, generics |
| Browser DevTools | Intermediate | Network tab debugging |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Browser DevTools** | Network request inspection |
| **Postman / Insomnia** | HTTP request testing |
| **Read Tool** | Read client source files |
| **Grep Tool** | Search for HTTP patterns |
| **Node.js** | Test fetch in Node environment |
| **msw (Mock Service Worker)** | Mock HTTP responses for testing |
| **curl** | Command-line HTTP testing |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find fetch implementation
Grep: "fetch\(|window\.fetch|globalThis\.fetch" --glob="*.blade.ts"

# Find header handling
Grep: "Headers|Content-Type|Accept|Authorization" --glob="*.blade.ts"

# Find body serialization
Grep: "JSON\.stringify|FormData|body:" --glob="*.blade.ts"

# Find error handling
Grep: "catch|throw|Error|reject" --glob="*.blade.ts"

# Find status code handling
Grep: "status|ok|response\." --glob="*.blade.ts"

# Find URL building
Grep: "encodeURIComponent|URLSearchParams|url\." --glob="*.blade.ts"

# Find timeout handling
Grep: "AbortController|timeout|signal" --glob="*.blade.ts"

# Find CSRF handling
Grep: "csrf|XSRF|X-CSRF" --glob="*.blade.ts"

# Find credentials handling
Grep: "credentials|cookies|same-origin|include" --glob="*.blade.ts"

# Read the fetch wrapper
Read: resources/stubs/fetch.blade.ts

# Read the client factory
Read: resources/stubs/client.blade.ts

# Read URL builder
Read: resources/stubs/url-builder.blade.ts
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Fetch API error handling best practices 2026"
WebSearch: "TypeScript fetch wrapper patterns"
WebSearch: "HTTP client retry logic implementation"
WebSearch: "Fetch API timeout AbortController"
WebSearch: "Laravel Sanctum CORS setup"
WebSearch: "fetch credentials include vs same-origin"
WebSearch: "TypeScript HTTP client error types"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API"
Prompt: "Extract key information about Fetch API usage, options, and error handling"

WebFetch: "https://developer.mozilla.org/en-US/docs/Web/API/AbortController"
Prompt: "Extract information about request cancellation and timeouts"

WebFetch: "https://laravel.com/docs/12.x/sanctum"
Prompt: "Extract information about Sanctum SPA authentication and CORS"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com TypeScript fetch wrapper"
WebSearch: "site:github.com typed fetch client"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Testing Commands
```bash
# Generate output
php artisan trpc:generate

# Test in Node.js
node -e "import('./resources/js/api/fetch.js').then(m => console.log(m))"

# Test with curl (compare behavior)
curl -X GET http://localhost/api/users -H "Accept: application/json"
```

---

## Files to Review
- `resources/stubs/fetch.blade.ts`
- `resources/stubs/client.blade.ts`
- `resources/stubs/url-builder.blade.ts`

## Review Checklist

### 1. URL Building
- [ ] Path parameters replaced correctly (`/users/{user}` → `/users/123`)
- [ ] Query parameters appended correctly (`?page=1&per_page=10`)
- [ ] Special characters URL-encoded
- [ ] Trailing slashes handled consistently
- [ ] Base URL concatenation correct (no double slashes)

### 2. HTTP Methods
Test each method:
- [ ] `GET` - No body, query params in URL
- [ ] `POST` - JSON body, Content-Type header
- [ ] `PUT` - JSON body, Content-Type header
- [ ] `PATCH` - JSON body, Content-Type header
- [ ] `DELETE` - Optional body support

### 3. Headers
- [ ] `Content-Type: application/json` set for body requests
- [ ] `Accept: application/json` set for all requests
- [ ] Custom headers merge correctly
- [ ] CSRF token handling (Laravel Sanctum)
- [ ] Authorization header support

### 4. Request Body
- [ ] JSON serialization correct
- [ ] `FormData` support for file uploads
- [ ] Empty body handled for DELETE
- [ ] Nested objects serialized correctly

### 5. Response Handling
- [ ] 2xx responses parsed as JSON
- [ ] 204 No Content returns `undefined`/`null`
- [ ] Non-JSON responses handled gracefully
- [ ] Response type matches declared type

### 6. Error Handling
- [ ] 4xx errors throw/return error object
- [ ] 5xx errors throw/return error object
- [ ] Network errors caught
- [ ] Timeout errors caught
- [ ] Error includes status code, message, body

### 7. Validation Errors (422)
```typescript
// Laravel validation errors should be typed
{
  message: "The given data was invalid.",
  errors: {
    email: ["The email field is required."],
    password: ["The password must be at least 8 characters."]
  }
}
```

### 8. Configuration Options
- [ ] `baseUrl` - API base URL
- [ ] `headers` - Default headers
- [ ] `credentials` - Cookie handling (`include`, `same-origin`)
- [ ] `onError` - Global error handler
- [ ] `onRequest` - Request interceptor (optional)
- [ ] `onResponse` - Response interceptor (optional)

### 9. Edge Cases
- [ ] Empty response body
- [ ] HTML response (server error page)
- [ ] Redirect responses (3xx)
- [ ] Large response bodies
- [ ] Concurrent requests
- [ ] Request cancellation (AbortController)

### 10. Security
- [ ] No credentials leaked in URLs
- [ ] CORS handled correctly
- [ ] XSS vectors in error messages escaped

### 11. Browser Compatibility
- [ ] Modern browsers (Chrome, Firefox, Safari, Edge)
- [ ] Fetch API polyfill not required (or documented)

## Test Scenarios
```typescript
// Happy path
await api.users.index();
await api.users.show({ user: 1 });
await api.users.store({ name: 'John', email: 'john@example.com' });

// Error scenarios
await api.users.show({ user: 999 }); // 404
await api.users.store({ email: 'invalid' }); // 422 validation
await api.users.index(); // 500 server error

// Edge cases
await api.posts.store({ content: ''.padEnd(100000, 'x') }); // Large body
await api.files.upload({ file: new File(...) }); // File upload
```

## Questions to Answer
1. Should request/response interceptors be supported?
2. Should there be retry logic for failed requests?
3. Should there be request deduplication?

## Output
Document findings in `review/reports/09-http-client-report.md`
