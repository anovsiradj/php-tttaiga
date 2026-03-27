---
description: Taiga API integration
---

# Taiga API Integration Workflow

This project integrates with the Taiga API to manage projects, epics, user stories, tasks, and issues.

## Official Documentation
Always refer to the official Taiga API documentation for endpoint details, request/response formats, and authentication:
<https://docs.taiga.io/api.html>

## API Proxy (`api.php`)
The project includes a PHP proxy (`api.php`) to handle API requests. This is primarily used as a fallback when direct API calls from the frontend encounter CORS issues.

- **Usage**: Prefix the API path with `api.php/`.
- **Headers**:
  - `Authorization`: `Bearer <token>`
  - `X-Taiga-Api-Url`: The base API URL (e.g., `https://taiga.jmc.co.id/api/v1`).
- **Configuration**: Default API URL is defined in `app/configs/taiga.php`.

## Frontend Integration
The project uses jQuery for AJAX requests and Select2 for enhanced dropdowns with remote search and infinite scrolling.

### Standard `$.ajax` Pattern
Always include the `Authorization` header and ensure proper error handling.

```javascript
$.ajax({
    url: apiUrl + '/epics', // or 'api.php/epics' as fallback
    type: 'GET',
    data: params,
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'X-Taiga-Api-Url': apiUrl // Required if using api.php proxy
    },
    success: function (data, status, xhr) {
        // Handle success
    },
    error: function (xhr) {
        // Handle error (use console.error and user-facing alerts)
    }
});
```

### Select2 Integration (`assets/taiga.js`)
Use `taigaInitRemoteSelect2` for dropdowns that require remote searching (projects, epics, user stories).

```javascript
taigaInitRemoteSelect2('#epicSelect', '/epics', {
    placeholder: 'All Epics',
    additionalParams: () => {
        const pid = $('#projectSelect').val();
        return pid ? { project: pid } : {};
    }
});
```

## Utility Functions (`assets/taiga.js`)
The `assets/taiga.js` file contains standardized helpers for common Taiga UI patterns:

### Pagination
- `taigaRenderPagination(xhr, containerSelector, onPageChange)`: Automatically parses Taiga's `X-Pagination-*` headers and renders Bootstrap pagination.

### Statuses and Badges
- `taigaFetchStatuses(apiUrl, token, projectId, type)`: Fetches project-specific status objects.
- `taigaGetStatusInfo(item)`: Safely extracts status name and color from a Taiga object.
- `taigaRenderStatusBadge(statusInfo)`: Generates a styled Bootstrap badge with dynamic text contrast.

### Filtering
- `taigaGetFilterParams()`: Collects values from standard filter IDs (`#searchInput`, `#projectSelect`, etc.).
- `taigaBindFilters(onFilterChange)`: Standardized event binding for search debounce and dropdown changes.

## Bulk Operations
Bulk operations should be implemented as sequential AJAX requests to maintain stability and provide progress tracking.

### Helpers
- `taigaLoadBulkItems(endpoint, $container, formatItemCallback)`: Loads items for bulk processing based on current filters.
- `taigaExecuteBulk(endpoint, ids, method, data, onComplete)`: Executes sequential `PATCH` or `DELETE` requests for a list of IDs.

## Best Practices
- **Server-Side Filtering**: Always prefer passing `q`, `project`, `status` etc. to the API over client-side filtering.
- **Dynamic Data**: Avoid hardcoded status names or IDs. Fetch statuses and members from the API based on the selected project.
- **Select2**: Use Select2 for all dropdowns, especially those with many options or remote data.
- **CORS Mitigation**: If encountering CORS issues during development, use the `api.php/` proxy prefix and include the `X-Taiga-Api-Url` header.
- **Pagination**: Implement pagination for all list views using the `taigaRenderPagination` helper and standard Taiga headers.