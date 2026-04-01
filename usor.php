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
							<button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#bulkCreateTaskModal">
								<i class="bi bi-plus-lg me-1"></i>
								Bulk Create
							</button>
							<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUpdateTaskModal">
								<i class="bi bi-pencil-square me-1"></i>
								Bulk Update
							</button>
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
			<h1 class="display-4 mb-2">${usor.subject || 'Untitled Usor'}</h1>
			<p class="lead mb-0">Ref: #${usor.ref}</p>
			<div class="mt-2">
				${statusBadge}
			</div>
		`;
				$('#usorHeaderContent').html(headerHtml);
			}

			function displayUsorDescription(usor) {
				if (!usor.description) {
					$('#usorDescriptionContent').closest('.card').hide();
					return;
				}
				const descriptionHtml = `
					<div class="description-content">
						${usor.description}
					</div>
				`;
				$('#usorDescriptionContent').html(descriptionHtml);
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
					html += `
				<div class="card task-card mb-2">
					<div class="card-body py-2">
						<div class="d-flex justify-content-between align-items-start">
							<h6 class="card-title mb-1">${task.subject || 'Untitled Task'}</h6>
							${statusBadge}
						</div>
						<p class="card-text text-muted small mb-1">
							Ref: #${task.ref}
						</p>
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



			// Bulk Task Create functionality
			$('#bulkCreateTaskModal').on('show.bs.modal', function () {
				populateBulkCreateTaskDropdowns();
			});

			$('#previewBulkTaskCreate').on('click', function () {
				previewBulkTaskCreate();
			});

			$('#submitBulkTaskCreate').on('click', function () {
				submitBulkTaskCreate();
			});

			// Bulk Task Update functionality
			$('#bulkUpdateTaskModal').on('show.bs.modal', function () {
				populateBulkUpdateTaskDropdowns();
			});

			$('#submitBulkTaskUpdate').on('click', function () {
				submitBulkTaskUpdate();
			});

			function populateBulkCreateTaskDropdowns() {
				// Populate project dropdown (current project should be selected)
				$('#bulkTaskProject').html(`<option value="${currentUsor.project}" selected>Current Project</option>`);

				// Populate usor dropdown (current usor should be selected)
				$('#bulkTaskUserStory').html(`<option value="${currentUsor.id}" selected>Current Usor (#${currentUsor.ref})</option>`);

				// Populate status dropdown
				const statusOptions = ['New', 'Ready', 'In Progress', 'Done', 'Archived', 'Blocked'];
				let statusHtml = '<option value="">Select Status</option>';
				statusOptions.forEach(status => {
					statusHtml += `<option value="${status.toLowerCase()}">${status}</option>`;
				});
				$('#bulkTaskStatus').html(statusHtml);
			}

			function populateBulkUpdateTaskDropdowns() {
				// Populate status dropdown for bulk update
				const statusOptions = ['New', 'Ready', 'In Progress', 'Done', 'Archived', 'Blocked'];
				let statusHtml = '<option value="">No Change</option>';
				statusOptions.forEach(status => {
					statusHtml += `<option value="${status.toLowerCase()}">${status}</option>`;
				});
				$('#bulkUpdateTaskStatus').html(statusHtml);

				const projectId = currentUsor.project;

				// Initialize Usor Select2
				taigaInitRemoteSelect2('#bulkUpdateTaskUsor', '/userstories', {
					placeholder: 'No Change',
					formatText: (item) => `#${item.ref}: ${item.subject}`,
					additionalParams: () => ({ project: projectId })
				});

				// Initialize Sprint Select2
				taigaInitRemoteSelect2('#bulkUpdateTaskSprint', '/milestones', {
					placeholder: 'No Change',
					additionalParams: () => ({ project: projectId })
				});

				// Load current tasks for selection
				loadTasksForBulkUpdate();
			}

			function previewBulkTaskCreate() {
				const taskTitles = $('#bulkTaskTitles').val().trim();
				if (!taskTitles) {
					alert('Please enter task titles');
					return;
				}

				const taskLines = taskTitles.split('\n').filter(line => line.trim());
				let previewHtml = `<p>${taskLines.length} tasks will be created:</p>`;
				previewHtml += '<ul class="list-group">';

				taskLines.forEach((title, index) => {
					previewHtml += `<li class="list-group-item">${index + 1}. ${title}</li>`;
				});

				previewHtml += '</ul>';
				$('#bulkTaskPreview').html(previewHtml);
			}

			function submitBulkTaskCreate() {
				const taskTitles = $('#bulkTaskTitles').val().trim();
				const status = $('#bulkTaskStatus').val();
				const description = $('#bulkTaskDescription').val().trim();

				if (!taskTitles) {
					alert('Please enter task titles');
					return;
				}

				const taskLines = taskTitles.split('\n').filter(line => line.trim());
				if (taskLines.length === 0) {
					alert('No valid tasks to create');
					return;
				}

				// Create tasks sequentially
				const promises = taskLines.map((title, index) => {
					const taskData = {
						subject: title.trim(),
						status: status || 'new',
						description: description,
						user_story: currentUsor.id,
						project: currentUsor.project
					};

					return $.ajax({
						url: 'api.php/tasks',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						data: JSON.stringify(taskData)
					});
				});

				// Execute all promises
				Promise.all(promises)
					.then(() => {
						alert(`Successfully created ${taskLines.length} tasks!`);
						$('#bulkCreateTaskModal').modal('hide');
						loadUsorTasks(currentUsor.id); // Reload tasks
					})
					.catch(error => {
						console.error('Failed to create tasks:', error);
						alert('Failed to create some tasks. Please check the console for details.');
					});
			}

			function loadTasksForBulkUpdate() {
				// Load tasks for the current usor
				$.ajax({
					url: 'api.php/tasks?user_story=' + currentUsor.id,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (tasks) {
						let tasksHtml = '';
						tasks.forEach(task => {
							tasksHtml += `
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="${task.id}" id="task-${task.id}">
							<label class="form-check-label" for="task-${task.id}">
								#${task.ref}: ${task.subject}
							</label>
						</div>
					`;
						});
						$('#bulkUpdateTaskList').html(tasksHtml || '<p>No tasks available for update.</p>');
					},
					error: function (xhr) {
						console.error('Failed to load tasks:', xhr);
						$('#bulkUpdateTaskList').html('<p class="text-danger">Failed to load tasks.</p>');
					}
				});
			}

			function submitBulkTaskUpdate() {
				const selectedTasks = [];
				$('#bulkUpdateTaskList input:checked').each(function () {
					selectedTasks.push($(this).val());
				});

				if (selectedTasks.length === 0) {
					alert('Please select at least one task to update');
					return;
				}

				const updateData = {};
				const status = $('#bulkUpdateTaskStatus').val();

				if (status) updateData.status = status;

				const usor = $('#bulkUpdateTaskUsor').val();
				if (usor) updateData.user_story = usor === 'null' ? null : parseInt(usor);

				const sprint = $('#bulkUpdateTaskSprint').val();
				if (sprint) updateData.milestone = sprint === 'null' ? null : parseInt(sprint);

				if (Object.keys(updateData).length === 0) {
					alert('Please select at least one field to update');
					return;
				}

				// Update tasks sequentially
				const promises = selectedTasks.map(taskId => {
					return $.ajax({
						url: 'api.php/tasks/' + taskId,
						type: 'PATCH',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						data: JSON.stringify(updateData)
					});
				});

				// Execute all promises
				Promise.all(promises)
					.then(() => {
						alert(`Successfully updated ${selectedTasks.length} tasks!`);
						$('#bulkUpdateTaskModal').modal('hide');
						loadUsorTasks(currentUsor.id); // Reload tasks
					})
					.catch(error => {
						console.error('Failed to update tasks:', error);
						alert('Failed to update some tasks. Please check the console for details.');
					});
			}

			}
		});
	</script>

	<?php include __DIR__ . '/app/partials/task_bulk_create.php'; ?>
	<?php include __DIR__ . '/app/partials/task_bulk_update.php'; ?>
	<?php include __DIR__ . '/app/partials/task_bulk_delete.php'; ?>

</body>

</html>