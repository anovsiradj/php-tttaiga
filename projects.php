<?php

require __DIR__ . '/app/init.php';

$pageTitle = 'My Projects';
$searchPlaceholder = 'Search projects...';
$additionalControls = '
<div class="dropdown d-inline-block me-2">
	<button class="btn btn-primary dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
		<i class="bi bi-gear me-1"></i> Bulk Actions
	</button>
	<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bulkActionsDropdown">
		<li>
			<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateProjectModal">
				<i class="bi bi-pencil-square me-2"></i> Bulk Prefix Name
			</a>
		</li>
	</ul>
</div>
<select class="form-select d-inline-block" id="sortSelect" style="width: 150px;">
	<option value="name">Sort by Name</option>
	<option value="created_date">Sort by Created</option>
	<option value="modified_date">Sort by Modified</option>
</select>';

$bulkDeleteModalId = 'bulkDeleteModal'; // if we ever add bulk delete to projects
$filterProjectEnable = false;
$filterStatusEnable = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Projects - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Select2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container mt-4">
		<?php include __DIR__ . '/app/partials/list_header.php' ?>

		<?php
		$totalLabel = 'Total Projects';
		$totalId = 'totalProjects';
		$filteredId = 'filteredProjects';
		$selectionCountId = 'selectedProjectsCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="projectsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading projects...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Projects pagination" class="mt-4">
			<ul class="pagination justify-content-center" id="projectsPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<!-- Select2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

	<script src="assets/taiga.js"></script>
	<script src="assets/app.js"></script>
	<script src="assets/theme.js"></script>

	<script>
		$(document).ready(function () {
			const token = localStorage.getItem('taiga_token');
			const userData = localStorage.getItem('taiga_user');

			if (!token || !userData) {
				window.location.href = 'login.php';
				return;
			}

			const config = <?php echo json_encode(include 'app/configs/taiga.php'); ?>;
			const apiUrl = localStorage.getItem('taiga_api_url') || config.servers.default.api_url;
			const user = JSON.parse(userData);

			let allProjects = [];

			// Define globals for taiga.js helpers
			window.apiUrl = apiUrl;
			window.taigaToken = token;

			// Load projects list (data, not dropdown)
			loadProjects();

			// Initial filter binding (handles Select2 for dropdowns)
			taigaBindFilters(loadProjects);

			$('#selectAllBtn').on('click', function () {
				$('#projectsContent input.project-checkbox').prop('checked', true);
				updateSelectionCount();
			});

			$('#clearSelectionBtn').on('click', function () {
				$('#projectsContent input.project-checkbox').prop('checked', false);
				updateSelectionCount();
			});

			$('#bulkUpdateProjectModal').on('show.bs.modal', function () {
				const count = $('#projectsContent input.project-checkbox:checked').length;
				$('#selectedProjectsCountLabel').text(count);
			});

			$('#submitBulkProjectUpdate').on('click', function () {
				submitBulkProjectUpdate();
			});

			function loadProjects(page = 1) {
				const params = {
					...taigaGetFilterParams(),
					page: page
				};

				// Load projects first (using direct API as it might not have CORS issues)
				$.ajax({
					url: apiUrl + '/projects',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (projects, status, xhr) {
						allProjects = projects;
						displayProjects(projects);
						taigaRenderPagination(xhr, '#projectsPagination', loadProjects);
					},
					error: function (xhr) {
						console.error('Failed to load projects:', xhr);
						// Try fallback to proxy API if direct call fails (CORS issue)
						$.ajax({
							url: 'api.php/projects',
							type: 'GET',
							data: params,
							headers: {
								'Authorization': 'Bearer ' + token,
								'Content-Type': 'application/json',
								'X-Taiga-Api-Url': apiUrl
							},
							success: function (projects, status, xhr) {
								allProjects = projects;
								displayProjects(projects);
								taigaRenderPagination(xhr, '#projectsPagination', loadProjects);
							},
							error: function (fallbackXhr) {
								$('#projectsContent').html(`
							<div class="alert alert-danger">
								Failed to load projects. Please try again.
								<button class="btn btn-sm btn-outline-danger ms-2" onclick="loadProjects()">Retry</button>
							</div>
						`);
								$('#projectsPagination').empty();
							}
						});
					}
				});
			}

			function displayProjects(projects) {
				if (projects.length === 0) {
					$('#projectsContent').html(`
				<div class="alert alert-info">
					No projects found. Create your first project in Taiga!
				</div>
			`);
					return;
				}

				let html = '<div class="row">';

				projects.forEach(project => {
					html += `
				<div class="col-md-6 col-lg-4 mb-4">
					<div class="card project-card h-100" data-project-id="${project.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input project-checkbox" type="checkbox" value="${project.id}" data-version="${project.version}" data-name="${project.name}" id="project-${project.id}">
									<label class="form-check-label" for="project-${project.id}"></label>
								</div>
								<span class="badge bg-${project.is_private ? 'secondary' : 'primary'}">
									${project.is_private ? 'Private' : 'Public'}
								</span>
							</div>
							<h5 class="card-title">${project.name}</h5>
							<p class="card-text text-muted project-description">
								${project.description || 'No description available.'}
							</p>
							<div class="d-flex justify-content-between align-items-center">
								<small class="text-muted">
									Created: ${new Date(project.created_date).toLocaleDateString()}
								</small>
							</div>
						</div>
						<div class="card-footer bg-transparent">
							<button class="btn btn-primary btn-sm view-project" data-project-id="${project.id}">
								View Details
							</button>
						</div>
					</div>
				</div>
			`;
				});

				html += '</div>';
				$('#projectsContent').html(html);

				// Update counts
				$('#totalProjects').text(projects.length);
				$('#filteredProjects').text(projects.length);
				updateSelectionCount();

				// Add click event for project checkboxes
				$('.project-checkbox').on('change', updateSelectionCount);

				// Add click event for project cards
				$('.view-project').on('click', function (e) {
					e.stopPropagation();
					const projectId = $(this).data('project-id');
					window.location.href = `project.php?id=${projectId}`;
				});

				$('.project-card').on('click', function () {
					const projectId = $(this).data('project-id');
					window.location.href = `project.php?id=${projectId}`;
				});
			}

			function updateSelectionCount() {
				const selectedCount = $('#projectsContent input.project-checkbox:checked').length;
				$('#selectedProjectsCount').text(selectedCount);
			}

			function submitBulkProjectUpdate() {
				const prefix = $('#projectPrefixInput').val().trim();
				if (!prefix) {
					alert('Please enter a prefix');
					return;
				}

				const selectedProjects = [];
				$('#projectsContent input.project-checkbox:checked').each(function () {
					selectedProjects.push({
						id: $(this).val(),
						version: $(this).data('version'),
						name: $(this).data('name')
					});
				});

				if (selectedProjects.length === 0) {
					alert('Please select at least one project');
					return;
				}

				const $btn = $('#submitBulkProjectUpdate');
				$btn.prop('disabled', true).text('Applying...');

				// Use reinforced taigaExecuteBulk that supports data functions
				taigaExecuteBulk('/projects/', selectedProjects, 'PATCH', (item) => {
					return {
						name: `[${prefix}] ${item.name}`
					};
				}, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Apply Prefix');
					if (errorCount === 0) {
						alert(`Successfully added prefix to ${successCount} projects!`);
						$('#bulkUpdateProjectModal').modal('hide');
						loadProjects();
					} else {
						alert(`Updated ${successCount} projects, but ${errorCount} failed. Check console for details.`);
					}
				});
			}

			// Sort and filter handled by taigaBindFilters and taigaGetFilterParams
		});
	</script>

	<?php include __DIR__ . '/app/partials/project_bulk_update.php'; ?>

</body>

</html>