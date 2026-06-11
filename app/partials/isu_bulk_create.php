<!-- Bulk Create Isu Modal -->
<div class="modal fade" id="issueBulkCreateModal" tabindex="-1" aria-labelledby="issueBulkCreateModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="issueBulkCreateModalLabel">Bulk Create Isus</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label" for="bulkCreateIssueProject">Project</label>
					<select class="form-select" id="bulkCreateIssueProject" required></select>
				</div>
				<div class="row">
					<div class="col-md-6 mb-3">
						<label class="form-label" for="bulkCreateIssueStatus">Default Status</label>
						<select class="form-select" id="bulkCreateIssueStatus"></select>
					</div>
					<div class="col-md-6 mb-3">
						<label class="form-label" for="bulkCreateIssueAssignee">Default Assignee</label>
						<select class="form-select" id="bulkCreateIssueAssignee"></select>
					</div>
				</div>
				<div class="mb-3">
					<label class="form-label" for="bulkCreateIssueText">Isus</label>
					<textarea class="form-control" id="bulkCreateIssueText" rows="8" placeholder="Subject|Description optional"></textarea>
					<div class="form-text">One isu per line. Description is optional.</div>
				</div>
				<div id="bulkCreateIssuePreview" class="border rounded p-2 bg-body-tertiary d-none"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="previewBulkCreateIssues">Preview</button>
				<button type="button" class="btn btn-success" id="submitBulkCreateIssues">Create Isus</button>
			</div>
		</div>
	</div>
</div>
