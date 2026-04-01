<!-- Bulk Create Usor Modal -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1" aria-labelledby="bulkCreateModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkCreateModalLabel">Bulk Create Usors</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="bulkCreateForm">
					<div id="bulkCreateSearchContext" class="alert alert-info py-2 d-none">
						<i class="bi bi-search me-2"></i>
						Active Search: <strong id="activeSearchQuery"></strong>
						<div class="form-check d-inline-block ms-3">
							<input class="form-check-input" type="checkbox" id="prependSearchCheck" checked>
							<label class="form-check-label small" for="prependSearchCheck">Prepend to subjects</label>
						</div>
					</div>
					<div class="mb-3">
						<label class="form-label">Usors (one per line)</label>
						<textarea class="form-control" id="bulkCreateText" rows="10" placeholder="Enter usors, one per line. Format: Subject|Description (optional)|Status (optional)" required></textarea>
						<small class="form-text text-muted">Example: Login page|Create login form with validation|new</small>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label class="form-label">Project</label>
							<select class="form-select" id="bulkCreateProject" required>
								<option value="">Select Project</option>
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Epik (optional)</label>
							<select class="form-select" id="bulkCreateEpic">
								<option value="">Select Epik</option>
							</select>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col-md-4">
							<label class="form-label">Default Status</label>
							<select class="form-select" id="bulkCreateStatus"></select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Assigned To</label>
							<select class="form-select" id="bulkCreateAssignee"></select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Priority (optional)</label>
							<input type="number" class="form-control" id="bulkCreatePriority" min="1" max="100" value="10">
						</div>
					</div>
				</form>
				<div id="bulkCreatePreview" class="mt-3 d-none">
					<h6>Preview:</h6>
					<div class="border rounded p-2 bg-body-tertiary" id="previewContent"></div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="previewBulkCreate">Preview</button>
				<button type="button" class="btn btn-success" id="submitBulkCreate">Create Usors</button>
			</div>
		</div>
	</div>
</div>
