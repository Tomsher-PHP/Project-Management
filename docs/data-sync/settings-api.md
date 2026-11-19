# PMS Common Data Sync Settings API Documentation

## 1. Overview

The **PMS Common Data Sync Settings API** provides a unified, centralized RESTful interface to manage core settings master data across the Project Management System (PMS). 

External systems (such as ERP, CRM, and automation platforms) can manage all supported settings modules through a single set of standardized common endpoints by supplying the target module name in request parameters.

### Completed Common Endpoints

| Operation | Method | Endpoint | Primary Use Case |
|---|---|---|---|
| **Sync (Upsert)** | `POST` | `/api/data-sync/settings/sync` | Idempotent create or update of master records |
| **List** | `GET` | `/api/data-sync/settings?module={module}` | Paginated listing with filtering, search, and sorting |
| **Get** | `GET` | `/api/data-sync/settings/{code}?module={module}` | Retrieve single record details by unique code |
| **Delete** | `DELETE` | `/api/data-sync/settings/{code}?module={module}` | Safe soft deletion with dependency protections |

---

## 2. Authentication & Headers

All Data Sync endpoints enforce server-to-server API Key authentication via the `api.key` middleware and rate limiting via the `throttle:api-client` middleware.

### Required Headers

```http
Authorization: Bearer <key_id>.<secret>
Accept: application/json
Content-Type: application/json
```

### Rate Limiting

- **Quota:** 60 requests per minute per authenticated API Client.
- **Headers returned:** `X-RateLimit-Limit`, `X-RateLimit-Remaining`.
- **Exceeded:** Returns **HTTP 429 Too Many Requests**.

---

## 3. Permissions & Scopes

API authorization is scope-based. Each API client possesses one or more granular scopes:

| Common Endpoint | Required Scope | Description |
|---|---|---|
| `POST /api/data-sync/settings/sync` | `settings.write` | Create or update settings master records |
| `GET /api/data-sync/settings` | `settings.read` | Read and list settings master records |
| `GET /api/data-sync/settings/{code}` | `settings.read` | View single settings master record |
| `DELETE /api/data-sync/settings/{code}` | `settings.write` | Soft-delete settings master records |

Scope verification occurs before module validation. If an authenticated key lacks the required scope, the API returns **HTTP 403 Forbidden** immediately.

---

## 4. Supported Modules

The list of allowed settings modules is controlled by configuration in `config/constants.php`:

```php
'data_sync_modules' => [
    'technologies'       => 'technologies',
    'industries'         => 'industries',
    'project_categories' => 'project_categories',
    'project_statuses'   => 'project_statuses',
],
```

The `ModuleResolver` verifies module names against this configuration. Supplying an unconfigured or unsupported module returns **HTTP 422 Unprocessable Content**.

---

## 5. Common Sync API (Upsert)

### Endpoint

```http
POST /api/data-sync/settings/sync
```

### Behavior
- Requires `module` and a 6-digit numeric `code`.
- **Create:** If the `code` does not exist in the selected module, creates a new record and returns **HTTP 201 Created** with `"operation": "created"`.
- **Update:** If the `code` exists as an active record, updates the provided fields (omitted fields retain their existing values) and returns **HTTP 200 OK** with `"operation": "updated"`.
- **Soft-Deleted Conflict:** If the `code` belongs to a soft-deleted record, returns **HTTP 409 Conflict** (`"The supplied code belongs to a deleted record."`).

---

### Module Examples

#### A. Technologies

**Request Payload:**
```json
{
  "module": "technologies",
  "code": "845621",
  "name": "Vue.js",
  "sort_order": 1,
  "is_active": true
}
```

**Response (Create - HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Record created successfully.",
  "data": {
    "module": "technologies",
    "id": 14,
    "name": "Vue.js",
    "code": "845621",
    "sort_order": 1,
    "is_system": false,
    "is_active": true,
    "operation": "created"
  }
}
```

**Response (Update - HTTP 200 OK):**
```json
{
  "success": true,
  "message": "Record updated successfully.",
  "data": {
    "module": "technologies",
    "id": 14,
    "name": "Vue.js 3",
    "code": "845621",
    "sort_order": 1,
    "is_system": false,
    "is_active": true,
    "operation": "updated"
  }
}
```

---

#### B. Industries

**Request Payload:**
```json
{
  "module": "industries",
  "code": "743813",
  "name": "Fintech Solutions",
  "parent_code": null,
  "sort_order": 2,
  "is_active": true
}
```

**Child Industry Request Payload:**
```json
{
  "module": "industries",
  "code": "640358",
  "name": "Payment Gateway Providers",
  "parent_code": "743813",
  "sort_order": 1,
  "is_active": true
}
```

**Response (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Record created successfully.",
  "data": {
    "module": "industries",
    "id": 22,
    "name": "Payment Gateway Providers",
    "code": "640358",
    "parent_code": "743813",
    "sort_order": 1,
    "is_system": false,
    "is_active": true,
    "operation": "created"
  }
}
```

**Hierarchy Validation Rules:**
- `parent_code` must reference an existing active industry code.
- Self-parenting (`parent_code` matching record's own `code`) is rejected with HTTP 422.
- Circular references (e.g., A -> B -> A) are detected and rejected with HTTP 422.

---

#### C. Project Categories

**Request Payload:**
```json
{
  "module": "project_categories",
  "code": "397711",
  "name": "Mobile Application",
  "sort_order": 3,
  "is_active": true
}
```

**Response (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Record created successfully.",
  "data": {
    "module": "project_categories",
    "id": 8,
    "name": "Mobile Application",
    "code": "397711",
    "sort_order": 3,
    "is_system": false,
    "is_active": true,
    "operation": "created"
  }
}
```

---

#### D. Project Statuses

**Request Payload:**
```json
{
  "module": "project_statuses",
  "code": "900001",
  "name": "In Review",
  "color": "#0d6efd",
  "type": "in_progress",
  "sort_order": 6,
  "is_default": false,
  "is_active": true
}
```

**Response (HTTP 201 Created):**
```json
{
  "success": true,
  "message": "Record created successfully.",
  "data": {
    "module": "project_statuses",
    "id": 9,
    "name": "In Review",
    "code": "900001",
    "color": "#0d6efd",
    "type": "in_progress",
    "sort_order": 6,
    "is_default": false,
    "is_completed": false,
    "is_system": false,
    "is_active": true,
    "operation": "created"
  }
}
```

**Project Status Business Rules:**
- `type` must be one of: `open`, `in_progress`, `completed`, `archived`.
- `color` must be a valid hex color code (`#RGB` or `#RRGGBB`).
- `is_completed` is strictly derived from `type === 'completed'`. Directly passing `is_completed` cannot override this rule.
- Setting `is_default: true` automatically clears the default flag on all other project statuses within a transaction.

---

## 6. Common List API

### Endpoint

```http
GET /api/data-sync/settings?module={module}
```

### Example

```http
GET /api/data-sync/settings?module=industries&is_active=1&page=1&per_page=50&sort_by=sort_order&sort_direction=asc
```

### Supported Query Parameters

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `module` | String | **Yes** | — | Target settings module (`technologies`, `industries`, `project_categories`, `project_statuses`) |
| `search` | String | No | `null` | Substring match against `name` or `code` |
| `is_active` | Boolean | No | `null` | Filter by active (`1`, `true`) or inactive (`0`, `false`) |
| `page` | Integer | No | `1` | Page number (minimum 1) |
| `per_page` | Integer | No | `50` | Records per page (minimum 1, maximum 100) |
| `sort_by` | String | No | `sort_order` | Allowed fields: `name`, `code`, `sort_order`, `created_at`, `updated_at` (plus `type`, `is_default`, `is_completed` for `project_statuses`) |
| `sort_direction` | String | No | `asc` | `asc` or `desc` |

### Response Example (HTTP 200 OK)

```json
{
  "success": true,
  "message": "Records retrieved successfully.",
  "data": [
    {
      "id": 22,
      "name": "Fintech Solutions",
      "code": "743813",
      "parent_code": null,
      "sort_order": 1,
      "is_system": false,
      "is_active": true
    },
    {
      "id": 23,
      "name": "Payment Gateway Providers",
      "code": "640358",
      "parent_code": "743813",
      "sort_order": 2,
      "is_system": false,
      "is_active": true
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 2,
    "last_page": 1
  }
}
```

---

## 7. Common Get API

### Endpoint

```http
GET /api/data-sync/settings/{code}?module={module}
```

### Example

```http
GET /api/data-sync/settings/640358?module=industries
```

### Resolution Rules
- The record is resolved using **both** `{code}` from the route and `module` from the query string.
- Codes are non-unique across different modules (e.g., code `111111` in `technologies` and `111111` in `industries` resolve independently).
- Missing or soft-deleted records return **HTTP 404 Not Found**.

### Response Example (HTTP 200 OK)

```json
{
  "success": true,
  "message": "Record retrieved successfully.",
  "data": {
    "id": 23,
    "name": "Payment Gateway Providers",
    "code": "640358",
    "parent_code": "743813",
    "sort_order": 2,
    "is_system": false,
    "is_active": true
  }
}
```

---

## 8. Common Delete API

### Endpoint

```http
DELETE /api/data-sync/settings/{code}?module={module}
```

### Example

```http
DELETE /api/data-sync/settings/845621?module=technologies
```

### Deletion Rules & Protections
1. **Soft Deletion:** Records are soft-deleted via Laravel SoftDeletes (`deleted_at` timestamp populated). Records are never physically deleted.
2. **System Records Protected:** Records where `is_system = true` cannot be deleted and return **HTTP 403 Forbidden** (`"System records cannot be deleted."`).
3. **Active References Protected:** Records referenced by existing projects, customers, child industries, or status histories return **HTTP 409 Conflict** (`"This record is currently in use and cannot be deleted."`).
4. **Default Status Protected:** The active default project status cannot be deleted and returns **HTTP 409 Conflict** (`"Default project status cannot be deleted."`).
5. **Idempotent Repeated Deletion:** Deleting a record that is already soft-deleted returns **HTTP 200 OK** with:
   ```json
   {
     "success": true,
     "message": "Record already deleted."
   }
   ```
6. **Missing Record:** Non-existent codes return **HTTP 404 Not Found**.

---

## 9. Error Responses

| Status Code | Reason | Example JSON Response |
|---|---|---|
| **200 OK** | Successful read, update, or idempotent delete | `{"success": true, "message": "Record updated successfully.", "data": {...}}` |
| **201 Created** | Successful creation | `{"success": true, "message": "Record created successfully.", "data": {...}}` |
| **401 Unauthorized** | Missing, malformed, or invalid API key | `{"success": false, "message": "Unauthenticated."}` |
| **403 Forbidden** | Missing required scope or attempt to delete system record | `{"success": false, "message": "Insufficient API permissions."}` |
| **404 Not Found** | Record code not found or soft-deleted | `{"success": false, "message": "Record not found."}` |
| **409 Conflict** | Soft-deleted code in sync, referenced record deletion, or idempotency payload mismatch | `{"success": false, "message": "The supplied code belongs to a deleted record."}` |
| **422 Unprocessable** | Missing/invalid module, validation failure, per_page > 100 | `{"success": false, "message": "Validation failed.", "errors": {"module": ["The module field is required."]}}` |
| **429 Too Many Requests** | Rate limit exceeded (60 req/min) | `{"success": false, "message": "Too Many Requests."}` |
| **500 Server Error** | Unexpected unhandled exception | `{"success": false, "message": "Server error."}` |

---

## 10. Backward Compatibility

The original module-specific endpoints continue to function without modification:

```http
GET    /api/data-sync/settings/{module}
GET    /api/data-sync/settings/{module}/{code}
POST   /api/data-sync/settings/{module}
PUT    /api/data-sync/settings/{module}/{code}
DELETE /api/data-sync/settings/{module}/{code}
```

Routing constraints in `routes/api.php` ensure that 6-digit codes (e.g. `/api/data-sync/settings/123456`) and the reserved `/sync` path route correctly to common endpoints without colliding with module names.

---

## 11. Idempotency & Concurrency

### Optional Header

```http
Idempotency-Key: <unique-uuid-or-hash>
```

### Key Caching & Isolation
- The cache key is namespaced per client, operation, and module:
  `data-sync:idempotency:sync:{api_client_id}:{module}:{sha1(idempotency_key)}`
- Cached responses persist for 10 minutes.
- Replaying the identical request with the same `Idempotency-Key` returns the cached status code (200 or 201) and body.
- Reusing an `Idempotency-Key` with a different request payload returns **HTTP 409 Conflict** (`"Idempotency-Key reuse with different request payload."`).
- Failed requests (HTTP 4xx/5xx) are never cached.

### Concurrency Characteristics & Limitations
- **Row-Level Locking:** Existing records are locked for update using `lockForUpdate()` inside a database transaction during `sync()`.
- **Concurrent Creation:** When two concurrent requests attempt to create a record with the same code simultaneously, the database unique key constraint guarantees that only one succeeds; the second request safely receives **HTTP 409 Conflict** (`"A record with this code already exists."`) rather than creating a duplicate.
- **Future SaaS Multi-Tenancy:** In multi-tenant environments with separate tenant databases, cache keys will incorporate the tenant identifier (`data-sync:idempotency:sync:{tenant_id}:{api_client_id}:{module}:{hash}`).

