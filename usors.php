<?php
require __DIR__ . '/app/construct.php';
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
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container mt-4">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1>Usors</h1>
			<div class="d-flex">
				<button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
						<path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
					</svg>
					Bulk Create
				</button>
				<button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
						<path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
					</svg>
					Bulk Update
				</button>
				<input type="text" class="form-control me-2" id="searchInput" placeholder="Search user stories..." style="width: 250px;">
				<select class="form-select me-2" id="projectSelect" style="width: 200px;">
					<option value="">All Projects</option>
				</select>
				<select class="form-select me-2" id="epicSelect" style="width: 200px;">
					<option value="">All Epics</option>
				</select>
				<select class="form-select me-2" id="statusSelect" style="width: 150px;">
					<option value="">All Statuses</option>
					<option value="new">New</option>
					<option value="ready">Ready</option>
					<option value="in progress">In Progress</option>
					<option value="done">Done</option>
					<option value="archived">Archived</option>
					<option value="blocked">Blocked</option>
				</select>
				<button class="btn btn-outline-secondary" id="refreshBtn">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
						<path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
						<path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
					</svg>
				</button>
			</div>
		</div>

		<div id="usorsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading user stories...</span>
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
			const user = JSON.parse(userData);

			// Load user stories
			loadUsors();
			taigaLoadProjects(apiUrl, token);
			loadEpics();

			// Event listeners
			$('#searchInput').on('input', filterUsors);
			$('#projectSelect').on('change', filterUsors);
			$('#epicSelect').on('change', filterUsors);
			$('#statusSelect').on('change', filterUsors);
			$('#refreshBtn').on('click', function () {
				loadUsors();
				taigaLoadProjects(apiUrl, token);
				loadEpics();
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

			function loadUsors() {
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
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (usors) {
						displayUsors(usors);
					},
					error: function (xhr) {
						console.error('Failed to load user stories:', xhr);
						$('#usorsContent').html(`
					<div class="alert alert-danger">
						Unable to load user stories. Please try again.
					</div>
				`);
					}
				});
			}


			function loadEpics() {
				$.ajax({
					url: apiUrl + '/epics',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (epics) {
						let options = '<option value="">All Epics</option>';
						epics.forEach(epic => {
							options += `<option value="${epic.id}">${epic.subject || 'Untitled Epic'}</option>`;
						});
						$('#epicSelect').html(options);
					},
					error: function (xhr) {
						console.error('Failed to load epics:', xhr);
					}
				});
			}

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
					const statusClass = getStatusClass(usor.status);
					html += `
				<div class="col-md-6 col-lg-4 mb-3">
					<div class="card usor-card">
					<div class="card-body">
						<div class="d-flex justify-content-between align-items-start mb-2">
							<h6 class="card-title mb-0">${usor.subject || 'Untitled Story'}</h6>
							<span class="badge status-badge bg-${statusClass}">
								${usor.status || 'Unknown'}
							</span>
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
								<svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1">
									<path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
									<path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
								</svg>
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

			function filterUsors() {
				const searchText = $('#searchInput').val().toLowerCase();
				const projectId = $('#projectSelect').val();
				const epicId = $('#epicSelect').val();
				const status = $('#statusSelect').val();

				$.ajax({
					url: apiUrl + '/userstories',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (usors) {
						let filteredUsors = usors.filter(usor => {
							const matchesSearch = !searchText ||
								(usor.subject && usor.subject.toLowerCase().includes(searchText)) ||
								(usor.description && usor.description.toLowerCase().includes(searchText));
							const matchesProject = !projectId || usor.project == projectId;
							const matchesEpic = !epicId || usor.epic == epicId;
							const matchesStatus = !status || (usor.status && usor.status.toLowerCase() === status.toLowerCase());

							return matchesSearch && matchesProject && matchesEpic && matchesStatus;
						});
						displayUsors(filteredUsors);
					},
					error: function (xhr) {
						console.error('Failed to filter user stories:', xhr);
					}
				});
			}

			function getStatusClass(status) {
				// Handle null, undefined, or non-string values
				if (!status) return 'secondary';

				// Convert to string and handle case where status might be a number or other type
				const statusString = String(status).toLowerCase().trim();
				switch (statusString) {
					case 'new': return 'new';
					case 'ready': return 'ready';
					case 'in progress': return 'in-progress';
					case 'done': return 'done';
					case 'archived': return 'archived';
					case 'blocked': return 'blocked';
					default: return 'secondary';
				}
			}

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