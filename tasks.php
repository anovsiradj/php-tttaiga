<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Tasks - Taiga API</title>
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
		$pageTitle = 'Tasks';
		$bulkCreateModalId = 'bulkCreateTaskModal';
		$bulkUpdateModalId = 'bulkUpdateTaskModal';
		$searchPlaceholder = 'Search tasks...';
		$userStorySelect = true;
		$extendedStatuses = true;
		include __DIR__ . '/app/partials/list_header.php';

		$totalLabel = 'Total Tasks';
		$totalId = 'totalTasks';
		$filteredId = 'filteredTasks';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="tasksContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading tasks...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Tasks pagination" class="mt-4">
			<ul class="pagination justify-content-center" id="tasksPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

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
								<select class="form-select" id="bulkTaskProject">
									<option value="">Loading projects...</option>
								</select>
							</div>
						</div>
					</div>

					<div class="mb-3">
						<label for="bulkTaskUserStory" class="form-label">User Story</label>
						<select class="form-select" id="bulkTaskUserStory">
							<option value="">Loading user stories...</option>
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

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

			// Load tasks and filters
			loadTasks();
			taigaLoadProjects(apiUrl, token);
			loadUserStories();

			// Event listeners
			let searchTimeout;
			$('#searchInput').on('input', function () {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(function () {
					loadTasks(1);
				}, 500);
			});
			$('#projectSelect').on('change', function() { loadTasks(1); });
			$('#userStorySelect').on('change', function() { loadTasks(1); });
			$('#statusSelect').on('change', function() { loadTasks(1); });
			$('#refreshBtn').on('click', function () {
				loadTasks(1);
				taigaLoadProjects(apiUrl, token);
				loadUserStories();
			});

			$('#selectAllBtn').on('click', function () {
				$('#tasksContent input[type="checkbox"]').prop('checked', true);
				updateSelectionCount();
			});

			$('#clearSelectionBtn').on('click', function () {
				$('#tasksContent input[type="checkbox"]').prop('checked', false);
				updateSelectionCount();
			});



			// Bulk Task Create functionality (same as in usor.php)
			$('#bulkCreateTaskModal').on('show.bs.modal', function () {
				populateBulkCreateTaskDropdowns();
			});

			$('#previewBulkTaskCreate').on('click', function () {
				previewBulkTaskCreate();
			});

			$('#submitBulkTaskCreate').on('click', function () {
				submitBulkTaskCreate();
			});

			// Bulk Task Update functionality (same as in usor.php)
			$('#bulkUpdateTaskModal').on('show.bs.modal', function () {
				populateBulkUpdateTaskDropdowns();
			});

			$('#submitBulkTaskUpdate').on('click', function () {
				submitBulkTaskUpdate();
			});

			function loadTasks(page = 1) {
				const searchTerm = $('#searchInput').val().trim();
				const projectId = $('#projectSelect').val();
				const userStoryId = $('#userStorySelect').val();
				const status = $('#statusSelect').val();

				const params = { page: page };
				
				if (searchTerm) {
					params.q = searchTerm;
				}
				if (projectId) {
					params.project = projectId;
				}
				if (userStoryId) {
					params.user_story = userStoryId;
				}
				if (status) {
					params.status = status;
				}
				
				$('#tasksContent').html(`
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading tasks...</span>
				</div>
			</div>
		`);

				$.ajax({
					url: apiUrl + '/tasks',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (tasks, status, xhr) {
						displayTasks(tasks);
						taigaRenderPagination(xhr, '#tasksPagination', loadTasks);
					},
					error: function (xhr) {
						console.error('Failed to load tasks:', xhr);
						$('#tasksContent').html(`
					<div class="alert alert-danger">
						Unable to load tasks. Please try again.
					</div>
				`);
						$('#tasksPagination').empty();
					}
				});
			}


			function loadUserStories() {
				$.ajax({
					url: apiUrl + '/userstories',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (userStories) {
						let html = '<option value="">All User Stories</option>';
						userStories.forEach(us => {
							html += `<option value="${us.id}">#${us.ref}: ${us.subject}</option>`;
						});
						$('#userStorySelect').html(html);
					},
					error: function (xhr) {
						console.error('Failed to load user stories:', xhr);
					}
				});
			}

			function displayTasks(tasks) {
				$('#totalTasks').text(tasks.length);
				$('#filteredTasks').text(tasks.length);

				if (tasks.length === 0) {
					$('#tasksContent').html(`
				<div class="alert alert-info">
					No tasks found.
				</div>
			`);
					return;
				}

				let html = '<div class="row">';
				tasks.forEach(task => {
					const statusClass = taigaGetStatusClass(task.status);
					const createdDate = task.created_date ? new Date(task.created_date).toLocaleDateString() : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4 mb-3">
					<div class="card task-card" data-task-id="${task.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input task-checkbox" type="checkbox" value="${task.id}" id="task-${task.id}">
									<label class="form-check-label" for="task-${task.id}"></label>
								</div>
								<span class="badge status-badge bg-${statusClass}">
									${task.status || 'Unknown'}
								</span>
							</div>
							
							<h6 class="card-title mb-2">${task.subject || 'Untitled Task'}</h6>
							
							${task.description ? `
								<p class="card-text task-description text-muted small mb-2">
									${task.description}
								</p>
							` : ''}
							
							<div class="d-flex justify-content-between align-items-center">
								<small class="text-muted">Ref: #${task.ref}</small>
								<small class="text-muted">${createdDate}</small>
							</div>
							
							${task.user_story ? `
								<small class="text-muted d-block mt-2">User Story: #${task.user_story}</small>
							` : ''}
							
							${task.project ? `
								<small class="text-muted d-block">Project ID: ${task.project}</small>
							` : ''}
							
							<div class="mt-3">
								<a href="usor.php?id=${task.user_story}" class="btn btn-sm btn-outline-primary">
									View User Story
								</a>
							</div>
						</div>
					</div>
				</div>
			`;
				});
				html += '</div>';

				$('#tasksContent').html(html);

				// Add click event to checkboxes
				$('.task-checkbox').on('change', updateSelectionCount);

				// Add click event to cards (excluding checkbox area)
				$('.task-card').on('click', function (e) {
					if (!$(e.target).is('.form-check, .form-check-input, .form-check-label')) {
						const taskId = $(this).data('task-id');
						// You could implement a task detail view here
						console.log('Clicked task:', taskId);
					}
				});
			}

			// Local filter function removed as filtering is now done via API.

			function updateSelectionCount() {
				const selectedCount = $('#tasksContent input:checked').length;
				$('#selectionCount').text(selectedCount);
			}



			// Bulk operations functions (same as in usor.php)
			function populateBulkCreateTaskDropdowns() {
				// Populate project dropdown
				$.ajax({
					url: apiUrl + '/projects',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (projects) {
						let html = '<option value="">Select Project</option>';
						projects.forEach(project => {
							html += `<option value="${project.id}">${project.name}</option>`;
						});
						$('#bulkTaskProject').html(html);
					}
				});

				// Populate user story dropdown
				$.ajax({
					url: apiUrl + '/userstories',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (userStories) {
						let html = '<option value="">Select User Story</option>';
						userStories.forEach(us => {
							html += `<option value="${us.id}">#${us.ref}: ${us.subject}</option>`;
						});
						$('#bulkTaskUserStory').html(html);
					}
				});

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

				// Load all tasks for selection
				loadAllTasksForBulkUpdate();
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
				const projectId = $('#bulkTaskProject').val();
				const userStoryId = $('#bulkTaskUserStory').val();

				if (!taskTitles) {
					alert('Please enter task titles');
					return;
				}

				if (!projectId) {
					alert('Please select a project');
					return;
				}

				if (!userStoryId) {
					alert('Please select a user story');
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
						user_story: userStoryId,
						project: projectId
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
						loadTasks(); // Reload tasks list
					})
					.catch(error => {
						console.error('Failed to create tasks:', error);
						alert('Failed to create some tasks. Please check the console for details.');
					});
			}

			function loadAllTasksForBulkUpdate() {
				// Load all tasks for bulk update selection
				$.ajax({
					url: apiUrl + '/tasks',
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
							<input class="form-check-input" type="checkbox" value="${task.id}" id="bulk-task-${task.id}">
							<label class="form-check-label" for="bulk-task-${task.id}">
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
						loadTasks(); // Reload tasks list
					})
					.catch(error => {
						console.error('Failed to update tasks:', error);
						alert('Failed to update some tasks. Please check the console for details.');
					});
			}
		});
	</script>

</body>

</html>