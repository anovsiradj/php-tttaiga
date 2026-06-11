<!-- Bulk Update Isu Modal -->
<div class="modal fade" id="issueBulkUpdateModal" tabindex="-1" aria-labelledby="issueBulkUpdateModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="issueBulkUpdateModalLabel">Bulk Update Isus</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label" for="bulkUpdateIssueProject">Project for Options</label>
					<select class="form-select" id="bulkUpdateIssueProject">
						<option value="">Select project to load statuses and members</option>
					</select>
					<div class="form-text">Autofilled from the active filter when available. You can still change it here.</div>
				</div>
				<div class="mb-3">
					<label class="form-label">Selected Isus to Update</label>
					<div id="bulkUpdateIssueList" class="border p-3 rounded bg-light" style="max-height: 150px; overflow-y: auto;">
						<!-- Selected isus will be listed here -->
					</div>
				</div>

				<div class="row">
					<div class="col-md-6">
						<div class="mb-3">
							<label for="bulkUpdateIssueStatus" class="form-label">Status</label>
							<select class="form-select" id="bulkUpdateIssueStatus">
								<option value="">No Change</option>
							</select>
						</div>
					</div>
					<div class="col-md-6">
						<div class="mb-3">
							<label for="bulkUpdateIssueAssignee" class="form-label">Assign To</label>
							<select class="form-select" id="bulkUpdateIssueAssignee">
								<option value="">No Change</option>
							</select>
						</div>
					</div>
				</div>

				<div class="alert alert-warning">
					<i class="bi bi-exclamation-triangle-fill me-2"></i>
					This will apply changes to all selected isus.
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitBulkIssueUpdate">Update Isus</button>
			</div>
		</div>
	</div>
</div>
