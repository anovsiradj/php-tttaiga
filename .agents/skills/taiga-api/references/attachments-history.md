# Attachments, History, Reactions

## Attachments

Available for major modules including epics, user stories, tasks, issues, and wiki pages.

- List: `GET /{module}/attachments?object_id={id}&project={projectId}`
- Create: `POST /{module}/attachments`
- Get/Edit/Delete: `/{module}/attachments/{attachmentId}`

Create uses multipart form data:

- `attached_file=@file`
- `from_comment`
- `object_id`
- `project`

Do not force `Content-Type: application/json` for uploads.

## History And Comments

- History endpoint: `GET /history/{type}/{id}`
- Types include `userstory`, `task`, `issue`, and `wiki`.
- Comment edit/delete/undelete endpoints are documented under History.
- Sanitize rendered comments and markdown before inserting into HTML.

## Voting And Watching

Common work-item endpoints:

- `POST /{module}/{id}/upvote`
- `POST /{module}/{id}/downvote`
- `GET /{module}/{id}/voters`
- `POST /{module}/{id}/watch`
- `POST /{module}/{id}/unwatch`
- `GET /{module}/{id}/watchers`
