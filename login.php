<?php
require __DIR__ . '/app/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	$action = $_POST['action'];
	if ($action === 'login') {
		$config = include __DIR__ . '/app/configs/taiga.php';
		$apiUrl = tttaiga_resolve_api_url($config, $_POST['taiga_api_url'] ?? null);
		$token = trim((string) ($_POST['taiga_token'] ?? ''));
		if (!$apiUrl || $token === '') {
			tttaiga_json_error(400, 'Invalid login session');
		}

		session_regenerate_id(true);
		$_SESSION['taiga_token'] = $token;
		$_SESSION['taiga_user'] = $_POST['taiga_user'] ?? '';
		$_SESSION['taiga_api_url'] = $apiUrl;
		header('Content-Type: application/json');
		echo json_encode(['success' => true]);
		exit;
	} elseif ($action === 'logout') {
		$_SESSION = [];
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
		}
		session_destroy();
		header('Content-Type: application/json');
		echo json_encode(['success' => true]);
		exit;
	}
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">

<head>
	<?php include __DIR__ . '/app/layouts/main_head.php'; ?>
	<style>
		body {
			background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
			height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0;
		}
		.login-card {
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(10px);
			border-radius: 1rem;
			box-shadow: 0 1rem 3rem rgba(0,0,0,0.175);
			width: 100%;
			max-width: 400px;
			padding: 2.5rem;
		}
		.login-header {
			text-align: center;
			margin-bottom: 2rem;
		}
		.login-logo {
			font-size: 2.5rem;
			font-weight: 800;
			color: #182848;
			letter-spacing: -1px;
			margin-bottom: 0.5rem;
		}
		.login-subtitle {
			color: #6c757d;
			font-size: 0.9rem;
		}
		.form-control {
			border-radius: 0.5rem;
			padding: 0.75rem 1rem;
			border: 1px solid #dee2e6;
		}
		.form-control:focus {
			box-shadow: 0 0 0 0.25rem rgba(75, 108, 183, 0.25);
			border-color: #4b6cb7;
		}
		.btn-login {
			background: linear-gradient(to right, #4b6cb7, #182848);
			border: none;
			border-radius: 0.5rem;
			padding: 0.75rem;
			font-weight: 600;
			letter-spacing: 0.5px;
			transition: transform 0.2s;
		}
		.btn-login:hover {
			transform: translateY(-2px);
			box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
		}
		.btn-login:active {
			transform: translateY(0);
		}
	</style>
</head>

<body>

	<div class="login-card animate__animated animate__fadeIn">
		<div class="login-header">
			<div class="login-logo">TTTaiga</div>
			<div class="login-subtitle text-uppercase small ls-wide">Management Dashboard</div>
		</div>
		
		<form id="loginForm">
			<div class="mb-3">
				<label for="server" class="form-label small fw-bold text-muted text-uppercase">Server Instance</label>
				<select class="form-select" id="server" name="server" required></select>
			</div>
			
			<div class="mb-3">
				<label for="username" class="form-label small fw-bold text-muted text-uppercase">Username</label>
				<div class="input-group">
					<span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
					<input type="text" class="form-control border-start-0" id="username" name="username" placeholder="Enter your username" required>
				</div>
			</div>
			
			<div class="mb-4">
				<label for="password" class="form-label small fw-bold text-muted text-uppercase">Password</label>
				<div class="input-group">
					<span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
					<input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Enter your password" required>
				</div>
			</div>
			
			<div id="errorMessage" class="alert alert-danger d-none py-2 px-3 small border-0 shadow-sm mb-4"></div>
			
			<button type="submit" class="btn btn-primary w-100 btn-login" id="loginBtn">SIGN IN</button>
		</form>
		
		<div class="mt-4 text-center">
			<p class="small text-muted mb-0">&copy; <?php echo date('Y'); ?> TTTaiga Team</p>
		</div>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script src="assets/app.js"></script>
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

				$btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>AUTHENTICATING...');
				$error.addClass('d-none').text('');

				$.ajax({
					url: 'api.php/auth',
					type: 'POST',
					contentType: 'application/json',
					headers: {
						'X-Taiga-Api-Url': apiUrl
					},
					data: JSON.stringify({
						type: 'normal',
						username: username,
						password: password
					}),
					success: function (response) {
						$.ajax({
							url: 'login.php',
							type: 'POST',
							data: {
								action: 'login',
								taiga_token: response.auth_token,
								taiga_user: JSON.stringify(response),
								taiga_api_url: apiUrl
							},
							success: function () {
								localStorage.setItem('taiga_token', 'session');
								localStorage.setItem('taiga_user', JSON.stringify(response));
								localStorage.setItem('taiga_api_url', apiUrl);
								window.location.href = 'projects.php';
							}
						});
					},
					error: function (xhr) {
						console.error('Login failed:', xhr);
						$btn.prop('disabled', false).text('SIGN IN');
						$error.removeClass('d-none').text(xhr.responseJSON?._error_message || 'Login failed. Please check your credentials.');
					}
				});
			});
		});
	</script>

</body>

</html>
