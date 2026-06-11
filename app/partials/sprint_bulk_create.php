<!-- Bulk Create Sprint Modal -->
<div class="modal fade" id="bulkCreateSprintModal" tabindex="-1" aria-labelledby="bulkCreateSprintModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="bulkCreateSprintModalLabel">Bulk Create Sprints</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label" for="bulkCreateSprintProject">Project</label>
					<select class="form-select" id="bulkCreateSprintProject" required></select>
				</div>
				<div class="row">
					<div class="col-md-6 mb-3">
						<label class="form-label" for="bulkCreateSprintStart">Default Start</label>
						<input type="date" class="form-control" id="bulkCreateSprintStart">
					</div>
					<div class="col-md-6 mb-3">
						<label class="form-label" for="bulkCreateSprintFinish">Default Finish</label>
						<input type="date" class="form-control" id="bulkCreateSprintFinish">
					</div>
				</div>
				<label class="form-label" for="bulkCreateSprintText">Sprints</label>
				<textarea class="form-control" id="bulkCreateSprintText" rows="8" placeholder="Sprint Name|Description optional"></textarea>
				<div class="form-text">One sprint per line. Description is optional.</div>
				<div id="bulkCreateSprintPreview" class="border rounded p-2 bg-body-tertiary mt-3 d-none"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="previewBulkCreateSprints">Preview</button>
				<button type="button" class="btn btn-success" id="submitBulkCreateSprints">Create Sprints</button>
			</div>
		</div>
	</div>
</div>
