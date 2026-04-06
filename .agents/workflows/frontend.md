---
description: Taiga API workflow (frontend)
---

# Taiga API Workflow (Frontend)

Frontend calls the Taiga API through the local proxy (`api.php`) using jQuery AJAX. Shared UI helpers live in `assets/taiga.js`.

## Globals (from `assets/app.js`)
- `window.taigaToken`: Taiga auth token (from `localStorage.taiga_token`)
- `window.apiUrl`: Taiga API base URL (from `localStorage.taiga_api_url`)
- `window.taigaModel`: Current user model (from `localStorage.taiga_user`)

## Standard Request Pattern
Use `api.php<endpoint>` and always include these headers:
- `Authorization: Bearer <token>`
- `X-Taiga-Api-Url: <apiUrl>`
- `Content-Type: application/json`

Example:

```javascript
const params = taigaGetFilterParams();

$.ajax({
	url: 'api.php/epics',
	type: 'GET',
	dataType: 'json',
	data: params,
	headers: {
		'Authorization': `Bearer ${window.taigaToken}`,
		'Content-Type': 'application/json',
		'X-Taiga-Api-Url': window.apiUrl
	},
	success: function (data, status, xhr) {
		taigaRenderPagination(xhr, '#pagination', function (page) {
			// reload list with new page
		});
	},
	error: function (xhr) {
		console.error('Taiga request failed:', xhr);
	}
});
```

## Select2 Remote Dropdowns
Use `taigaInitRemoteSelect2(selector, endpoint, options)` for large datasets and remote searching.

```javascript
taigaInitRemoteSelect2('#epicSelect', '/epics', {
	placeholder: '(Semua Epik)',
	additionalParams: () => {
		const pid = $('#projectSelect').val();
		return pid ? { project: pid } : {};
	}
});
```

## Standard Filter Wiring
Use `taigaBindFilters(onFilterChange)` to:
- debounce `#searchInput`
- initialize Select2 filters (project, epik, usor, status, assigned_to, sort)
- refresh dependent dropdowns when project changes

`taigaGetFilterParams()` reads these standard IDs when present:
- `#searchInput`, `#projectSelect`, `#statusSelect`, `#epicSelect`, `#userStorySelect`, `#assignedToSelect`, `#sortSelect`

## Statuses and Badges
For project-specific statuses:
- `taigaFetchStatuses(apiUrl, token, projectId, type)`
- `taigaPopulateStatusDropdown($select, statuses)`

For rendering:
- `taigaGetStatusInfo(item)`
- `taigaRenderStatusBadge(statusInfo)`

## Bulk Operations
Bulk operations should execute sequentially to keep UI responsive and error handling predictable:
- `taigaLoadBulkItems(endpoint, $container, formatItemCallback)`
- `taigaExecuteBulk(endpoint, items, method, data, onComplete)`
