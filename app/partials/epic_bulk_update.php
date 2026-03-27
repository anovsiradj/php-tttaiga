<!-- Bulk Update Epic Modal -->
<div class="modal fade" id="bulkUpdateEpicModal" tabindex="-1" aria-labelledby="bulkUpdateEpicModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkUpdateEpicModalLabel">Bulk Update Epics</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="bulkUpdateEpicForm">
					<div class="mb-3">
						<label class="form-label">Select Epics to Update</label>
						<div id="bulkUpdateEpics" class="border p-3" style="max-height: 200px; overflow-y: auto;">
							<div class="text-center text-muted">
								<div class="spinner-border spinner-border-sm" role="status">
									<span class="visually-hidden">Loading epics...</span>
								</div>
								<p class="mt-2 mb-0">Loading epics...</p>
							</div>
						</div>
						<div class="form-text">Select the epics you want to update</div>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label class="form-label">Status</label>
							<select class="form-select" id="bulkUpdateEpicStatus">
								<option value="">No Change</option>
							</select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Priority</label>
							<input type="number" class="form-control" id="bulkUpdateEpicPriority" placeholder="Leave empty for no change">
						</div>
					</div>
					<div class="mb-3 mt-3">
						<label class="form-label">Description (optional)</label>
						<textarea class="form-control" id="bulkUpdateEpicDescription" rows="3" placeholder="Leave empty for no change"></textarea>
					</div>
					<div class="mb-3">
						<label class="form-label">Color (optional)</label>
						<input type="color" class="form-control form-control-color" id="bulkUpdateEpicColor">
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitBulkUpdateEpic">Update Epics</button>
			</div>
		</div>
	</div>
</div>
