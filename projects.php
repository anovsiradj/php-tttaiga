<?php

require __DIR__ . '/app/init.php';

$pageTitle = 'Projek';
$searchPlaceholder = 'Search projects...';
$bulkActions = '<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateProjectModal"><i class="bi bi-pencil-square me-2"></i> Bulk Prefix</a></li>';

$sortOptions = [
	'name' => 'Name (A-Z)',
	'-name' => 'Name (Z-A)',
	'created_date' => 'Created (Oldest)',
	'-created_date' => 'Created (Newest)',
	'modified_date' => 'Modified (Oldest)',
	'-modified_date' => 'Modified (Newest)',
	'total_fans' => 'Fans (Fewest)',
	'-total_fans' => 'Fans (Most)',
	'total_activity' => 'Activity (Lowest)',
	'-total_activity' => 'Activity (Highest)',
];

$bulkDeleteModalId = 'bulkDeleteModal'; // if we ever add bulk delete to projects
$filterProjectEnable = false;
$filterStatusEnable = false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<?php include __DIR__ . '/app/layouts/main_head.php'; ?>
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

			let allowFilterLoad = true;
			const onFilterChange = function (page = 1) {
				if (!allowFilterLoad) return;
				loadProjects(page);
			};

			// Initial filter binding (handles Select2 for dropdowns)
			taigaBindFilters(onFilterChange);

			taigaBindSelectionLogic('project-checkbox', function(checkedCount) {
				const filtered = parseInt($('#filteredProjects').text()) || 0;
				const total = parseInt($('#totalProjects').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalProjects', 'filteredProjects', 'selectedProjectsCount');
			});

			$('#bulkUpdateProjectModal').on('show.bs.modal', function () {
				const count = $('#projectsContent input.project-checkbox:checked').length;
				$('#selectedProjectsCountLabel').text(count);
			});

			$('#submitBulkProjectUpdate').on('click', function () {
				submitBulkProjectUpdate();
			});

			allowFilterLoad = false;
			taigaApplyFiltersFromUrl().then(function (page) {
				allowFilterLoad = true;
				loadProjects(page);
			}, function () {
				allowFilterLoad = true;
				loadProjects(1);
			});

			function loadProjects(page = 1) {
				taigaReplaceUrlQuery({
					...taigaGetFilterParams(),
					page: page
				});

				const params = {
					...taigaGetFilterParams(),
					member: window.taigaModel ? window.taigaModel.id : null,
					page: page
				};

				// Remove member if null so it doesn't send member=null
				if (!params.member) {
					delete params.member;
				}

				$.ajax({
					url: 'api.php/projects',
					type: 'GET',
					data: params,
					dataType: 'json',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (projects, status, xhr) {
						if (!Array.isArray(projects)) {
							console.error('Projects response is not an array:', projects);
							this.error(xhr, 'parsererror', 'Not an array');
							return;
						}
						allProjects = projects;
						displayProjects(projects);
						taigaRenderPagination(xhr, '#projectsPagination', loadProjects);
					},
					error: function (xhr) {
						console.error('Failed to load projects:', xhr);
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

			function displayProjects(projects) {
				if (projects.length === 0) {
					$('#projectsContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row">';

				if (Array.isArray(projects)) {
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
							<h5 class="card-title text-truncate">${project.name}</h5>
							<p class="card-text text-muted project-description small">
								${project.description || ''}
							</p>
							<div class="mt-3 pt-2 border-top">
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted">
										Created by: ${project.owner ? project.owner.full_name_display || project.owner.username : 'Unknown'}
									</small>
									<small class="text-muted">
										${new Date(project.created_date).toLocaleDateString()}
									</small>
								</div>
								<div class="d-flex justify-content-between align-items-center">
									<small class="text-muted">
										Updated: ${new Date(project.modified_date).toLocaleDateString()}
									</small>
									<small class="text-muted">
										Fans: ${project.total_fans || 0}
									</small>
								</div>
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
				}

				html += '</div>';
				$('#projectsContent').html(html);

				// Update counts
				taigaUpdateSelectionUI(projects.length, projects.length, 0, 'totalProjects', 'filteredProjects', 'selectedProjectsCount');

				$('.view-project').on('click', function (e) {
					e.stopPropagation();
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
					// Strip existing prefix if any (e.g., "[OLD] Project" -> "Project")
					const cleanName = item.name.replace(/^\[.*?\]\s*/, '');
					return {
						name: `[${prefix}] ${cleanName}`
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
