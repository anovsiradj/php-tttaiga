<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo (isset($pageTitle) ? $pageTitle . ' - ' : '') . 'TTTaiga'; ?></title>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<!-- Custom CSS -->
<link href="assets/app.css" rel="stylesheet">

<script>
	// Sync PHP session to localStorage
	(function() {
		const sessionToken = <?php echo json_encode($_SESSION['taiga_token'] ?? null); ?>;
		const sessionUser = <?php echo json_encode($_SESSION['taiga_user'] ?? null); ?>;
		const sessionApiUrl = <?php echo json_encode($_SESSION['taiga_api_url'] ?? null); ?>;

		if (sessionToken) localStorage.setItem('taiga_token', sessionToken);
		if (sessionUser) localStorage.setItem('taiga_user', sessionUser);
		if (sessionApiUrl) localStorage.setItem('taiga_api_url', sessionApiUrl);

		// If session is empty but localStorage has data, we might need to handle that, 
		// but per request "fully use php session" suggests PHP is the source of truth.
		if (!sessionToken && !window.location.pathname.endsWith('login.php')) {
			// localStorage.clear(); // Optional: clear if session is gone?
		}
	})();
</script>
<script src="https://cdn.jsdelivr.net/npm/commonmark@0.30.0/dist/commonmark.min.js"></script>
