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
		$bulkCreateModalId = 'bulkCreateModal';
		$bulkUpdateModalId = 'bulkUpdateModal';
		$searchPlaceholder = 'Search user stories...';
		$epicSelect = true;
		include __DIR__ . '/app/partials/list_header.php';
		?>

		<div id="usorsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading user stories...</span>
				</div>
			</div>
		</div>

		<nav aria-label="User Stories pagination" class="mt-4">
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
					<div class="card usor-card h-100">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-start mb-2">
							<h6 class="card-title mb-0 text-truncate pe-2">${usor.subject || 'Untitled Story'}</h6>
							${statusBadge}
						</div>
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
						$('#bulkCreateProject').html(options);
					}
				});

				// Populate epic dropdown
				$.ajax({
					url: apiUrl + '/epics',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (epics) {
						let options = '<option value="">Select Epic</option>';
						epics.forEach(epic => {
							options += `<option value="${epic.id}">${epic.subject || 'Untitled Epic'}</option>`;
						});
						$('#bulkCreateEpic').html(options);
					}
				});
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
				const text = $('#bulkCreateText').val().trim();

				if (!projectId) {
					alert('Please select a project');
					return;
				}

				if (!text) {
					alert('Please enter some user stories to create');
					return;
				}

				const stories = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Story',
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
						priority: priority ? parseInt(priority) : undefined
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
				// Load user stories for selection
				$.ajax({
					url: apiUrl + '/userstories',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (usors) {
						let options = '';
						usors.forEach(usor => {
							options += `<option value="${usor.id}">#${usor.ref}: ${usor.subject || 'Untitled Story'}</option>`;
						});
						$('#bulkUpdateUsors').html(options);
					}
				});
			}

			function submitBulkUpdate() {
				const selectedUsors = $('#bulkUpdateUsors').val();
				const status = $('#bulkUpdateStatus').val();
				const priority = $('#bulkUpdatePriority').val();
				const description = $('#bulkUpdateDescription').val().trim();

				if (!selectedUsors || selectedUsors.length === 0) {
					alert('Please select at least one user story to update');
					return;
				}

				const updateData = {};
				if (status) updateData.status = status;
				if (priority) updateData.priority = parseInt(priority);
				if (description) updateData.description = description;

				if (Object.keys(updateData).length === 0) {
					alert('Please specify at least one field to update');
					return;
				}

				const $btn = $('#submitBulkUpdate');
				const originalText = $btn.text();
				$btn.prop('disabled', true).text('Updating...');

				let updatedCount = 0;
				let errorCount = 0;

				selectedUsors.forEach(usorId => {
					$.ajax({
						url: apiUrl + '/userstories/' + usorId,
						type: 'PATCH',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
						},
						data: JSON.stringify(updateData),
						success: function () {
							updatedCount++;
							if (updatedCount + errorCount === selectedUsors.length) {
								finishBulkUpdate(updatedCount, errorCount);
							}
						},
						error: function () {
							errorCount++;
							if (updatedCount + errorCount === selectedUsors.length) {
								finishBulkUpdate(updatedCount, errorCount);
							}
						}
					});
				});
			}

			function finishBulkUpdate(updatedCount, errorCount) {
				const $btn = $('#submitBulkUpdate');
				$btn.prop('disabled', false).text('Update Stories');

				if (errorCount === 0) {
					alert(`Successfully updated ${updatedCount} user stories!`);
					$('#bulkUpdateModal').modal('hide');
					loadUsors();
				} else {
					alert(`Updated ${updatedCount} user stories, but ${errorCount} failed.`);
				}
			}
		});
	</script>

	<!-- Bulk Create Modal -->
	<div class="modal fade" id="bulkCreateModal" tabindex="-1" aria-labelledby="bulkCreateModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkCreateModalLabel">Bulk Create User Stories</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="bulkCreateForm">
						<div class="mb-3">
							<label class="form-label">User Stories (one per line)</label>
							<textarea class="form-control" id="bulkCreateText" rows="10" placeholder="Enter user stories, one per line. Format: Subject|Description (optional)|Status (optional)" required></textarea>
							<small class="form-text text-muted">Example: Login page|Create login form with validation|new</small>
						</div>
						<div class="row">
							<div class="col-md-6">
								<label class="form-label">Project</label>
								<select class="form-select" id="bulkCreateProject" required>
									<option value="">Select Project</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label">Epic (optional)</label>
								<select class="form-select" id="bulkCreateEpic">
									<option value="">Select Epic</option>
								</select>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<label class="form-label">Default Status</label>
								<select class="form-select" id="bulkCreateStatus">
									<option value="new">New</option>
									<option value="ready">Ready</option>
									<option value="in progress">In Progress</option>
									<option value="done">Done</option>
									<option value="archived">Archived</option>
									<option value="blocked">Blocked</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label">Priority (optional)</label>
								<input type="number" class="form-control" id="bulkCreatePriority" min="1" max="100" value="10">
							</div>
						</div>
					</form>
					<div id="bulkCreatePreview" class="mt-3 d-none">
						<h6>Preview:</h6>
						<div class="border rounded p-2 bg-body-tertiary" id="previewContent"></div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-outline-primary" id="previewBulkCreate">Preview</button>
					<button type="button" class="btn btn-success" id="submitBulkCreate">Create Stories</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Bulk Update Modal -->
	<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkUpdateModalLabel">Bulk Update User Stories</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="bulkUpdateForm">
						<div class="mb-3">
							<label class="form-label">Select User Stories to Update</label>
							<select class="form-select" id="bulkUpdateUsors" multiple size="8">
								<option value="">Loading user stories...</option>
							</select>
							<small class="form-text text-muted">Hold Ctrl/Cmd to select multiple stories</small>
						</div>
						<div class="row">
							<div class="col-md-6">
								<label class="form-label">Status</label>
								<select class="form-select" id="bulkUpdateStatus">
									<option value="">No Change</option>
									<option value="new">New</option>
									<option value="ready">Ready</option>
									<option value="in progress">In Progress</option>
									<option value="done">Done</option>
									<option value="archived">Archived</option>
									<option value="blocked">Blocked</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label">Priority</label>
								<input type="number" class="form-control" id="bulkUpdatePriority" placeholder="Leave empty for no change">
							</div>
						</div>
						<div class="mb-3">
							<label class="form-label">Description (optional)</label>
							<textarea class="form-control" id="bulkUpdateDescription" rows="3" placeholder="Leave empty for no change"></textarea>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="submitBulkUpdate">Update Stories</button>
				</div>
			</div>
		</div>
	</div>

</body>

</html>