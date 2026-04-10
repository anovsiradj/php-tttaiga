<?php
require __DIR__ . '/app/init.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<?php
	$pageTitle = 'Me';
	include __DIR__ . '/app/layouts/main_head.php';
	?>
</head>

<body>
	<?php include __DIR__ . '/app/layouts/main_navbar.php' ?>

	<div class="container pb-5">
		<div class="row justify-content-center">
			<div class="col-md-8 col-lg-6">
				<div class="card shadow-sm border-0">
					<div class="card-body p-4" id="profileContent">
						<div class="text-center py-5">
							<div class="spinner-border text-primary" role="status">
								<span class="visually-hidden">Loading...</span>
							</div>
						</div>
					</div>
				</div>
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
		$(document).ready(function() {
			const token = localStorage.getItem('taiga_token');
			const userData = localStorage.getItem('taiga_user');

			if (!token || !userData) {
				window.location.href = 'login.php';
				return;
			}

			const user = JSON.parse(userData);
			$('#profileHeaderContent').html(`<h1 class="display-4 text-white mb-0">Me</h1>`);
			renderProfile(user);


			function renderProfile(user) {
				const avatar = user.photo || 'https://via.placeholder.com/150?text=' + (user.username || 'User');
				const html = `
					<div class="text-center mb-4">
						<div class="position-relative d-inline-block">
							<img src="${avatar}" class="rounded-circle shadow" style="width: 150px; height: 150px; object-fit: cover; border: 5px solid var(--bs-body-bg);">
						</div>
						<h2 class="mt-3 mb-1">${user.full_name || user.username}</h2>
						<p class="text-muted mb-0">@${user.username}</p>
					</div>

					<div class="profile-details">
						<div class="mb-3">
							<label class="form-label small text-muted text-uppercase fw-bold mb-1">Email Address</label>
							<div class="p-2 bg-light rounded border">${user.email || 'N/A'}</div>
						</div>
						
						<div class="mb-3">
							<label class="form-label small text-muted text-uppercase fw-bold mb-1">Biography</label>
							<div class="p-2 bg-light rounded border">${user.bio || '<em>No biography provided.</em>'}</div>
						</div>

						<div class="row">
							<div class="col-6">
								<label class="form-label small text-muted text-uppercase fw-bold mb-1">User ID</label>
								<div class="p-2 bg-light rounded border"><code>${user.id || 'N/A'}</code></div>
							</div>
							<div class="col-6">
								<label class="form-label small text-muted text-uppercase fw-bold mb-1">Color Scheme</label>
								<div class="p-2 bg-light rounded border d-flex align-items-center">
									<div class="rounded-circle me-2" style="width: 15px; height: 15px; background-color: ${user.color || '#666'};"></div>
									${user.color || 'Default'}
								</div>
							</div>
						</div>
					</div>

					<div class="mt-4 pt-3 border-top text-center">
						<p class="small text-muted mb-0">
							Joined: ${user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
						</p>
					</div>
				`;
				$('#profileContent').html(html);
			}
		});
	</script>

</body>

</html>