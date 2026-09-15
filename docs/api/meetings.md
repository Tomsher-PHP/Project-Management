# External Meetings API Integration Guide

This guide provides technical documentation and integration instructions for external server-to-server applications integrating with Azooshi Project Management System (PMS).

---

## 1. Overview

The **External Meetings API** provides a secure, server-to-server HTTP endpoint for trusted external applications to retrieve currently active Scheduled Meetings from Azooshi PMS.

### Key Characteristics
* **Endpoint**: `GET /api/meetings/active`
* **Authentication**: Application-to-Application Bearer API Key (No username/password, web session, browser cookie, or Spatie user permissions required).
* **Scope Required**: `meetings.read`
* **Rate Limit**: 60 requests per minute per API client identity.
* **No Versioning**: Direct endpoint access (`/api/meetings/active`).

---

## 2. Architecture & Pipeline

```text
External Application
        │
        ▼ (Authorization: Bearer YOUR_API_KEY)
1. AuthenticateApiKey Middleware
        │  ├── Missing / Invalid / Revoked / Expired → 401 Unauthorized
        │  └── Valid Credentials → Attach ApiClient to Request
        ▼
2. ThrottleRequests Middleware (throttle:api-client)
        │  ├── > 60 req/min for ApiClient → 429 Too Many Requests
        │  └── <= 60 req/min → Pass
        ▼
3. MeetingController@active
        │  ├── Missing 'meetings.read' Scope → 403 Forbidden
        │  └── Has Scope → Execute Active Meetings Query
        ▼
4. MeetingService & MeetingResource
        │  ├── Filter: meetingStatus = 'scheduled' AND end_at >= now()
        │  └── Ordering: start_at ASC, id ASC
        ▼
200 OK JSON Response
```

---

## 3. Base URL & Endpoint

* **Production Placeholder**: `https://your-pms-domain.com/api/meetings/active`
* **Local Development**: `http://project.test/api/meetings/active`

---

## 4. API Key Management (CLI Administration)

API credentials represent external application identities, not human user accounts. Administrators issue and manage API credentials via Artisan CLI commands.

### Create an API Client
```bash
php artisan api-client:create \
  --name="External Calendar Application" \
  --scope="meetings.read" \
  --expires-at="2027-12-31"
```

* **`--name`**: Human-readable name of the external application (prompted interactively if omitted).
* **`--scope`**: Scope(s) granted. Currently supported scope: `meetings.read`.
* **`--expires-at`**: (Optional) Expiration date (`YYYY-MM-DD` or `never`).

> [!WARNING]
> **Single Exposure Notice**: The full raw API key is displayed **only once** upon creation. Azooshi stores only a deterministic SHA-256 hash (`secret_hash`). Store the raw key in a secure location immediately.

### Revoke an API Client
```bash
php artisan api-client:revoke azo_live_a1b2c3d4e5
```
Sets `is_active = false`. The credential is retained for audit history, but all subsequent authentication attempts will be rejected with `401 Unauthorized` immediately.

### Rotate API Secret
```bash
php artisan api-client:rotate azo_live_a1b2c3d4e5
```
Generates a new random secret and replaces `secret_hash`. The old secret is invalidated immediately. The new raw key is displayed **only once**.

---

## 5. API Key Format & Storage

### Credential Structure
```text
azo_live_a1b2c3d4e5.azo_sec_7x9m2k4p8n1v3c5x7z9q2w4e6r8t0y2u4i6o8p0a
└── Key ID ───────┘ └── Secret Component ─────────────────────────┘
```

* **Public Key ID**: Prefix `azo_live_` followed by public identifier.
* **Secret Component**: Prefix `azo_sec_` followed by cryptographically secure random bytes.
* **Full Bearer Token**: `<Key_ID>.<Secret_Component>`

### Recommended Storage
Store the API key in environment variables or a secret manager on the external application server:
```env
AZOOSHI_API_TOKEN=azo_live_a1b2c3d4e5.azo_sec_7x9m2k4p8n1v3c5x7z9q2w4e6r8t0y2u4i6o8p0a
```

> [!CAUTION]
> Never commit API keys to version control repository or include them in browser/frontend JavaScript applications.

---

## 6. HTTP Request Specification

### Endpoint
```http
GET /api/meetings/active HTTP/1.1
Host: your-pms-domain.com
Authorization: Bearer YOUR_API_KEY
Accept: application/json
```

### Request Headers
| Header | Value | Description |
| :--- | :--- | :--- |
| `Authorization` | `Bearer YOUR_API_KEY` | Plaintext raw API key issued by `api-client:create`. |
| `Accept` | `application/json` | Required to receive JSON formatted responses. |

* **Body**: None.
* **Query Parameters**: None.

---

## 7. Integration Code Examples

### cURL
```bash
curl -X GET "https://your-pms-domain.com/api/meetings/active" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Accept: application/json"
```

### Laravel (PHP)
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.azooshi.api_key'))
    ->acceptJson()
    ->get('https://your-pms-domain.com/api/meetings/active');

if ($response->successful()) {
    $meetings = $response->json('data');
}
```

### Node.js (Fetch)
```javascript
const response = await fetch('https://your-pms-domain.com/api/meetings/active', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${process.env.AZOOSHI_API_KEY}`,
    'Accept': 'application/json'
  }
});

if (response.ok) {
  const result = await response.json();
  console.log(result.data);
}
```

### Python (Requests)
```python
import os
import requests

api_key = os.getenv('AZOOSHI_API_KEY')
headers = {
    'Authorization': f'Bearer {api_key}',
    'Accept': 'application/json'
}

response = requests.get('https://your-pms-domain.com/api/meetings/active', headers=headers)

if response.status_code == 200:
    data = response.json().get('data', [])
```

---

## 8. Response Specification

### HTTP 200 Success Response
```json
{
    "success": true,
    "data": [
        {
            "id": 101,
            "title": "Quarterly Strategy Review",
            "description": "Align on Q4 deliverables and team goals.",
            "start_at": "2026-09-14T10:00:00.000000Z",
            "end_at": "2026-09-14T11:30:00.000000Z",
            "url": "https://meet.google.com/abc-defg-hij",
            "location_details": "Conference Room B",
            "rescheduled_from_id": null,
            "status": {
                "id": 1,
                "code": "scheduled",
                "name": "Scheduled",
                "color": "#10B981"
            },
            "meeting_type": {
                "id": 2,
                "name": "Strategy Review",
                "color": "#3B82F6"
            },
            "location": {
                "id": 1,
                "name": "Google Meet"
            },
            "project": {
                "id": 45,
                "name": "Project Alpha"
            },
            "organizer": {
                "id": 12,
                "name": "Jane Organizer",
                "email": "jane@example.com"
            },
            "participants": [
                {
                    "id": 1,
                    "user_id": 15,
                    "name": "John Smith",
                    "email": "john@example.com",
                    "is_external": false
                },
                {
                    "id": 2,
                    "user_id": null,
                    "name": "External Consultant",
                    "email": "consultant@external.com",
                    "is_external": true
                }
            ],
            "tags": [
                {
                    "id": 3,
                    "name": "Executive",
                    "color": "#EF4444"
                }
            ]
        }
    ]
}
```

### Empty Response
When no active scheduled meetings exist in the system:
```json
{
    "success": true,
    "data": []
}
```

---

## 9. Active Meeting Definitions & Filtering

An active meeting is defined strictly by the following rules:

1. **Status Definition**: `meeting_statuses.code = 'scheduled'`.
2. **Active Time Window**: `end_at >= current application time`.
   * **Future Scheduled Meetings** (`start_at > now()`): **Included**.
   * **Currently-Running Scheduled Meetings** (`start_at <= now()` & `end_at >= now()`): **Included**.
   * **Ended Meetings** (`end_at < now()`): **Excluded**.
3. **Excluded Statuses**: Meetings with status `rescheduled`, `completed`, `cancelled`, or `in_progress` are **excluded**.
4. **Reschedule Chain Handling**: In a reschedule chain (#1 Rescheduled $\rightarrow$ #2 Rescheduled $\rightarrow$ #3 Scheduled), Meetings #1 and #2 are excluded. Only the active replacement Meeting #3 is returned.
5. **Ordering**: `start_at` **ASC**, `id` **ASC** (chronological order, next upcoming meeting first).

---

## 10. Error Handling & HTTP Status Codes

| HTTP Status | Message | Description & Cause |
| :--- | :--- | :--- |
| **`401 Unauthorized`** | `{"success": false, "message": "Unauthenticated."}` | Missing Bearer token, invalid API key, malformed header, revoked client, expired key, or soft-deleted client. |
| **`403 Forbidden`** | `{"success": false, "message": "Forbidden."}` | API key authenticated successfully but lacks `meetings.read` scope. |
| **`429 Too Many Requests`** | `{"success": false, "message": "Too Many Requests."}` | Rate limit exceeded (> 60 requests/min for this client). Includes `Retry-After` header. |

### 429 Too Many Requests Example
```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/json
Retry-After: 45
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0

{
    "success": false,
    "message": "Too Many Requests."
}
```

---

## 11. Security & Data Privacy

* **Plaintext Non-Storage**: Raw API keys exist in memory only during credential generation/rotation. Azooshi stores SHA-256 hashes only.
* **Sensitive User Fields Excluded**: Passwords, `remember_token`, `password_otp`, internal permissions, roles, and session tokens are **never exposed** in API output.
* **Data Sanitization**: Internal audit fields and database credentials are excluded from JSON resource output.
