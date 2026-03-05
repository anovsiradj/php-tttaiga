<?php
/**
 * Expected variables:
 * $pageTitle - The title of the page (e.g. 'Usors')
 * $bulkCreateModalId - The ID of the bulk create modal
 * $bulkUpdateModalId - The ID of the bulk update modal
 * $searchPlaceholder - Placeholder for the search input
 * $additionalControls - (Optional) Extra HTML for the actions bar
 */
?>
<div class="d-flex justify-content-between align-items-center mb-4">
	<h1>
		<?php echo $pageTitle; ?>
	</h1>
	<div class="d-flex">
		<?php if (isset($bulkCreateModalId)): ?>
			<button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#<?php echo $bulkCreateModalId; ?>">
				<i class="bi bi-plus-lg me-1"></i>
				Bulk Create
			</button>
		<?php endif; ?>

		<?php if (isset($bulkUpdateModalId)): ?>
			<button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#<?php echo $bulkUpdateModalId; ?>">
				<i class="bi bi-pencil-square me-1"></i>
				Bulk Update
			</button>
		<?php endif; ?>

		<?php if (isset($additionalControls))
			echo $additionalControls; ?>

		<input type="text" class="form-control me-2" id="searchInput" placeholder="<?php echo $searchPlaceholder; ?>" style="width: 250px;">

		<select class="form-select me-2" id="projectSelect" style="width: 200px;">
			<option value="">All Projects</option>
		</select>

		<?php if (isset($epicSelect) && $epicSelect): ?>
			<select class="form-select me-2" id="epicSelect" style="width: 200px;">
				<option value="">All Epics</option>
			</select>
		<?php endif; ?>

		<?php if (isset($userStorySelect) && $userStorySelect): ?>
			<select class="form-select me-2" id="userStorySelect" style="width: 200px;">
				<option value="">All User Stories</option>
			</select>
		<?php endif; ?>

		<select class="form-select me-2" id="statusSelect" style="width: 150px;">
			<option value="">All Statuses</option>
			<?php if (isset($statusOptions)) {
				foreach ($statusOptions as $val => $label) {
					echo "<option value=\"$val\">$label</option>";
				}
			} else { ?>
				<option value="new">New</option>
				<option value="ready">Ready</option>
				<option value="in progress">In Progress</option>
				<option value="done">Done</option>
				<?php if (isset($extendedStatuses)): ?>
					<option value="archived">Archived</option>
					<option value="blocked">Blocked</option>
				<?php endif; ?>
			<?php } ?>
		</select>

		<button class="btn btn-outline-secondary" id="refreshBtn">
			<i class="bi bi-arrow-clockwise"></i>
		</button>
	</div>
</div>