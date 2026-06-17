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

	<div class="container mt-4">
		<?php
		$pageTitle = 'Tasks';
		$statusType = 'task';

		$searchPlaceholder = 'Search tasks...';
		$userStorySelect = true;
		$filterAssignedEnable = true;
		$sortOptions = [
			'subject' => 'Subject (A-Z)',
			'-subject' => 'Subject (Z-A)',
			'created_date' => 'Created (Oldest)',
			'-created_date' => 'Created (Newest)',
			'modified_date' => 'Modified (Oldest)',
			'-modified_date' => 'Modified (Newest)',
			'status' => 'Status (ASC)',
			'-status' => 'Status (DESC)',
			'task_order' => 'Task Order (ASC)',
			'-task_order' => 'Task Order (DESC)',
		];
		$primaryAction = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#singleTaskModal"><i class="bi bi-plus-lg me-1"></i> Add New</button>';
$bulkActions = '
	<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkCreateTaskModal"><i class="bi bi-plus-lg me-2"></i> Bulk Create</a></li>
	<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateTaskModal"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
	<li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#bulkDeleteTaskModal"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
';
		include __DIR__ . '/app/partials/list_header.php';

		$totalLabel = 'Total Tasks';
		$totalId = 'totalTasks';
		$filteredId = 'filteredTasks';
		$selectionCountId = 'selectedTasksCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="tasksContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading tasks...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Tasks pagination" class="pagination-container">
			<ul class="pagination justify-content-center" id="tasksPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

	<?php include __DIR__ . '/app/partials/task_multiple_form.php'; ?>
<?php include __DIR__ . '/app/partials/task_multiple_delete.php'; ?>
<?php include __DIR__ . '/app/partials/task_single_form.php'; ?>

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

			// Define globals for taiga.js helpers
			window.apiUrl = apiUrl;
			window.taigaToken = token;

			let allowFilterLoad = true;
			const onFilterChange = function (page = 1) {
				if (!allowFilterLoad) return;
				loadTasks(page);
			};

			taigaBindFilters(onFilterChange);

			taigaBindSelectionLogic('task-checkbox', function(checkedCount) {
				const filtered = parseInt($('#filteredTasks').text()) || 0;
				const total = parseInt($('#totalTasks').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalTasks', 'filteredTasks', 'selectedTasksCount');
			});

			allowFilterLoad = false;
			taigaApplyFiltersFromUrl().then(function (page) {
				allowFilterLoad = true;
				loadTasks(page);
			}, function () {
				allowFilterLoad = true;
				loadTasks(1);
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
				taigaReplaceUrlQuery({
					...taigaGetFilterParams(),
					page: page
				});

				const params = {
					...taigaGetFilterParams(),
					page: page
				};
				
				$('#tasksContent').html(`
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading tasks...</span>
				</div>
			</div>
		`);

				$.ajax({
					url: 'api.php/tasks',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (tasks, status, xhr) {
						displayTasks(tasks, xhr);
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


			// loadUsors function removed as it is now handled by taigaLoadUsors in taiga.js

			function displayTasks(tasks, xhr) {
				taigaUpdateListCounts(xhr, tasks.length, 'totalTasks', 'filteredTasks', 'selectedTasksCount');

				if (tasks.length === 0) {
					$('#tasksContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row taiga-list-grid">';
				tasks.forEach(task => {
					const statusInfo = taigaGetStatusInfo(task);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const assignedTo = task.assigned_to_extra ? task.assigned_to_extra.full_name_display : (task.assigned_to ? 'User ID: ' + task.assigned_to : 'Unassigned');
					const owner = task.owner_extra ? task.owner_extra.full_name_display : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4">
					<div class="card taiga-list-card task-card h-100" data-task-id="${task.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input task-checkbox" type="checkbox" value="${task.id}" id="task-${task.id}">
									<label class="form-check-label" for="task-${task.id}"></label>
								</div>
								${statusBadge}
							</div>
							
							<h6 class="card-title text-truncate">${task.subject || 'Untitled Task'}</h6>
							
							<p class="card-text taiga-card-description task-description text-muted small mb-0">
								${task.description || ''}
							</p>
							
							<div class="taiga-card-meta">
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted">Ref: #${task.ref}</small>
									<small class="text-muted">${new Date(task.created_date).toLocaleDateString()}</small>
								</div>
								
								<div class="mb-1">
									<small class="text-muted d-block text-truncate">
										Assigned: <strong>${assignedTo}</strong>
									</small>
								</div>
								
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted text-truncate">By: ${owner}</small>
									<small class="text-muted">Upd: ${new Date(task.modified_date).toLocaleDateString()}</small>
								</div>

								${task.user_story_extra ? `
									<small class="text-muted d-block mt-2 text-truncate">Usor: #${task.user_story_extra.ref} ${task.user_story_extra.subject}</small>
								` : (task.user_story ? `<small class="text-muted d-block mt-2">Usor Ref: #${task.user_story}</small>` : '')}
							</div>
						</div>
						<div class="card-footer taiga-card-actions">
					<a href="task.php?id=${task.id}" class="btn btn-sm btn-outline-secondary shadow-sm">
						View Task
					</a>
					<button class="btn btn-outline-primary btn-sm edit-task" data-task-id="${task.id}" data-bs-toggle="modal" data-bs-target="#singleTaskModal">
						Edit
					</button>
					${task.user_story ? `
						<a href="usor.php?id=${task.user_story}" class="btn btn-sm btn-outline-secondary shadow-sm">
							View Usor
						</a>
					` : ''}
				</div>
					</div>
				</div>
			`;
				});
				html += '</div>';

				$('#tasksContent').html(html);

				// Add click event to checkboxes
				$('.task-checkbox').on('change', updateSelectionCount);
			}

			// Local filter function removed as filtering is now done via API.

			function updateSelectionCount() {
				const selectedCount = $('#tasksContent input:checked').length;
				$('#selectedTasksCount').text(selectedCount);
			}



			function populateBulkCreateTaskDropdowns() {
				const currentProjectId = $('#projectSelect').val();
				const currentUserStoryId = $('#userStorySelect').val();
				const usText = $('#userStorySelect option:selected').text();

				$('#bulkTaskProject').closest('.col-md-6').show();
				$('#bulkTaskUserStory').closest('.mb-3').show();

				const refreshProjectInputs = function (projectId, selectedUserStoryId, selectedUserStoryText) {
					taigaPopulateBulkStatuses('task', $('#bulkTaskStatus'), projectId);
					taigaPopulateBulkMembers($('#bulkTaskAssignee'), projectId);

					const $userStory = $('#bulkTaskUserStory');
					if ($userStory.data('select2')) {
						$userStory.select2('destroy');
					}
					$userStory.empty().append(new Option(projectId ? 'Select Usor' : 'Select project first', '', false, false));
					$userStory.prop('disabled', !projectId);
					taigaInitRemoteSelect2('#bulkTaskUserStory', '/userstories', {
						placeholder: projectId ? 'Select Usor' : 'Select project first',
						formatText: (item) => `#${item.ref}: ${item.subject}`,
						additionalParams: () => projectId ? { project: projectId } : {}
					});
					$userStory.prop('disabled', !projectId);
					if (selectedUserStoryId) {
						$userStory.append(new Option(selectedUserStoryText || ('Usor ' + selectedUserStoryId), selectedUserStoryId, true, true)).trigger('change');
					}
				};

				$('#bulkTaskProject').off('change.bulkTaskShared').on('change.bulkTaskShared', function () {
					refreshProjectInputs($(this).val(), null, null);
				});

				taigaPopulateProjectSelect($('#bulkTaskProject'), currentProjectId).done(function () {
					if (currentProjectId) {
						$('#bulkTaskProject').val(String(currentProjectId)).trigger('change.select2');
					}
					refreshProjectInputs(currentProjectId, currentUserStoryId, usText);
				});

				// Update search context alert only if search is active
				const currentSearch = $('#searchInput').val();
				if (currentSearch) {
					$('#activeTaskSearchQuery').text(currentSearch);
					$('#bulkTaskSearchContext').removeClass('d-none');
				} else {
					$('#bulkTaskSearchContext').addClass('d-none');
				}
				$('#bulkTaskTitles').attr('placeholder', 'Enter task titles, one per line');
			}

			function populateBulkUpdateTaskDropdowns() {
				const filterParams = taigaGetFilterParams();
				const projectId = filterParams.project;

				const refreshUpdateInputs = function (selectedProjectId) {
					taigaPopulateBulkStatuses('task', $('#bulkUpdateTaskStatus'), selectedProjectId, 'No Change');
					taigaPopulateBulkMembers($('#bulkUpdateTaskAssignee'), selectedProjectId, 'No Change');

					$('#bulkUpdateTaskUsor, #bulkUpdateTaskSprint').each(function () {
						const $select = $(this);
						if ($select.data('select2')) {
							$select.select2('destroy');
						}
						$select.empty().append(new Option(selectedProjectId ? 'No Change' : 'Select project first', '', false, false));
						$select.prop('disabled', !selectedProjectId);
					});

					taigaInitRemoteSelect2('#bulkUpdateTaskUsor', '/userstories', {
						placeholder: selectedProjectId ? 'No Change' : 'Select project first',
						formatText: (item) => `#${item.ref}: ${item.subject}`,
						additionalParams: () => selectedProjectId ? { project: selectedProjectId } : {}
					});

					taigaInitRemoteSelect2('#bulkUpdateTaskSprint', '/milestones', {
						placeholder: selectedProjectId ? 'No Change' : 'Select project first',
						additionalParams: () => selectedProjectId ? { project: selectedProjectId } : {}
					});
					$('#bulkUpdateTaskUsor, #bulkUpdateTaskSprint').prop('disabled', !selectedProjectId);
				};

				$('#bulkUpdateTaskProject').off('change.bulkTaskShared').on('change.bulkTaskShared', function () {
					refreshUpdateInputs($(this).val());
				});

				taigaPopulateProjectSelect($('#bulkUpdateTaskProject'), projectId).done(function () {
					if (projectId) {
						$('#bulkUpdateTaskProject').val(String(projectId)).trigger('change.select2');
					}
					refreshUpdateInputs(projectId);
				});

				// Load tasks for selection matching current filters
				taigaLoadBulkItems('/tasks', $('#bulkUpdateTaskList'), item => {
					return `
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-task-${item.id}">
							<label class="form-check-label" for="bulk-task-${item.id}">
								#${item.ref}: ${item.subject}
							</label>
						</div>
					`;
				});
			}

			function submitBulkTaskUpdate() {
				const selectedTasks = [];
				$('#bulkUpdateTaskList input:checked').each(function () {
					selectedTasks.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				if (selectedTasks.length === 0) {
					alert('Please select at least one task to update');
					return;
				}

				const updateData = {};
				const status = $('#bulkUpdateTaskStatus').val();

				if (status) updateData.status = parseInt(status);
				const assignee = $('#bulkUpdateTaskAssignee').val();
				if (assignee) updateData.assigned_to = parseInt(assignee);

				const usor = $('#bulkUpdateTaskUsor').val();
				if (usor) updateData.user_story = usor === 'null' ? null : parseInt(usor);

				const sprint = $('#bulkUpdateTaskSprint').val();
				if (sprint) updateData.milestone = sprint === 'null' ? null : parseInt(sprint);

				if (Object.keys(updateData).length === 0) {
					alert('Please select at least one field to update');
					return;
				}

				const $btn = $('#submitBulkTaskUpdate');
				$btn.prop('disabled', true).text('Updating...');

				taigaExecuteBulk('/tasks/', selectedTasks, 'PATCH', updateData, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Update Tasks');
					if (errorCount === 0) {
						alert(`Successfully updated ${successCount} tasks!`);
						$('#bulkUpdateTaskModal').modal('hide');
						loadTasks();
					} else {
						alert(`Updated ${successCount} tasks, but ${errorCount} failed.`);
					}
				});
			}

			function previewBulkTaskCreate() {
				const titles = $('#bulkTaskTitles').val().trim().split('\n').filter(t => t.trim());
				if (titles.length === 0) {
					alert('Please enter at least one task title');
					return;
				}

				let html = '<h6>Tasks to be created:</h6><ul class="mb-0">';
				titles.forEach(title => {
					html += `<li>${title}</li>`;
				});
				html += '</ul>';
				$('#bulkTaskPreview').html(html).show();
			}

			function submitBulkTaskCreate() {
				const titles = $('#bulkTaskTitles').val().trim().split('\n').filter(t => t.trim());
				if (titles.length === 0) {
					alert('Please enter at least one task title');
					return;
				}

				const projectId = $('#bulkTaskProject').val() || $('#projectSelect').val();
				const userStoryId = $('#bulkTaskUserStory').val() || $('#userStorySelect').val();
				const statusId = $('#bulkTaskStatus').val();
				const assigneeId = $('#bulkTaskAssignee').val();
				const commonDescription = $('#bulkTaskDescription').val().trim();

				if (!projectId) {
					alert('Please select a project');
					return;
				}

				const $btn = $('#submitBulkTaskCreate');
				$btn.prop('disabled', true).text('Creating...');

				let createdCount = 0;
				let errorCount = 0;

				const currentSearch = $('#searchInput').val();
				const prependSearch = $('#prependTaskSearchCheck').is(':checked');

				titles.forEach(title => {
					let finalSubject = title;
					if (currentSearch && prependSearch) {
						finalSubject = `[${currentSearch}] ${finalSubject}`;
					}

					const taskData = {
						subject: finalSubject,
						project: parseInt(projectId),
						description: commonDescription
					};

					if (userStoryId) taskData.user_story = parseInt(userStoryId);
					if (statusId) taskData.status = parseInt(statusId);
					if (assigneeId) taskData.assigned_to = parseInt(assigneeId);

					$.ajax({
						url: 'api.php/tasks',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						data: JSON.stringify(taskData),
						success: function () {
							createdCount++;
							if (createdCount + errorCount === titles.length) {
								finishBulkTaskCreate(createdCount, errorCount);
							}
						},
						error: function (xhr) {
							console.error('Failed to create task:', title, xhr);
							errorCount++;
							if (createdCount + errorCount === titles.length) {
								finishBulkTaskCreate(createdCount, errorCount);
							}
						}
					});
				});
			}

			function finishBulkTaskCreate(success, errors) {
				$('#submitBulkTaskCreate').prop('disabled', false).text('Create Tasks');
				if (errors === 0) {
					alert(`Successfully created ${success} tasks!`);
					$('#bulkCreateTaskModal').modal('hide');
					loadTasks();
				}
			}

			// Bulk Delete Logic
			$('#bulkDeleteTaskModal').on('show.bs.modal', function () {
				const selectedTasks = [];
				$('.task-checkbox:checked').each(function () {
					const title = $(this).closest('.card-body').find('.card-title').text();
					selectedTasks.push(title);
				});
				$('#selectedTasksList').html(selectedTasks.map(t => `<div>${t}</div>`).join(''));
			});

			$('#confirmBulkDeleteTasks').on('click', function () {
				const selectedTasks = [];
				$('.task-checkbox:checked').each(function () {
					selectedTasks.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				if (selectedTasks.length === 0) {
					alert('Please select at least one task to delete');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Deleting...');

				taigaExecuteBulk('/tasks/', selectedTasks, 'DELETE', null, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Delete Tasks');
					if (errorCount === 0) {
						alert(`Successfully deleted ${successCount} tasks!`);
						$('#bulkDeleteTaskModal').modal('hide');
						loadTasks();
					} else {
						alert(`Deleted ${successCount} tasks, but ${errorCount} failed.`);
					}
				});
			});
			// Single Task Create/Update
			$('#singleTaskModal').on('show.bs.modal', function (e) {
				const taskId = $(e.relatedTarget).data('task-id');
				const filterParams = taigaGetFilterParams();
				const initialProjectId = filterParams.project;
				const initialUsorId = filterParams.user_story;

				// Load projects first
				$.ajax({
					url: 'api.php/projects',
					type: 'GET',
					data: { member: taigaModel?.id ?? null },
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (projects) {
						let options = '<option value="">Select Project</option>';
						projects.forEach(project => {
							options += `<option value="${project.id}">${project.name}</option>`;
						});
						$('#singleTaskProject').html(options);
						if (initialProjectId) {
							$('#singleTaskProject').val(initialProjectId).trigger('change');
							if (initialUsorId) {
								setTimeout(() => {
									$('#singleTaskUsor').val(initialUsorId);
								}, 300);
							}
						}
					}
				});

				if (taskId) {
					// Edit mode: Load task data
					$('#singleTaskModalLabel').text('Edit Task');
					$.ajax({
						url: `api.php/tasks/${taskId}`,
						type: 'GET',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						success: function (task) {
							$('#singleTaskId').val(task.id);
							$('#singleTaskVersion').val(task.version);
							$('#singleTaskSubject').val(task.subject);
							$('#singleTaskDescription').val(task.description);
							$('#singleTaskProject').val(task.project).trigger('change');
							if (task.status) {
								taigaPopulateBulkStatuses('task', $('#singleTaskStatus'), task.project, 'Select Status');
								$('#singleTaskStatus').val(task.status);
							}
							if (task.assigned_to) {
								taigaPopulateBulkMembers($('#singleTaskAssignee'), task.project, 'Unassigned');
								$('#singleTaskAssignee').val(task.assigned_to);
							}
							if (task.user_story) {
								setTimeout(() => {
									$('#singleTaskUsor').val(task.user_story);
								}, 300);
							}
							if (task.milestone) {
								setTimeout(() => {
									$('#singleTaskSprint').val(task.milestone);
								}, 300);
							}
						},
						error: function (xhr) {
							console.error('Failed to load task:', xhr);
							alert('Failed to load task. Please try again.');
						}
					});
				} else {
					// Create mode: Reset form
					$('#singleTaskModalLabel').text('Create Task');
					$('#singleTaskForm')[0].reset();
					$('#singleTaskId').val('');
					$('#singleTaskVersion').val('');
				}
			});

			// Update statuses, members, user stories, and sprints when project changes
			$('#singleTaskProject').off('change').on('change', function () {
				const projectId = $(this).val();
				if (projectId) {
					taigaPopulateBulkStatuses('task', $('#singleTaskStatus'), projectId, 'Select Status');
					taigaPopulateBulkMembers($('#singleTaskAssignee'), projectId, 'Unassigned');

					// Load user stories for this project
					$.ajax({
						url: 'api.php/userstories',
						type: 'GET',
						data: { project: projectId },
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						success: function (usors) {
							let options = '<option value="">Select User Story</option>';
							usors.forEach(usor => {
								options += `<option value="${usor.id}">#${usor.ref} ${usor.subject || 'Untitled User Story'}</option>`;
							});
							$('#singleTaskUsor').html(options);
						}
					});

					// Load sprints for this project
					$.ajax({
						url: 'api.php/milestones',
						type: 'GET',
						data: { project: projectId },
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						success: function (sprints) {
							let options = '<option value="">Select Sprint</option>';
							sprints.forEach(sprint => {
								options += `<option value="${sprint.id}">${sprint.name}</option>`;
							});
							$('#singleTaskSprint').html(options);
						}
					});
				} else {
					$('#singleTaskStatus').html('<option value="">Select Project First</option>');
					$('#singleTaskAssignee').html('<option value="">Select Project First</option>');
					$('#singleTaskUsor').html('<option value="">Select Project First</option>');
					$('#singleTaskSprint').html('<option value="">Select Project First</option>');
				}
			});

			$('#submitSingleTask').on('click', function () {
				const taskId = $('#singleTaskId').val();
				const taskVersion = $('#singleTaskVersion').val();
				const projectId = $('#singleTaskProject').val();
				const subject = $('#singleTaskSubject').val().trim();
				const description = $('#singleTaskDescription').val().trim();
				const status = $('#singleTaskStatus').val();
				const assignee = $('#singleTaskAssignee').val();
				const usorId = $('#singleTaskUsor').val();
				const sprintId = $('#singleTaskSprint').val();

				if (!projectId) {
					alert('Please select a project');
					return;
				}
				if (!subject) {
					alert('Please enter a subject');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Saving...');

				const data = {
					subject: subject,
					description: description,
					project: parseInt(projectId)
				};
				if (status) data.status = parseInt(status);
				if (assignee) data.assigned_to = parseInt(assignee);
				if (usorId) data.user_story = parseInt(usorId);
				if (sprintId) data.milestone = parseInt(sprintId);
				if (taskId) {
					data.version = parseInt(taskVersion);
				}

				$.ajax({
					url: taskId ? `api.php/tasks/${taskId}` : 'api.php/tasks',
					type: taskId ? 'PATCH' : 'POST',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					data: JSON.stringify(data),
					success: function () {
						$btn.prop('disabled', false).text('Save');
						$('#singleTaskModal').modal('hide');
						alert(taskId ? 'Task updated successfully!' : 'Task created successfully!');
						loadTasks();
					},
					error: function (xhr) {
						$btn.prop('disabled', false).text('Save');
						console.error('Failed to save task:', xhr);
						alert('Failed to save task. Please try again.');
					}
				});
			});
		});
	</script>

</body>

</html>
