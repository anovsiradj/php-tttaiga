---
description: Taiga API workflow (backend)
---

# Taiga API Workflow (Backend)

Backend integration is a single PHP proxy entrypoint: `api.php`. Browser requests go to `api.php/<taiga-endpoint>`, and the proxy forwards them to the configured Taiga server.

## Configuration
- Default Taiga API base URL: `app/configs/taiga.php`
- Optional override per request: `X-Taiga-Api-Url` header

## Authorization Behavior
The proxy forwards `Authorization` when provided by the browser.

Fallbacks:
- If `$_SESSION['taiga_token']` exists and the request is missing `Authorization`, the proxy injects `Bearer <session token>`.
- If Apache strips the `Authorization` header, the proxy attempts to read it from `apache_request_headers()`.

## Request Forwarding
- Method: forwards `GET`, `POST`, `PATCH`, `PUT`, `DELETE`
- Body: forwards raw `php://input` as-is
- Query string: forwarded; for `GET /projects`, the proxy auto-adds `member=<user_id>` if missing and the user_id can be decoded from the bearer token payload
- Status code: forwards Taiga’s HTTP status code back to the client
- Headers: forwards response `X-*` headers back to the client

## CORS
`api.php` responds with permissive CORS headers (including `Access-Control-Allow-Origin: *`) and handles `OPTIONS` preflight by exiting early.

## Caching
`GET` requests to selected endpoints are cached on disk under `storage/tmp`:
- `/epic-statuses`, `/userstory-statuses`, `/task-statuses`, `/issue-statuses`, `/memberships`, `/users`

Cache key:
- Includes API base URL + path + normalized query params
- For `/memberships` and `*-statuses`, cache is normalized to only include `project`

TTL:
- Most cached endpoints: 3600s
- `/memberships`: 300s
- `/users`: 300s

Proxy adds `X-Cache: HIT|MISS` to indicate cache behavior.
