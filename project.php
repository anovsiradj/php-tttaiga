<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<?php include __DIR__ . '/app/layouts/main_head.php'; ?>
</head>

<body class="item-page">

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<?php
	$backUrl = 'projects.php';
	$backLabel = 'Back to Projects';
	$headerId = 'projectHeaderContent';
	$loadingLabel = 'Loading project...';
	include __DIR__ . '/app/partials/item_header.php';
	?>

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

				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Komentar</h5>
					</div>
					<div class="card-body" id="projectCommentsContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading comments...</span>
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
	<script src="assets/taiga.js"></script>
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

			taigaLoadComments('#projectCommentsContent', 'project', projectId, apiUrl, token);

			// Load project data
			loadProjectData(projectId);

			function loadProjectData(projectId) {
				// Load project basic info
				$.ajax({
					url: 'api.php/projects/' + projectId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
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
						<small class="text-muted">Usor</small>
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
						<small class="text-muted">Isu</small>
					</div>
				</div>
			</div>
		`;
				$('#projectStatsContent').html(statsHtml);
			}

			function loadProjectMembers(projectId) {
				// Load project members
				$.ajax({
					url: 'api.php/projects/' + projectId + '/memberships',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
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
			<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between">
				<div class="me-3">
					<h1 class="display-4 mb-2">${project.name}</h1>
					<p class="lead mb-0">${project.slug}</p>
					<span class="badge bg-${project.is_private ? 'secondary' : 'primary'} mt-2">
						${project.is_private ? 'Private Project' : 'Public Project'}
					</span>
				</div>
				<div class="mt-3 mt-md-0">
					<a class="btn btn-light btn-sm d-none" id="projectTaigaLinkBtn" href="#" target="_blank" rel="noopener">Open in Taiga</a>
				</div>
			</div>
		`;
				$('#projectHeaderContent').html(headerHtml);

				const taigaLink = taigaItemPermalink('project', project, apiUrl);
				if (taigaLink) {
					$('#projectTaigaLinkBtn').removeClass('d-none').attr('href', taigaLink);
				}
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
				const html = taigaRenderMarkdown(project.description || '');
				$('#projectDescriptionContent').html(html);
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
