# Data Sync – Settings Masters API Documentation

## Overview

The **Data Sync Settings API** allows external applications (CRM, ERP, and custom business software) to read, create, update, and soft-delete PMS Settings Masters using a unified REST interface and unique 6-digit `code` identifiers.

- **Base URL:** `/api/data-sync/settings`
- **Content-Type:** `application/json` (`Accept: application/json`)

---

## Authentication & Authorization

All endpoints use the existing PMS server-to-server `ApiClient` bearer token authentication (`api.key` middleware):

```http
Authorization: Bearer <key_id>.<secret>
```

### Required Scopes

| Scope | Permitted Operations |
|---|---|
| `settings.read` | `GET` (List and Get) |
| `settings.write` | `POST` (Create), `PUT` (Update), `DELETE` (Soft Delete) |

Read and write scopes are independent. A single `ApiClient` key can hold `meetings.read`, `settings.read`, and `settings.write` simultaneously.

### Rate Limiting

- Shared `throttle:api-client` rate limiter: **60 requests per minute** per `ApiClient` (shared across `/api/meetings/active` and `/api/data-sync/settings/*` for the same API key).
- Exceeding the limit returns **HTTP 429**:
  ```json
  {
    "success": false,
    "message": "Too Many Requests."
  }
  ```

---

## Supported Modules

Module slugs are validated against `config('constants.data_sync_modules')`:

| Slug (`{module}`) | Description |
|---|---|
| `technologies` | Technologies master |
| `industries` | Industries master (hierarchical via `parent_code`) |
| `project_categories` | Project Categories master |
| `project_statuses` | Project Statuses master |

Invalid module slugs return **HTTP 404**:
```json
{
  "success": false,
  "message": "Invalid Data Sync module."
}
```

---

## Endpoints

### 1. List Records
- **Method & URL:** `GET /api/data-sync/settings/{module}`
- **Required Scope:** `settings.read`

#### Query Parameters
| Parameter | Type | Rules / Notes | Default |
|---|---|---|---|
| `page` | integer | `>= 1` | `1` |
| `per_page` | integer | `1` to `100` | `50` |
| `search` | string | Max 255 chars; matches `name` or `code` (`LIKE`) | — |
| `is_active` | boolean | `0`, `1`, `true`, `false` (Laravel `boolean` rule: `0`/`1`/boolean) | All |
| `sort_by` | string | Allowlisted: `id`, `name`, `code`, `sort_order`, `is_active`, `created_at`, `updated_at` (plus `type`, `is_default`, `is_completed` for `project_statuses`) | `sort_order` |
| `sort_direction` | string | `asc` or `desc` | `asc` |

#### Sample Request
```http
GET /api/data-sync/settings/industries?is_active=1&page=1&per_page=50
Authorization: Bearer <token>
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Records retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Information Technology",
      "code": "743813",
      "parent_code": null,
      "sort_order": 1,
      "is_system": true,
      "is_active": true
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 2. Get Record by Code
- **Method & URL:** `GET /api/data-sync/settings/{module}/{code}`
- **Required Scope:** `settings.read`

#### Sample Request
```http
GET /api/data-sync/settings/project_statuses/839533
Authorization: Bearer <token>
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Record retrieved successfully.",
  "data": {
    "id": 1,
    "name": "Project Confirmed",
    "code": "839533",
    "color": "#6c757d",
    "type": "open",
    "sort_order": 1,
    "is_default": true,
    "is_completed": false,
    "is_system": true,
    "is_active": true
  }
}
```

---

### 3. Create Record
- **Method & URL:** `POST /api/data-sync/settings/{module}`
- **Required Scope:** `settings.write`
- **Optional Header:** `Idempotency-Key: <unique-request-key>`

#### Sample Request
```http
POST /api/data-sync/settings/industries
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Automotive",
  "code": "845621",
  "parent_code": null,
  "sort_order": 15,
  "is_active": true
}
```

#### Success Response (`201 Created`)
```json
{
  "success": true,
  "message": "Record created successfully.",
  "data": {
    "id": 15,
    "name": "Automotive",
    "code": "845621",
    "parent_code": null,
    "sort_order": 15,
    "is_system": false,
    "is_active": true
  }
}
```

---

### 4. Update Record
- **Method & URL:** `PUT /api/data-sync/settings/{module}/{code}`
- **Required Scope:** `settings.write`
- Supports partial field updates; unspecified fields are preserved. `code` cannot be modified.

#### Sample Request
```http
PUT /api/data-sync/settings/industries/845621
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Automotive Industry",
  "sort_order": 16,
  "is_active": true
}
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Record updated successfully.",
  "data": {
    "id": 15,
    "name": "Automotive Industry",
    "code": "845621",
    "parent_code": null,
    "sort_order": 16,
    "is_system": false,
    "is_active": true
  }
}
```

---

### 5. Delete Record (Soft Delete)
- **Method & URL:** `DELETE /api/data-sync/settings/{module}/{code}`
- **Required Scope:** `settings.write`

#### Sample Request
```http
DELETE /api/data-sync/settings/industries/845621
Authorization: Bearer <token>
```

#### Success Responses (`200 OK`)
- First deletion:
  ```json
  {
    "success": true,
    "message": "Record deleted successfully."
  }
  ```
- Already soft-deleted:
  ```json
  {
    "success": true,
    "message": "Record already deleted."
  }
  ```

---

## Module Fields & Validation Rules

### Technologies (`technologies`) & Project Categories (`project_categories`)
| Field | Create | Update | Notes |
|---|---|---|---|
| `name` | Required | Optional | String, max 255, unique among non-deleted records |
| `code` | Optional | Not editable | Exactly 6 digits (`digits:6`); auto-generated if omitted |
| `sort_order` | Optional | Optional | Integer `0..127`; defaults to `max(sort_order) + 1` on create |
| `is_active` | Optional | Optional | Boolean; defaults to `true` on create |
| `is_system` | Server-managed | Not editable | Always `false` on API create; ignored if supplied |

### Industries (`industries`)
Same as above, plus:
| Field | Create | Update | Notes |
|---|---|---|---|
| `parent_code` | Optional | Optional | Nullable 6-digit code of an active (non-deleted) parent industry. Self-parenting and circular hierarchies are rejected (`422`). Explicit `null` clears the parent; omitting on `PUT` preserves the existing parent. |

### Project Statuses (`project_statuses`)
| Field | Create | Update | Notes |
|---|---|---|---|
| `name` | Required | Optional | String, max 255, unique among non-deleted records |
| `code` | Optional | Not editable | Exactly 6 digits (`digits:6`); auto-generated if omitted |
| `color` | Optional | Optional | Nullable hex color (`#RGB` or `#RRGGBB`) |
| `type` | Required | Optional | Must be one of `config('project_constants.project_status_types')` (`open`, `in_progress`, `completed`, `archived`) |
| `sort_order` | Optional | Optional | Integer `0..127` |
| `is_default` | Optional | Optional | Boolean; setting `true` atomically clears `is_default` on other statuses inside a transaction |
| `is_completed` | Server-derived | Server-derived | Automatically derived as `type === 'completed'`; external values are ignored |
| `is_active` | Optional | Optional | Boolean |
| `is_system` | Server-managed | Not editable | Always `false` on API create |

---

## Important Business Rules

- **Unique Code Identification:** All external GET/PUT/DELETE operations identify records by their 6-digit `code` (the `id` returned in responses is informational only).
- **Duplicate Code Handling:** Supplying a `code` on `POST` that already exists (including soft-deleted records) returns **HTTP 409** rather than silently regenerating a new code.
- **Soft Deletion & Relationship Protection:**
  - Soft-deleted records are excluded from `GET` endpoints.
  - System records (`is_system = true`) cannot be deleted (**HTTP 403**).
  - Referenced records cannot be deleted (**HTTP 409**):
    - `technologies`: referenced in `project_technology`
    - `industries`: has active child industries or referenced by `customers`
    - `project_categories`: referenced in `projects.project_category_ids`
    - `project_statuses`: `is_default = true`, referenced in `projects.status_id`, or referenced in `project_status_histories`
  - To retire a referenced record without deleting it, set `is_active: false` via `PUT`.
- **Idempotency (`POST`):**
  - Sending an `Idempotency-Key` header caches successful `201` responses in Laravel Cache for **10 minutes**, scoped by `api_client.id` and `{module}`.
  - Repeating the same key with the same payload returns the cached `201` response without creating a duplicate; reusing the key with a different payload returns **HTTP 409**.
  - Failed requests (`422`, `409`, `500`) are not cached.
  - *Limitation:* Cache-backed idempotency does not use a distributed lock or persistent database table, so simultaneous concurrent requests with the same `Idempotency-Key` are not serialized.

---

## Standard Error Responses

| Status | Meaning | Example Body |
|---|---|---|
| `401` | Missing, invalid, revoked, or expired API key | `{ "success": false, "message": "Unauthenticated." }` |
| `403` | Missing scope or system record deletion | `{ "success": false, "message": "Insufficient API permissions." }` / `"System records cannot be deleted."` |
| `404` | Invalid module or unknown/deleted record code | `{ "success": false, "message": "Invalid Data Sync module." }` / `"Record not found."` |
| `409` | Duplicate code, idempotency mismatch, or record in use | `{ "success": false, "message": "A record with this code already exists." }` |
| `422` | Validation error | `{ "success": false, "message": "Validation failed.", "errors": { ... } }` |
| `429` | Rate limit exceeded (60 req/min per API client) | `{ "success": false, "message": "Too Many Requests." }` |

