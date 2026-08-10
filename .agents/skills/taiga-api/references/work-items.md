# Work Items

Project aliases in this repo:

- `usor` = user story = `/userstories`
- `isu` = issue = `/issues`
- `sprint` = milestone = `/milestones`

## Epics

- List/Create: `GET|POST /epics`
- Get/Edit/Delete: `/epics/{id}`
- Get by ref: documented by-ref endpoint.
- Required create fields: `project`, `subject`.
- Common fields: `description`, `status`, `assigned_to`, `color`, `tags`, `watchers`.

## User Stories

- List/Create: `GET|POST /userstories`
- Get/Edit/Delete: `/userstories/{id}`
- Get by ref: documented by-ref endpoint.
- Required create fields: `project`, `subject`.
- Common fields: `description`, `status`, `milestone`, `assigned_to`, `epic`, `points`, `tags`.
- Has bulk create and order/milestone bulk update endpoints.

## Tasks

- List/Create: `GET|POST /tasks`
- Get/Edit/Delete: `/tasks/{id}`
- Required create fields: `project`, `subject`.
- Common fields: `user_story`, `milestone`, `status`, `assigned_to`, `description`, `tags`.
- Has bulk create.

## Issues

- List/Create: `GET|POST /issues`
- Get/Edit/Delete: `/issues/{id}`
- Required create fields: `project`, `subject`.
- Common fields: `status`, `severity`, `priority`, `type`, `milestone`, `assigned_to`, `description`, `tags`.

## Common List Filters

`project`, `status`, `owner`, `assigned_to`, `milestone`, `tags`, `watchers`, `status__is_closed`, `exclude_*`, `order_by`.
