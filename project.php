<?php
require __DIR__ . '/app/construct.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Project Details - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="project-header">
		<div class="container">
			<a href="projects.php" class="back-btn">
				<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
					<path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5z" />
				</svg>
				Back to Projects
			</a>
			<div id="projectHeaderContent">
				<div class="loading-spinner">
					<div class="spinner-border text-light" role="status">
						<span class="visually-hidden">Loading project...</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="container">
		<div class="row">
			<div class="col-md-8">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Project Details</h5>
					</div>
					<div class="card-body" id="projectDetailsContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading details...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Description</h5>
					</div>
					<div class="card-body" id="projectDescriptionContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading description...</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Project Stats</h5>
					</div>
					<div class="card-body" id="projectStatsContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading stats...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">Team Members</h5>
					</div>
					<div class="card-body" id="projectMembersContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading members...</span>
							</div>
						</div>
					</div>
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

			// Get project ID from URL
			const urlParams = new URLSearchParams(window.location.search);
			const projectId = urlParams.get('id');

			if (!projectId) {
				window.location.href = 'projects.php';
				return;
			}

			// Load project data
			loadProjectData(projectId);

			function loadProjectData(projectId) {
				// Load project basic info
				$.ajax({
					url: apiUrl + '/projects/' + projectId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (project) {
						displayProjectHeader(project);
						displayProjectDetails(project);
						displayProjectDescription(project);

						// Load additional data
						loadProjectStats(projectId);
						loadProjectMembers(projectId);
					},
					error: function (xhr) {
						console.error('Failed to load project:', xhr);
						$('#projectHeaderContent').html(`
					<div class="alert alert-danger">
						Failed to load project. Please check if you have access to this project.
						<a href="projects.php" class="btn btn-sm btn-outline-danger ms-2">Back to Projects</a>
					</div>
				`);
					}
				});
			}

			function loadProjectStats(projectId) {
				// This would typically include user stories, tasks, etc.
				// For now, we'll show basic stats
				const statsHtml = `
			<div class="row text-center">
				<div class="col-4">
					<div class="stats-card">
						<div class="stat-number">0</div>
						<small class="text-muted">User Stories</small>
					</div>
				</div>
				<div class="col-4">
					<div class="stats-card">
						<div class="stat-number">0</div>
						<small class="text-muted">Tasks</small>
					</div>
				</div>
				<div class="col-4">
					<div class="stats-card">
						<div class="stat-number">0</div>
						<small class="text-muted">Issues</small>
					</div>
				</div>
			</div>
		`;
				$('#projectStatsContent').html(statsHtml);
			}

			function loadProjectMembers(projectId) {
				// Load project members
				$.ajax({
					url: apiUrl + '/projects/' + projectId + '/memberships',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (members) {
						displayProjectMembers(members);
					},
					error: function (xhr) {
						console.error('Failed to load members:', xhr);
						$('#projectMembersContent').html(`
					<div class="alert alert-warning">
						Unable to load team members.
					</div>
				`);
					}
				});
			}

			function displayProjectHeader(project) {
				const headerHtml = `
			<h1 class="display-4 mb-2">${project.name}</h1>
			<p class="lead mb-0">${project.slug}</p>
			<span class="badge bg-${project.is_private ? 'secondary' : 'primary'} mt-2">
				${project.is_private ? 'Private Project' : 'Public Project'}
			</span>
		`;
				$('#projectHeaderContent').html(headerHtml);
			}

			function displayProjectDetails(project) {
				const detailsHtml = `
			<div class="row">
				<div class="col-6">
					<strong>Created:</strong><br>
					${new Date(project.created_date).toLocaleDateString()}
				</div>
				<div class="col-6">
					<strong>Modified:</strong><br>
					${new Date(project.modified_date).toLocaleDateString()}
				</div>
				<div class="col-6 mt-3">
					<strong>Total Members:</strong><br>
					${project.total_memberships || 0}
				</div>
				<div class="col-6 mt-3">
					<strong>Total Milestones:</strong><br>
					${project.total_milestones || 0}
				</div>
				<div class="col-12 mt-3">
					<strong>Project ID:</strong><br>
					<code>${project.id}</code>
				</div>
			</div>
		`;
				$('#projectDetailsContent').html(detailsHtml);
			}

			function displayProjectDescription(project) {
				const descriptionHtml = project.description
					? `<p class="card-text">${project.description}</p>`
					: `<p class="text-muted">No description provided for this project.</p>`;

				$('#projectDescriptionContent').html(descriptionHtml);
			}

			function displayProjectMembers(members) {
				if (members.length === 0) {
					$('#projectMembersContent').html(`
				<p class="text-muted">No team members found.</p>
			`);
					return;
				}

				let html = '';
				members.forEach(member => {
					const avatar = member.photo
						? member.photo
						: 'https://via.placeholder.com/40x40?text=?';

					html += `
				<div class="d-flex align-items-center mb-3">
					<img src="${avatar}" class="member-avatar me-3" alt="${member.full_name}">
					<div>
						<strong>${member.full_name || member.username}</strong>
						<br>
						<small class="text-muted">@${member.username}</small>
					</div>
				</div>
			`;
				});

				$('#projectMembersContent').html(html);
			}
		});
	</script>

</body>

</html>