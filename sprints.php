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
		$pageTitle = 'Sprints';
		$bulkUpdateModalId = 'bulkUpdateSprintModal';
		$searchPlaceholder = 'Search sprints...';
		$filterStatusEnable = false; // We'll handle closed/open filter separately if needed or just use search
		$sortOptions = [
			'name' => 'Name (A-Z)',
			'-name' => 'Name (Z-A)',
			'created_date' => 'Created (Oldest)',
			'-created_date' => 'Created (Newest)',
			'modified_date' => 'Modified (Oldest)',
			'-modified_date' => 'Modified (Newest)',
			'estimated_start' => 'Start Date (ASC)',
			'-estimated_start' => 'Start Date (DESC)',
			'estimated_finish' => 'Finish Date (ASC)',
			'-estimated_finish' => 'Finish Date (DESC)',
		];
		$primaryAction = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#singleSprintModal"><i class="bi bi-plus-lg me-1"></i> Add New</button>';
$bulkActions = '
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkCreateSprintModal"><i class="bi bi-plus-lg me-2"></i> Bulk Create</a></li>
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#bulkUpdateSprintModal"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
			<li><a class="dropdown-item text-danger" href="#" id="bulkDeleteBtn"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
		';
		include __DIR__ . '/app/partials/list_header.php';
		?>

		<?php
		$totalId = 'totalSprints';
		$filteredId = 'filteredSprints';
		$selectionCountId = 'selectedSprintsCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="sprintsContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading sprints...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Sprints pagination" class="pagination-container">
			<ul class="pagination justify-content-center" id="sprintsPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

			window.apiUrl = apiUrl;
			window.taigaToken = token;

			let selectedIds = new Set();

			let allowFilterLoad = true;
			const onFilterChange = function (page = 1) {
				if (!allowFilterLoad) return;
				loadSprints(page);
			};

			// Initial filter binding
			taigaBindFilters(onFilterChange);

			taigaBindSelectionLogic('sprint-checkbox', function(checkedCount) {
				const filtered = parseInt($('#filteredSprints').text()) || 0;
				const total = parseInt($('#totalSprints').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalSprints', 'filteredSprints', 'selectedSprintsCount');
			});

			$('#bulkCreateSprintModal').on('show.bs.modal', function () {
				const projectId = $('#projectSelect').val();
				taigaPopulateProjectSelect($('#bulkCreateSprintProject'), projectId);
			});

			$('#previewBulkCreateSprints').on('click', function () {
				const items = taigaParseBulkLines($('#bulkCreateSprintText').val());
				taigaRenderBulkPreview($('#bulkCreateSprintPreview'), items, 'name');
			});

			$('#submitBulkCreateSprints').on('click', function () {
				const projectId = $('#bulkCreateSprintProject').val();
				const start = $('#bulkCreateSprintStart').val();
				const finish = $('#bulkCreateSprintFinish').val();
				const items = taigaParseBulkLines($('#bulkCreateSprintText').val());
				if (!projectId) {
					alert('Please select a project');
					return;
				}
				if (items.length === 0) {
					alert('Please enter at least one sprint');
					return;
				}
				const $btn = $(this);
				$btn.prop('disabled', true).text('Creating...');
				taigaExecuteBulkCreate('/milestones', items, item => {
					const payload = {
						project: parseInt(projectId),
						name: item.name,
						description: item.description || ''
					};
					if (start) payload.estimated_start = start;
					if (finish) payload.estimated_finish = finish;
					return payload;
				}, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Create Sprints');
					if (errorCount === 0) {
						$('#bulkCreateSprintModal').modal('hide');
						$('#bulkCreateSprintText').val('');
						$('#bulkCreateSprintPreview').addClass('d-none').empty();
						loadSprints();
					}
					alert(errorCount === 0
						? `Successfully created ${successCount} sprints!`
						: `Created ${successCount} sprints, but ${errorCount} failed.`);
				});
			});

			// Bulk Update Sprint functionality
			$('#bulkUpdateSprintModal').on('show.bs.modal', function () {
				populateBulkUpdateSprintDropdowns();
			});

			$('#submitBulkUpdateSprint').on('click', function () {
				submitBulkUpdateSprint();
			});

			allowFilterLoad = false;
			taigaApplyFiltersFromUrl().then(function (page) {
				allowFilterLoad = true;
				loadSprints(page);
			}, function () {
				allowFilterLoad = true;
				loadSprints(1);
			});

			function loadSprints(page = 1) {
				taigaReplaceUrlQuery({
					...taigaGetFilterParams(),
					page: page
				});

				const params = {
					...taigaGetFilterParams(),
					page: page
				};
				
				$('#sprintsContent').html(`
					<div class="loading-spinner">
						<div class="spinner-border text-primary" role="status">
							<span class="visually-hidden">Loading sprints...</span>
						</div>
					</div>
				`);

				$.ajax({
					url: 'api.php/milestones',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (sprints, status, xhr) {
						displaySprints(sprints, xhr);
						taigaRenderPagination(xhr, '#sprintsPagination', loadSprints);
						updateSelectedCount();
					},
					error: function (xhr) {
						console.error('Failed to load sprints:', xhr);
						$('#sprintsContent').html(`
							<div class="alert alert-danger">
								Failed to load sprints. Please try again.
							</div>
						`);
						$('#sprintsPagination').empty();
					}
				});
			}

			function displaySprints(sprints, xhr) {
				taigaUpdateListCounts(xhr, sprints.length, 'totalSprints', 'filteredSprints', 'selectedSprintsCount');

				if (sprints.length === 0) {
					$('#sprintsContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row taiga-list-grid">';

				sprints.forEach(sprint => {
					const isClosed = sprint.closed;
					const statusBadge = isClosed ? 
						'<span class="badge bg-secondary">Closed</span>' : 
						'<span class="badge bg-success">Open</span>';

					html += `
						<div class="col-md-6 col-lg-4">
							<div class="card taiga-list-card sprint-card h-100 ${selectedIds.has(sprint.id) ? 'taiga-selected' : ''}" data-sprint-id="${sprint.id}">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-start mb-2">
										<div class="form-check">
											<input class="form-check-input sprint-checkbox" type="checkbox" value="${sprint.id}" ${selectedIds.has(sprint.id) ? 'checked' : ''}>
										</div>
										${statusBadge}
									</div>
									<h6 class="card-title text-truncate">${sprint.name || 'Untitled Sprint'}</h6>
									<p class="card-text text-muted taiga-card-description small mb-0">
										${sprint.description || ''}
									</p>
									<div class="taiga-card-meta">
										<div class="d-flex justify-content-between align-items-center mb-1">
											<small class="text-muted d-block text-truncate">
												<i class="bi bi-calendar-event me-1"></i>
												${sprint.estimated_start || '?'} to ${sprint.estimated_finish || '?'}
											</small>
										</div>
										<div class="d-flex justify-content-between align-items-center mb-1">
											<small class="text-muted text-truncate">
												By: ${sprint.owner_extra ? (sprint.owner_extra.full_name_display || sprint.owner_extra.username) : (sprint.owner ? 'ID: ' + sprint.owner : 'Unknown')}
											</small>
											<small class="text-muted">
												Upd: ${new Date(sprint.modified_date).toLocaleDateString()}
											</small>
										</div>
										<div class="progress mt-2" style="height: 5px;">
											<div class="progress-bar" role="progressbar" style="width: ${sprint.closed_points ? Math.round((sprint.closed_points / sprint.total_points) * 100) : 0}%"></div>
										</div>
									</div>
								</div>
								<div class="card-footer taiga-card-actions">
									<a href="sprint.php?id=${sprint.id}" class="btn btn-outline-primary btn-sm">
										View Details
									</a>
									<button class="btn btn-outline-secondary btn-sm edit-sprint" data-sprint-id="${sprint.id}" data-bs-toggle="modal" data-bs-target="#singleSprintModal">
										Edit
									</button>
								</div>
							</div>
						</div>
					`;
				});

				html += '</div>';
				$('#sprintsContent').html(html);

				// Add click event for selection
				$('.sprint-checkbox').on('change', function (e) {
					const id = parseInt($(this).val());
					if (this.checked) {
						selectedIds.add(id);
						$(this).closest('.card').addClass('taiga-selected');
					} else {
						selectedIds.delete(id);
						$(this).closest('.card').removeClass('taiga-selected');
					}
					updateSelectedCount();
				});

				$('.view-sprint').on('click', function () {
					const sprintId = $(this).data('sprint-id');
					window.location.href = `sprint.php?id=${sprintId}`;
				});
			}

			function updateSelectedCount() {
				const count = selectedIds.size;
				const filtered = parseInt($('#filteredSprints').text()) || 0;
				const total = parseInt($('#totalSprints').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, count, 'totalSprints', 'filteredSprints', 'selectedSprintsCount');
				if (count > 0) {
					$('#bulkDeleteBtn').removeClass('d-none');
				} else {
					$('#bulkDeleteBtn').addClass('d-none');
				}
			}

			// Bulk Delete functionality
			$('#bulkDeleteBtn').on('click', function (e) {
				e.preventDefault();
				if (!confirm(`Are you sure you want to delete ${selectedIds.size} sprints?`)) return;

				const $btn = $(this);
				$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Deleting...');

				const deletePromises = Array.from(selectedIds).map(id => {
					return $.ajax({
						url: 'api.php/milestones/' + id,
						type: 'DELETE',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						}
					});
				});

				Promise.all(deletePromises)
					.then(() => {
						alert('Successfully deleted');
						selectedIds.clear();
						loadSprints();
					})
					.catch(err => {
						console.error(err);
						alert('Some deletions failed');
					})
					.finally(() => {
						$btn.prop('disabled', false).html('<i class="bi bi-trash me-1"></i> Delete Selected');
					});
			});

			// Bulk Update Sprint Functions
			function populateBulkUpdateSprintDropdowns() {
				// Load sprints for selection in modal
				$.ajax({
					url: 'api.php/milestones',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (sprints) {
						let options = '';
						sprints.forEach(sprint => {
							const selected = selectedIds.has(sprint.id) ? 'selected' : '';
							options += `<option value="${sprint.id}" ${selected}>${sprint.name || 'Untitled Sprint'}</option>`;
						});
						$('#bulkUpdateSprints').html(options).trigger('change');
					}
				});
			}

			function submitBulkUpdateSprint() {
				const selectedSprints = $('#bulkUpdateSprints').val();
				const isClosed = $('#bulkUpdateClosed').val();
				const description = $('#bulkUpdateDescription').val().trim();

				if (!selectedSprints || selectedSprints.length === 0) {
					alert('Please select at least one sprint to update');
					return;
				}

				const updateData = {};
				if (isClosed !== "") updateData.closed = (isClosed === "true");
				if (description) updateData.description = description;

				if (Object.keys(updateData).length === 0) {
					alert('Please specify at least one field to update');
					return;
				}

				const $btn = $('#submitBulkUpdateSprint');
				$btn.prop('disabled', true).text('Updating...');

				const updatePromises = selectedSprints.map(id => {
					return $.ajax({
						url: 'api.php/milestones/' + id,
						type: 'PATCH',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						data: JSON.stringify(updateData)
					});
				});

				Promise.all(updatePromises)
					.then(() => {
						alert('Successfully updated');
						$('#bulkUpdateSprintModal').modal('hide');
						loadSprints();
					})
					.catch(err => {
						console.error(err);
						alert('Some updates failed');
					})
					.finally(() => {
						$btn.prop('disabled', false).text('Update Sprints');
					});
			}
			// Single Sprint Create/Update
			$('#singleSprintModal').on('show.bs.modal', function (e) {
				const sprintId = $(e.relatedTarget).data('sprint-id');
				const filterParams = taigaGetFilterParams();
				const initialProjectId = filterParams.project;

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
						$('#singleSprintProject').html(options);
						if (initialProjectId) {
							$('#singleSprintProject').val(initialProjectId);
						}
					}
				});

				if (sprintId) {
					// Edit mode: Load sprint data
					$('#singleSprintModalLabel').text('Edit Sprint');
					$.ajax({
						url: `api.php/milestones/${sprintId}`,
						type: 'GET',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						success: function (sprint) {
							$('#singleSprintId').val(sprint.id);
							$('#singleSprintVersion').val(sprint.version);
							$('#singleSprintName').val(sprint.name);
							$('#singleSprintProject').val(sprint.project);
							if (sprint.estimated_start) {
								$('#singleSprintStart').val(sprint.estimated_start.split('T')[0]);
							}
							if (sprint.estimated_finish) {
								$('#singleSprintEnd').val(sprint.estimated_finish.split('T')[0]);
							}
						},
						error: function (xhr) {
							console.error('Failed to load sprint:', xhr);
							alert('Failed to load sprint. Please try again.');
						}
					});
				} else {
					// Create mode: Reset form
					$('#singleSprintModalLabel').text('Create Sprint');
					$('#singleSprintForm')[0].reset();
					$('#singleSprintId').val('');
					$('#singleSprintVersion').val('');
				}
			});

			$('#submitSingleSprint').on('click', function () {
				const sprintId = $('#singleSprintId').val();
				const sprintVersion = $('#singleSprintVersion').val();
				const projectId = $('#singleSprintProject').val();
				const name = $('#singleSprintName').val().trim();
				const start = $('#singleSprintStart').val();
				const end = $('#singleSprintEnd').val();

				if (!projectId) {
					alert('Please select a project');
					return;
				}
				if (!name) {
					alert('Please enter a name');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Saving...');

				const data = {
					name: name,
					project: parseInt(projectId)
				};
				if (start) data.estimated_start = start;
				if (end) data.estimated_finish = end;
				if (sprintId) {
					data.version = parseInt(sprintVersion);
				}

				$.ajax({
					url: sprintId ? `api.php/milestones/${sprintId}` : 'api.php/milestones',
					type: sprintId ? 'PATCH' : 'POST',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					data: JSON.stringify(data),
					success: function () {
						$btn.prop('disabled', false).text('Save');
						$('#singleSprintModal').modal('hide');
						alert(sprintId ? 'Sprint updated successfully!' : 'Sprint created successfully!');
						loadSprints();
					},
					error: function (xhr) {
						$btn.prop('disabled', false).text('Save');
						console.error('Failed to save sprint:', xhr);
						alert('Failed to save sprint. Please try again.');
					}
				});
			});
		});
	</script>

	<?php include __DIR__ . '/app/partials/sprint_multiple_form.php'; ?>
<?php include __DIR__ . '/app/partials/sprint_single_form.php'; ?>

</body>

</html>
