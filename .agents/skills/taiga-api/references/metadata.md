# Metadata And Filter Data

Use metadata endpoints to populate selects and translate IDs to labels.

## Statuses

- `GET /epic-statuses?project={projectId}`
- `GET /userstory-statuses?project={projectId}`
- `GET /task-statuses?project={projectId}`
- `GET /issue-statuses?project={projectId}`

Status resources generally support list/create/get/edit/delete and bulk order update.

## Issue Metadata

- `GET /priorities?project={projectId}`
- `GET /severities?project={projectId}`
- `GET /issue-types?project={projectId}`

## Other Project Metadata

- `GET /points?project={projectId}`
- `GET /roles?project={projectId}`
- `GET /memberships?project={projectId}`

## Filters Data

Use these for richer filter sidebars/dropdowns:

- `GET /epics/filters_data?project={projectId}`
- `GET /userstories/filters_data?project={projectId}`
- `GET /tasks/filters_data?project={projectId}`
- `GET /issues/filters_data?project={projectId}`
