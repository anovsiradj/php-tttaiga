<!-- Single Create/Update Task Modal -->
<div class="modal fade" id="singleTaskModal" tabindex="-1" aria-labelledby="singleTaskModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="singleTaskModalLabel">Create Task</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="singleTaskForm">
					<input type="hidden" id="singleTaskId">
					<input type="hidden" id="singleTaskVersion">
					<div class="mb-3">
						<label class="form-label">Project</label>
						<select class="form-select" id="singleTaskProject" required></select>
					</div>
					<div class="mb-3">
						<label class="form-label">User Story</label>
						<select class="form-select" id="singleTaskUsor"></select>
					</div>
					<div class="mb-3">
						<label class="form-label">Sprint</label>
						<select class="form-select" id="singleTaskSprint"></select>
					</div>
					<div class="mb-3">
						<label class="form-label">Subject</label>
						<input type="text" class="form-control" id="singleTaskSubject" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Description</label>
						<textarea class="form-control" id="singleTaskDescription" rows="4"></textarea>
					</div>
					<div class="row">
						<div class="col-md-6">
							<label class="form-label">Status</label>
							<select class="form-select" id="singleTaskStatus"></select>
						</div>
						<div class="col-md-6">
							<label class="form-label">Assigned To</label>
							<select class="form-select" id="singleTaskAssignee"></select>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitSingleTask">Save</button>
			</div>
		</div>
	</div>
</div>
