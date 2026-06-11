<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<?php include __DIR__ . '/app/layouts/main_head.php'; ?>
</head>

<body class="item-page">
	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<?php
	$backUrl = 'isus.php';
	$backLabel = 'Back to Isus';
	$headerId = 'issueHeaderContent';
	$loadingLabel = 'Loading isu...';
	include __DIR__ . '/app/partials/item_header.php';
	?>

	<div class="container pb-5">
		<div class="row">
			<div class="col-md-8">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Isu Details</h5>
					</div>
					<div class="card-body" id="issueDetailsContent">
						<div class="loading-spinner text-center p-3">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading details...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Description</h5>
					</div>
					<div class="card-body" id="issueDescriptionContent">
						<div class="loading-spinner text-center p-3">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading description...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Komentar</h5>
					</div>
					<div class="card-body" id="issueCommentsContent">
						<div class="loading-spinner text-center p-3">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading comments...</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Metadata</h5>
					</div>
					<div class="card-body" id="issueMetadataContent">
						<div class="loading-spinner text-center p-3">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading metadata...</span>
							</div>
						</div>
					</div>
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

			// Get issue ID from URL
			const urlParams = new URLSearchParams(window.location.search);
			const issueId = urlParams.get('id');

			if (!issueId) {
				window.location.href = 'isus.php';
				return;
			}

			taigaLoadComments('#issueCommentsContent', 'issue', issueId, apiUrl, token);

			// Load issue data
			loadIssueData(issueId);

			function loadIssueData(issueId) {
				$.ajax({
					url: 'api.php/issues/' + issueId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json',
						'X-Taiga-Api-Url': apiUrl
					},
					success: function (issue) {
						displayIssueHeader(issue);
						displayIssueDetails(issue);
						displayIssueDescription(issue);
						displayIssueMetadata(issue);
					},
					error: function (xhr) {
						console.error('Failed to load isu:', xhr);
						$('#issueHeaderContent').html(`
					<div class="alert alert-danger">
						Failed to load isu. It might have been deleted or you don't have access.
						<a href="isus.php" class="btn btn-sm btn-outline-light ms-2">Back to Isus</a>
					</div>
				`);
						$('#issueDetailsContent, #issueDescriptionContent, #issueMetadataContent').html('');
					}
				});
			}

			function displayIssueHeader(issue) {
				const statusInfo = taigaGetStatusInfo(issue);
				const statusBadge = taigaRenderStatusBadge(statusInfo);
				const headerHtml = `
			<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between">
				<div class="me-3">
					<h1 class="display-4 mb-2 text-white">${issue.subject || 'Untitled Isu'}</h1>
					<p class="lead mb-0 text-white-50">Ref: #${issue.ref}</p>
					<div class="mt-2">
						${statusBadge}
					</div>
				</div>
				<div class="mt-3 mt-md-0">
					<a class="btn btn-light btn-sm d-none" id="issueTaigaLinkBtn" href="#" target="_blank" rel="noopener">Open in Taiga</a>
				</div>
			</div>
		`;
				$('#issueHeaderContent').html(headerHtml);

				const taigaLink = taigaItemPermalink('issue', issue, apiUrl);
				if (taigaLink) {
					$('#issueTaigaLinkBtn').removeClass('d-none').attr('href', taigaLink);
				} else if (issue.project) {
					taigaGetProjectSlug(issue.project, apiUrl, token).then(function (projectSlug) {
						const link = taigaItemPermalink('issue', issue, apiUrl, { projectSlug: projectSlug });
						if (link) {
							$('#issueTaigaLinkBtn').removeClass('d-none').attr('href', link);
						}
					});
				}
			}

			function displayIssueDetails(issue) {
				const detailsHtml = `
			<div class="row">
				<div class="col-6">
					<strong>Project:</strong><br>
					<span id="projectName">Loading...</span>
				</div>
				<div class="col-6">
					<strong>Type:</strong><br>
					<span>${issue.type || 'N/A'}</span>
				</div>
				<div class="col-6 mt-3">
					<strong>Severity:</strong><br>
					<span>${issue.severity || 'N/A'}</span>
				</div>
				<div class="col-6 mt-3">
					<strong>Priority:</strong><br>
					<span>${issue.priority || 'N/A'}</span>
				</div>
				<div class="col-6 mt-3">
					<strong>Created:</strong><br>
					${new Date(issue.created_date).toLocaleString()}
				</div>
				<div class="col-6 mt-3">
					<strong>Modified:</strong><br>
					${new Date(issue.modified_date).toLocaleString()}
				</div>
			</div>
		`;
				$('#issueDetailsContent').html(detailsHtml);

				// Load project name
				if (issue.project) {
					$.ajax({
						url: 'api.php/projects/' + issue.project,
						type: 'GET',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json',
							'X-Taiga-Api-Url': apiUrl
						},
						success: function (project) {
							$('#projectName').text(project.name);
						},
						error: function () {
							$('#projectName').text('Project ID: ' + issue.project);
						}
					});
				}
			}

			function displayIssueDescription(issue) {
				const html = taigaRenderMarkdown(issue.description || '');
				$('#issueDescriptionContent').html(html);
			}

			function displayIssueMetadata(issue) {
				const metadataHtml = `
			<div class="small">
				<div class="mb-2">
					<strong>Isu ID:</strong><br>
					<code>${issue.id}</code>
				</div>
				<div class="mb-2">
					<strong>Version:</strong><br>
					${issue.version || '1'}
				</div>
				<div class="mb-2">
					<strong>Owner:</strong><br>
					${issue.owner || 'N/A'}
				</div>
				<div>
					<strong>Assigned To:</strong><br>
					${issue.assigned_to || 'Unassigned'}
				</div>
			</div>
		`;
				$('#issueMetadataContent').html(metadataHtml);
			}


		});
	</script>
</body>

</html>
