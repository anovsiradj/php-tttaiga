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

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1>
		<?php echo $pageTitle; ?>
	</h1>
	<div class="d-flex">
		<?php if (isset($bulkCreateModalId)): ?>
			<div class="me-2">
				<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#<?php echo $bulkCreateModalId; ?>">
					<i class="bi bi-plus-lg me-1"></i>
					Bulk Create
				</button>
			</div>
		<?php endif; ?>

		<?php if (isset($bulkUpdateModalId)): ?>
			<div class="me-2">
				<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $bulkUpdateModalId; ?>">
					<i class="bi bi-pencil-square me-1"></i>
					Bulk Update
				</button>
			</div>
		<?php endif; ?>

		<?php if (isset($additionalControls)) { ?>
			<?= $additionalControls ?>
		<?php } ?>

		<div class="me-2" style="width: 250px;">
			<input type="text" class="form-control" id="searchInput" placeholder="<?php echo $searchPlaceholder; ?>">
		</div>

		<?php if ($filterProjectEnable) { ?>
			<div class="me-2" style="width: 200px;">
				<select class="form-select" id="projectSelect">
					<option value="">All Projects</option>
				</select>
			</div>
		<?php } ?>

		<?php if (isset($epicSelect) && $epicSelect): ?>
			<div class="me-2" style="width: 200px;">
				<select class="form-select" id="epicSelect">
					<option value="">All Epics</option>
				</select>
			</div>
		<?php endif; ?>

		<?php if (isset($userStorySelect) && $userStorySelect): ?>
			<div class="me-2" style="width: 200px;">
				<select class="form-select" id="userStorySelect">
					<option value="">All User Stories</option>
				</select>
			</div>
		<?php endif; ?>

		<?php if ($filterStatusEnable) { ?>
			<div class="me-2" style="width: 150px;">
				<select class="form-select" id="statusSelect" data-status-type="<?php echo $statusType ?? ''; ?>">
					<option value="">All Statuses</option>
				</select>
			</div>
		<?php } ?>

		<div>
			<button class="btn btn-outline-secondary" id="refreshBtn">
				<i class="bi bi-arrow-clockwise"></i>
			</button>

		</div>
	</div>
</div>