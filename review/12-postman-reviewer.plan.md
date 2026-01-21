# Postman Reviewer

## Role Overview
Review the Postman collection generator for correctness and usability.

## Why This Matters
Postman collections aid API testing and documentation. A good collection saves developers time.

---

## Required Skills
| Skill | Level | Description |
|-------|-------|-------------|
| Postman | Advanced | Collections, environments, variables, tests |
| Postman Collection Format | Advanced | v2.1 schema structure |
| REST API Design | Intermediate | HTTP methods, status codes, headers |
| JSON Schema | Intermediate | Validating collection structure |
| API Documentation | Intermediate | Request/response documentation |

## Tools & Plugins Access
| Tool | Purpose |
|------|---------|
| **Postman App** | Import and test collections |
| **Insomnia** | Alternative API client testing |
| **Read Tool** | Read Postman generator files |
| **Grep Tool** | Search for Postman patterns |
| **Bash Tool** | Generate Postman collection |
| **jq** | JSON inspection and validation |
| **JSON Schema Validator** | Validate collection format |

## Document Search Instructions
Use these search patterns for detailed analysis:

```bash
# Find Postman generator
Read: src/Generators/PostmanGenerator.php

# Find all Postman data classes
Glob: "src/Data/Postman/*.php"

# Find collection structure building
Grep: "PostmanCollectionData|PostmanItemData|PostmanFolderData" --glob="*.php"

# Find request building
Grep: "PostmanRequestData|PostmanUrlData|PostmanBodyData" --glob="*.php"

# Find variable handling
Grep: "PostmanVariableData|{{.*}}" --glob="*.php"

# Find auth configuration
Grep: "PostmanAuthData|bearer|auth" --glob="*Postman*.php"

# Find header building
Grep: "PostmanHeaderData|Content-Type|Accept" --glob="*.php"

# Find body building
Grep: "PostmanBodyData|raw|formdata" --glob="*.php"

# Find script/test generation
Grep: "PostmanScriptData|PostmanEventData|prerequest|test" --glob="*.php"
```

---

## Knowledge Extension (REQUIRED BEFORE REPORT)

### Step 1: Web Searches to Perform
Execute these searches and document findings:

```
WebSearch: "Postman Collection v2.1 schema 2026"
WebSearch: "Postman collection generation best practices"
WebSearch: "Postman environment variables patterns"
WebSearch: "Postman pre-request scripts examples"
WebSearch: "OpenAPI vs Postman collection comparison"
WebSearch: "Postman collection folder organization"
WebSearch: "Postman dynamic variables"
```

### Step 2: Official Documentation to Fetch
```
WebFetch: "https://learning.postman.com/docs/sending-requests/create-requests/request-basics/"
Prompt: "Extract key information about Postman request structure"

WebFetch: "https://learning.postman.com/docs/sending-requests/variables/variables/"
Prompt: "Extract information about Postman variables and environments"

WebFetch: "https://schema.postman.com/"
Prompt: "Extract information about Postman collection schema format"
```

### Step 3: GitHub Research
```
WebSearch: "site:github.com Postman collection generator PHP"
WebSearch: "site:github.com Laravel Postman export"
```

### Step 4: Document Findings
Add a "Sources & Research" section to your report with:
- Key findings from each web search
- Relevant patterns from official docs
- Industry best practices discovered

---

### Testing Commands
```bash
# Generate Postman collection
php artisan trpc:generate --postman

# Find generated collection
Glob: "*.postman_collection.json"

# Validate JSON
cat collection.json | jq .

# Check schema compliance
# (Use Postman's schema: https://schema.postman.com/collection/json/v2.1.0/draft-07/collection.json)
```

### Postman Import Testing
1. Generate collection with `php artisan trpc:generate --postman`
2. Open Postman
3. Import → Upload Files → Select generated JSON
4. Verify:
   - Folders are organized correctly
   - Requests have correct methods
   - URLs include variables (`:param`)
   - Bodies have example data
   - Headers are set
   - Auth is configured

---

## Files to Review
- `src/Generators/PostmanGenerator.php`
- `src/Data/Postman/PostmanCollectionData.php`
- `src/Data/Postman/PostmanFolderData.php`
- `src/Data/Postman/PostmanItemData.php`
- `src/Data/Postman/PostmanRequestData.php`
- `src/Data/Postman/PostmanUrlData.php`
- `src/Data/Postman/PostmanBodyData.php`
- `src/Data/Postman/PostmanHeaderData.php`
- `src/Data/Postman/PostmanAuthData.php`
- `src/Data/Postman/PostmanVariableData.php`
- `src/Data/Postman/PostmanQueryParamData.php`
- `src/Data/Postman/PostmanEventData.php`
- `src/Data/Postman/PostmanScriptData.php`

## Review Checklist

### 1. Collection Structure
- [ ] Valid Postman Collection v2.1 schema
- [ ] Collection name and description set
- [ ] Folders organize routes logically (by resource/prefix)

### 2. Import Test
```bash
php artisan trpc:generate --postman
```
- [ ] JSON is valid
- [ ] Imports into Postman without errors
- [ ] Imports into Insomnia (if compatible)

### 3. Request Configuration
For each request:
- [ ] Method is correct (GET, POST, etc.)
- [ ] URL is correct with path variables
- [ ] Headers include `Accept: application/json`
- [ ] Headers include `Content-Type: application/json` for body requests

### 4. URL Variables
```json
{
  "url": {
    "raw": "{{baseUrl}}/api/users/:user",
    "host": ["{{baseUrl}}"],
    "path": ["api", "users", ":user"],
    "variable": [
      { "key": "user", "value": "1", "description": "User ID" }
    ]
  }
}
```
- [ ] Path parameters use Postman syntax (`:param`)
- [ ] Variables have default values
- [ ] Variables have descriptions

### 5. Query Parameters
- [ ] GET requests include query params
- [ ] Params have descriptions
- [ ] Optional params marked as disabled

### 6. Request Body
- [ ] POST/PUT/PATCH have body templates
- [ ] Body matches request Data class structure
- [ ] JSON is properly formatted
- [ ] Example values are sensible

### 7. Authentication
- [ ] Collection-level auth configuration
- [ ] Supports Bearer token
- [ ] Supports Laravel Sanctum
- [ ] Variables for auth tokens

### 8. Environment Variables
- [ ] `{{baseUrl}}` variable used
- [ ] `{{token}}` variable for auth
- [ ] Environment template provided

### 9. Pre-request Scripts
- [ ] CSRF token handling (if needed)
- [ ] Dynamic value generation (timestamps, etc.)

### 10. Tests/Post-response Scripts
- [ ] Status code assertions
- [ ] Response structure validation
- [ ] Variable extraction from responses

### 11. Documentation
- [ ] Request descriptions from PHPDoc
- [ ] Response examples included
- [ ] Error response examples

## Test Scenarios
After import, test:
1. GET request with no params
2. GET request with path params
3. GET request with query params
4. POST request with JSON body
5. PUT/PATCH request
6. DELETE request
7. Authentication flow

## Postman Collection Schema Reference
```json
{
  "info": {
    "name": "Laravel tRPC API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [...],
  "variable": [...],
  "auth": {...}
}
```

## Questions to Answer
1. Should OpenAPI/Swagger export be supported instead/additionally?
2. Should there be environment file generation?
3. Should response examples be auto-generated?

## Output
Document findings in `review/reports/12-postman-report.md`
