<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sprint - Taiga API</title>
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

	<?php
	$backUrl = 'sprints.php';
	$backLabel = 'Back to Sprints';
	$headerId = 'sprintHeaderContent';
	$loadingLabel = 'Loading sprint...';
	include __DIR__ . '/app/partials/item_header.php';
	?>

	<div class="container pb-5">
		<div class="row">
			<div class="col-md-8 mx-auto">
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h5 class="mb-0" id="formTitle">Add New Sprint</h5>
						<div id="statusIndicator"></div>
					</div>
					<div class="card-body">
						<form id="sprintForm">
							<div class="mb-3">
								<label class="form-label" for="projectSelect">Project</label>
								<select class="form-select" id="projectSelect" name="project" required>
									<option value="">Select a project</option>
								</select>
							</div>
							<div class="mb-3">
								<label class="form-label" for="sprintName">Sprint Name</label>
								<input type="text" class="form-control" id="sprintName" name="name" placeholder="e.g. Iteration 1" required>
							</div>
							<div class="row mb-3">
								<div class="col-md-6">
									<label class="form-label" for="startDate">Estimated Start</label>
									<input type="date" class="form-control" id="startDate" name="estimated_start" required>
								</div>
								<div class="col-md-6">
									<label class="form-label" for="finishDate">Estimated Finish</label>
									<input type="date" class="form-control" id="finishDate" name="estimated_finish" required>
								</div>
							</div>
							<div class="mb-3">
								<label class="form-label" for="sprintDescription">Description</label>
								<textarea class="form-control" id="sprintDescription" name="description" rows="5" placeholder="Sprint goals and objectives..."></textarea>
							</div>
							<div class="mb-3 form-check d-none" id="closedToggle">
								<input type="checkbox" class="form-check-input" id="isClosed" name="closed">
								<label class="form-check-label" for="isClosed">Sprint is closed</label>
							</div>
							<div class="d-flex justify-content-end gap-2">
								<button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
								<button type="submit" class="btn btn-primary" id="saveBtn">Create Sprint</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
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

			const urlParams = new URLSearchParams(window.location.search);
			const sprintId = urlParams.get('id');

			// Initialize Select2 for project
			taigaInitRemoteSelect2('#projectSelect', '/projects', {
				placeholder: 'Select a project',
				additionalParams: () => ({ member: JSON.parse(userData).id })
			});

			if (sprintId) {
				// Edit/View mode
				$('#formTitle').text('Edit Sprint');
				$('#saveBtn').text('Update Sprint');
				$('#closedToggle').removeClass('d-none');
				loadSprint(sprintId);
			} else {
				// Create mode
				$('#sprintHeaderContent').html('<h2 class="text-white">Create New Sprint</h2>');
			}

			function loadSprint(id) {
				$.ajax({
					url: apiUrl + '/milestones/' + id,
					type: 'GET',
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					success: function (sprint) {
						$('#sprintHeaderContent').html(`<h2 class="text-white">${sprint.name}</h2>`);
						$('#sprintName').val(sprint.name);
						$('#startDate').val(sprint.estimated_start);
						$('#finishDate').val(sprint.estimated_finish);
						$('#sprintDescription').val(sprint.description);
						$('#isClosed').prop('checked', sprint.closed);
						
						// If Select2 is used, we need to manually set the option if not loaded
						if (sprint.project) {
							const $newOption = new Option(sprint.project_extra?.name || 'Current Project', sprint.project, true, true);
							$('#projectSelect').append($newOption).trigger('change');
						}

						if (sprint.closed) {
							$('#statusIndicator').html('<span class="badge bg-secondary">Closed</span>');
						} else {
							$('#statusIndicator').html('<span class="badge bg-success">Open</span>');
						}
					},
					error: function () {
						alert('Failed to load sprint data');
						window.location.href = 'sprints.php';
					}
				});
			}

			$('#sprintForm').on('submit', function (e) {
				e.preventDefault();
				const formData = {
					project: parseInt($('#projectSelect').val()),
					name: $('#sprintName').val(),
					estimated_start: $('#startDate').val(),
					estimated_finish: $('#finishDate').val(),
					description: $('#sprintDescription').val(),
				};

				if (sprintId) {
					formData.closed = $('#isClosed').is(':checked');
				}

				const method = sprintId ? 'PATCH' : 'POST';
				const url = sprintId ? `${apiUrl}/milestones/${sprintId}` : `${apiUrl}/milestones`;

				const $btn = $('#saveBtn');
				$btn.prop('disabled', true).text('Saving...');

				$.ajax({
					url: url,
					type: method,
					headers: {
						'Authorization': 'Bearer ' + token,
						'Content-Type': 'application/json'
					},
					data: JSON.stringify(formData),
					success: function () {
						alert(sprintId ? 'Sprint updated' : 'Sprint created');
						window.location.href = 'sprints.php';
					},
					error: function (xhr) {
						console.error(xhr);
						alert('Error saving sprint: ' + (xhr.responseJSON?._error_message || 'Unknown error'));
						$btn.prop('disabled', false).text(sprintId ? 'Update Sprint' : 'Create Sprint');
					}
				});
			});
		});
	</script>

</body>

</html>
