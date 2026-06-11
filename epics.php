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
		$pageTitle = 'Epik';
		$statusType = 'epic';
		$bulkCreateModalId = 'bulkCreateEpicModal';
		$bulkUpdateModalId = 'bulkUpdateEpicModal';
		$searchPlaceholder = 'Search epiks...';
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
			'epic_order' => 'Custom Order (ASC)',
			'-epic_order' => 'Custom Order (DESC)',
		];
		$bulkActions = '
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkCreateEpicModal"><i class="bi bi-plus-lg me-2"></i> Bulk Create</a></li>
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateEpicModal"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
			<li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#bulkDeleteEpicModal"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
		';
		include __DIR__ . '/app/partials/list_header.php';

		$totalId = 'totalEpics';
		$filteredId = 'filteredEpics';
		$selectionCountId = 'selectedEpicsCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="epicsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading epik...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Epiks pagination" class="pagination-container">
			<ul class="pagination justify-content-center" id="epicsPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<!-- Theme Script -->
	<!-- Select2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

			window.apiUrl = apiUrl;
			window.taigaToken = token;

			let allowFilterLoad = true;
			const onFilterChange = function (page = 1) {
				if (!allowFilterLoad) return;
				loadEpics(page);
			};

			// Initial filter binding (handles Select2 for dropdowns)
			taigaBindFilters(onFilterChange);

			taigaBindSelectionLogic('epic-checkbox', function (checkedCount) {
				const filtered = parseInt($('#filteredEpics').text()) || 0;
				const total = parseInt($('#totalEpics').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalEpics', 'filteredEpics', 'selectedEpicsCount');
			});

			// Bulk Create Epik functionality
			$('#bulkCreateEpicModal').on('show.bs.modal', function () {
				populateBulkCreateEpicDropdowns();
			});

			$('#previewBulkCreateEpic').on('click', function () {
				previewBulkCreateEpic();
			});

			$('#submitBulkCreateEpic').on('click', function () {
				submitBulkCreateEpic();
			});

			// Bulk Update Epik functionality
			$('#bulkUpdateEpicModal').on('show.bs.modal', function () {
				populateBulkUpdateEpicDropdowns();
			});

			$('#submitBulkUpdateEpic').on('click', function () {
				submitBulkUpdateEpic();
			});

			// Bulk Delete functionality
			$('#bulkDeleteEpicModal').on('show.bs.modal', function () {
				const selectedEpics = [];
				$('.epic-checkbox:checked').each(function () {
					const title = $(this).closest('.card-body').find('.card-title').text();
					selectedEpics.push(title);
				});
				$('#selectedEpicsList').html(selectedEpics.map(e => `<div>${e}</div>`).join(''));
			});

			$('#confirmBulkDeleteEpics').on('click', function () {
				const selectedEpics = [];
				$('.epic-checkbox:checked').each(function () {
					selectedEpics.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				if (selectedEpics.length === 0) {
					alert('Please select at least one epik to delete');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Deleting...');

				taigaExecuteBulk('/epics/', selectedEpics, 'DELETE', null, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Delete Epiks');
					if (errorCount === 0) {
						alert(`Successfully deleted ${successCount} epiks!`);
						$('#bulkDeleteEpicModal').modal('hide');
						loadEpics();
					} else {
						alert(`Deleted ${successCount} epiks, but ${errorCount} failed.`);
					}
				});
			});

			allowFilterLoad = false;
			taigaApplyFiltersFromUrl().then(function (page) {
				allowFilterLoad = true;
				loadEpics(page);
			}, function () {
				allowFilterLoad = true;
				loadEpics(1);
			});

			function loadProjectsAndEpics() {
				// Load projects first
				$.ajax({
					url: 'api.php/projects',
					type: 'GET',
					data: { member: taigaModel?.id ?? null },
					dataType: 'json',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (projects) {
						if (!Array.isArray(projects)) {
							console.error('Projects response is not an array:', projects);
							this.error(null, 'parsererror', 'Not an array');
							return;
						}
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

			function loadEpics(page = 1) {
				taigaReplaceUrlQuery({
					...taigaGetFilterParams(),
					page: page
				});

				const params = {
					...taigaGetFilterParams(),
					page: page
				};

				// Load epics for all projects
				$.ajax({
					url: 'api.php/epics',
					type: 'GET',
					data: params,
					dataType: 'json',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (epics, status, xhr) {
						if (!Array.isArray(epics)) {
							console.error('Epiks response is not an array:', epics);
							this.error(xhr, 'parsererror', 'Not an array');
							return;
						}
						allEpics = epics;
						displayEpics(epics, xhr);
						taigaRenderPagination(xhr, '#epicsPagination', loadEpics);
					},
					error: function (xhr) {
						console.error('Failed to load epics:', xhr);
						$('#epicsContent').html(`
					<div class="alert alert-danger">
						Failed to load epics. Please try again.
						<button class="btn btn-sm btn-outline-danger ms-2" onclick="loadEpics()">Retry</button>
					</div>
				`);
						$('#epicsPagination').empty();
					}
				});
			}

			function populateProjectSelect(projects) {
				let html = '<option value="">All Projects</option>';
				if (Array.isArray(projects)) {
					projects.forEach(project => {
						html += `<option value="${project.id}">${project.name}</option>`;
					});
				}
				$('#projectSelect').html(html);
			}

			function displayEpics(epics, xhr) {
				taigaUpdateListCounts(xhr, epics.length, 'totalEpics', 'filteredEpics', 'selectedEpicsCount');

				if (epics.length === 0) {
					$('#epicsContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row taiga-list-grid">';

				epics.forEach(epic => {
					const project = allProjects.find(p => p.id === epic.project) || {};
					const statusInfo = taigaGetStatusInfo(epic);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const assignedTo = epic.assigned_to_extra ? epic.assigned_to_extra.full_name_display : (epic.assigned_to ? 'User ID: ' + epic.assigned_to : 'Unassigned');
					const owner = epic.owner_extra ? epic.owner_extra.full_name_display : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4">
					<div class="card taiga-list-card epic-card h-100" data-epic-id="${epic.id}" data-project-id="${epic.project}" data-status="${epic.status}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input epic-checkbox" type="checkbox" value="${epic.id}" data-version="${epic.version}" id="epic-${epic.id}">
									<label class="form-check-label" for="epic-${epic.id}"></label>
								</div>
								${statusBadge}
							</div>
							<h6 class="card-title text-truncate">${epic.subject || 'Untitled Epik'}</h6>
							<p class="card-text text-muted taiga-card-description epic-description small mb-0">
								${epic.description || ''}
							</p>
							<div class="taiga-card-meta">
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted">
										Project: ${project.name || 'Unknown'}
									</small>
									<small class="text-muted">
										Ref: #${epic.ref}
									</small>
								</div>
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted">
										Assigned to: <strong>${assignedTo}</strong>
									</small>
								</div>
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted">
										Created by: ${owner}
									</small>
									<small class="text-muted">
										${new Date(epic.created_date).toLocaleDateString()}
									</small>
								</div>
								<div class="d-flex justify-content-between align-items-center">
									<small class="text-muted">
										Updated: ${new Date(epic.modified_date).toLocaleDateString()}
									</small>
								</div>
							</div>
						</div>
						<div class="card-footer taiga-card-actions">
							<button class="btn btn-outline-primary btn-sm view-epic" data-epic-id="${epic.id}">
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
			}

			// Local filter function removed as filtering is now done via API.



			// Bulk Create Epik Functions
			function populateBulkCreateEpicDropdowns() {
				// Populate project dropdown
				$.ajax({
					url: 'api.php/projects',
					type: 'GET',
					data: { member: typeof taigaModel !== 'undefined' ? taigaModel.id : null },
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
						$('#bulkCreateEpicProject').html(options).select2({
							theme: 'bootstrap-5',
							width: '100%',
							placeholder: 'Select Project',
							dropdownParent: $('#bulkCreateEpicModal')
						});

						// Initial status population if a project is already selected (e.g. from filter)
						const currentProjectId = $('#projectSelect').val();
						if (currentProjectId) {
							$('#bulkCreateEpicProject').val(currentProjectId);
							taigaPopulateBulkStatuses('epic', $('#bulkCreateEpicStatus'), currentProjectId);
						}
					}
				});

				// Update statuses when project changes
				$('#bulkCreateEpicProject').off('change').on('change', function () {
					taigaPopulateBulkStatuses('epic', $('#bulkCreateEpicStatus'), $(this).val());
				});
			}

			function previewBulkCreateEpic() {
				const text = $('#bulkCreateEpicText').val().trim();
				if (!text) {
					alert('Please enter some epiks to create');
					return;
				}

				const epics = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Epik',
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
					alert('Please enter some epiks to create');
					return;
				}

				const epics = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Epik',
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
						url: 'api.php/epics',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
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
				$btn.prop('disabled', false).text('Create Epiks');

				if (errorCount === 0) {
					alert(`Successfully created ${createdCount} epics!`);
					$('#bulkCreateEpicModal').modal('hide');
					loadProjectsAndEpics();
				} else {
					alert(`Created ${createdCount} epics, but ${errorCount} failed.`);
				}
			}

			// Bulk Update Epik Functions
			function populateBulkUpdateEpicDropdowns() {
				const filterParams = taigaGetFilterParams();
				const projectId = filterParams.project;
				taigaPopulateBulkStatuses('epic', $('#bulkUpdateEpicStatus'), projectId, 'No Change');

				taigaLoadBulkItems('/epics', $('#bulkUpdateEpics'), item => {
					return `
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-epic-${item.id}">
							<label class="form-check-label" for="bulk-epic-${item.id}">
								#${item.ref}: ${item.subject || 'Untitled Epik'}
							</label>
						</div>
					`;
				});
			}

			function submitBulkUpdateEpic() {
				const selectedEpics = [];
				$('#bulkUpdateEpics input:checked').each(function () {
					selectedEpics.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				const status = $('#bulkUpdateEpicStatus').val();
				const priority = $('#bulkUpdateEpicPriority').val();
				const description = $('#bulkUpdateEpicDescription').val().trim();
				const color = $('#bulkUpdateEpicColor').val();

				if (!selectedEpics || selectedEpics.length === 0) {
					alert('Please select at least one epik to update');
					return;
				}

				const updateData = {};
				if (status) updateData.status = parseInt(status);
				if (priority) updateData.priority = parseInt(priority);
				if (description) updateData.description = description;
				if (color) updateData.color = color;

				if (Object.keys(updateData).length === 0) {
					alert('Please specify at least one field to update');
					return;
				}

				const $btn = $('#submitBulkUpdateEpic');
				$btn.prop('disabled', true).text('Updating...');

				taigaExecuteBulk('/epics/', selectedEpics, 'PATCH', updateData, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Update Epiks');
					if (errorCount === 0) {
						alert(`Successfully updated ${successCount} epiks!`);
						$('#bulkUpdateEpicModal').modal('hide');
						loadEpics();
					} else {
						alert(`Updated ${successCount} epiks, but ${errorCount} failed.`);
					}
				});
			}
		});
	</script>

	<?php include __DIR__ . '/app/partials/epic_bulk_create.php'; ?>
	<?php include __DIR__ . '/app/partials/epic_bulk_update.php'; ?>
	<?php include __DIR__ . '/app/partials/epic_bulk_delete.php'; ?>

</body>

</html>
