<!-- Single Create/Update Issue Modal -->
<div class="modal fade" id="singleIsuModal" tabindex="-1" aria-labelledby="singleIsuModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="singleIsuModalLabel">Create Issue</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="singleIsuForm">
					<input type="hidden" id="singleIsuId">
					<input type="hidden" id="singleIsuVersion">
					<div class="mb-3">
						<label class="form-label">Project</label>
						<select class="form-select" id="singleIsuProject" required></select>
					</div>
					<div class="mb-3">
						<label class="form-label">Subject</label>
						<input type="text" class="form-control" id="singleIsuSubject" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Description</label>
						<textarea class="form-control" id="singleIsuDescription" rows="4"></textarea>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label class="form-label">Status</label>
							<select class="form-select" id="singleIsuStatus"></select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Assigned To</label>
							<select class="form-select" id="singleIsuAssignee"></select>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col-md-4">
							<label class="form-label">Type</label>
							<select class="form-select" id="singleIsuType"></select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Priority</label>
							<select class="form-select" id="singleIsuPriority"></select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Severity</label>
							<select class="form-select" id="singleIsuSeverity"></select>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitSingleIsu">Save</button>
			</div>
		</div>
	</div>
</div>
