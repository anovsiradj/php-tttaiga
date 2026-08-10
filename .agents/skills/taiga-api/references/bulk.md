# Bulk Operations

Use Taiga bulk endpoints where they exist. Use app-level loops only when Taiga has no bulk endpoint or the UI needs per-item progress.

## Documented Bulk Create

- `POST /epics/bulk_create`
- `POST /epics/{epicId}/related_userstories/bulk_create`
- `POST /userstories/bulk_create`
- `POST /tasks/bulk_create`
- Membership/invitation bulk creation endpoints.

## Bulk Update / Order

Taiga documents several order/move endpoints:

- Project/status/custom-attribute/points/priority/severity order updates.
- User story backlog, kanban, sprint order, and milestone bulk update.

## App-Level Bulk

For custom bulk update/delete:

- Fetch current item `id` and `version`.
- Send one `PATCH` or `DELETE` per selected item.
- Include `version` for updates.
- Track success/failure per item.
- Do not stop the whole batch on the first failure unless the action is transactional by design.
