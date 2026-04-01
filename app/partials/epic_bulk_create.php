<!-- Bulk Create Epik Modal -->
<div class="modal fade" id="bulkCreateEpicModal" tabindex="-1" aria-labelledby="bulkCreateEpicModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkCreateEpicModalLabel">Bulk Create Epiks</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="bulkCreateEpicForm">
					<div class="mb-3">
						<label class="form-label">Epiks (one per line)</label>
						<textarea class="form-control" id="bulkCreateEpicText" rows="10" placeholder="Enter epiks, one per line. Format: Subject|Description (optional)|Status (optional)" required></textarea>
						<small class="form-text text-muted">Example: Authentication Module|Implement login and registration|new</small>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label class="form-label">Project</label>
							<select class="form-select" id="bulkCreateEpicProject" required>
								<option value="">Select Project</option>
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Default Status</label>
							<select class="form-select" id="bulkCreateEpicStatus">
								<option value="">Select Status</option>
							</select>
						</div>
					</div>
					<div class="row mt-3">
						<div class="col-md-6">
							<label class="form-label">Color (optional)</label>
							<input type="color" class="form-control form-control-color" id="bulkCreateEpicColor" value="#fd7e14">
						</div>
						<div class="col-md-6">
							<label class="form-label">Priority (optional)</label>
							<input type="number" class="form-control" id="bulkCreateEpicPriority" min="1" max="100" value="10">
						</div>
					</div>
				</form>
				<div id="bulkCreateEpicPreview" class="mt-3 d-none">
					<h6>Preview:</h6>
					<div class="border rounded p-2 bg-body-tertiary" id="epicPreviewContent"></div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="previewBulkCreateEpic">Preview</button>
				<button type="button" class="btn btn-success" id="submitBulkCreateEpic">Create Epiks</button>
			</div>
		</div>
	</div>
</div>
