<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<?php include __DIR__ . '/app/layouts/main_head.php'; ?>
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<?php
	$backUrl = 'usors.php';
	$backLabel = 'Back to Usors';
	$headerId = 'usorHeaderContent';
	$loadingLabel = 'Loading usor...';
	include __DIR__ . '/app/partials/item_header.php';
	?>

	<div class="container">
		<div class="row">
			<div class="col-md-8">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Description</h5>
					</div>
					<div class="card-body" id="usorDescriptionContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading description...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-4">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0">Tasks</h5>
						<div>
							<small class="text-muted me-3" id="tasksCount">Loading...</small>
							<a class="btn btn-outline-primary btn-sm" id="viewTasksListBtn" href="tasks.php">
								View Task List
							</a>
						</div>
					</div>
					<div class="card-body" id="usorTasksContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading tasks...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Komentar</h5>
					</div>
					<div class="card-body" id="usorCommentsContent">
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
						<h5 class="mb-0">Details</h5>
					</div>
					<div class="card-body" id="usorMetadataContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading details...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">Statistics</h5>
					</div>
					<div class="card-body" id="usorStatsContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading statistics...</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

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
			const user = JSON.parse(userData);

			// Get usor ID from URL
			const urlParams = new URLSearchParams(window.location.search);
			const usorId = urlParams.get('id');

			if (!usorId) {
				window.location.href = 'usors.php';
				return;
			}

			// Load usor data
			loadUsor(usorId);
			loadUsorTasks(usorId);
			taigaLoadComments('#usorCommentsContent', 'userstory', usorId, apiUrl, token);

			// Store current user story data for reference
			let currentUsor = null;
			function loadUsor(usorId) {
				$.ajax({
					url: 'api.php/userstories/' + usorId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (usor) {
						currentUsor = usor; // Store for bulk operations
						displayUsorHeader(usor);
						displayUsorDescription(usor);
						displayUsorMetadata(usor);
						displayUsorStats(usor);
						const url = `tasks.php?project=${encodeURIComponent(usor.project)}&user_story=${encodeURIComponent(usor.id)}`;
						$('#viewTasksListBtn').attr('href', url);
					},
					error: function (xhr) {
						console.error('Failed to load usor:', xhr);
						$('#usorHeaderContent').html(`
					<div class="alert alert-danger">
						Unable to load usor. Please try again.
					</div>
				`);
					}
				});
			}

			function loadUsorTasks(usorId) {
				$.ajax({
					url: 'api.php/tasks?user_story=' + usorId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (tasks) {
						displayUsorTasks(tasks);
					},
					error: function (xhr) {
						console.error('Failed to load tasks:', xhr);
						$('#usorTasksContent').html(`
					<div class="alert alert-warning">
						Unable to load tasks for this usor.
					</div>
				`);
					}
				});
			}

			function displayUsorHeader(usor) {
				const statusInfo = taigaGetStatusInfo(usor);
				const statusBadge = taigaRenderStatusBadge(statusInfo);
				const headerHtml = `
			<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between">
				<div class="me-3">
					<h1 class="display-4 mb-2">${usor.subject || 'Untitled Usor'}</h1>
					<p class="lead mb-0">Ref: #${usor.ref}</p>
					<div class="mt-2">
						${statusBadge}
					</div>
				</div>
				<div class="mt-3 mt-md-0">
					<a class="btn btn-light btn-sm d-none" id="usorTaigaLinkBtn" href="#" target="_blank" rel="noopener">Open in Taiga</a>
				</div>
			</div>
		`;
				$('#usorHeaderContent').html(headerHtml);

				const taigaLink = taigaItemPermalink('usor', usor, apiUrl);
				if (taigaLink) {
					$('#usorTaigaLinkBtn').removeClass('d-none').attr('href', taigaLink);
				} else if (usor.project) {
					taigaGetProjectSlug(usor.project, apiUrl, token).then(function (projectSlug) {
						const link = taigaItemPermalink('usor', usor, apiUrl, { projectSlug: projectSlug });
						if (link) {
							$('#usorTaigaLinkBtn').removeClass('d-none').attr('href', link);
						}
					});
				}
			}

			function displayUsorDescription(usor) {
				const html = taigaRenderMarkdown(usor.description || '');
				$('#usorDescriptionContent').html(html);
			}

			function displayUsorMetadata(usor) {
				const metadataHtml = `
			<div class="mb-2">
				<strong>Project:</strong><br>
				<span id="projectName">Loading project...</span>
			</div>
			${usor.epic ? `
				<div class="mb-2">
					<strong>Epik:</strong><br>
					<span id="epicName">Loading epik...</span>
				</div>
			` : ''}
			<div class="mb-2">
				<strong>Priority:</strong><br>
				${usor.priority || 'Not specified'}
			</div>
			<div class="mb-2">
				<strong>Created:</strong><br>
				${usor.created_date ? new Date(usor.created_date).toLocaleDateString() : 'Unknown'}
			</div>
			<div class="mb-2">
				<strong>Modified:</strong><br>
				${usor.modified_date ? new Date(usor.modified_date).toLocaleDateString() : 'Unknown'}
			</div>
			<div class="mb-2">
				<strong>Assigned To:</strong><br>
				${usor.assigned_to ? usor.assigned_to : 'Unassigned'}
			</div>
		`;
				$('#usorMetadataContent').html(metadataHtml);

				// Load project name
				if (usor.project) {
					loadProjectName(usor.project);
				}

				// Load epic name if exists
				if (usor.epic) {
					loadEpicName(usor.epic);
				}
			}

			function displayUsorStats(usor) {
				const statsHtml = `
			<div class="text-center">
				<div class="stat-number">#${usor.ref}</div>
				<small class="text-muted">Reference</small>
			</div>
			<hr>
			<div class="text-center">
				<div class="stat-number">${usor.points ? usor.points.total || '0' : '0'}</div>
				<small class="text-muted">Usor Points</small>
			</div>
		`;
				$('#usorStatsContent').html(statsHtml);
			}

			function displayUsorTasks(tasks) {
				$('#tasksCount').text(`${tasks.length} task(s)`);

				if (tasks.length === 0) {
					$('#usorTasksContent').html(`
						<div class="text-muted italic">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '';
				tasks.forEach(task => {
					const statusInfo = taigaGetStatusInfo(task);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const assignedTo = task.assigned_to_extra ? task.assigned_to_extra.full_name_display : (task.assigned_to ? 'User ID: ' + task.assigned_to : 'Unassigned');
					html += `
				<div class="card task-card mb-2">
					<div class="card-body py-2">
						<div class="d-flex justify-content-between align-items-start">
							<h6 class="card-title mb-1">${task.subject || 'Untitled Task'}</h6>
							${statusBadge}
						</div>
						<div class="d-flex justify-content-between align-items-center mb-1">
							<small class="text-muted">Ref: #${task.ref}</small>
							<small class="text-muted text-truncate ms-2">Assigned: <strong>${assignedTo}</strong></small>
						</div>
						${task.description ? `
							<p class="card-text small text-muted mb-0">
								${task.description.substring(0, 80) + (task.description.length > 80 ? '...' : '')}
							</p>
						` : ''}
					</div>
				</div>
			`;
				});

				$('#usorTasksContent').html(html);
			}

			function loadProjectName(projectId) {
				$.ajax({
					url: 'api.php/projects/' + projectId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (project) {
						$('#projectName').text(project.name || 'Unknown Project');
					},
					error: function (xhr) {
						console.error('Failed to load project:', xhr);
						$('#projectName').text('Unknown Project');
					}
				});
			}

			function loadEpicName(epicId) {
				$.ajax({
					url: 'api.php/epics/' + epicId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (epic) {
						$('#epicName').text(epic.subject || 'Untitled Epik');
					},
					error: function (xhr) {
						console.error('Failed to load epik:', xhr);
						$('#epicName').text('Unknown Epik');
					}
				});
			}
		});
	</script>

</body>

</html>
