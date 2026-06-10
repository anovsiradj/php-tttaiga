# Projects, Members, Roles, Sprints

## Projects

- List: `GET /projects`
- Create: `POST /projects`
- Get: `GET /projects/{id}`
- Get by slug: `GET /projects/by_slug?slug={slug}`
- Edit: `PATCH /projects/{id}`
- Delete: `DELETE /projects/{id}`

Common list params: `member`, `order_by`.

## Memberships

- List: `GET /memberships?project={projectId}`
- Create: `POST /memberships`
- Get: `GET /memberships/{id}`
- Edit: `PATCH /memberships/{id}`
- Delete: `DELETE /memberships/{id}`

Use memberships to populate member/assignee selectors.

## Roles

- List: `GET /roles?project={projectId}`
- CRUD: `/roles/{id}`

Use roles for permission and filter controls.

## Milestones/Sprints

Taiga calls sprints `milestones`.

- List: `GET /milestones?project={projectId}`
- Create: `POST /milestones`
- Get: `GET /milestones/{id}`
- Edit: `PATCH /milestones/{id}`
- Delete: `DELETE /milestones/{id}`

Include `version` on edits.
