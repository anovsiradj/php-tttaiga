<!-- Single Create/Update Project Modal -->
<div class="modal fade" id="singleProjectModal" tabindex="-1" aria-labelledby="singleProjectModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="singleProjectModalLabel">Create Project</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="singleProjectForm">
					<input type="hidden" id="singleProjectId">
					<input type="hidden" id="singleProjectVersion">
					<div class="mb-3">
						<label class="form-label">Project Name</label>
						<input type="text" class="form-control" id="singleProjectName" required>
					</div>
					<div class="mb-3">
						<label class="form-label">Description</label>
						<textarea class="form-control" id="singleProjectDescription" rows="4"></textarea>
					</div>
					<div class="mb-3">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" id="singleProjectPrivate">
							<label class="form-check-label" for="singleProjectPrivate">Private Project</label>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitSingleProject">Save</button>
			</div>
		</div>
	</div>
</div>
