<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Epic Details - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>
	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>


	<?php
	$backUrl = 'epics.php';
	$backLabel = 'Back to Epics';
	$headerId = 'epicHeaderContent';
	$loadingLabel = 'Loading epic...';
	include __DIR__ . '/app/partials/item_header.php';
	?>

	<div class="container">
		<div class="row">
			<div class="col-md-8">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Epic Details</h5>
					</div>
					<div class="card-body" id="epicDetailsContent">
						<div class="loading-spinner">
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
					<div class="card-body" id="epicDescriptionContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading description...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">User Stories</h5>
					</div>
					<div class="card-body" id="epicStoriesContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading user stories...</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-4">
				<div class="card mb-4">
					<div class="card-header">
						<h5 class="mb-0">Epic Stats</h5>
					</div>
					<div class="card-body" id="epicStatsContent">
						<div class="loading-spinner">
							<div class="spinner-border" role="status">
								<span class="visually-hidden">Loading stats...</span>
							</div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header">
						<h5 class="mb-0">Metadata</h5>
					</div>
					<div class="card-body" id="epicMetadataContent">
						<div class="loading-spinner">
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

			// Get epic ID from URL
			const urlParams = new URLSearchParams(window.location.search);
			const epicId = urlParams.get('id');

			if (!epicId) {
				window.location.href = 'epics.php';
				return;
			}

			// Load epic data
			loadEpicData(epicId);


			function loadEpicData(epicId) {
				// Load epic basic info
				$.ajax({
					url: apiUrl + '/epics/' + epicId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (epic) {
						displayEpicHeader(epic);
						displayEpicDetails(epic);
						displayEpicDescription(epic);
						displayEpicMetadata(epic);

						// Load additional data
						loadEpicStats(epicId);
						loadEpicUserStories(epicId);
					},
					error: function (xhr) {
						console.error('Failed to load epic:', xhr);
						$('#epicHeaderContent').html(`
					<div class="alert alert-danger">
						Failed to load epic. Please check if you have access to this epic.
						<a href="epics.php" class="btn btn-sm btn-outline-danger ms-2">Back to Epics</a>
					</div>
				`);
					}
				});
			}

			function loadEpicStats(epicId) {
				// Load epic statistics
				const statsHtml = `
			<div class="row text-center">
				<div class="col-6">
					<div class="stats-card">
						<div class="stat-number">0</div>
						<small class="text-muted">User Stories</small>
					</div>
				</div>
				<div class="col-6">
					<div class="stats-card">
						<div class="stat-number">0</div>
						<small class="text-muted">Points</small>
					</div>
				</div>
			</div>
		`;
				$('#epicStatsContent').html(statsHtml);
			}

			function loadEpicUserStories(epicId) {
				// Load user stories for this epic
				$.ajax({
					url: apiUrl + '/userstories?epic=' + epicId,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (stories) {
						displayEpicUserStories(stories);
					},
					error: function (xhr) {
						console.error('Failed to load user stories:', xhr);
						$('#epicStoriesContent').html(`
					<div class="alert alert-warning">
						Unable to load user stories for this epic.
					</div>
				`);
					}
				});
			}

			function displayEpicHeader(epic) {
				const statusInfo = taigaGetStatusInfo(epic);
				const statusBadge = taigaRenderStatusBadge(statusInfo);
				const headerHtml = `
			<h1 class="display-4 mb-2">${epic.subject || 'Untitled Epic'}</h1>
			<p class="lead mb-0">Ref: #${epic.ref}</p>
			<div class="mt-2 text-white">
				${statusBadge}
			</div>
		`;
				$('#epicHeaderContent').html(headerHtml);
			}

			function displayEpicDetails(epic) {
				const detailsHtml = `
			<div class="row">
				<div class="col-6">
					<strong>Project:</strong><br>
					<span id="projectName">Loading...</span>
				</div>
				<div class="col-6">
					<strong>Color:</strong><br>
					<span style="color: ${epic.color || '#666666'};">${epic.color || 'Default'}</span>
				</div>
				<div class="col-6 mt-3">
					<strong>Created:</strong><br>
					${new Date(epic.created_date).toLocaleDateString()}
				</div>
				<div class="col-6 mt-3">
					<strong>Modified:</strong><br>
					${new Date(epic.modified_date).toLocaleDateString()}
				</div>
			</div>
		`;
				$('#epicDetailsContent').html(detailsHtml);

				// Load project name
				if (epic.project) {
					$.ajax({
						url: apiUrl + '/projects/' + epic.project,
						type: 'GET',
						headers: {
							'Authorization': 'Bearer ' + token,
							'Content-Type': 'application/json'
						},
						success: function (project) {
							$('#projectName').text(project.name);
						},
						error: function () {
							$('#projectName').text('Unknown Project');
						}
					});
				}
			}

			function displayEpicDescription(epic) {
				const descriptionHtml = epic.description ?
					`<p class="card-text">${epic.description}</p>` :
					`<p class="text-muted">No description provided for this epic.</p>`;

				$('#epicDescriptionContent').html(descriptionHtml);
			}

			function displayEpicMetadata(epic) {
				const metadataHtml = `
			<div class="small">
				<div class="mb-2">
					<strong>Epic ID:</strong><br>
					<code>${epic.id}</code>
				</div>
				<div class="mb-2">
					<strong>Version:</strong><br>
					${epic.version || 'N/A'}
				</div>
				<div class="mb-2">
					<strong>Project ID:</strong><br>
					<code>${epic.project || 'N/A'}</code>
				</div>
				<div class="mb-2">
					<strong>Owner:</strong><br>
					${epic.owner || 'N/A'}
				</div>
				<div>
					<strong>Assigned To:</strong><br>
					${epic.assigned_to || 'Unassigned'}
				</div>
			</div>
		`;
				$('#epicMetadataContent').html(metadataHtml);
			}

			function displayEpicUserStories(stories) {
				if (stories.length === 0) {
					$('#epicStoriesContent').html(`
				<p class="text-muted">No user stories found for this epic.</p>
			`);
					return;
				}

				let html = '';
				stories.forEach(story => {
					const statusInfo = taigaGetStatusInfo(story);
					const statusBadge = taigaRenderStatusBadge(statusInfo);
					html += `
				<div class="card user-story-card mb-2">
					<div class="card-body py-2">
						<div class="d-flex justify-content-between align-items-start">
							<h6 class="card-title mb-1">${story.subject || 'Untitled Story'}</h6>
							${statusBadge}
						</div>
						<p class="card-text text-muted small mb-1">
							Ref: #${story.ref}
						</p>
						<p class="card-text small text-muted mb-0">
							${story.description ? story.description.substring(0, 100) + '...' : 'No description'}
						</p>
					</div>
				</div>
			`;
				});

				$('#epicStoriesContent').html(html);
			}


		});
	</script>

</body>

</html>