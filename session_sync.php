<?php
require __DIR__ . '/app/construct.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	if ($action === 'login') {
		$_SESSION['taiga_token'] = $_POST['taiga_token'] ?? '';
		$_SESSION['taiga_user'] = $_POST['taiga_user'] ?? '';
		$_SESSION['taiga_api_url'] = $_POST['taiga_api_url'] ?? '';
		echo json_encode(['success' => true]);
	} elseif ($action === 'logout') {
		session_destroy();
		echo json_encode(['success' => true]);
	} else {
		echo json_encode(['error' => 'Invalid action']);
	}
} else {
	echo json_encode(['error' => 'Invalid method']);
}
