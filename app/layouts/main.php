<?php

$meta['title'] ??= 'TTTaiga';
$meta['description'] ??= '';
$meta['keywords'] ??= '';
$meta['author'] ??= '';
$meta['image'] ??= '';
$meta['url'] ??= '';
$meta['type'] ??= 'website';
$meta['site_name'] ??= 'TTTaiga';
$meta['locale'] ??= 'id_ID';
$meta['robots'] ??= 'index, follow';
$meta['canonical'] ??= '';
$meta['image_width'] ??= '';
$meta['image_height'] ??= '';
$meta['image_alt'] ??= '';
$meta['image_type'] ??= '';
$meta['image_url'] ??= '';
$meta['image_title'] ??= '';
$meta['image_description'] ??= '';
$meta['image_width'] ??= '';
$meta['image_height'] ??= '';
$meta['image_alt'] ??= '';
$meta['image_type'] ??= '';
$meta['image_url'] ??= '';
$meta['image_title'] ??= '';
$meta['image_description'] ??= '';

?>

<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $meta['title'] ?></title>

	<link rel="icon" href="assets/logo.png" type="image/png">

	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

	<link href="assets/app.css" rel="stylesheet">

	<?php $cutter->section('styles') ?>
</head>

<body>
	<?php include __DIR__ . '/main_navbar.php' ?>

	<?php $cutter->section('content') ?>

	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

	<script src="assets/app.js"></script>
	<script src="assets/taiga.js"></script>
	<script src="assets/theme.js"></script>

	<?php $cutter->section('scripts') ?>
</body>

</html>