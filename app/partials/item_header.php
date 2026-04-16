<?php
/**
 * Expected variables:
 * $backUrl - URL to go back
 * $backLabel - Label for back button (e.g. 'Back to Usors')
 * $headerId - ID for the dynamic header content (e.g. 'usorHeaderContent')
 * $loadingLabel - text for the loading spinner
 */
?>

<div class="item-header" style="padding: 2rem 0; margin-bottom: 2rem;">
	<div class="container">
		<a href="<?php echo $backUrl; ?>" class="back-btn" style="text-decoration: none; color: var(--text-muted); display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
			<i class="bi bi-arrow-left"></i>
			<?php echo $backLabel; ?>
		</a>
		<div id="<?php echo $headerId; ?>">
			<div class="loading-spinner">
				<div class="spinner-border text-light" role="status">
					<span class="visually-hidden">
						<?php echo $loadingLabel; ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>