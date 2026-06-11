<!-- Bulk Create Project Modal -->
<div class="modal fade" id="bulkCreateProjectModal" tabindex="-1" aria-labelledby="bulkCreateProjectModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkCreateProjectModalLabel">Bulk Create Projects</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<label class="form-label" for="bulkCreateProjectText">Projects</label>
				<textarea class="form-control" id="bulkCreateProjectText" rows="8" placeholder="Project Name|Description optional"></textarea>
				<div class="form-text">One project per line. Description is optional.</div>
				<div id="bulkCreateProjectPreview" class="border rounded p-2 bg-body-tertiary mt-3 d-none"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="previewBulkCreateProjects">Preview</button>
				<button type="button" class="btn btn-success" id="submitBulkCreateProjects">Create Projects</button>
			</div>
		</div>
	</div>
</div>
