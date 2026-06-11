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
		$pageTitle = 'Usor';
		$statusType = 'us';


		$bulkDeleteModalId = 'bulkDeleteModal';
		$searchPlaceholder = 'Search usors...';
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

		$totalLabel = 'Total Usors';
		$totalId = 'totalUsors';
		$filteredId = 'filteredUsors';
		$selectionCountId = 'selectedUsorsCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="usorsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading Usor...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Usors pagination" class="pagination-container">
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

			let allowFilterLoad = true;
			const onFilterChange = function (page = 1) {
				if (!allowFilterLoad) return;
				loadUsors(page);
			};

			// Initial filter binding (handles Select2 for dropdowns)
			taigaBindFilters(onFilterChange);

			taigaBindSelectionLogic('story-checkbox', function (checkedCount) {
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

			allowFilterLoad = false;
			taigaApplyFiltersFromUrl().then(function (page) {
				allowFilterLoad = true;
				loadUsors(page);
			}, function () {
				allowFilterLoad = true;
				loadUsors(1);
			});

			function loadUsors(page = 1) {
				taigaReplaceUrlQuery({
					...taigaGetFilterParams(),
					page: page
				});

				const params = {
					...taigaGetFilterParams(),
					page: page
				};

				$('#usorsContent').html(`
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading Usor...</span>
				</div>
			</div>
		`);

				$.ajax({
					url: 'api.php/userstories',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (usors, status, xhr) {
						displayUsors(usors, xhr);
						taigaRenderPagination(xhr, '#usorsPagination', loadUsors);
					},
					error: function (xhr) {
						console.error('Failed to load user stories:', xhr);
						$('#usorsContent').html(`
					<div class="alert alert-danger">
						Unable to load usors. Please try again.
					</div>
				`);
						$('#usorsPagination').empty();
					}
				});
			}


			// loadEpics function removed as it is now handled by taigaLoadEpics in taiga.js

			function displayUsors(usors, xhr) {
				taigaUpdateListCounts(xhr, usors.length, 'totalUsors', 'filteredUsors', 'selectedUsorsCount');

				if (usors.length === 0) {
					$('#usorsContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row taiga-list-grid">';
				usors.forEach(usor => {
					const statusInfo = taigaGetStatusInfo(usor);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const assignedTo = usor.assigned_to_extra ? usor.assigned_to_extra.full_name_display : (usor.assigned_to ? 'User ID: ' + usor.assigned_to : 'Unassigned');
					const owner = usor.owner_extra ? usor.owner_extra.full_name_display : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4">
					<div class="card taiga-list-card usor-card h-100" data-us-id="${usor.id}" data-project-id="${usor.project}" data-status="${usor.status}">
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
						<h6 class="card-title text-truncate">${usor.subject || 'Untitled Usor'}</h6>

						<div class="taiga-card-description usor-description text-muted small mb-0">
							${usor.description || ''}
						</div>
						
						<div class="taiga-card-meta">
							<div class="d-flex justify-content-between align-items-center mb-1">
								<small class="text-muted">Ref: #${usor.ref} | Project ID: ${usor.project || 'N/A'}</small>
								<small class="text-muted">${new Date(usor.created_date).toLocaleDateString()}</small>
							</div>
							
							<div class="mb-1">
								<small class="text-muted d-block text-truncate">
									Assigned: <strong>${assignedTo}</strong>
								</small>
							</div>
							
							<div class="d-flex justify-content-between align-items-center mb-1">
								<small class="text-muted text-truncate">By: ${owner}</small>
								<small class="text-muted">Upd: ${new Date(usor.modified_date).toLocaleDateString()}</small>
							</div>

							${usor.epic_extra ? `
								<small class="text-muted d-block mt-2 text-truncate">Epik: #${usor.epic_extra.ref} ${usor.epic_extra.subject}</small>
							` : (usor.epic ? `<small class="text-muted d-block mt-2">Epik ID: #${usor.epic}</small>` : '')}
						</div>
					</div>
					<div class="card-footer taiga-card-actions">
							<a href="usor.php?id=${usor.id}" class="btn btn-outline-primary btn-sm shadow-sm">
								<i class="bi bi-eye me-1"></i>
								View Details
							</a>
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
							url: 'api.php/epics?project=' + projectId,
							type: 'GET',
							headers: {
								'Authorization': 'Bearer ' + token,
								'Content-Type': 'application/json',
								'X-Taiga-Api-Url': apiUrl
							},
							success: function (epics) {
								let html = '<option value="">Select Epik</option>';
								epics.forEach(epic => {
									html += `<option value="${epic.id}">${epic.subject || 'Untitled Epik'}</option>`;
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
					$('#bulkCreateText').attr('placeholder', `Enter usors... (Active search: ${currentSearch})`);
				} else {
					$('#bulkCreateSearchContext').addClass('d-none');
					$('#bulkCreateText').attr('placeholder', 'Enter usors, one per line. Format: Subject|Description (optional)|Status (optional)');
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
					alert('Please enter some usors to create');
					return;
				}

				const stories = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					return {
						subject: parts[0]?.trim() || 'Untitled Usor',
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
					alert('Please enter some usors to create');
					return;
				}

				const currentSearch = $('#searchInput').val();
				const prependSearch = $('#prependSearchCheck').is(':checked');

				const stories = text.split('\n').filter(line => line.trim()).map(line => {
					const parts = line.split('|');
					let subject = parts[0]?.trim() || 'Untitled Usor';
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
						url: 'api.php/userstories',
						type: 'POST',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
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
					alert(`Successfully created ${createdCount} usors!`);
					$('#bulkCreateModal').modal('hide');
					loadUsors();
				} else {
					alert(`Created ${createdCount} usors, but ${errorCount} failed.`);
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
								#${item.ref}: ${item.subject || 'Untitled Usor'}
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
					alert('Please select at least one usor to update');
					return;
				}

				const updateData = {};
				const status = $('#bulkUpdateStatus').val();
				const assignee = $('#bulkUpdateAssignee').val();
				const priority = $('#bulkUpdatePriority').val();
				const description = $('#bulkUpdateDescription').val()?.trim();

				if (status) updateData.status = parseInt(status);
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
					$btn.prop('disabled', false).text('Update Usors');
					if (errorCount === 0) {
						alert(`Successfully updated ${successCount} usors!`);
						$('#bulkUpdateModal').modal('hide');
						loadUsors();
					} else {
						alert(`Updated ${successCount} usors, but ${errorCount} failed.`);
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
					alert('Please select at least one usor to delete');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Deleting...');

				taigaExecuteBulk('/userstories/', selectedUsors, 'DELETE', null, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Delete Usors');
					if (errorCount === 0) {
						alert(`Successfully deleted ${successCount} usors!`);
						$('#bulkDeleteModal').modal('hide');
						loadUsors();
					} else {
						alert(`Deleted ${successCount} usors, but ${errorCount} failed.`);
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
