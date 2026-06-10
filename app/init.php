<?php

use app\helpers\App;

require __DIR__ . '/../vendor/autoload.php';

App::$configDirs[] = __DIR__ . '/configs';

if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

function tttaiga_is_authenticated(): bool
{
	return !empty($_SESSION['taiga_token']);
}

function tttaiga_require_auth(): void
{
	if (tttaiga_is_authenticated()) {
		return;
	}

	header('Location: login.php');
	exit;
}

function tttaiga_json_error(int $statusCode, string $message): void
{
	http_response_code($statusCode);
	header('Content-Type: application/json');
	echo json_encode(['error' => $message]);
	exit;
}

function tttaiga_allowed_api_urls(array $config): array
{
	$urls = [];
	foreach (($config['servers'] ?? []) as $server) {
		if (!empty($server['api_url'])) {
			$urls[] = rtrim((string) $server['api_url'], '/');
		}
	}
	return array_values(array_unique($urls));
}

function tttaiga_resolve_api_url(array $config, ?string $requestedUrl = null, ?string $sessionUrl = null): ?string
{
	$allowedUrls = tttaiga_allowed_api_urls($config);

	if ($requestedUrl) {
		$requestedUrl = rtrim((string) $requestedUrl, '/');
		return in_array($requestedUrl, $allowedUrls, true) ? $requestedUrl : null;
	}

	$candidates = [$sessionUrl, $config['servers']['default']['api_url'] ?? null];

	foreach ($candidates as $url) {
		if (!$url) {
			continue;
		}
		$url = rtrim((string) $url, '/');
		if (in_array($url, $allowedUrls, true)) {
			return $url;
		}
	}

	return null;
}

$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (PHP_SAPI !== 'cli' && !in_array($scriptName, ['login.php', 'api.php'], true)) {
	tttaiga_require_auth();
}
