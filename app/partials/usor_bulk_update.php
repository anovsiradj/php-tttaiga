<!-- Bulk Update Usor Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-labelledby="bulkUpdateModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkUpdateModalLabel">Bulk Update Usors</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="bulkUpdateForm">
					<div class="mb-3">
						<label class="form-label">Select Usors to Update</label>
						<div id="bulkUpdateUsors" class="border p-3" style="max-height: 200px; overflow-y: auto;">
							<div class="text-center text-muted">
								<div class="spinner-border spinner-border-sm" role="status">
									<span class="visually-hidden">Loading usors...</span>
								</div>
								<p class="mt-2 mb-0">Loading usors...</p>
							</div>
						</div>
						<div class="form-text">Select the usors you want to update</div>
					</div>
					<div class="row">
						<div class="col-md-4">
							<label class="form-label">Status</label>
							<select class="form-select" id="bulkUpdateStatus">
								<option value="">No Change</option>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Assigned To</label>
							<select class="form-select" id="bulkUpdateAssignee">
								<option value="">No Change</option>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Priority</label>
							<input type="number" class="form-control" id="bulkUpdatePriority" placeholder="No change">
						</div>
					</div>
					<div class="mb-3 mt-3">
						<label class="form-label">Description (optional)</label>
						<textarea class="form-control" id="bulkUpdateDescription" rows="3" placeholder="Leave empty for no change"></textarea>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitBulkUpdate">Update Usors</button>
			</div>
		</div>
	</div>
</div>
