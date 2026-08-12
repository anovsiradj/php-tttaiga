<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
	<div class="container">
		<a class="navbar-brand" href="index.php">TTTaiga</a>
		<div class="navbar-nav ms-auto">
			<a class="nav-link active" href="index.php">Me</a>
			<a class="nav-link" href="projects.php">Project</a>
			<a class="nav-link" href="sprints.php">Sprint</a>
			<a class="nav-link" href="epiks.php">Epik</a>
			<a class="nav-link" href="usors.php">Usor</a>
			<a class="nav-link" href="tasks.php">Task</a>
			<a class="nav-link" href="isus.php">Isu</a>

			<?php include __DIR__ . '/../../vendor/anovsiradj/web-skit/widgets/twbs/v5-dark-mode-toggle.html'; ?>
			<button class="btn btn-outline-danger btn-sm" id="logoutBtn" title="Logout" data-bs-toggle="tooltip">
				<i class="bi bi-box-arrow-right"></i>
			</button>
		</div>
	</div>
</nav>