# Auth

## Normal Login

`POST /auth`

```json
{
  "type": "normal",
  "username": "user",
  "password": "password"
}
```

Use `auth_token` from the response as the bearer token.

## Refresh

`POST /auth/refresh`

Use when the app has a refresh flow. If refresh is not implemented, clear the app session and redirect to login on `401` or `403`.

## Backend Apps

- Keep Taiga bearer tokens server-side when PHP/Node/etc. sessions are available.
- Proxy API requests from the backend and inject `Authorization` from the session.
- Restrict selectable Taiga server URLs to configured values; do not trust arbitrary client headers.
- Do not persist bearer tokens in `localStorage`.
