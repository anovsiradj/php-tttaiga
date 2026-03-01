<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Epics - Taiga API</title>
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
			<h1>Epics</h1>
			<div class="d-flex">
				<button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#bulkCreateEpicModal">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
						<path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
					</svg>
					Bulk Create
				</button>
				<button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#bulkUpdateEpicModal">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
						<path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z" />
					</svg>
					Bulk Update
				</button>
				<input type="text" class="form-control me-2" id="searchInput" placeholder="Search epics..." style="width: 250px;">
				<select class="form-select me-2" id="projectSelect" style="width: 200px;">
					<option value="">All Projects</option>
				</select>
				<select class="form-select me-2" id="statusSelect" style="width: 150px;">
					<option value="">All Statuses</option>
					<option value="new">New</option>
					<option value="in progress">In Progress</option>
					<option value="done">Done</option>
					<option value="archived">Archived</option>
				</select>
				<button class="btn btn-outline-secondary" id="refreshBtn">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
						<path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
						<path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
					</svg>
				</button>
			</div>
		</div>

		<div id="epicsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading epics...</span>
				</div>
			</div>
		</div>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<!-- Theme Script -->
	<script src="assets/taiga.js"></script>
	<script src="assets/theme.js"></script>
	<script src="assets/app.js"></script>

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

			let allProjects = [];
			let allEpics = [];

			// Load projects and epics
			loadProjectsAndEpics();

			// Event listeners


			$('#refreshBtn').on('click', function () {
				loadProjectsAndEpics();
			});

			$('#searchInput').on('input', function () {
				filterEpics();
			});

			$('#projectSelect').on('change', function () {
				filterEpics();
			});

			$('#statusSelect').on('change', function () {
				filterEpics();
			});

			// Bulk Create Epic functionality
			$('#bulkCreateEpicModal').on('show.bs.modal', function () {
				populateBulkCreateEpicDropdowns();
			});

			$('#previewBulkCreateEpic').on('click', function () {
				previewBulkCreateEpic();
			});

			$('#submitBulkCreateEpic').on('click', function () {
				submitBulkCreateEpic();
			});

			// Bulk Update Epic functionality
			$('#bulkUpdateEpicModal').on('show.bs.modal', function () {
				populateBulkUpdateEpicDropdowns();
			});

			$('#submitBulkUpdateEpic').on('click', function () {
				submitBulkUpdateEpic();
			});

			function loadProjectsAndEpics() {
				// Load projects first
				$.ajax({
					url: apiUrl + '/projects',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (projects) {
						allProjects = projects;
						populateProjectSelect(projects);
						loadEpics();
					},
					error: function (xhr) {
						console.error('Failed to load projects:', xhr);
						$('#epicsContent').html(`
					<div class="alert alert-danger">
						Failed to load projects. Please try again.
						<button class="btn btn-sm btn-outline-danger ms-2" onclick="loadProjectsAndEpics()">Retry</button>
					</div>
				`);
					}
				});
			}

			function loadEpics() {
				// Load epics for all projects
				$.ajax({
					url: apiUrl + '/epics',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (epics) {
						allEpics = epics;
						displayEpics(epics);
					},
					error: function (xhr) {
						console.error('Failed to load epics:', xhr);
						$('#epicsContent').html(`
					<div class="alert alert-danger">
						Failed to load epics. Please try again.
						<button class="btn btn-sm btn-outline-danger ms-2" onclick="loadEpics()">Retry</button>
					</div>
				`);
					}
				});
			}

			function populateProjectSelect(projects) {
				let html = '<option value="">All Projects</option>';
				projects.forEach(project => {
					html += `<option value="${project.id}">${project.name}</option>`;
				});
				$('#projectSelect').html(html);
			}

			function displayEpics(epics) {
				if (epics.length === 0) {
					$('#epicsContent').html(`
				<div class="alert alert-info">
					No epics found. Create your first epic in Taiga!
				</div>
			`);
					return;
				}

				let html = '<div class="row">';

				epics.forEach(epic => {
					const project = allProjects.find(p => p.id === epic.project) || {};
					const statusClass = taigaGetStatusClass(epic.status);

					html += `
				<div class="col-md-6 col-lg-4 mb-4">
					<div class="card epic-card h-100" data-epic-id="${epic.id}" data-project-id="${epic.project}" data-status="${epic.status}">
						<div class="card-body">
							<span class="badge status-badge bg-${statusClass} float-end">
								${epic.status || 'Unknown'}
							</span>
							<h5 class="card-title">${epic.subject || 'Untitled Epic'}</h5>
							<p class="card-text text-muted epic-description">
								${epic.description || 'No description available.'}
							</p>
							<div class="d-flex justify-content-between align-items-center mt-3">
								<small class="text-muted">
									Project: ${project.name || 'Unknown'}
								</small>
								<small class="text-muted">
									Ref: #${epic.ref}
								</small>
							</div>
							<div class="d-flex justify-content-between align-items-center mt-2">
								<small class="text-muted">
									Created: ${new Date(epic.created_date).toLocaleDateString()}
								</small>
								<small class="text-muted">
									Modified: ${new Date(epic.modified_date).toLocaleDateString()}
								</small>
							</div>
						</div>
						<div class="card-footer bg-transparent">
							<button class="btn btn-primary btn-sm view-epic" data-epic-id="${epic.id}">
								View Details
							</button>
						</div>
					</div>
				</div>
			`;
				});

				html += '</div>';
				$('#epicsContent').html(html);

				// Add click event for epic cards
				$('.view-epic').on('click', function (e) {
					e.stopPropagation();
					const epicId = $(this).data('epic-id');
					window.location.href = `epic.php?id=${epicId}`;
				});

				$('.epic-card').on('click', function () {
					const epicId = $(this).data('epic-id');
					window.location.href = `epic.php?id=${epicId}`;
				});
			}

			function filterEpics() {
				const searchTerm = $('#searchInput').val().toLowerCase();
				const projectId = $('#projectSelect').val();
				const status = $('#statusSelect').val();

				$('.epic-card').each(function () {
					const epicSubject = $(this).find('.card-title').text().toLowerCase();
					const epicDesc = $(this).find('.epic-description').text().toLowerCase();
					const cardProjectId = $(this).data('project-id');
					const cardStatus = $(this).data('status');

					let show = true;

					if (searchTerm && !epicSubject.includes(searchTerm) && !epicDesc.includes(searchTerm)) {
						show = false;
					}

					if (projectId && cardProjectId != projectId) {
						show = false;
					}

					if (status && cardStatus !== status) {
						show = false;
					}

					if (show) {
						$(this).parent().show();
					} else {
						$(this).parent().hide();
					}
				});
			}



			// Bulk Create Epic Functions
			function populateBulkCreateEpicDropdowns() {
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
						$('#bulkCreateEpicProject').html(options);
					}
				});
			}

			function previewBulkCreateEpic() {
				const text = $('#bulkCreateEpicText').val().trim();
				if (!text) {
					alert('Please enter some epics to create');
					return;
				}

				const epics = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Epic',
						description: parts[1]?.trim() || '',
						status: parts[2]?.trim() || $('#bulkCreateEpicStatus').val()
					};
				});

				let previewHtml = '<div class="small">';
				epics.forEach((epic, index) => {
					previewHtml += `
				<div class="mb-2 p-2 border-bottom">
					<strong>${index + 1}.</strong> ${epic.subject}
					${epic.description ? `<br><span class="text-muted">${epic.description}</span>` : ''}
					${epic.status ? `<br><span class="badge bg-secondary">${epic.status}</span>` : ''}
				</div>
			`;
				});
				previewHtml += '</div>';

				$('#epicPreviewContent').html(previewHtml);
				$('#bulkCreateEpicPreview').removeClass('d-none');
			}

			function submitBulkCreateEpic() {
				const projectId = $('#bulkCreateEpicProject').val();
				const priority = $('#bulkCreateEpicPriority').val();
				const color = $('#bulkCreateEpicColor').val();
				const defaultStatus = $('#bulkCreateEpicStatus').val();
				const text = $('#bulkCreateEpicText').val().trim();

				if (!projectId) {
					alert('Please select a project');
					return;
				}

				if (!text) {
					alert('Please enter some epics to create');
					return;
				}

				const epics = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Epic',
						description: parts[1]?.trim() || '',
						status: parts[2]?.trim() || defaultStatus
					};
				});

				const $btn = $('#submitBulkCreateEpic');
				const originalText = $btn.text();
				$btn.prop('disabled', true).text('Creating...');

				let createdCount = 0;
				let errorCount = 0;

				epics.forEach((epic, index) => {
					const epicData = {
						subject: epic.subject,
						description: epic.description,
						project: parseInt(projectId),
						status: epic.status,
						priority: priority ? parseInt(priority) : undefined,
						color: color
					};

					$.ajax({
						url: apiUrl + '/epics',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
						},
						data: JSON.stringify(epicData),
						success: function () {
							createdCount++;
							if (createdCount + errorCount === epics.length) {
								finishBulkCreateEpic(createdCount, errorCount);
							}
						},
						error: function () {
							errorCount++;
							if (createdCount + errorCount === epics.length) {
								finishBulkCreateEpic(createdCount, errorCount);
							}
						}
					});
				});
			}

			function finishBulkCreateEpic(createdCount, errorCount) {
				const $btn = $('#submitBulkCreateEpic');
				$btn.prop('disabled', false).text('Create Epics');

				if (errorCount === 0) {
					alert(`Successfully created ${createdCount} epics!`);
					$('#bulkCreateEpicModal').modal('hide');
					loadProjectsAndEpics();
				} else {
					alert(`Created ${createdCount} epics, but ${errorCount} failed.`);
				}
			}

			// Bulk Update Epic Functions
			function populateBulkUpdateEpicDropdowns() {
				// Load epics for selection
				$.ajax({
					url: apiUrl + '/epics',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (epics) {
						let options = '';
						epics.forEach(epic => {
							options += `<option value="${epic.id}">#${epic.ref}: ${epic.subject || 'Untitled Epic'}</option>`;
						});
						$('#bulkUpdateEpics').html(options);
					}
				});
			}

			function submitBulkUpdateEpic() {
				const selectedEpics = $('#bulkUpdateEpics').val();
				const status = $('#bulkUpdateEpicStatus').val();
				const priority = $('#bulkUpdateEpicPriority').val();
				const description = $('#bulkUpdateEpicDescription').val().trim();
				const color = $('#bulkUpdateEpicColor').val();

				if (!selectedEpics || selectedEpics.length === 0) {
					alert('Please select at least one epic to update');
					return;
				}

				const updateData = {};
				if (status) updateData.status = status;
				if (priority) updateData.priority = parseInt(priority);
				if (description) updateData.description = description;
				if (color) updateData.color = color;

				if (Object.keys(updateData).length === 0) {
					alert('Please specify at least one field to update');
					return;
				}

				const $btn = $('#submitBulkUpdateEpic');
				const originalText = $btn.text();
				$btn.prop('disabled', true).text('Updating...');

				let updatedCount = 0;
				let errorCount = 0;

				selectedEpics.forEach(epicId => {
					$.ajax({
						url: apiUrl + '/epics/' + epicId,
						type: 'PATCH',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
						},
						data: JSON.stringify(updateData),
						success: function () {
							updatedCount++;
							if (updatedCount + errorCount === selectedEpics.length) {
								finishBulkUpdateEpic(updatedCount, errorCount);
							}
						},
						error: function () {
							errorCount++;
							if (updatedCount + errorCount === selectedEpics.length) {
								finishBulkUpdateEpic(updatedCount, errorCount);
							}
						}
					});
				});
			}

			function finishBulkUpdateEpic(updatedCount, errorCount) {
				const $btn = $('#submitBulkUpdateEpic');
				$btn.prop('disabled', false).text('Update Epics');

				if (errorCount === 0) {
					alert(`Successfully updated ${updatedCount} epics!`);
					$('#bulkUpdateEpicModal').modal('hide');
					loadProjectsAndEpics();
				} else {
					alert(`Updated ${updatedCount} epics, but ${errorCount} failed.`);
				}
			}
		});
	</script>

	<!-- Bulk Create Epic Modal -->
	<div class="modal fade" id="bulkCreateEpicModal" tabindex="-1" aria-labelledby="bulkCreateEpicModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkCreateEpicModalLabel">Bulk Create Epics</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="bulkCreateEpicForm">
						<div class="mb-3">
							<label class="form-label">Epics (one per line)</label>
							<textarea class="form-control" id="bulkCreateEpicText" rows="10" placeholder="Enter epics, one per line. Format: Subject|Description (optional)|Status (optional)" required></textarea>
							<small class="form-text text-muted">Example: Authentication Module|Implement login and registration|new</small>
						</div>
						<div class="row">
							<div class="col-md-6">
								<label class="form-label">Project</label>
								<select class="form-select" id="bulkCreateEpicProject" required>
									<option value="">Select Project</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label">Default Status</label>
								<select class="form-select" id="bulkCreateEpicStatus">
									<option value="new">New</option>
									<option value="in progress">In Progress</option>
									<option value="done">Done</option>
									<option value="archived">Archived</option>
								</select>
							</div>
						</div>
						<div class="row mt-3">
							<div class="col-md-6">
								<label class="form-label">Color (optional)</label>
								<input type="color" class="form-control form-control-color" id="bulkCreateEpicColor" value="#fd7e14">
							</div>
							<div class="col-md-6">
								<label class="form-label">Priority (optional)</label>
								<input type="number" class="form-control" id="bulkCreateEpicPriority" min="1" max="100" value="10">
							</div>
						</div>
					</form>
					<div id="bulkCreateEpicPreview" class="mt-3 d-none">
						<h6>Preview:</h6>
						<div class="border rounded p-2 bg-body-tertiary" id="epicPreviewContent"></div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-outline-primary" id="previewBulkCreateEpic">Preview</button>
					<button type="button" class="btn btn-success" id="submitBulkCreateEpic">Create Epics</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Bulk Update Epic Modal -->
	<div class="modal fade" id="bulkUpdateEpicModal" tabindex="-1" aria-labelledby="bulkUpdateEpicModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkUpdateEpicModalLabel">Bulk Update Epics</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="bulkUpdateEpicForm">
						<div class="mb-3">
							<label class="form-label">Select Epics to Update</label>
							<select class="form-select" id="bulkUpdateEpics" multiple size="8">
								<option value="">Loading epics...</option>
							</select>
							<small class="form-text text-muted">Hold Ctrl/Cmd to select multiple epics</small>
						</div>
						<div class="row">
							<div class="col-md-6">
								<label class="form-label">Status</label>
								<select class="form-select" id="bulkUpdateEpicStatus">
									<option value="">No Change</option>
									<option value="new">New</option>
									<option value="in progress">In Progress</option>
									<option value="done">Done</option>
									<option value="archived">Archived</option>
								</select>
							</div>
							<div class="col-md-6">
								<label class="form-label">Priority</label>
								<input type="number" class="form-control" id="bulkUpdateEpicPriority" placeholder="Leave empty for no change">
							</div>
						</div>
						<div class="mb-3">
							<label class="form-label">Description (optional)</label>
							<textarea class="form-control" id="bulkUpdateEpicDescription" rows="3" placeholder="Leave empty for no change"></textarea>
						</div>
						<div class="mb-3">
							<label class="form-label">Color (optional)</label>
							<input type="color" class="form-control form-control-color" id="bulkUpdateEpicColor">
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="submitBulkUpdateEpic">Update Epics</button>
				</div>
			</div>
		</div>
	</div>

</body>

</html>