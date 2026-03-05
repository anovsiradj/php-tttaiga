<?php
require __DIR__ . '/app/init.php';
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
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container mt-4">
		<?php
		$pageTitle = 'My Projects';
		$searchPlaceholder = 'Search projects...';
		$additionalControls = '
		<select class="form-select me-2" id="sortSelect" style="width: 150px;">
			<option value="name">Sort by Name</option>
			<option value="created_date">Sort by Created</option>
			<option value="modified_date">Sort by Modified</option>
		</select>';
		include __DIR__ . '/app/partials/list_header.php';
		?>

		<div id="projectsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading projects...</span>
				</div>
			</div>
		</div>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

			// Load projects
			loadProjects();

			// Event listeners


			$('#refreshBtn').on('click', function () {
				loadProjects();
			});

			$('#searchInput').on('input', function () {
				filterProjects();
			});

			$('#sortSelect').on('change', function () {
				sortProjects();
			});

			function loadProjects() {
				// Load projects first (using direct API as it might not have CORS issues)
				$.ajax({
					url: apiUrl + '/projects',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (projects) {
						allProjects = projects;
						displayProjects(projects);
					},
					error: function (xhr) {
						console.error('Failed to load projects:', xhr);
						// Try fallback to proxy API if direct call fails (CORS issue)
						$.ajax({
							url: 'api.php/projects',
							type: 'GET',
							headers: {
								'Authorization': 'Bearer ' + token,
								'Content-Type': 'application/json',
								'X-Taiga-Api-Url': apiUrl
							},
							success: function (projects) {
								allProjects = projects;
								displayProjects(projects);
							},
							error: function (fallbackXhr) {
								$('#projectsContent').html(`
							<div class="alert alert-danger">
								Failed to load projects. Please try again.
								<button class="btn btn-sm btn-outline-danger ms-2" onclick="loadProjects()">Retry</button>
							</div>
						`);
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
							<h5 class="card-title">${project.name}</h5>
							<p class="card-text text-muted project-description">
								${project.description || 'No description available.'}
							</p>
							<div class="d-flex justify-content-between align-items-center">
								<small class="text-muted">
									Created: ${new Date(project.created_date).toLocaleDateString()}
								</small>
								<span class="badge bg-${project.is_private ? 'secondary' : 'primary'}">
									${project.is_private ? 'Private' : 'Public'}
								</span>
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

			function filterProjects() {
				const searchTerm = $('#searchInput').val().toLowerCase();

				$('.project-card').each(function () {
					const projectName = $(this).find('.card-title').text().toLowerCase();
					const projectDesc = $(this).find('.project-description').text().toLowerCase();

					let show = true;

					if (searchTerm && !projectName.includes(searchTerm) && !projectDesc.includes(searchTerm)) {
						show = false;
					}

					if (show) {
						$(this).parent().show();
					} else {
						$(this).parent().hide();
					}
				});
			}

			function sortProjects() {
				const sortBy = $('#sortSelect').val();
				// This would require storing the original projects data for proper sorting
				// For now, we'll just reload with proper API sorting parameters
				loadProjects();
			}
		});
	</script>

</body>

</html>