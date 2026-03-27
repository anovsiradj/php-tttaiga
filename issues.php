<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Issues - Taiga API</title>
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
		$pageTitle = 'Issues';
		$statusType = 'issue';
		$searchPlaceholder = 'Search issues...';
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
			<li><a class="dropdown-item" href="#" id="bulkUpdateBtn"><i class="bi bi-pencil-square me-2"></i> Bulk Update</a></li>
			<li><a class="dropdown-item text-danger" href="#" id="bulkDeleteBtn"><i class="bi bi-trash me-2"></i> Bulk Delete</a></li>
		';
		include __DIR__ . '/app/partials/list_header.php';

		$totalLabel = 'Total Issues';
		$totalId = 'totalIssues';
		$filteredId = 'filteredIssues';
		$selectionCountId = 'selectionCount';
		include __DIR__ . '/app/partials/list_status.php';
		?>

		<div id="issuesContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading issues...</span>
				</div>
			</div>
		</div>

		<nav aria-label="Issues pagination" class="pagination-container">
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

			// Load issues list
			loadIssues();

			// Initial filter binding
			taigaBindFilters(loadIssues);

			taigaBindSelectionLogic('issue-checkbox', function(checkedCount) {
				const filtered = parseInt($('#filteredIssues').text()) || 0;
				const total = parseInt($('#totalIssues').text()) || 0;
				taigaUpdateSelectionUI(total, filtered, checkedCount, 'totalIssues', 'filteredIssues', 'selectionCount');
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
					alert('Please select at least one issue to delete');
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
			$('#confirmBulkDeleteIssues').on('click', function() {
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
						alert(`Successfully deleted ${successCount} issues!`);
					} else {
						alert(`Deleted ${successCount} issues, but ${errorCount} failed.`);
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
					alert('Please select at least one issue to update');
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
						alert(`Successfully updated ${successCount} issues!`);
					} else {
						alert(`Updated ${successCount} issues, but ${errorCount} failed.`);
					}
					loadIssues();
					$('#selectionCount').text('0');
				});
			});

			function loadIssues(page = 1) {
				const params = {
					...taigaGetFilterParams(),
					page: page
				};
				
				$('#issuesContent').html(`
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading issues...</span>
				</div>
			</div>
		`);

				$.ajax({
					url: apiUrl + '/issues',
					type: 'GET',
					data: params,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (issues, status, xhr) {
						displayIssues(issues);
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


			function displayIssues(issues) {
				$('#totalIssues').text(issues.length);
				$('#filteredIssues').text(issues.length);

				if (issues.length === 0) {
					$('#issuesContent').html(`
				<div class="alert alert-info">
					No issues found.
				</div>
			`);
					return;
				}

				let html = '<div class="row">';
				issues.forEach(issue => {
					const statusInfo = taigaGetStatusInfo(issue);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					const createdDate = issue.created_date ? new Date(issue.created_date).toLocaleDateString() : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4 mb-3">
					<div class="card issue-card h-100" data-issue-id="${issue.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input issue-checkbox" type="checkbox" value="${issue.id}" data-version="${issue.version}" id="issue-${issue.id}">
									<label class="form-check-label" for="issue-${issue.id}"></label>
								</div>
								${statusBadge}
							</div>
							
							<h6 class="card-title mb-2">${issue.subject || 'Untitled Issue'}</h6>
							
							<div class="d-flex justify-content-between align-items-center mb-2">
								<span class="badge bg-secondary">${issue.type || 'Bug'}</span>
								<span class="badge bg-danger">${issue.severity || 'Normal'}</span>
								<span class="badge bg-info">${issue.priority || 'Normal'}</span>
							</div>
							
							${issue.description ? `
								<p class="card-text text-muted small mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
									${issue.description}
								</p>
							` : ''}
							
							<div class="d-flex justify-content-between align-items-center">
								<small class="text-muted">Ref: #${issue.ref}</small>
								<small class="text-muted">${createdDate}</small>
							</div>
							
							${issue.project ? `
								<small class="text-muted d-block mt-2">Project ID: ${issue.project}</small>
							` : ''}
							
							<div class="mt-3">
								<a href="issue.php?id=${issue.id}" class="btn btn-sm btn-outline-primary w-100">
									View Issue Details
								</a>
							</div>
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

			// Bulk Delete Logic
			$('#issueBulkDeleteModal').on('show.bs.modal', function () {
				const selectedIssues = [];
				$('.issue-checkbox:checked').each(function () {
					const title = $(this).closest('.card-body').find('.card-title').text();
					selectedIssues.push(title);
				});
				$('#selectedIssuesList').html(selectedIssues.map(i => `<div>${i}</div>`).join(''));
			});

			$('#confirmBulkDeleteIssues').on('click', function () {
				const selectedIssues = [];
				$('.issue-checkbox:checked').each(function () {
					selectedIssues.push({
						id: $(this).val(),
						version: $(this).data('version')
					});
				});

				if (selectedIssues.length === 0) {
					alert('Please select at least one issue to delete');
					return;
				}

				const $btn = $(this);
				$btn.prop('disabled', true).text('Deleting...');

				taigaExecuteBulk('/issues/', selectedIssues, 'DELETE', null, (successCount, errorCount) => {
					$btn.prop('disabled', false).text('Delete Issues');
					if (errorCount === 0) {
						alert(`Successfully deleted ${successCount} issues!`);
						$('#issueBulkDeleteModal').modal('hide');
						loadIssues();
					} else {
						alert(`Deleted ${successCount} issues, but ${errorCount} failed.`);
					}
				});
			});
		});
	</script>

	<?php include __DIR__ . '/app/partials/issue_bulk_update.php'; ?>
	<?php include __DIR__ . '/app/partials/issue_bulk_delete.php'; ?>

</body>

</html>