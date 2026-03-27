<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Usors - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Select2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container mt-4">
		<?php
		$pageTitle = 'Usors';
		$statusType = 'us';


		$bulkDeleteModalId = 'bulkDeleteModal';
		$searchPlaceholder = 'Search user stories...';
		$epicSelect = true;
		$sortOptions = [
			'subject' => 'Subject (A-Z)',
			'-subject' => 'Subject (Z-A)',
			'created_date' => 'Created (Oldest)',
			'-created_date' => 'Created (Newest)',
			'modified_date' => 'Modified (Oldest)',
			'-modified_date' => 'Modified (Newest)',
			'status' => 'Status (ASC)',
			'-status' => 'Status (DESC)',
			'backlog_order' => 'Backlog Order (ASC)',
			'-backlog_order' => 'Backlog Order (DESC)',
			'kanban_order' => 'Kanban Order (ASC)',
			'-kanban_order' => 'Kanban Order (DESC)',
			'sprint_order' => 'Sprint Order (ASC)',
			'-sprint_order' => 'Sprint Order (DESC)',
		];
		$bulkActions = '
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkCreateModal"><i class="bi bi-plus-lg me-2"></i> Bulk Create</a></li>
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
			<li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
		';
		include __DIR__ . '/app/partials/list_header.php';

		$totalLabel = 'Total Stories';
		$totalId = 'totalUsors';
		$filteredId = 'filteredUsors';
		$selectionCountId = 'selectedUsorsCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="usorsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading user stories...</span>
				</div>
			</div>
		</div>

		<nav aria-label="User Stories pagination" class="pagination-container">
			<ul class="pagination justify-content-center" id="usorsPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>


	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script src="assets/app.js"></script>
	<!-- Select2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

			window.apiUrl = apiUrl;
			window.taigaToken = token;

			// Load initial user stories list
			loadUsors();

			// Initial filter binding (handles Select2 for dropdowns)
			taigaBindFilters(loadUsors);

			taigaBindSelectionLogic('story-checkbox', function(checkedCount) {
				const filtered = parseInt($('#filteredUsors').text()) || 0;
				const total = parseInt($('#totalUsors').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalUsors', 'filteredUsors', 'selectedUsorsCount');
			});

			// Bulk Create functionality
			$('#bulkCreateModal').on('show.bs.modal', function () {
				populateBulkCreateDropdowns();
			});

			$('#previewBulkCreate').on('click', function () {
				previewBulkCreate();
			});

			$('#submitBulkCreate').on('click', function () {
				submitBulkCreate();
			});

			// Bulk Update functionality
			$('#bulkUpdateModal').on('show.bs.modal', function () {
				populateBulkUpdateDropdowns();
			});

			$('#submitBulkUpdate').on('click', function () {
				submitBulkUpdate();
			});

			function loadUsors(page = 1) {
				const params = {
					...taigaGetFilterParams(),
					page: page
				};
				
				$('#usorsContent').html(`
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading user stories...</span>
				</div>
			</div>
		`);

				$.ajax({
					url: apiUrl + '/userstories',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (usors, status, xhr) {
						displayUsors(usors);
						taigaRenderPagination(xhr, '#usorsPagination', loadUsors);
					},
					error: function (xhr) {
						console.error('Failed to load user stories:', xhr);
						$('#usorsContent').html(`
					<div class="alert alert-danger">
						Unable to load user stories. Please try again.
					</div>
				`);
						$('#usorsPagination').empty();
					}
				});
			}


			// loadEpics function removed as it is now handled by taigaLoadEpics in taiga.js

			function displayUsors(usors) {
				if (usors.length === 0) {
					$('#usorsContent').html(`
				<div class="alert alert-info">
					No user stories found.
				</div>
			`);
					return;
				}

				let html = '<div class="row">';
				usors.forEach(usor => {
					const statusInfo = taigaGetStatusInfo(usor);
					const statusBadge = taigaRenderStatusBadge(statusInfo);

					html += `
				<div class="col-md-6 col-lg-4 mb-3">
					<div class="card usor-card h-100" data-us-id="${usor.id}" data-project-id="${usor.project}" data-status="${usor.status}">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-start mb-2">
							<div class="form-check">
								<input class="form-check-input story-checkbox" type="checkbox" value="${usor.id}" data-version="${usor.version}" id="us-${usor.id}">
								<label class="form-check-label" for="us-${usor.id}"></label>
							</div>
							<div class="d-flex flex-column align-items-end">
								${statusBadge}
								<small class="text-muted mt-1">#${usor.ref}</small>
							</div>
						</div>
						<h6 class="card-title mb-1 text-truncate pe-2">${usor.subject || 'Untitled Story'}</h6>
						<p class="card-text text-muted small mb-1">
							Ref: #${usor.ref} | Project: ${usor.project || 'N/A'}
						</p>
						${usor.epic ? `<p class="card-text text-muted small mb-2">Epic: #${usor.epic}</p>` : ''}
						<div class="usor-description text-muted small mb-3">
							${usor.description ? usor.description.substring(0, 120) + '...' : 'No description'}
						</div>
						<div class="d-flex justify-content-end">
							<a href="usor.php?id=${usor.id}" class="btn btn-primary btn-sm">
								<i class="bi bi-eye me-1"></i>
								View
							</a>
						</div>
					</div>
				</div>
				</div>
			`;
				});
				html += '</div>';

				$('#usorsContent').html(html);

				// Add click event to checkboxes
				$('.story-checkbox').on('change', updateSelectionCount);
			}

			function updateSelectionCount() {
				const selectedCount = $('#usorsContent input:checked').length;
				$('#selectedUsorsCount').text(selectedCount);
			}

			// Local filter function removed as filtering is now done via API.



			function viewUsor(usorId) {
				window.location.href = `usor.php?id=${usorId}`;
			}

			// Bulk Create Functions
			function populateBulkCreateDropdowns() {
				// Populate project dropdown
				$.ajax({
					url: apiUrl + '/projects',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (projects) {
						let options = '<option value="">Select Project</option>';
						projects.forEach(project => {
							options += `<option value="${project.id}">${project.name}</option>`;
						});
						$('#bulkCreateProject').html(options).select2({
							theme: 'bootstrap-5',
							width: '100%',
							placeholder: 'Select Project',
							dropdownParent: $('#bulkCreateModal')
						});

						const currentProjectId = $('#projectSelect').val();
						if (currentProjectId) {
							$('#bulkCreateProject').val(currentProjectId);
							taigaPopulateBulkStatuses('us', $('#bulkCreateStatus'), currentProjectId);
							taigaPopulateBulkMembers($('#bulkCreateAssignee'), currentProjectId);
						}
					}
				});

				// Update statuses, members and epics when project changes
				$('#bulkCreateProject').off('change').on('change', function () {
					const projectId = $(this).val();
					taigaPopulateBulkStatuses('us', $('#bulkCreateStatus'), projectId);
					taigaPopulateBulkMembers($('#bulkCreateAssignee'), projectId);
					
					// Also update epics for this project
					if (projectId) {
						$.ajax({
							url: apiUrl + '/epics?project=' + projectId,
							type: 'GET',
							headers: {
								'Authorization': 'Bearer ' + token,
								'Content-Type': 'application/json'
							},
							success: function (epics) {
								let html = '<option value="">Select Epic</option>';
								epics.forEach(epic => {
									html += `<option value="${epic.id}">${epic.subject || 'Untitled Epic'}</option>`;
								});
								$('#bulkCreateEpic').html(html);
								
								// Set default epic from filter if applicable
								const currentEpicId = $('#epicSelect').val();
								if (currentEpicId) {
									$('#bulkCreateEpic').val(currentEpicId);
								}
							}
						});
					} else {
						$('#bulkCreateEpic').html('<option value="">Select Project first</option>');
					}
				});

				// Update search hint and context alert
				const currentSearch = $('#searchInput').val();
				if (currentSearch) {
					$('#activeSearchQuery').text(currentSearch);
					$('#bulkCreateSearchContext').removeClass('d-none');
					$('#bulkCreateText').attr('placeholder', `Enter user stories... (Active search: ${currentSearch})`);
				} else {
					$('#bulkCreateSearchContext').addClass('d-none');
					$('#bulkCreateText').attr('placeholder', 'Enter user stories, one per line. Format: Subject|Description (optional)|Status (optional)');
				}

				// Initial load if project is already selected
				const initialProjectId = $('#projectSelect').val();
				if (initialProjectId) {
					$('#bulkCreateProject').trigger('change');
				} else {
					$('#bulkCreateEpic').html('<option value="">Select Project first</option>');
				}
			}

			function previewBulkCreate() {
				const text = $('#bulkCreateText').val().trim();
				if (!text) {
					alert('Please enter some user stories to create');
					return;
				}

				const stories = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Story',
						description: parts[1]?.trim() || '',
						status: parts[2]?.trim() || $('#bulkCreateStatus').val()
					};
				});

				let previewHtml = '<div class="small">';
				stories.forEach((story, index) => {
					previewHtml += `
				<div class="mb-2 p-2 border-bottom">
					<strong>${index + 1}.</strong> ${story.subject}
					${story.description ? `<br><span class="text-muted">${story.description}</span>` : ''}
					${story.status ? `<br><span class="badge bg-secondary">${story.status}</span>` : ''}
				</div>
			`;
				});
				previewHtml += '</div>';

				$('#previewContent').html(previewHtml);
				$('#bulkCreatePreview').removeClass('d-none');
			}

			function submitBulkCreate() {
				const projectId = $('#bulkCreateProject').val();
				const epicId = $('#bulkCreateEpic').val();
				const priority = $('#bulkCreatePriority').val();
				const defaultStatus = $('#bulkCreateStatus').val();
				const assignee = $('#bulkCreateAssignee').val();
				const text = $('#bulkCreateText').val().trim();

				if (!projectId) {
					alert('Please select a project');
					return;
				}

				if (!text) {
					alert('Please enter some user stories to create');
					return;
				}

				const currentSearch = $('#searchInput').val();
				const prependSearch = $('#prependSearchCheck').is(':checked');

				const stories = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					let subject = parts[0]?.trim() || 'Untitled Story';
					if (currentSearch && prependSearch) {
						subject = `[${currentSearch}] ${subject}`;
					}
					return {
						subject: subject,
						description: parts[1]?.trim() || '',
						status: parts[2]?.trim() || defaultStatus
					};
				});

				const $btn = $('#submitBulkCreate');
				const originalText = $btn.text();
				$btn.prop('disabled', true).text('Creating...');

				let createdCount = 0;
				let errorCount = 0;

				stories.forEach((story, index) => {
					const storyData = {
						subject: story.subject,
						description: story.description,
						project: parseInt(projectId),
						status: story.status,
						priority: priority ? parseInt(priority) : undefined,
						assigned_to: assignee ? parseInt(assignee) : undefined
					};

					if (epicId) {
						storyData.epic = parseInt(epicId);
					}

					$.ajax({
						url: apiUrl + '/userstories',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
						},
						data: JSON.stringify(storyData),
						success: function () {
							createdCount++;
							if (createdCount + errorCount === stories.length) {
								finishBulkCreate(createdCount, errorCount);
							}
						},
						error: function () {
							errorCount++;
							if (createdCount + errorCount === stories.length) {
								finishBulkCreate(createdCount, errorCount);
							}
						}
					});
				});
			}

			function finishBulkCreate(createdCount, errorCount) {
				const $btn = $('#submitBulkCreate');
				$btn.prop('disabled', false).text('Create Stories');

				if (errorCount === 0) {
					alert(`Successfully created ${createdCount} user stories!`);
					$('#bulkCreateModal').modal('hide');
					loadUsors();
				} else {
					alert(`Created ${createdCount} user stories, but ${errorCount} failed.`);
				}
			}

			// Bulk Update Functions
			function populateBulkUpdateDropdowns() {
				const filterParams = taigaGetFilterParams();
				const projectId = filterParams.project;
				taigaPopulateBulkStatuses('us', $('#bulkUpdateStatus'), projectId, 'No Change');
				taigaPopulateBulkMembers($('#bulkUpdateAssignee'), projectId, 'No Change');

				taigaLoadBulkItems('/userstories', $('#bulkUpdateUsors'), item => {
					return `
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-usor-${item.id}">
							<label class="form-check-label" for="bulk-usor-${item.id}">
								#${item.ref}: ${item.subject || 'Untitled Story'}
							</label>
						</div>
					`;
				});
			}

			function submitBulkUpdate() {
				const selectedUsors = [];
				$('#bulkUpdateUsors input:checked').each(function () {
					selectedUsors.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				if (selectedUsors.length === 0) {
					alert('Please select at least one user story to update');
					return;
				}

				const updateData = {};
				if (status) updateData.status = status;
				const assignee = $('#bulkUpdateAssignee').val();
				if (assignee) updateData.assigned_to = parseInt(assignee);
				if (priority) updateData.priority = parseInt(priority);
				if (description) updateData.description = description;

				if (Object.keys(updateData).length === 0) {
					alert('Please specify at least one field to update');
					return;
				}

				const $btn = $('#submitBulkUpdate');
				$btn.prop('disabled', true).text('Updating...');

				taigaExecuteBulk('/userstories/', selectedUsors, 'PATCH', updateData, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Update Stories');
					if (errorCount === 0) {
						alert(`Successfully updated ${successCount} user stories!`);
						$('#bulkUpdateModal').modal('hide');
						loadUsors();
					} else {
						alert(`Updated ${successCount} user stories, but ${errorCount} failed.`);
					}
				});
			}

			// Bulk Delete Logic
			$('#bulkDeleteModal').on('show.bs.modal', function () {
				const selectedUsors = [];
				$('#usorsContent input.story-checkbox:checked').each(function () {
					const title = $(this).closest('.card-body').find('.card-title').text();
					selectedUsors.push(title);
				});
				$('#selectedUsorsList').html(selectedUsors.map(u => `<div>${u}</div>`).join(''));
			});

			$('#confirmBulkDelete').on('click', function () {
				const selectedUsors = [];
				$('#usorsContent input.story-checkbox:checked').each(function () {
					selectedUsors.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				if (selectedUsors.length === 0) {
					alert('Please select at least one user story to delete');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Deleting...');

				taigaExecuteBulk('/userstories/', selectedUsors, 'DELETE', null, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Delete Stories');
					if (errorCount === 0) {
						alert(`Successfully deleted ${successCount} user stories!`);
						$('#bulkDeleteModal').modal('hide');
						loadUsors();
					} else {
						alert(`Deleted ${successCount} user stories, but ${errorCount} failed.`);
					}
				});
			});
		});
	</script>

	<?php include __DIR__ . '/app/partials/usor_bulk_create.php'; ?>
	<?php include __DIR__ . '/app/partials/usor_bulk_update.php'; ?>
	<?php include __DIR__ . '/app/partials/usor_bulk_delete.php'; ?>

</body>

</html>