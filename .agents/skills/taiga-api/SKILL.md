---
name: taiga-api
description: Taiga REST API integration guidance for Codex. Use when implementing or reviewing code that calls Taiga endpoints, including authentication, session-backed proxies, projects, memberships, milestones/sprints, epics, user stories/usor, tasks, issues/isu, statuses, custom attributes, attachments, history/comments, pagination, filters, bulk creation, and optimistic concurrency/version handling.
---

# Taiga API

## Workflow

1. Use the official Taiga API as the source of truth: https://docs.taiga.io/api.html.
2. For endpoint names, required fields, query filters, and response/status behavior, open `references/index.md`, then load only the relevant small reference file.
3. Prefer existing project API proxy/client helpers when working inside an app. Do not duplicate auth, pagination, or error handling unless the project has no shared helper.
4. Preserve user authentication boundaries. Store bearer tokens server-side when the app has a backend, forward them from a session-backed proxy, and auto-logout or refresh on `401`/`403`.
5. For writes, include `version` on editable resources when Taiga expects optimistic concurrency control.
6. For list screens, preserve Taiga pagination headers and filters so UI paging and counts stay accurate.

## Implementation Guidance

- Base URL shape is usually `{server}/api/v1`.
- Send JSON requests with `Content-Type: application/json`.
- Send authenticated requests with `Authorization: Bearer {auth_token}`.
- Use `PATCH` for partial edits and `PUT` only when replacing the whole object.
- Treat `*_extra_info` and `*_extra` fields as read-only display fields.
- Use module metadata endpoints for select controls: statuses, memberships, priorities, severities, issue types, roles, and filters data.
- For bulk create, use Taiga bulk endpoints where available instead of firing many single creates, unless the UI requires per-item progress or partial retry handling.
- For attachments, switch to multipart form data and do not force JSON content type.

## Reference Map

- General request rules, pagination, OCC: `references/general.md`
- Login, refresh, session-backed proxies: `references/auth.md`
- Projects, members, roles, sprints: `references/projects.md`
- Epics, usor, tasks, isu: `references/work-items.md`
- Statuses, issue metadata, filters data: `references/metadata.md`
- Bulk create/update/delete strategy: `references/bulk.md`
- Attachments, history, comments, votes/watchers: `references/attachments-history.md`

## Project Naming Notes

This repository uses Indonesian aliases:

- `usor` = Taiga user story, endpoint `/userstories`
- `isu` = Taiga issue, endpoint `/issues`
- `sprint` = Taiga milestone, endpoint `/milestones`
- `member` = Taiga membership, endpoint `/memberships`

Keep aliases in UI text when the project already uses them, but use the official endpoint names in API code.
