<?php
/**
 * Expected variables:
 * $totalLabel - e.g. 'Total Issues'
 * $totalId - e.g. 'totalIssues'
 * $filteredId - e.g. 'filteredIssues'
 * $selectionCountId - (Optional) ID for selection count span
 */
?>
<div class="filter-section">
	<div class="row">
		<div class="col-md-3">
			<strong>
				<?php echo $totalLabel; ?>:
			</strong> <span id="<?php echo $totalId; ?>">0</span>
		</div>
		<div class="col-md-3">
			<strong>Filtered:</strong> <span id="<?php echo $filteredId; ?>">0</span>
		</div>
		<?php if (isset($selectionCountId)): ?>
			<div class="col-md-3">
				<strong>Selected:</strong> <span id="<?php echo $selectionCountId; ?>">0</span>
			</div>
		<?php endif; ?>
		<div class="col-md-<?php echo isset($selectionCountId) ? '3' : '6'; ?> text-end">
			<button class="btn btn-sm btn-outline-secondary me-2" id="selectAllBtn">Select All</button>
			<button class="btn btn-sm btn-outline-secondary" id="clearSelectionBtn">Clear Selection</button>
		</div>
	</div>
</div>