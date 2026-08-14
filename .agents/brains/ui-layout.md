# UI LAYOUT: List Pages (projects/sprints/epiks/usors/tasks/isus)

## Stack (top -> bottom, list pages)
1. navbar (`app/layouts/main_navbar.php`)
2. header = `.page-title-row` (H1 title + refresh `#refreshBtn` + Add New)
3. toolbar = `.list-toolbar` (search `#searchInput` + sort `#sortSelect` wrapped in `.sort-select-wrap`)
4. filter bar = `.filter-toolbar` (domain selects: project/epic/userStory/status/assigned/additionalControls) — RENDERED ONLY IF `$hasFilters`
5. `.sticky-bulk-bar`
6. content + pagination

## Rules / decisions
- ALL list pages SHARE `app/partials/list_header.php`; per-page var overrides drive visibility.
- Standard control IDs NEVER change: `#searchInput #sortSelect #refreshBtn #projectSelect #statusSelect #epicSelect #userStorySelect #assignedToSelect` — JS (`taiga.js`) reads by ID only, no parent-structure assumptions.
- `$hasFilters` computed in partial: true if any of project/epic/usTitle/status/assigned/additionalControls enabled. Prevents empty filter bar (projects.php & sprints.php have few/no domain filters).
- Search + sort moved OUT of filter bar into own toolbar to declutter filter row.
- toolbar STATIC (not sticky). filter bar stays fully visible (no collapse).
- Styling: `.list-toolbar` in `assets/app.css` — flex wrap, search `flex 1 1 260px`, sort wrapper `flex 0 0 220px`.

## LESSONS LEARNED (user corrections)
- WHAT: user phrased "add new header above old header" but MEANT a **new toolbar**, positioned **BELOW** the header (title row), i.e. header -> toolbar -> filter bar. NOT toolbar above title.
- Confirmed UI/UX choices via question flow: toolbar static; all domain filters stay visible; title stays in old header; search+sort only items moved.
- DO NOT render search/sort inside `.filter-toolbar` anymore (historical: search+sort were in filter bar).