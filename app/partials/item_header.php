<?php
/**
 * Expected variables:
 * $backUrl - URL to go back
 * $backLabel - Label for back button (e.g. 'Back to Usors')
 * $headerId - ID for the dynamic header content (e.g. 'usorHeaderContent')
 * $loadingLabel - text for the loading spinner
 */
$backUrl ??= '#';
$backLabel ??= 'Back';
$headerId ??= 'itemHeaderContent';
$loadingLabel ??= 'Loading...';
?>

<div class="item-header">
	<div class="container">
		<a href="<?php echo htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8'); ?>" class="back-btn">
			<i class="bi bi-arrow-left"></i>
			<?php echo htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8'); ?>
		</a>
		<div id="<?php echo htmlspecialchars($headerId, ENT_QUOTES, 'UTF-8'); ?>" class="item-header-content">
			<div class="loading-spinner">
				<div class="spinner-border text-light" role="status">
					<span class="visually-hidden">
						<?php echo htmlspecialchars($loadingLabel, ENT_QUOTES, 'UTF-8'); ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>
