<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Usor Details - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="usor-header">
		<div class="container">
			<a href="usors.php" class="back-btn">
				<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
					<path fill-rule="evenodd" d="M12 8a.5.5 0 0 1-.5.5H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5a.5.5 0 0 1 .5.5z" />
				</svg>
				Back to User Stories
			</a>
			<div id="usorHeaderContent">
				<div class="loading-spinner">
					<div class="spinner-border text-light" role="status">
						<span class="visually-hidden">Loading user story...</span>
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
								<svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
									<path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
								</svg>
								Bulk Create
							</button>
							<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUpdateTaskModal">
								<svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
									<path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
								</svg>
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

			function loadUsor(usorId) {
				$.ajax({
					url: apiUrl + '/userstories/' + usorId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (usor) {
						displayUsorHeader(usor);
						displayUsorDescription(usor);
						displayUsorMetadata(usor);
						displayUsorStats(usor);
					},
					error: function (xhr) {
						console.error('Failed to load user story:', xhr);
						$('#usorHeaderContent').html(`
					<div class="alert alert-danger">
						Unable to load user story. Please try again.
					</div>
				`);
					}
				});
			}

			function loadUsorTasks(usorId) {
				$.ajax({
					url: apiUrl + '/tasks?user_story=' + usorId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (tasks) {
						displayUsorTasks(tasks);
					},
					error: function (xhr) {
						console.error('Failed to load tasks:', xhr);
						$('#usorTasksContent').html(`
					<div class="alert alert-warning">
						Unable to load tasks for this user story.
					</div>
				`);
					}
				});
			}

			function displayUsorHeader(usor) {
				const statusClass = taigaGetStatusClass(usor.status);
				const headerHtml = `
			<h1 class="display-4 mb-2">${usor.subject || 'Untitled Story'}</h1>
			<p class="lead mb-0">Ref: #${usor.ref}</p>
			<span class="badge status-badge bg-${statusClass} mt-2">
				${usor.status || 'Unknown Status'}
			</span>
		`;
				$('#usorHeaderContent').html(headerHtml);
			}

			function displayUsorDescription(usor) {
				let descriptionHtml;
				if (usor.description) {
					descriptionHtml = `
				<div class="description-content">
					${usor.description}
				</div>
			`;
				} else {
					descriptionHtml = `
				<p class="text-muted">No description provided.</p>
			`;
				}
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
					<strong>Epic:</strong><br>
					<span id="epicName">Loading epic...</span>
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
				<small class="text-muted">Story Points</small>
			</div>
		`;
				$('#usorStatsContent').html(statsHtml);
			}

			function displayUsorTasks(tasks) {
				$('#tasksCount').text(`${tasks.length} task(s)`);

				if (tasks.length === 0) {
					$('#usorTasksContent').html(`
				<p class="text-muted">No tasks found for this user story.</p>
			`);
					return;
				}

				let html = '';
				tasks.forEach(task => {
					const statusClass = taigaGetStatusClass(task.status);
					html += `
				<div class="card task-card mb-2">
					<div class="card-body py-2">
						<div class="d-flex justify-content-between align-items-start">
							<h6 class="card-title mb-1">${task.subject || 'Untitled Task'}</h6>
							<span class="badge status-badge bg-${statusClass}">
								${task.status || 'Unknown'}
							</span>
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
					url: apiUrl + '/projects/' + projectId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
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
					url: apiUrl + '/epics/' + epicId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (epic) {
						$('#epicName').text(epic.subject || 'Untitled Epic');
					},
					error: function (xhr) {
						console.error('Failed to load epic:', xhr);
						$('#epicName').text('Unknown Epic');
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

				// Populate user story dropdown (current user story should be selected)
				$('#bulkTaskUserStory').html(`<option value="${currentUsor.id}" selected>Current User Story (#${currentUsor.ref})</option>`);

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
						url: apiUrl + '/tasks',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
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
				// Load tasks for the current user story
				$.ajax({
					url: apiUrl + '/tasks?user_story=' + currentUsor.id,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
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

				if (Object.keys(updateData).length === 0) {
					alert('Please select at least one field to update');
					return;
				}

				// Update tasks sequentially
				const promises = selectedTasks.map(taskId => {
					return $.ajax({
						url: apiUrl + '/tasks/' + taskId,
						type: 'PATCH',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
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

			// Store current user story data for reference
			let currentUsor = null;
			function loadUsor(usorId) {
				$.ajax({
					url: apiUrl + '/userstories/' + usorId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (usor) {
						currentUsor = usor; // Store for bulk operations
						displayUsorHeader(usor);
						displayUsorDescription(usor);
						displayUsorMetadata(usor);
						displayUsorStats(usor);
					},
					error: function (xhr) {
						console.error('Failed to load user story:', xhr);
						$('#usorHeaderContent').html(`
					<div class="alert alert-danger">
						Unable to load user story. Please try again.
					</div>
				`);
					}
				});
			}
		});
	</script>

	<!-- Bulk Create Task Modal -->
	<div class="modal fade" id="bulkCreateTaskModal" tabindex="-1" aria-labelledby="bulkCreateTaskModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkCreateTaskModalLabel">Bulk Create Tasks</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label for="bulkTaskTitles" class="form-label">Task Titles (one per line)</label>
						<textarea class="form-control" id="bulkTaskTitles" rows="5" placeholder="Enter task titles, one per line\nExample:\nDesign database schema\nImplement user authentication\nCreate API endpoints"></textarea>
						<div class="form-text">Enter each task title on a separate line</div>
					</div>

					<div class="row">
						<div class="col-md-6">
							<div class="mb-3">
								<label for="bulkTaskStatus" class="form-label">Status</label>
								<select class="form-select" id="bulkTaskStatus">
									<option value="">Select Status</option>
									<option value="new">New</option>
									<option value="ready">Ready</option>
									<option value="in progress">In Progress</option>
									<option value="done">Done</option>
									<option value="archived">Archived</option>
									<option value="blocked">Blocked</option>
								</select>
							</div>
						</div>
						<div class="col-md-6">
							<div class="mb-3">
								<label for="bulkTaskProject" class="form-label">Project</label>
								<select class="form-select" id="bulkTaskProject" disabled>
									<option value="">Loading...</option>
								</select>
							</div>
						</div>
					</div>

					<div class="mb-3">
						<label for="bulkTaskUserStory" class="form-label">User Story</label>
						<select class="form-select" id="bulkTaskUserStory" disabled>
							<option value="">Loading...</option>
						</select>
					</div>

					<div class="mb-3">
						<label for="bulkTaskDescription" class="form-label">Description (applies to all tasks)</label>
						<textarea class="form-control" id="bulkTaskDescription" rows="3" placeholder="Optional description that will be applied to all created tasks"></textarea>
					</div>

					<div id="bulkTaskPreview" class="alert alert-info">
						<p class="mb-0">Preview will appear here after clicking "Preview"</p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-info" id="previewBulkTaskCreate">Preview</button>
					<button type="button" class="btn btn-success" id="submitBulkTaskCreate">Create Tasks</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Bulk Update Task Modal -->
	<div class="modal fade" id="bulkUpdateTaskModal" tabindex="-1" aria-labelledby="bulkUpdateTaskModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkUpdateTaskModalLabel">Bulk Update Tasks</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label class="form-label">Select Tasks to Update</label>
						<div id="bulkUpdateTaskList" class="border p-3" style="max-height: 200px; overflow-y: auto;">
							<div class="text-center text-muted">
								<div class="spinner-border spinner-border-sm" role="status">
									<span class="visually-hidden">Loading tasks...</span>
								</div>
								<p class="mt-2 mb-0">Loading tasks...</p>
							</div>
						</div>
						<div class="form-text">Select the tasks you want to update</div>
					</div>

					<div class="mb-3">
						<label for="bulkUpdateTaskStatus" class="form-label">Update Status</label>
						<select class="form-select" id="bulkUpdateTaskStatus">
							<option value="">No Change</option>
							<option value="new">New</option>
							<option value="ready">Ready</option>
							<option value="in progress">In Progress</option>
							<option value="done">Done</option>
							<option value="archived">Archived</option>
							<option value="blocked">Blocked</option>
						</select>
						<div class="form-text">Leave as "No Change" to keep current status</div>
					</div>

					<div class="alert alert-warning">
						<h6 class="alert-heading">⚠️ Warning</h6>
						<p class="mb-0">This action will update all selected tasks with the chosen settings. This cannot be undone.</p>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="submitBulkTaskUpdate">Update Tasks</button>
				</div>
			</div>
		</div>
	</div>

</body>

</html>