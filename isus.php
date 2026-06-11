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
		$pageTitle = 'Isu';
		$statusType = 'issue';
		$searchPlaceholder = 'Search isus...';
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
			'priority' => 'Priority (ASC)',
			'-priority' => 'Priority (DESC)',
			'severity' => 'Severity (ASC)',
			'-severity' => 'Severity (DESC)',
			'type' => 'Type (ASC)',
			'-type' => 'Type (DESC)',
		];
		$bulkActions = '
			<li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#issueBulkCreateModal"><i class="bi bi-plus-lg me-2"></i> Bulk Create</a></li>
			<li><a class="dropdown-item" href="#" id="bulkUpdateBtn"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
			<li><a class="dropdown-item text-danger" href="#" id="bulkDeleteBtn"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
		';
		include __DIR__ . '/app/partials/list_header.php';

		$totalLabel = 'Total Isus';
		$totalId = 'totalIssues';
		$filteredId = 'filteredIssues';
		$selectionCountId = 'selectionCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="issuesContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading Isu...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Isus pagination" class="pagination-container">
			<ul class="pagination justify-content-center" id="issuesPagination">
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

			// Define globals for taiga.js helpers
			window.apiUrl = apiUrl;
			window.taigaToken = token;

			let allowFilterLoad = true;
			const onFilterChange = function (page = 1) {
				if (!allowFilterLoad) return;
				loadIssues(page);
			};

			// Initial filter binding
			taigaBindFilters(onFilterChange);

			taigaBindSelectionLogic('issue-checkbox', function (checkedCount) {
				const filtered = parseInt($('#filteredIssues').text()) || 0;
				const total = parseInt($('#totalIssues').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalIssues', 'filteredIssues', 'selectionCount');
			});

			$('#issueBulkCreateModal').on('show.bs.modal', function () {
				const projectId = $('#projectSelect').val();
				taigaPopulateProjectSelect($('#bulkCreateIssueProject'), projectId);
				if (projectId) {
					taigaPopulateBulkStatuses('issue', $('#bulkCreateIssueStatus'), projectId, 'Default');
					taigaPopulateBulkMembers($('#bulkCreateIssueAssignee'), projectId, 'Unassigned');
				}
			});

			$('#bulkCreateIssueProject').on('change', function () {
				const projectId = $(this).val();
				taigaPopulateBulkStatuses('issue', $('#bulkCreateIssueStatus'), projectId, 'Default');
				taigaPopulateBulkMembers($('#bulkCreateIssueAssignee'), projectId, 'Unassigned');
			});

			$('#previewBulkCreateIssues').on('click', function () {
				const items = taigaParseBulkLines($('#bulkCreateIssueText').val());
				taigaRenderBulkPreview($('#bulkCreateIssuePreview'), items, 'subject');
			});

			$('#submitBulkCreateIssues').on('click', function () {
				const projectId = $('#bulkCreateIssueProject').val();
				const status = $('#bulkCreateIssueStatus').val();
				const assignee = $('#bulkCreateIssueAssignee').val();
				const items = taigaParseBulkLines($('#bulkCreateIssueText').val());
				if (!projectId) {
					alert('Please select a project');
					return;
				}
				if (items.length === 0) {
					alert('Please enter at least one isu');
					return;
				}
				const $btn = $(this);
				$btn.prop('disabled', true).text('Creating...');
				taigaExecuteBulkCreate('/issues', items, item => {
					const payload = {
						project: parseInt(projectId),
						subject: item.subject,
						description: item.description || ''
					};
					if (status) payload.status = parseInt(status);
					if (assignee) payload.assigned_to = parseInt(assignee);
					return payload;
				}, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Create Isus');
					if (errorCount === 0) {
						$('#issueBulkCreateModal').modal('hide');
						$('#bulkCreateIssueText').val('');
						$('#bulkCreateIssuePreview').addClass('d-none').empty();
						loadIssues();
					}
					alert(errorCount === 0
						? `Successfully created ${successCount} isus!`
						: `Created ${successCount} isus, but ${errorCount} failed.`);
				});
			});

			// Bulk Delete functionality
			$('#bulkDeleteBtn').on('click', function (e) {
				e.preventDefault();
				const selectedIssues = [];
				const selectedSubjects = [];
				$('#issuesContent input.issue-checkbox:checked').each(function () {
					selectedIssues.push($(this).val());
					selectedSubjects.push($(this).closest('.card').find('.card-title').text());
				});

				if (selectedIssues.length === 0) {
					alert('Please select at least one isu to delete');
					return;
				}

				let listHtml = '<ul class="list-group list-group-flush">';
				selectedSubjects.forEach(subject => {
					listHtml += `<li class="list-group-item py-1 small">${subject}</li>`;
				});
				listHtml += '</ul>';
				$('#selectedIssuesList').html(listHtml);

				$('#issueBulkDeleteModal').modal('show');
			});
			// Bulk Delete confirmation
			$('#confirmBulkDeleteIssues').on('click', function () {
				const selectedIssues = [];
				$('#issuesContent input.issue-checkbox:checked').each(function () {
					selectedIssues.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				const btn = $(this);
				const originalText = btn.html();
				btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...').prop('disabled', true);

				taigaExecuteBulk('/issues/', selectedIssues, 'DELETE', null, (successCount, errorCount) => {
					btn.html(originalText).prop('disabled', false);
					$('#issueBulkDeleteModal').modal('hide');
					if (errorCount === 0) {
						alert(`Successfully deleted ${successCount} isus!`);
					} else {
						alert(`Deleted ${successCount} isus, but ${errorCount} failed.`);
					}
					loadIssues();
					$('#selectionCount').text('0');
				});
			});

			// Bulk Update functionality
			$('#bulkUpdateBtn').on('click', function (e) {
				e.preventDefault();
				const selectedIssues = [];
				const selectedSubjects = [];
				$('#issuesContent input.issue-checkbox:checked').each(function () {
					selectedIssues.push($(this).val());
					selectedSubjects.push($(this).closest('.card').find('.card-title').text());
				});

				if (selectedIssues.length === 0) {
					alert('Please select at least one isu to update');
					return;
				}

				let listHtml = '<ul class="list-group list-group-flush">';
				selectedSubjects.forEach(subject => {
					listHtml += `<li class="list-group-item py-1 small">${subject}</li>`;
				});
				listHtml += '</ul>';
				$('#bulkUpdateIssueList').html(listHtml);

				populateBulkUpdateIssueDropdowns();
				$('#issueBulkUpdateModal').modal('show');
			});

			function populateBulkUpdateIssueDropdowns() {
				const filterParams = taigaGetFilterParams();
				const projectId = filterParams.project;
				taigaPopulateBulkStatuses('issue', $('#bulkUpdateIssueStatus'), projectId, 'No Change');
				taigaPopulateBulkMembers($('#bulkUpdateIssueAssignee'), projectId, 'No Change');

				const selectedIssueIds = [];
				$('#issuesContent input.issue-checkbox:checked').each(function () {
					selectedIssueIds.push($(this).val());
				});

				taigaLoadBulkItems('/issues', $('#bulkUpdateIssueList'), item => {
					return `
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="${item.id}" data-version="${item.version}" id="bulk-issue-${item.id}" checked>
							<label class="form-check-label" for="bulk-issue-${item.id}">
								#${item.ref}: ${item.subject}
							</label>
						</div>
					`;
				}, selectedIssueIds);
			}

			$('#submitBulkIssueUpdate').on('click', function () {
				const selectedIssues = [];
				$('#bulkUpdateIssueList input:checked').each(function () {
					selectedIssues.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				const updateData = {};
				const status = $('#bulkUpdateIssueStatus').val();
				const assignee = $('#bulkUpdateIssueAssignee').val();

				if (status) updateData.status = parseInt(status);
				if (assignee) updateData.assigned_to = parseInt(assignee);

				if (Object.keys(updateData).length === 0) {
					alert('Please select at least one field to update');
					return;
				}

				const btn = $(this);
				const originalText = btn.text();
				btn.prop('disabled', true).text('Updating...');

				taigaExecuteBulk('/issues/', selectedIssues, 'PATCH', updateData, (successCount, errorCount) => {
					btn.prop('disabled', false).text(originalText);
					$('#issueBulkUpdateModal').modal('hide');
					if (errorCount === 0) {
						alert(`Successfully updated ${successCount} isus!`);
					} else {
						alert(`Updated ${successCount} isus, but ${errorCount} failed.`);
					}
					loadIssues();
					$('#selectionCount').text('0');
				});
			});

			allowFilterLoad = false;
			taigaApplyFiltersFromUrl().then(function (page) {
				allowFilterLoad = true;
				loadIssues(page);
			}, function () {
				allowFilterLoad = true;
				loadIssues(1);
			});

			function loadIssues(page = 1) {
				taigaReplaceUrlQuery({
					...taigaGetFilterParams(),
					page: page
				});

				const params = {
					...taigaGetFilterParams(),
					page: page
				};

				$('#issuesContent').html(`
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading Isu...</span>
				</div>
			</div>
		`);

				$.ajax({
					url: 'api.php/issues',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (issues, status, xhr) {
						displayIssues(issues, xhr);
						taigaRenderPagination(xhr, '#issuesPagination', loadIssues);
					},
					error: function (xhr) {
						console.error('Failed to load issues:', xhr);
						$('#issuesContent').html(`
					<div class="alert alert-danger">
						Unable to load issues. Please try again.
					</div>
				`);
						$('#issuesPagination').empty();
					}
				});
			}

			function displayIssues(issues, xhr) {
				taigaUpdateListCounts(xhr, issues.length, 'totalIssues', 'filteredIssues', 'selectionCount');

				if (issues.length === 0) {
					$('#issuesContent').html(`
						<div class="text-muted italic p-3 text-center">
							<em>(kosong)</em>
						</div>
					`);
					return;
				}

				let html = '<div class="row taiga-list-grid">';
				issues.forEach(issue => {
					const statusInfo = taigaGetStatusInfo(issue);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const assignedTo = issue.assigned_to_extra ? issue.assigned_to_extra.full_name_display : (issue.assigned_to ? 'User ID: ' + issue.assigned_to : 'Unassigned');
					const owner = issue.owner_extra ? issue.owner_extra.full_name_display : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4">
					<div class="card taiga-list-card issue-card h-100" data-issue-id="${issue.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input issue-checkbox" type="checkbox" value="${issue.id}" data-version="${issue.version}" id="issue-${issue.id}">
									<label class="form-check-label" for="issue-${issue.id}"></label>
								</div>
								${statusBadge}
							</div>
							
							<h6 class="card-title text-truncate">${issue.subject || 'Untitled Isu'}</h6>
							
							<div class="d-flex flex-wrap gap-1 mb-2">
								<span class="badge bg-secondary small">${issue.type_extra ? issue.type_extra.name : (issue.type || 'Bug')}</span>
								<span class="badge bg-danger small">${issue.severity_extra ? issue.severity_extra.name : (issue.severity || 'Normal')}</span>
								<span class="badge bg-info small">${issue.priority_extra ? issue.priority_extra.name : (issue.priority || 'Normal')}</span>
							</div>
							
							<p class="card-text text-muted taiga-card-description small mb-0">
								${issue.description || ''}
							</p>
							
							<div class="taiga-card-meta">
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted">Ref: #${issue.ref}</small>
									<small class="text-muted">${new Date(issue.created_date).toLocaleDateString()}</small>
								</div>
								
								<div class="mb-1">
									<small class="text-muted d-block text-truncate">
										Assigned: <strong>${assignedTo}</strong>
									</small>
								</div>
								
								<div class="d-flex justify-content-between align-items-center mb-1">
									<small class="text-muted text-truncate">By: ${owner}</small>
									<small class="text-muted">Upd: ${new Date(issue.modified_date).toLocaleDateString()}</small>
								</div>
							</div>
						</div>
						<div class="card-footer taiga-card-actions">
							<a href="isu.php?id=${issue.id}" class="btn btn-sm btn-outline-primary shadow-sm">
								View Details
							</a>
						</div>
					</div>
				</div>
			`;
				});
				html += '</div>';

				$('#issuesContent').html(html);

				// Add click event to checkboxes
				$('.issue-checkbox').on('change', updateSelectionCount);
			}

			// Local filter function removed as filtering is now done via API.

			function updateSelectionCount() {
				const selectedCount = $('#issuesContent input.issue-checkbox:checked').length;
				$('#selectionCount').text(selectedCount);
			}

		});
	</script>

	<?php include __DIR__ . '/app/partials/isu_bulk_create.php'; ?>
	<?php include __DIR__ . '/app/partials/isu_bulk_update.php'; ?>
	<?php include __DIR__ . '/app/partials/isu_bulk_delete.php'; ?>

</body>

</html>
