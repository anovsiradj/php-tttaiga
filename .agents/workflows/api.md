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
The project uses jQuery for AJAX requests.

### Standard `$.ajax` Pattern
```javascript
$.ajax({
    url: apiUrl + '/epics', // or 'api.php/epics' as fallback
    type: 'GET',
    data: params,
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'X-Taiga-Api-Url': apiUrl // only needed if using api.php
    },
    success: function (data, status, xhr) {
        // Handle success
    },
    error: function (xhr) {
        // Handle error
    }
});
```

### Shared Helpers (`assets/taiga.js`)
Utility functions for common Taiga UI components are located in `assets/taiga.js`:
- `taigaRenderPagination(xhr, containerSelector, onPageChange)`: Renders Bootstrap pagination based on Taiga API headers (`X-Paginated`, `X-Pagination-Count`, etc.).
- `taigaGetStatusClass(status)`: Returns appropriate CSS classes for status badges.

## Best Practices
- **Pagination**: Always implementation pagination for list views using the `taigaRenderPagination` helper.
- **Error Handling**: Provide clear error messages and retry options for failed API calls.
- **Search and Filtering**: Prefer server-side filtering (passing `q`, `project`, `status` parameters to the API) over client-side filtering.