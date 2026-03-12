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
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container mt-4">
		<?php
		$pageTitle = 'Issues';
		$additionalControls = '<button class="btn btn-danger me-2" id="bulkDeleteBtn"><i class="bi bi-trash"></i> Bulk Delete</button>';
		$searchPlaceholder = 'Search issues...';
		$statusOptions = [
			'new' => 'New',
			'in progress' => 'In Progress',
			'ready for test' => 'Ready for test',
			'closed' => 'Closed',
			'needs info' => 'Needs Info',
			'rejected' => 'Rejected',
			'postponed' => 'Postponed'
		];
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

		<nav aria-label="Issues pagination" class="mt-4">
			<ul class="pagination justify-content-center" id="issuesPagination">
				<!-- Pagination items will be injected here -->
			</ul>
		</nav>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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

			// Load issues and filters
			loadIssues();
			taigaLoadProjects(apiUrl, token);

			// Event listeners
			let searchTimeout;
			$('#searchInput').on('input', function () {
				clearTimeout(searchTimeout);
				searchTimeout = setTimeout(function () {
					loadIssues(1);
				}, 500);
			});
			$('#projectSelect').on('change', function() { loadIssues(1); });
			$('#statusSelect').on('change', function() { loadIssues(1); });
			$('#refreshBtn').on('click', function () {
				loadIssues(1);
				taigaLoadProjects(apiUrl, token);
			});

			$('#selectAllBtn').on('click', function () {
				$('#issuesContent input.issue-checkbox').prop('checked', true);
				updateSelectionCount();
			});

			$('#clearSelectionBtn').on('click', function () {
				$('#issuesContent input.issue-checkbox').prop('checked', false);
				updateSelectionCount();
			});


			// Bulk Delete functionality
			$('#bulkDeleteBtn').on('click', function () {
				const selectedIssues = [];
				$('#issuesContent input.issue-checkbox:checked').each(function () {
					selectedIssues.push($(this).val());
				});

				if (selectedIssues.length === 0) {
					alert('Please select at least one issue to delete');
					return;
				}

				if (!confirm('Are you sure you want to delete ' + selectedIssues.length + ' issues? This action cannot be undone.')) {
					return;
				}

				// Show absolute loading state
				const btn = $(this);
				const originalText = btn.html();
				btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...').prop('disabled', true);

				// Delete issues sequentially
				const promises = selectedIssues.map(issueId => {
					return $.ajax({
						url: apiUrl + '/issues/' + issueId,
						type: 'DELETE',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
						}
					});
				});

				// Execute all promises
				Promise.all(promises)
					.then(() => {
						alert(`Successfully deleted ${selectedIssues.length} issues!`);
						loadIssues(); // Reload issues list
						$('#selectionCount').text('0');
					})
					.catch(error => {
						console.error('Failed to delete issues:', error);
						alert('Failed to delete some issues. Please check the console for details.');
					})
					.finally(() => {
						btn.html(originalText).prop('disabled', false);
					});
			});

			function loadIssues(page = 1) {
				const searchTerm = $('#searchInput').val().trim();
				const projectId = $('#projectSelect').val();
				const status = $('#statusSelect').val();

				const params = { page: page };
				
				if (searchTerm) {
					params.q = searchTerm;
				}
				if (projectId) {
					params.project = projectId;
				}
				if (status) {
					params.status = status;
				}
				
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
					const statusClass = taigaGetIssueStatusClass(issue.status);
					const createdDate = issue.created_date ? new Date(issue.created_date).toLocaleDateString() : 'Unknown';

					html += `
				<div class="col-md-6 col-lg-4 mb-3">
					<div class="card issue-card" data-issue-id="${issue.id}">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-start mb-2">
								<div class="form-check">
									<input class="form-check-input issue-checkbox" type="checkbox" value="${issue.id}" id="issue-${issue.id}">
									<label class="form-check-label" for="issue-${issue.id}"></label>
								</div>
								<span class="badge status-badge bg-${statusClass}">
									${issue.status || 'Unknown'}
								</span>
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


		});
	</script>

</body>

</html>