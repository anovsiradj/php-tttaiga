<?php
/**
 * Expected variables:
 * $totalLabel - e.g. 'Total Isus' (Legacy support)
 * $totalId - e.g. 'totalIssues'
 * $filteredId - e.g. 'filteredIssues'
 * $selectionCountId - (Optional) ID for selection count span
 * $bulkActions - (Optional) HTML for dropdown items
 */
?>
<div class="sticky-bulk-bar" id="bulkActionsBar">
	<div class="container d-flex justify-content-between align-items-center">
		<div class="d-flex align-items-center">
			<div class="master-checkbox-container me-3" id="masterCheckboxContainer">
				<div class="form-check mb-0">
					<input class="form-check-input" type="checkbox" id="masterCheckbox">
					<label class="form-check-label fw-bold" for="masterCheckbox">Select All</label>
				</div>
			</div>
			
			<div class="text-muted small border-start ps-3">
				<span class="me-3">Total: <strong id="<?php echo $totalId; ?>">0</strong></span>
				<span class="me-3">Filtered: <strong id="<?php echo $filteredId; ?>">0</strong></span>
				<span>Selected: <strong id="<?php echo $selectionCountId ?? 'selectedCount'; ?>">0</strong></span>
			</div>
		</div>

		<div class="d-flex align-items-center">
			<button class="btn btn-sm btn-outline-secondary me-3" id="clearSelectionBtn" disabled>
				Clear
			</button>

			<div id="bulkActionsDropdownContainer">
				<?php if (isset($bulkActions)): ?>
					<div class="dropdown">
						<button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
							<i class="bi bi-gear-fill me-1"></i> Bulk Actions
						</button>
						<ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="bulkActionsDropdown">
							<?php echo $bulkActions; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
