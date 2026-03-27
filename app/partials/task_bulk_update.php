<!-- Bulk Update Task Modal -->
<div class="modal fade" id="bulkUpdateTaskModal" tabindex="-1" aria-labelledby="bulkUpdateTaskModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkUpdateTaskModalLabel">Bulk Update Tasks</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label small mb-1 text-muted">Active Project</label>
					<input type="text" class="form-control form-control-sm bg-light" id="bulkUpdateTaskProjectDisplay" readonly style="max-width: 300px;">
				</div>
				<div class="mb-3">
					<label class="form-label">Select Tasks to Update</label>
					<div id="bulkUpdateTaskList" class="border p-3" style="max-height: 200px; overflow-y: auto;">
						<div class="text-center text-muted">
							<div class="spinner-border spinner-border-sm" role="status">
								<span class="visually-hidden">Loading tasks...</span>
							</div>
							<p class="mt-2 mb-0">Loading tasks...</p>
						</div>
					</div>
					<div class="form-text">Select the tasks you want to update</div>
				</div>

				<div class="mb-3">
					<label for="bulkUpdateTaskStatus" class="form-label">Update Status</label>
					<select class="form-select" id="bulkUpdateTaskStatus">
						<option value="">No Change</option>
					</select>
					<div class="form-text">Leave as "No Change" to keep current status</div>
				</div>

				<div class="mb-3">
					<label for="bulkUpdateTaskAssignee" class="form-label">Assign To</label>
					<select class="form-select" id="bulkUpdateTaskAssignee">
						<option value="">No Change</option>
					</select>
					<div class="form-text">Leave as "No Change" to keep current assignment</div>
				</div>

				<div class="alert alert-warning">
					<h6 class="alert-heading">⚠️ Warning</h6>
					<p class="mb-0">This action will update all selected tasks with the chosen settings. This cannot be undone.</p>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitBulkTaskUpdate">Update Tasks</button>
			</div>
		</div>
	</div>
</div>
