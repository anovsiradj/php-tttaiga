<?php
$sessionUser = null;
if (!empty($_SESSION['taiga_user'])) {
	$decodedUser = json_decode((string) $_SESSION['taiga_user'], true);
	if (is_array($decodedUser)) {
		$sessionUser = $decodedUser;
	}
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo (isset($pageTitle) ? $pageTitle . ' - ' : '') . 'TTTaiga'; ?></title>
<link rel="icon" href="assets/logo.png" type="image/png">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

<!-- Custom CSS -->
<link href="assets/app.css" rel="stylesheet">

<!-- Web-skit: Dark Mode Toggle -->
<link href="vendor/anovsiradj/web-skit/widgets/twbs/v5-dark-mode-toggle.css" rel="stylesheet">

<script>
	(function() {
		const sessionUser = <?php echo json_encode($sessionUser); ?>;
		const sessionApiUrl = <?php echo json_encode($_SESSION['taiga_api_url'] ?? null); ?>;
		const isAuthenticated = <?php echo !empty($_SESSION['taiga_token']) ? 'true' : 'false'; ?>;

		window.TTTaigaSession = {
			authenticated: isAuthenticated,
			user: sessionUser,
			apiUrl: sessionApiUrl
		};

		if (isAuthenticated) {
			localStorage.setItem('taiga_token', 'session');
			if (sessionUser) localStorage.setItem('taiga_user', JSON.stringify(sessionUser));
			if (sessionApiUrl) localStorage.setItem('taiga_api_url', sessionApiUrl);
		} else {
			localStorage.removeItem('taiga_token');
			localStorage.removeItem('taiga_user');
			localStorage.removeItem('taiga_api_url');
		}
	})();
</script>
<script src="https://cdn.jsdelivr.net/npm/commonmark@0.30.0/dist/commonmark.min.js"></script>

<!-- Web-skit: Dark Mode Toggle -->
<script src="vendor/anovsiradj/web-skit/widgets/twbs/v5-dark-mode-toggle.js"></script>
