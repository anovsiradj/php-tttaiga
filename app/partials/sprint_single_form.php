<!-- Single Create/Update Sprint Modal -->
<div class="modal fade" id="singleSprintModal" tabindex="-1" aria-labelledby="singleSprintModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="singleSprintModalLabel">Create Sprint</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="singleSprintForm">
					<input type="hidden" id="singleSprintId">
					<input type="hidden" id="singleSprintVersion">
					<div class="mb-3">
						<label class="form-label">Project</label>
						<select class="form-select" id="singleSprintProject" required></select>
					</div>
					<div class="mb-3">
						<label class="form-label">Name</label>
						<input type="text" class="form-control" id="singleSprintName" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Start Date</label>
						<input type="date" class="form-control" id="singleSprintStart">
					</div>
					<div class="mb-3">
						<label class="form-label">End Date</label>
						<input type="date" class="form-control" id="singleSprintEnd">
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitSingleSprint">Save</button>
			</div>
		</div>
	</div>
</div>
