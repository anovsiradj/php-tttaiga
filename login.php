<?php
require __DIR__ . '/app/construct.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<div class="login-card">
		<div class="taiga-logo">Taiga Login</div>
		<form id="loginForm">
			<div class="mb-3">
				<label for="server" class="form-label">Server</label>
				<select class="form-control form-select" id="server" name="server" required></select>
			</div>
			<div class="mb-3">
				<label for="username" class="form-label">Username</label>
				<input type="text" class="form-control" id="username" name="username" required>
			</div>
			<div class="mb-3">
				<label for="password" class="form-label">Password</label>
				<input type="password" class="form-control" id="password" name="password" required>
			</div>
			<div id="errorMessage" class="alert alert-danger d-none"></div>
			<button type="submit" class="btn btn-primary w-100" id="loginBtn">Login</button>
		</form>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script src="assets/app.js"></script>
	<script src="assets/theme.js"></script>

	<script>
		$(document).ready(function () {
			const config = <?php echo json_encode(include 'app/configs/taiga.php'); ?>;

			const $serverSelect = $('#server');
			$.each(config.servers, function (key, server) {
				$serverSelect.append($('<option>', {
					value: server.api_url,
					text: server.name
				}));
			});

			$('#loginForm').on('submit', function (e) {
				e.preventDefault();

				const apiUrl = $('#server').val();
				const username = $('#username').val();
				const password = $('#password').val();
				const $btn = $('#loginBtn');
				const $error = $('#errorMessage');

				$btn.prop('disabled', true).text('Logging in...');
				$error.addClass('d-none').text('');

				$.ajax({
					url: apiUrl + '/auth',
					type: 'POST',
					contentType: 'application/json',
					data: JSON.stringify({
						type: 'normal',
						username: username,
						password: password
					}),
					success: function (response) {
						localStorage.setItem('taiga_token', response.auth_token);
						localStorage.setItem('taiga_user', JSON.stringify(response));
						localStorage.setItem('taiga_api_url', apiUrl);

						$.post('session_sync.php', {
							action: 'login',
							taiga_token: response.auth_token,
							taiga_user: JSON.stringify(response),
							taiga_api_url: apiUrl
						}, function () {
							window.location.href = 'index.php';
						}).fail(function () {
							// Redirect anyway if session sync fails to ensure functionality doesn't break
							window.location.href = 'index.php';
						});
					},
					error: function (xhr) {
						$btn.prop('disabled', false).text('Login');
						let errorMsg = 'Login failed. Please check your credentials.';
						if (xhr.responseJSON && xhr.responseJSON._error_message) {
							errorMsg = xhr.responseJSON._error_message;
						}
						$error.removeClass('d-none').text(errorMsg);
					}
				});
			});
		});
	</script>

</body>

</html>