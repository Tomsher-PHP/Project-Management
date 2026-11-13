# PMS Data Sync API Documentation

## 1. Overview

The **PMS Data Sync API** provides a secure, unified REST interface for bidirectional and inbound synchronization between external business systems (CRM, ERP, and internal automation tools) and the Project Management System (PMS).

The API suite currently covers:
1. **Settings Masters:** Manage core master data (Technologies, Industries, Project Categories, Project Statuses) using standardized 6-digit numeric codes.
2. **Customer Management:** Inbound synchronization and updates of customer organizations and contacts.
3. **Project Management:** Inbound creation and updates of projects, estimates, timelines, and relationships.
4. **Permanent External Mapping:** Durable database-backed identifier mapping (`data_sync_entity_mappings`) to ensure idempotent processing and prevent duplicate records.

---

## 2. Base URL & Protocol

All Data Sync APIs follow an unversioned, direct endpoint structure. Do not include `/v1`, `/v2`, or other version prefixes.

- **Base URL:** `{{base_url}}` (e.g., `https://pms.yourcompany.com` or `http://localhost:8000`)
- **Transport:** HTTPS (TLS 1.2+ required in production)
- **Protocol:** RESTful JSON over HTTP

### Endpoint Path Structure

| Module | Method | Path |
|---|---|---|
| **Settings Masters** | `GET`, `POST` | `/api/data-sync/settings/{module}` |
| **Settings Masters** | `GET`, `PUT`, `DELETE` | `/api/data-sync/settings/{module}/{code}` |
| **Project & Customer Creation** | `POST` | `/api/data-sync/projects` |
| **Project Update** | `PUT` | `/api/integrations/projects/{project_code}` |
| **Customer Update** | `PUT` | `/api/integrations/customers/{customer_code}` |

---

## 3. Authentication & Authorization

All Data Sync endpoints enforce server-to-server API Key authentication via the `api.key` middleware.

### Request Headers

```http
Authorization: Bearer <key_id>.<secret>
Accept: application/json
Content-Type: application/json
```

Requests without a valid `Authorization` header or with an invalid, expired, or revoked API key return **HTTP 401 Unauthorized**:
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

---

## 4. API Client Permissions and Scopes

API authorization is scope-based. Each `ApiClient` key is granted an array of permitted scopes upon creation.

| Scope | Permitted Endpoints & Operations |
|---|---|
| `settings.read` | Read-only access to Settings Masters (`GET /api/data-sync/settings/*`) |
| `settings.write` | Write access to Settings Masters (`POST`, `PUT`, `DELETE /api/data-sync/settings/*`) |
| `projects.write` | Write access to Project and Customer Data Sync (`POST /api/data-sync/projects`, `PUT /api/integrations/projects/*`, `PUT /api/integrations/customers/*`) |
| `meetings.read` | Access to Active Meetings endpoint (`GET /api/meetings/active`) |

If an authenticated client lacks the required scope for an endpoint, the API returns **HTTP 403 Forbidden**:
```json
{
  "success": false,
  "message": "Insufficient API permissions."
}
```

---

## 5. Common Request Headers

| Header | Required | Description / Usage |
|---|---|---|
| `Authorization` | **Yes** | Bearer token in the format `<key_id>.<secret>` |
| `Accept` | **Yes** | Must be `application/json` |
| `Content-Type` | **Yes** (POST/PUT) | Must be `application/json` |
| `Idempotency-Key` | Optional | Unique string (UUID or hash) sent with `POST` requests to cache and replay the response for 10 minutes |

### Rate Limiting

All Data Sync endpoints share the `throttle:api-client` rate limiter:
- **Limit:** 60 requests per minute per `ApiClient`.
- When exceeded, returns **HTTP 429 Too Many Requests**:
  ```json
  {
    "success": false,
    "message": "Too Many Requests."
  }
  ```

---

## 6. Common API Responses

PMS Data Sync APIs return standardized JSON responses:

### Success Response Envelope
```json
{
  "success": true,
  "message": "Human-readable success message.",
  "data": { ... }
}
```

### Error Response Envelope
```json
{
  "success": false,
  "message": "Human-readable error description.",
  "errors": { ... }
}
```

---

## 7. Settings APIs

Settings APIs manage PMS master lookup data. Master records are identified externally using unique 6-digit numeric strings (`code`).

### Supported Modules

| Slug (`{module}`) | Model | Description |
|---|---|---|
| `technologies` | `Technology` | Core technology stack master |
| `industries` | `Industry` | Hierarchical business industry sectors |
| `project_categories` | `ProjectCategory` | Classification categories for projects |
| `project_statuses` | `ProjectStatus` | Project lifecycle status master |

Invalid module slugs return **HTTP 404**:
```json
{
  "success": false,
  "message": "Invalid Data Sync module."
}
```

---

### 7.1. List Settings Records
- **Method & URL:** `GET /api/data-sync/settings/{module}`
- **Required Scope:** `settings.read`

#### Query Parameters
| Parameter | Type | Default | Description |
|---|---|---|---|
| `page` | integer | `1` | Page number (`>= 1`) |
| `per_page` | integer | `50` | Results per page (`1` to `100`) |
| `search` | string | `null` | Filters records matching `name` or `code` (`LIKE %term%`) |
| `is_active` | boolean | `null` | Filters by active status (`1`, `0`, `true`, `false`) |
| `sort_by` | string | `sort_order` | Allowlisted: `id`, `name`, `code`, `sort_order`, `is_active`, `created_at` |
| `sort_direction` | string | `asc` | `asc` or `desc` |

#### Sample Request
```http
GET /api/data-sync/settings/technologies?is_active=1&page=1&per_page=50
Authorization: Bearer <api_key>
Accept: application/json
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Records retrieved successfully.",
  "data": [
    {
      "id": 1,
      "name": "Laravel Framework",
      "code": "500001",
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

### 7.2. Get Settings Record by Code
- **Method & URL:** `GET /api/data-sync/settings/{module}/{code}`
- **Required Scope:** `settings.read`

#### Sample Request
```http
GET /api/data-sync/settings/industries/743813
Authorization: Bearer <api_key>
Accept: application/json
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Record retrieved successfully.",
  "data": {
    "id": 1,
    "name": "Information Technology",
    "code": "743813",
    "parent_code": null,
    "sort_order": 1,
    "is_system": true,
    "is_active": true
  }
}
```

---

### 7.3. Create Settings Record
- **Method & URL:** `POST /api/data-sync/settings/{module}`
- **Required Scope:** `settings.write`
- **Optional Header:** `Idempotency-Key: <unique-key>`

#### Fields by Module

**Technologies (`technologies`) & Project Categories (`project_categories`):**
| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | **Yes** | Name, max 255 characters. Must be unique among non-deleted records. |
| `code` | string | Optional | Exactly 6 digits (`digits:6`). Auto-generated if omitted. |
| `sort_order` | integer | Optional | `0..127`. Defaults to `max(sort_order) + 1`. |
| `is_active` | boolean | Optional | Defaults to `true`. |

**Industries (`industries`):**
Same as above, plus:
| Field | Type | Required | Description |
|---|---|---|---|
| `parent_code` | string | Optional | Nullable 6-digit code of an active parent industry. Self-parenting and circular references are rejected (`422`). |

**Project Statuses (`project_statuses`):**
| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | **Yes** | Status name, max 255 characters. |
| `code` | string | Optional | Exactly 6 digits (`digits:6`). Auto-generated if omitted. |
| `color` | string | Optional | Hex color code (e.g., `#0d6efd`). |
| `type` | string | **Yes** | Must be one of: `open`, `in_progress`, `completed`, `archived`. |
| `sort_order` | integer | Optional | `0..127`. |
| `is_default` | boolean | Optional | When `true`, automatically clears `is_default` on all other statuses. |
| `is_active` | boolean | Optional | Defaults to `true`. |
| `is_completed`| boolean| Server-derived | Automatically set to `true` when `type === 'completed'`. |

#### Sample Request
```http
POST /api/data-sync/settings/industries
Authorization: Bearer <api_key>
Content-Type: application/json

{
  "name": "Artificial Intelligence",
  "code": "743820",
  "parent_code": "743813",
  "sort_order": 10,
  "is_active": true
}
```

#### Success Response (`201 Created`)
```json
{
  "success": true,
  "message": "Record created successfully.",
  "data": {
    "id": 12,
    "name": "Artificial Intelligence",
    "code": "743820",
    "parent_code": "743813",
    "sort_order": 10,
    "is_system": false,
    "is_active": true
  }
}
```

---

### 7.4. Update Settings Record
- **Method & URL:** `PUT /api/data-sync/settings/{module}/{code}`
- **Required Scope:** `settings.write`
- **Behavior:** Partial updates supported. The `code` field cannot be modified.

#### Sample Request
```http
PUT /api/data-sync/settings/industries/743820
Authorization: Bearer <api_key>
Content-Type: application/json

{
  "name": "Applied Artificial Intelligence",
  "sort_order": 12
}
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Record updated successfully.",
  "data": {
    "id": 12,
    "name": "Applied Artificial Intelligence",
    "code": "743820",
    "parent_code": "743813",
    "sort_order": 12,
    "is_system": false,
    "is_active": true
  }
}
```

---

### 7.5. Delete Settings Record (Soft Delete)
- **Method & URL:** `DELETE /api/data-sync/settings/{module}/{code}`
- **Required Scope:** `settings.write`

#### Protection Rules
- System records (`is_system = true`) cannot be deleted (**HTTP 403**).
- Records currently referenced by projects, customers, or child industries cannot be deleted (**HTTP 409**). To retire referenced records, set `is_active: false` via `PUT`.

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Record deleted successfully."
}
```

---

## 8. Customer APIs

### Update Customer
- **Method & URL:** `PUT /api/integrations/customers/{customer_code}`
- **Required Scope:** `projects.write`
- **Path Parameter:**
  - `customer_code`: Existing PMS customer code (e.g., `CUS00025`).

#### Supported Fields
| Field | Type | Rules | Description |
|---|---|---|---|
| `name` | string | Optional, max 255 | Company/organization name |
| `email` | string | Optional, email, max 150 | General contact email |
| `industry_code` | string | Optional, digits:6 | Resolves PMS `Industry` record by code |
| `website` | string | Optional, url, max 255 | Official company website |
| `country_code` | string | Optional, 2-3 chars | ISO country code (normalized to uppercase) |
| `emirate` | string | Optional, max 255 | Emirate / State / Region |
| `company_address` | string | Optional, text | Physical company address |
| `sales_person_email`| string | Optional, email | Resolves PMS `User` record |
| `is_active` | boolean| Optional | Account active status |
| `external_code` | string | Optional, max 255 | CRM customer code (verified against mappings) |
| `external_id` | string | Optional, max 255 | CRM customer primary key ID |
| `contacts` | array | Optional | List of customer contact modifications |
| `contacts.*.id` | integer| Optional | Existing PMS contact ID (verifies ownership) |
| `contacts.*.name` | string | Required for new | Contact person full name |
| `contacts.*.email`| string | Optional, email | Contact personal email |
| `contacts.*.mobile`| string | Optional | Mobile number |
| `contacts.*.whatsapp`| string | Optional | WhatsApp contact number |
| `contacts.*.landline`| string | Optional | Landline phone number |
| `contacts.*.designation`| string | Optional | Job title / designation |
| `contacts.*.is_primary`| boolean| Optional | Primary contact designation |
| `contacts.*.is_active` | boolean| Optional | Contact active status |

#### Contact Rules
1. **Additive Updates:** Existing contacts not mentioned in the request remain untouched. Passing `contacts: []` does not delete existing contacts.
2. **Identification:** Existing contacts must supply their PMS contact `id`. Contacts without an `id` are created as new contacts.
3. **Cross-Customer Protection:** Supplying a contact `id` that belongs to another customer returns **HTTP 422**.
4. **Primary Contact Management:** A customer can have only one primary contact. Setting any contact to `is_primary: true` automatically demotes any previously designated primary contact. Supplying multiple primary contacts in a single payload returns **HTTP 422**.

#### Sample Request
```http
PUT /api/integrations/customers/CUS00025
Authorization: Bearer <api_key>
Content-Type: application/json

{
  "name": "ABC Technologies International LLC",
  "email": "contact@abctech.ae",
  "industry_code": "743813",
  "country_code": "AE",
  "emirate": "Dubai",
  "website": "https://abctech.ae",
  "company_address": "Office 402, Business Bay, Dubai",
  "is_active": true,
  "contacts": [
    {
      "id": 15,
      "name": "Ahmed Mohammed",
      "designation": "Managing Director",
      "mobile": "+971501234567",
      "is_primary": true
    },
    {
      "name": "Fatima Al-Zahra",
      "email": "fatima@abctech.ae",
      "mobile": "+971509988776",
      "designation": "Operations Lead",
      "is_primary": false
    }
  ]
}
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Customer updated successfully.",
  "data": {
    "customer_id": 25,
    "customer_code": "CUS00025",
    "updated": true,
    "contacts": {
      "created": 1,
      "updated": 1
    }
  }
}
```

---

## 9. Project APIs

### 9.1. Create Customer and Projects
- **Method & URL:** `POST /api/data-sync/projects`
- **Required Scope:** `projects.write`
- **Optional Header:** `Idempotency-Key: <unique-key>`
- **Description:** Atomically synchronizes one customer organization, customer contacts, and one or more projects (up to 50 projects per request).

#### Payload Structure
The top-level payload requires `customer` and `projects`:

```json
{
  "customer": { ... },
  "projects": [ { ... }, { ... } ]
}
```

#### Customer Object (`customer`)
| Field | Type | Required | Description |
|---|---|---|---|
| `pms_customer_code` | string | Optional | If supplied, reuses existing PMS customer (`CUS00001`). Must exist in PMS. |
| `name` | string | Required if new | Customer company name |
| `email` | string | Optional | Email address |
| `industry_code` | string | Optional | 6-digit Industry code |
| `website` | string | Optional | Website URL |
| `country_code` | string | Optional | 2-3 character country code |
| `emirate` | string | Optional | Emirate or state |
| `company_address` | string | Optional | Address text |
| `sales_person_email`| string | Optional | Email of PMS sales user |
| `external_code` | string | Optional | CRM customer code |
| `external_id` | string | Optional | CRM customer ID |
| `is_active` | boolean| Optional | Defaults to `true` |
| `contacts` | array | Required if new | Array of contact objects (at least 1 contact required for new customers) |
| `contacts.*.name` | string | **Yes** | Contact name |
| `contacts.*.is_primary`| boolean| **Yes** | Exactly one contact must have `is_primary: true` |
| `contacts.*.email`| string | Optional | Contact email (required if other comms omitted) |
| `contacts.*.mobile`| string | Optional | Contact mobile (required if other comms omitted) |
| `contacts.*.whatsapp`| string | Optional | WhatsApp number |
| `contacts.*.landline`| string | Optional | Landline phone |
| `contacts.*.designation`| string | Optional | Job title |
| `contacts.*.is_active`| boolean| Optional | Defaults to `true` |

#### Projects Array (`projects[]`)
| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | **Yes** | Project title, max 255 characters |
| `external_code` | string | Optional | CRM project unique code |
| `external_id` | string | Optional | CRM project unique ID |
| `project_flow` | string | Optional | `agile` (default) or `linear` |
| `priority` | string | Optional | `low`, `medium` (default), `high`, `urgent` |
| `status_code` | string | Optional | 6-digit project status code. Defaults to configured default status. |
| `stage_code` | string | Optional | 6-digit project stage code. Defaults to configured default stage. |
| `start_date` | string | **Yes** | Date format `Y-m-d` |
| `end_date` | string | **Yes** | Date format `Y-m-d` (`>= start_date`) |
| `customer_end_date` | string | Optional | Date format `Y-m-d` (`>= end_date`) |
| `estimated_time_minutes` | number | Optional | Total project estimated minutes (converted to seconds) |
| `customer_estimate_minutes`| number | Optional | Customer-facing estimated minutes (converted to seconds) |
| `default_task_estimate_seconds`| integer | Optional | Default estimate per task in seconds |
| `domain` | string | Optional | Project domain / URL |
| `sales_person_email` | string | Optional | Email of salesperson user. Defaults to customer salesperson. |
| `category_codes` | array | Optional | Array of 6-digit Project Category codes |
| `technology_codes` | array | Optional | Array of 6-digit Technology codes |
| `service_type_codes` | array | Optional | Array of 6-digit Service Type codes |
| `parent_project_code` | string | Optional | Existing PMS project code to link as parent |
| `default_billable` | boolean | Optional | Defaults to `true` |
| `is_active` | boolean | Optional | Defaults to `true` |

#### Sample Request (1 Customer, 2 Projects)
```http
POST /api/data-sync/projects
Authorization: Bearer <api_key>
Content-Type: application/json
Idempotency-Key: crm-sync-batch-20261113-001

{
  "customer": {
    "external_code": "CRM-CUS-1025",
    "external_id": "9021",
    "name": "ABC Technologies LLC",
    "email": "info@abctech.ae",
    "industry_code": "743813",
    "website": "https://abctech.ae",
    "country_code": "AE",
    "emirate": "Dubai",
    "company_address": "Level 14, Al Saada Tower, Dubai",
    "sales_person_email": "sales@abctech.ae",
    "contacts": [
      {
        "name": "Ahmed Mohammed",
        "email": "ahmed@abctech.ae",
        "mobile": "+971501234567",
        "designation": "Director",
        "is_primary": true
      },
      {
        "name": "Sarah Jenkins",
        "email": "sarah@abctech.ae",
        "mobile": "+971507654321",
        "designation": "Technical Lead",
        "is_primary": false
      }
    ]
  },
  "projects": [
    {
      "external_code": "CRM-PRJ-1001",
      "external_id": "PRJ-901",
      "name": "Corporate Portal Redesign",
      "project_flow": "agile",
      "priority": "high",
      "status_code": "100001",
      "stage_code": "200001",
      "start_date": "2026-12-01",
      "end_date": "2027-02-28",
      "customer_end_date": "2027-03-15",
      "estimated_time_minutes": 12000,
      "customer_estimate_minutes": 10800,
      "default_task_estimate_seconds": 3600,
      "domain": "portal.abctech.ae",
      "sales_person_email": "sales@abctech.ae",
      "technology_codes": ["500001", "500002"],
      "category_codes": ["600001"],
      "service_type_codes": ["700001"],
      "default_billable": true,
      "is_active": true
    },
    {
      "external_code": "CRM-PRJ-1002",
      "external_id": "PRJ-902",
      "name": "Mobile Companion Application",
      "project_flow": "agile",
      "priority": "medium",
      "status_code": "100001",
      "stage_code": "200001",
      "start_date": "2027-01-15",
      "end_date": "2027-04-30",
      "estimated_time_minutes": 9000,
      "sales_person_email": "sales@abctech.ae",
      "technology_codes": ["500002"],
      "category_codes": ["600002"],
      "default_billable": true,
      "is_active": true
    }
  ]
}
```

#### Success Response (`201 Created` - When records are newly created)
```json
{
  "success": true,
  "message": "Customer and projects processed successfully.",
  "data": {
    "customer": {
      "customer_id": 25,
      "customer_code": "CUS00025",
      "created": true
    },
    "projects": [
      {
        "project_id": 101,
        "project_code": "PRJ00101",
        "external_code": "CRM-PRJ-1001",
        "external_id": "PRJ-901",
        "created": true
      },
      {
        "project_id": 102,
        "project_code": "PRJ00102",
        "external_code": "CRM-PRJ-1002",
        "external_id": "PRJ-902",
        "created": true
      }
    ]
  }
}
```

#### Repeated Request Response (`200 OK` - When records are reused via mapping)
```json
{
  "success": true,
  "message": "Customer and projects processed successfully.",
  "data": {
    "customer": {
      "customer_id": 25,
      "customer_code": "CUS00025",
      "created": false
    },
    "projects": [
      {
        "project_id": 101,
        "project_code": "PRJ00101",
        "external_code": "CRM-PRJ-1001",
        "external_id": "PRJ-901",
        "created": false
      },
      {
        "project_id": 102,
        "project_code": "PRJ00102",
        "external_code": "CRM-PRJ-1002",
        "external_id": "PRJ-902",
        "created": false
      }
    ]
  }
}
```

---

### 9.2. Update Project
- **Method & URL:** `PUT /api/integrations/projects/{project_code}`
- **Required Scope:** `projects.write`
- **Path Parameter:**
  - `project_code`: Existing PMS project code (e.g., `PRJ00101`).

#### Supported Partial Update Fields
All fields are optional. Omitted fields strictly retain their existing database values.

- `name`: string, max 255
- `project_flow`: `agile`, `linear`
- `priority`: `low`, `medium`, `high`, `urgent`
- `status_code`: string (resolves status, defaults if invalid)
- `stage_code`: string (resolves stage, defaults if invalid)
- `start_date`: date `Y-m-d`
- `end_date`: date `Y-m-d`
- `customer_end_date`: nullable date `Y-m-d`
- `estimated_time_minutes`: nullable numeric
- `customer_estimate_minutes`: nullable numeric
- `default_task_estimate_seconds`: nullable integer
- `domain`: nullable string, max 255
- `sales_person_email`: nullable email (resolves existing PMS user)
- `category_codes`: array of category codes (`[]` clears categories)
- `technology_codes`: array of technology codes (`[]` clears technologies)
- `service_type_codes`: array of service type codes (`[]` clears service types)
- `parent_project_code`: nullable string (validates against self-parenting and circular hierarchies)
- `default_billable`: boolean
- `is_active`: boolean
- `external_code`: nullable string (verified against client mapping)
- `external_id`: nullable string (verified against client mapping)

#### Sample Request
```http
PUT /api/integrations/projects/PRJ00101
Authorization: Bearer <api_key>
Content-Type: application/json

{
  "name": "Corporate Portal Redesign - Phase 2",
  "priority": "urgent",
  "status_code": "100002",
  "stage_code": "200002",
  "end_date": "2027-03-31",
  "estimated_time_minutes": 15000,
  "technology_codes": ["500001", "500002"],
  "category_codes": ["600001"]
}
```

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Project updated successfully.",
  "data": {
    "project_id": 101,
    "project_code": "PRJ00101",
    "updated": true
  }
}
```

---

## 10. Shared Reference Resolution Rules

| Reference Field | Empty / Null Value | Invalid Code / Email | Update Behavior when Omitted |
|---|---|---|---|
| `customer.pms_customer_code` | Runs new customer creation | Returns **HTTP 422** | N/A |
| `status_code` | Uses system default status (`is_default = 1`) | Uses system default status (`is_default = 1`) | Retains current project status |
| `stage_code` | Uses system default stage (`is_default = 1`) | Uses system default stage (`is_default = 1`) | Retains current project stage |
| `technology_codes` | Clears attached technologies (`[]`) | Ignores invalid codes; attaches valid ones | Retains existing technologies |
| `category_codes` | Clears attached categories (`[]`) | Ignores invalid codes; attaches valid ones | Retains existing categories |
| `service_type_codes` | Clears attached service types (`[]`) | Ignores invalid codes; attaches valid ones | Retains existing service types |
| `industry_code` | Sets to `null` | Returns **HTTP 422** | Retains current customer industry |
| `country_code` | Sets to `null` | Returns **HTTP 422** | Retains current customer country |
| `sales_person_email` | Sets to `null` | Returns **HTTP 422** | Retains current salesperson |
| `parent_project_code` | Sets to `null` | Returns **HTTP 422** (also rejects self/circular) | Retains current parent project |

---

## 11. External Record Mapping

External CRM entities are mapped permanently to PMS internal records in the dedicated `data_sync_entity_mappings` table:

```
[External CRM]  --->  [data_sync_entity_mappings]  --->  [PMS Internal Records]
external_code           api_client_id                      customers (id)
external_id             entity_type ('customer'/'project') projects (id)
                        external_code / external_id
                        local_id
```

### Table Structure
- `api_client_id`: Identifies the originating API client (foreign key to `api_clients`).
- `entity_type`: `customer` or `project`.
- `external_code`: CRM string code (nullable).
- `external_id`: CRM primary key ID (nullable).
- `local_id`: Bigint referencing PMS `customers.id` or `projects.id`.

### Guarantees
- **Client Isolation:** Each API client has independent mappings. Different CRM instances can use identical external codes without collision.
- **Uniqueness:** Composite unique indexes enforce uniqueness for `(api_client_id, entity_type, external_code)` and `(api_client_id, entity_type, external_id)`.
- **Safe Enrichment:** If an entity was originally mapped with only one identifier, subsequent requests providing both identifiers safely enrich the existing mapping record.
- **Conflict Detection:** If supplied `external_code` and `external_id` resolve to different PMS records, or belong to a different customer/project, the API returns **HTTP 409 Conflict**.

---

## 12. Idempotency & Duplicate Prevention

PMS provides two layers of duplicate protection:

1. **Short-Term Request Idempotency (`Idempotency-Key`):**
   - Clients may send an `Idempotency-Key` header with `POST` requests.
   - Successful responses (`201` or `200`) are cached for **10 minutes** scoped to the client.
   - Repeating the request with the identical payload returns the cached response immediately.
   - Reusing the key with a modified payload returns **HTTP 409 Conflict**.
2. **Permanent Entity Deduplication:**
   - Permanent mapping works independently of cache expiration.
   - Even if the 10-minute cache expires or `Idempotency-Key` is omitted, requests referencing existing `external_code` or `external_id` automatically resolve the mapped customer or project and return **HTTP 200 OK** without duplicating records.

---

## 13. Standard Error Responses

### HTTP 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### HTTP 403 Forbidden
```json
{
  "success": false,
  "message": "Insufficient API permissions."
}
```

### HTTP 404 Not Found
```json
{
  "success": false,
  "message": "Project not found."
}
```

### HTTP 409 Conflict (External Mapping Mismatch)
```json
{
  "success": false,
  "message": "External mapping belongs to a different project."
}
```

### HTTP 409 Conflict (PMS Code Mismatch)
```json
{
  "success": false,
  "message": "Conflicting PMS customer code."
}
```

### HTTP 422 Unprocessable Content (Validation Failure)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "end_date": [
      "The end date must be greater than or equal to start date."
    ],
    "sales_person_email": [
      "The selected sales person email does not exist."
    ]
  }
}
```

### HTTP 429 Too Many Requests
```json
{
  "success": false,
  "message": "Too Many Requests."
}
```

### HTTP 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to process project data synchronization."
}
```

---

## 14. Integration Guidelines & Best Practices

1. **Store Generated PMS Codes:** Always capture and store `customer_code` and `project_code` returned by the API for direct future update calls.
2. **Include External Identifiers:** Send `external_code` and/or `external_id` on both customer and project payloads to establish permanent mapping on the first sync.
3. **Use Idempotency Keys:** For network-sensitive or automated batch runs, generate a unique UUID v4 and pass it in the `Idempotency-Key` header.
4. **Partial Updates for Changes:** When updating existing records, send only the fields that have changed. Do not send unchanged fields as null.
5. **Handle Relationships by Code:** Pass 6-digit codes for technologies, categories, and service types. Valid codes are linked; unrecognized codes are safely ignored.

---

## 15. Future Module Organization

As additional PMS modules are integrated into the Data Sync suite (such as Users, Meetings, Leaves, and Broadcasts), their documentation and Postman requests must be appended directly into this centralized document and collection under matching top-level sections. No separate documentation files or collections should be created.
