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
		$bulkActions = '
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkCreateTaskModal"><i class="bi bi-plus-lg me-2"></i> Bulk Create</a></li>
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateTaskModal"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
			<li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#bulkDeleteTaskModal"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
		';
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

		<nav aria-label="Tasks pagination" class="pagination-container">
			<ul class="pagination justify-content-center" id="tasksPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

	<?php include __DIR__ . '/app/partials/task_bulk_create.php'; ?>
	<?php include __DIR__ . '/app/partials/task_bulk_update.php'; ?>
	<?php include __DIR__ . '/app/partials/task_bulk_delete.php'; ?>

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
			$('#bulkCreateTaskModal').on('show.bs.modal', function (e) {
				const projectId = $('#projectSelect').val();
				const userStoryId = $('#userStorySelect').val();

				if (!projectId || !userStoryId) {
					alert('Please select both a Project and a Usor filter first.');
					e.preventDefault();
					return false;
				}

				// Hide project and US selection as they are mandatory from filter
				$('#bulkTaskProject').closest('.col-md-6').hide();
				$('#bulkTaskUserStory').closest('.mb-3').hide();
				
				populateBulkCreateTaskDropdowns();
			});

			$('#previewBulkTaskCreate').on('click', function () {
				previewBulkTaskCreate();
			});

			$('#submitBulkTaskCreate').on('click', function () {
				submitBulkTaskCreate();
			});

			// Bulk Task Update functionality (same as in usor.php)
			$('#bulkUpdateTaskModal').on('show.bs.modal', function (e) {
				const projectId = $('#projectSelect').val();
				if (!projectId) {
					alert('Please select a Project filter first.');
					e.preventDefault();
					return false;
				}
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


			// loadUsors function removed as it is now handled by taigaLoadUsors in taiga.js

			function displayTasks(tasks) {
				$('#totalTasks').text(tasks.length);
				$('#filteredTasks').text(tasks.length);

				if (tasks.length === 0) {
					$('#tasksContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row">';
				tasks.forEach(task => {
					const statusInfo = taigaGetStatusInfo(task);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const assignedTo = task.assigned_to_extra ? task.assigned_to_extra.full_name_display : (task.assigned_to ? 'User ID: ' + task.assigned_to : 'Unassigned');
					const owner = task.owner_extra ? task.owner_extra.full_name_display : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4 mb-3">
					<div class="card task-card h-100" data-task-id="${task.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input task-checkbox" type="checkbox" value="${task.id}" id="task-${task.id}">
									<label class="form-check-label" for="task-${task.id}"></label>
								</div>
								${statusBadge}
							</div>
							
							<h6 class="card-title mb-2">${task.subject || 'Untitled Task'}</h6>
							
							${task.description ? `
								<p class="card-text task-description text-muted small mb-2 text-truncate">
									${task.description}
								</p>
							` : ''}
							
							<div class="mt-2 pt-2 border-top">
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
							
							<div class="mt-3 d-flex gap-2 flex-wrap">
								<a href="task.php?id=${task.id}" class="btn btn-sm btn-outline-secondary shadow-sm">
									View Task Details
								</a>
								${task.user_story ? `
									<a href="usor.php?id=${task.user_story}" class="btn btn-sm btn-outline-primary shadow-sm">
										View Usor Details
									</a>
								` : ''}
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
					if (!$(e.target).is('.form-check, .form-check-input, .form-check-label') && $(e.target).closest('a').length === 0) {
						const taskId = $(this).data('task-id');
						if (taskId) {
							window.location.href = `task.php?id=${taskId}`;
						}
					}
				});
			}

			// Local filter function removed as filtering is now done via API.

			function updateSelectionCount() {
				const selectedCount = $('#tasksContent input:checked').length;
				$('#selectionCount').text(selectedCount);
			}



			function populateBulkCreateTaskDropdowns() {
				const currentProjectId = $('#projectSelect').val();
				const currentUserStoryId = $('#userStorySelect').val();
				const projectText = $('#projectSelect option:selected').text();
				const usText = $('#userStorySelect option:selected').text();

				// Display context in read-only inputs
				$('#bulkTaskProjectDisplay').val(projectText);
				$('#bulkTaskUserStoryDisplay').val(usText);

				// Ensure underlying hidden selects have values for submission logic
				$('#bulkTaskProject').val(currentProjectId);
				$('#bulkTaskUserStory').val(currentUserStoryId);
				
				taigaPopulateBulkStatuses('task', $('#bulkTaskStatus'), currentProjectId);
				taigaPopulateBulkMembers($('#bulkTaskAssignee'), currentProjectId);

				// Update search context alert only if search is active
				const currentSearch = $('#searchInput').val();
				if (currentSearch) {
					$('#activeTaskSearchQuery').text(currentSearch);
					$('#bulkTaskSearchContext').removeClass('d-none');
				} else {
					$('#bulkTaskSearchContext').addClass('d-none');
				}
				$('#bulkTaskTitles').attr('placeholder', 'Enter task titles, one per line');

				// Initial user stories load if project is already selected
				const initialProjectId = $('#projectSelect').val();
				if (initialProjectId) {
					$('#bulkTaskProject').trigger('change');
				} else {
					// Fallback to all user stories if no project selected? 
					// Actually, Taiga usually requires a project for US.
					$('#bulkTaskUserStory').html('<option value="">Select Project first</option>');
				}
			}

			function populateBulkUpdateTaskDropdowns() {
				const filterParams = taigaGetFilterParams();
				const projectId = filterParams.project;
				const projectText = $('#projectSelect option:selected').text();

				// Display context in read-only input
				$('#bulkUpdateTaskProjectDisplay').val(projectText);
				
				// Hide project selection in update modal if filtered
				$('#bulkUpdateTaskProject').closest('.col-md-6').hide();

				taigaPopulateBulkStatuses('task', $('#bulkUpdateTaskStatus'), projectId, 'No Change');
				taigaPopulateBulkMembers($('#bulkUpdateTaskAssignee'), projectId, 'No Change');

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
		});
	</script>

</body>

</html>
