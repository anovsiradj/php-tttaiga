# Project Audit: TTTaiga

## Overview
Project: TTTaiga
Description: Simplified, flat-workflow Taiga implementation.
PHP Requirement: >=8.4
Local Environment: Windows, Apache/httpd (via APP_URL in .env)

## Dependencies
- Internal: 
  - `anovsiradj/wiet`
  - `anovsiradj/php-skit`
  - `anovsiradj/web-skit`
- External:
  - `symfony/var-dumper`
  - `yiisoft/arrays`
  - `yiisoft/strings`
- Dev Dependencies:
  - `phpunit/phpunit` (^13.2)

## UI/UX
- Framework: Bootstrap 5.
- Interactivity: Heavy reliance on jQuery for UI manipulation (modals, dynamic lists, filtering).
- Components: Card-based grid layout for list views.
- Consistency: Uses `app/layouts/` and `app/partials/` for shared components.

## Flows
- Entry: `web/index.php` or dedicated page (e.g., `tasks.php`).
- Rendering: Server-side rendering (PHP) for shell/layout.
- Data Interaction: Client-side (JS/jQuery) fetches data via AJAX from `api.php` and injects into the DOM.
- CRUD Actions: Modal-based, handling single/bulk operations via client-side AJAX requests.

## CRUD & Feature Audit
### Forms (Create/Update)
- **Shared Logic:** Form handling logic for CRUD is highly fragmented across individual page scripts (e.g., `tasks.php`, `usor.php`) rather than being centralized in `taiga.js`.
- **Validation:** Lacks robust client-side validation before AJAX submission, increasing risk of invalid API requests.
- **Bug Susceptibility:** Dynamic loading of dropdowns (status, members, userstories) relies on timed `setTimeout` (e.g., 300ms) to ensure DOM elements exist after parent project selection, which is prone to race conditions if network latency is high.
- **Error Handling:** Minimal user-friendly error messaging for failed form submissions.

### Bulk Actions
- **Implementation:** Generally uses `taigaExecuteBulk` for PATCH/DELETE operations, which is efficient for sequential processing, but UI feedback for partial failures (success vs. error counts) could be improved.
- **Sync/Filter Integration:**
  - Bulk update/delete relies on current filter params to fetch items. 
  - Synchronization issues might occur if filters are modified while the bulk action modal is open, potentially causing operations to be applied to an unexpected set of items.
  - No visual "locked" state for filters while a bulk operation is pending.

### Shared Filter/Input Integration
- **Mechanism:** Relies on `taigaGetFilterParams` and `taigaApplyFiltersFromUrl`.
- **Consistency:** Reasonable synchronization across main pages, but "shared input" (autofill in forms) behaves differently depending on whether the form is "single" or "bulk".
- **UX:** Lack of clear indicators when a form is pre-filled based on a shared filter makes it difficult for users to understand *why* certain defaults were chosen.


