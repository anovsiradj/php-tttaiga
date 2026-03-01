<?php
require __DIR__ . '/app/construct.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Dashboard - Taiga API</title>
	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
	<!-- Custom CSS -->
	<link href="assets/app.css" rel="stylesheet">
</head>

<body>

	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container">
		<div class="profile-card text-center" id="profileContent">
			<div class="spinner-border text-primary" role="status">
				<span class="visually-hidden">Loading...</span>
			</div>
		</div>
	</div>

	<!-- jQuery and Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<!-- Theme Script -->
	<script src="assets/app.js"></script>
	<script src="assets/theme.js"></script>

	<script>
		$(document).ready(function () {
			const token = localStorage.getItem('taiga_token');
			const userData = localStorage.getItem('taiga_user');

			if (!token || !userData) {
				window.location.href = 'login.php';
				return;
			}

			const user = JSON.parse(userData);
			console.debug(user)
			renderProfile(user);


			function renderProfile(user) {
				const html = `
			<img src="${user.photo || 'https://via.placeholder.com/150'}" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
			<h2>Welcome, ${user.full_name || user.username}!</h2>
			<p class="text-muted">@${user.username}</p>
			<hr>
			<div class="row text-start mt-4">
				<div class="col-6"><strong>Email:</strong></div>
				<div class="col-6">${user.email || 'N/A'}</div>
				<div class="col-6 mt-2"><strong>Bio:</strong></div>
				<div class="col-6 mt-2">${user.bio || 'No bio provided.'}</div>
			</div>
		`;
				$('#profileContent').html(html);
			}
		});
	</script>


</body>

</html>