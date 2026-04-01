<?php
/**
 * Expected variables:
 * $pageTitle - The title of the page (e.g. 'Usors')
 * $bulkCreateModalId - The ID of the bulk create modal
 * $bulkUpdateModalId - The ID of the bulk update modal
 * $searchPlaceholder - Placeholder for the search input
 * $additionalControls - (Optional) Extra HTML for the actions bar
 */

$filterProjectEnable ??= true;
$filterStatusEnable ??= true;
?>

<div class="page-title-row">
	<h1>
		<?php echo $pageTitle; ?>
	</h1>
	<div class="d-flex align-items-center">
		<button class="btn btn-outline-secondary btn-sm me-2" id="refreshBtn" title="Refresh">
			<i class="bi bi-arrow-clockwise"></i>
		</button>

		<?php if (isset($primaryAction)) { ?>
			<?= $primaryAction ?>
		<?php } elseif (isset($bulkCreateModalId)) { ?>
			<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $bulkCreateModalId; ?>">
				<i class="bi bi-plus-lg me-1"></i>
				Add New
			</button>
		<?php } ?>
	</div>
</div>

<div class="filter-toolbar">
	<div class="row g-2">
		<div class="col-md">
			<div class="input-group">
				<span class="input-group-text bg-transparent border-end-0 text-muted">
					<i class="bi bi-search"></i>
				</span>
				<input type="text" class="form-control border-start-0 ps-0" id="searchInput" placeholder="Pencarian ...">
			</div>
		</div>

		<?php if ($filterProjectEnable) { ?>
			<div class="col-md-auto" style="width: 200px;">
				<select class="form-select" id="projectSelect"></select>
			</div>
		<?php } ?>

		<?php if (isset($epicSelect) && $epicSelect): ?>
			<div class="col-md-auto" style="width: 200px;">
				<select class="form-select" id="epicSelect"></select>
			</div>
		<?php endif; ?>

		<?php if (isset($userStorySelect) && $userStorySelect): ?>
			<div class="col-md-auto" style="width: 200px;">
				<select class="form-select" id="userStorySelect"></select>
			</div>
		<?php endif; ?>

		<?php if ($filterStatusEnable) { ?>
			<div class="col-md-auto" style="width: 150px;">
				<select class="form-select" id="statusSelect" data-status-type="<?php echo $statusType ?? ''; ?>"></select>
			</div>
		<?php } ?>

		<?php if ($filterAssignedEnable ?? false) { ?>
			<div class="col-md-auto" style="width: 180px;">
				<select class="form-select" id="assignedToSelect"></select>
			</div>
		<?php } ?>

		<?php if (isset($sortOptions) && !empty($sortOptions)) { ?>
			<div class="col-md-auto" style="width: 180px;">
				<select class="form-select" id="sortSelect">
					<?php foreach ($sortOptions as $value => $label) { ?>
						<option value="<?php echo $value; ?>"><?php echo $label; ?></option>
					<?php } ?>
				</select>
			</div>
		<?php } ?>
	</div>
</div>