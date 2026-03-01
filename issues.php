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
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h1>Issues</h1>
			<div class="d-flex">
				<button class="btn btn-danger me-2" id="bulkDeleteBtn">
					<i class="bi bi-trash"></i> Bulk Delete
				</button>
				<input type="text" class="form-control me-2" id="searchInput" placeholder="Search issues..." style="width: 250px;">
				<select class="form-select me-2" id="projectSelect" style="width: 200px;">
					<option value="">All Projects</option>
				</select>
				<select class="form-select me-2" id="statusSelect" style="width: 150px;">
					<option value="">All Statuses</option>
					<option value="new">New</option>
					<option value="in progress">In Progress</option>
					<option value="ready for test">Ready for test</option>
					<option value="closed">Closed</option>
					<option value="needs info">Needs Info</option>
					<option value="rejected">Rejected</option>
					<option value="postponed">Postponed</option>
				</select>
				<button class="btn btn-outline-secondary" id="refreshBtn">
					<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
						<path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z" />
						<path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z" />
					</svg>
				</button>
			</div>
		</div>

		<div class="filter-section">
			<div class="row">
				<div class="col-md-3">
					<strong>Total Issues:</strong> <span id="totalIssues">0</span>
				</div>
				<div class="col-md-3">
					<strong>Filtered:</strong> <span id="filteredIssues">0</span>
				</div>
				<div class="col-md-3">
					<strong>Selected:</strong> <span id="selectionCount">0</span>
				</div>
				<div class="col-md-3 text-end">
					<button class="btn btn-sm btn-outline-secondary me-2" id="selectAllBtn">Select All</button>
					<button class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn">Clear Selection</button>
				</div>
			</div>
		</div>

		<div id="issuesContent">
			<div class="loading-spinner">
				<div class="spinner-border text-primary" role="status">
					<span class="visually-hidden">Loading issues...</span>
				</div>
			</div>
		</div>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script src="assets/app.js"></script>
	<script src="assets/theme.js"></script>
	<script src="assets/taiga.js"></script>

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
			$('#searchInput').on('input', filterIssues);
			$('#projectSelect').on('change', filterIssues);
			$('#statusSelect').on('change', filterIssues);
			$('#refreshBtn').on('click', function () {
				loadIssues();
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

			function loadIssues() {
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
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (issues) {
						displayIssues(issues);
					},
					error: function (xhr) {
						console.error('Failed to load issues:', xhr);
						$('#issuesContent').html(`
					<div class="alert alert-danger">
						Unable to load issues. Please try again.
					</div>
				`);
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

			function filterIssues() {
				const searchText = $('#searchInput').val().toLowerCase();
				const projectId = $('#projectSelect').val();
				const status = $('#statusSelect').val();

				$.ajax({
					url: apiUrl + '/issues',
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (issues) {
						let filteredIssues = issues;

						// Apply filters
						if (searchText) {
							filteredIssues = filteredIssues.filter(issue =>
								(issue.subject && issue.subject.toLowerCase().includes(searchText)) ||
								(issue.description && issue.description.toLowerCase().includes(searchText)) ||
								(issue.ref && issue.ref.toString().includes(searchText))
							);
						}

						if (projectId) {
							filteredIssues = filteredIssues.filter(issue => issue.project == projectId);
						}

						if (status) {
							filteredIssues = filteredIssues.filter(issue => issue.status && issue.status.toLowerCase() === status);
						}

						$('#filteredIssues').text(filteredIssues.length);
						displayIssues(filteredIssues);
						updateSelectionCount();
					},
					error: function (xhr) {
						console.error('Failed to filter issues:', xhr);
					}
				});
			}

			function updateSelectionCount() {
				const selectedCount = $('#issuesContent input.issue-checkbox:checked').length;
				$('#selectionCount').text(selectedCount);
			}


		});
	</script>

</body>

</html>