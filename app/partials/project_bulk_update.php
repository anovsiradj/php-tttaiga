<!-- Bulk Update Project Modal -->
<div class="modal fade" id="bulkUpdateProjectModal" tabindex="-1" aria-labelledby="bulkUpdateProjectModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkUpdateProjectModalLabel">Bulk Update Projects</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="bulkUpdateProjectForm">
					<div class="mb-3">
						<label class="form-label">Name Prefix</label>
						<div class="input-group">
							<span class="input-group-text">[</span>
							<input type="text" class="form-control" id="projectPrefixInput" placeholder="ARCHIVED">
							<span class="input-group-text">]</span>
						</div>
						<div class="form-text">Result will be <code>[PREFIX] Current Name</code></div>
					</div>
					<div class="alert alert-info">
						This will update <span id="selectedProjectsCountLabel">0</span> selected projects.
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="submitBulkProjectUpdate">Apply Prefix</button>
			</div>
		</div>
	</div>
</div>
