<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sprints - Taiga API</title>
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
		$bulkActions = '
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

			// Load initial sprints list
			loadSprints();

			// Initial filter binding
			taigaBindFilters(loadSprints);

			taigaBindSelectionLogic('sprint-checkbox', function(checkedCount) {
				const filtered = parseInt($('#filteredSprints').text()) || 0;
				const total = parseInt($('#totalSprints').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalSprints', 'filteredSprints', 'selectedSprintsCount');
			});

			// Bulk Update Sprint functionality
			$('#bulkUpdateSprintModal').on('show.bs.modal', function () {
				populateBulkUpdateSprintDropdowns();
			});

			$('#submitBulkUpdateSprint').on('click', function () {
				submitBulkUpdateSprint();
			});

			function loadSprints(page = 1) {
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
					url: apiUrl + '/milestones',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (sprints, status, xhr) {
						displaySprints(sprints);
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

			function displaySprints(sprints) {
				if (sprints.length === 0) {
					$('#sprintsContent').html(`
						<div class="alert alert-info">
							No sprints found. Use the "Add Sprint" page to create one!
						</div>
					`);
					return;
				}

				let html = '<div class="row">';

				sprints.forEach(sprint => {
					const isClosed = sprint.closed;
					const statusBadge = isClosed ? 
						'<span class="badge bg-secondary">Closed</span>' : 
						'<span class="badge bg-success">Open</span>';

					html += `
						<div class="col-md-6 col-lg-4 mb-4">
							<div class="card sprint-card h-100 ${selectedIds.has(sprint.id) ? 'border-primary bg-light' : ''}" data-sprint-id="${sprint.id}">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-start mb-2">
										<div class="form-check">
											<input class="form-check-input sprint-checkbox" type="checkbox" value="${sprint.id}" ${selectedIds.has(sprint.id) ? 'checked' : ''}>
										</div>
										${statusBadge}
									</div>
									<h5 class="card-title text-truncate">${sprint.name || 'Untitled Sprint'}</h5>
									<p class="card-text text-muted small text-truncate-2">
										${sprint.description || 'No description available.'}
									</p>
									<div class="mt-3">
										<small class="text-muted d-block">
											<i class="bi bi-calendar-event me-1"></i>
											${sprint.estimated_start || '?'} to ${sprint.estimated_finish || '?'}
										</small>
										<div class="progress mt-2" style="height: 5px;">
											<div class="progress-bar" role="progressbar" style="width: ${sprint.closed_points ? Math.round((sprint.closed_points / sprint.total_points) * 100) : 0}%"></div>
										</div>
									</div>
								</div>
								<div class="card-footer bg-transparent border-top-0 d-flex justify-content-between">
									<button class="btn btn-outline-primary btn-sm view-sprint" data-sprint-id="${sprint.id}">
										View Details
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
						$(this).closest('.card').addClass('border-primary bg-light');
					} else {
						selectedIds.delete(id);
						$(this).closest('.card').removeClass('border-primary bg-light');
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
				$('#selectedCount').text(count);
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
						url: apiUrl + '/milestones/' + id,
						type: 'DELETE',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
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
					url: apiUrl + '/milestones',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
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
						url: apiUrl + '/milestones/' + id,
						type: 'PATCH',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
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
		});
	</script>

	<!-- Bulk Update Sprint Modal -->
	<div class="modal fade" id="bulkUpdateSprintModal" tabindex="-1" aria-labelledby="bulkUpdateSprintModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="bulkUpdateSprintModalLabel">Bulk Update Sprints</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<form id="bulkUpdateSprintForm">
						<div class="mb-3">
							<label class="form-label">Select Sprints to Update</label>
							<select class="form-select" id="bulkUpdateSprints" multiple size="8">
								<option value="">Loading sprints...</option>
							</select>
							<small class="form-text text-muted">Hold Ctrl/Cmd to select multiple sprints</small>
						</div>
						<div class="mb-3">
							<label class="form-label">Status</label>
							<select class="form-select" id="bulkUpdateClosed">
								<option value="">No Change</option>
								<option value="false">Open</option>
								<option value="true">Closed</option>
							</select>
						</div>
						<div class="mb-3">
							<label class="form-label">Description (optional)</label>
							<textarea class="form-control" id="bulkUpdateDescription" rows="3" placeholder="Leave empty for no change"></textarea>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="submitBulkUpdateSprint">Update Sprints</button>
				</div>
			</div>
		</div>
	</div>

</body>

</html>
