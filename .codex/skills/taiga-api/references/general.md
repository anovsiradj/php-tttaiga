# General Taiga API Rules

- Base URL: `{taiga_server}/api/v1`.
- Send JSON requests with `Content-Type: application/json`.
- Send auth with `Authorization: Bearer {AUTH_TOKEN}`.
- Use `PATCH` for partial edits; use `PUT` only for whole-object replacement.
- Treat `*_extra` and `*_extra_info` fields as read-only display data.
- Send `Accept-Language: {LanguageId}` when translated API content matters.
- Handle `429` with retry/backoff.

## Pagination

List responses are paginated by default. Preserve these headers through proxies:

- `x-paginated`
- `x-paginated-by`
- `x-pagination-count`
- `x-pagination-current`
- `x-pagination-next`
- `x-pagination-prev`

Disable pagination only when intentional: `x-disable-pagination: True`.

## Version/OCC

Editable resources use `version`. Include the current version in update payloads:

```json
{
  "subject": "Updated title",
  "version": 3
}
```

Taiga increments `version` after successful writes and may reject stale conflicting updates.
