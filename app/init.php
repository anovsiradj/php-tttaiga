<?php

use app\helpers\App;
use app\helpers\TaigaApiConfig;

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
	return TaigaApiConfig::allowedUrls($config);
}

function tttaiga_resolve_api_url(array $config, ?string $requestedUrl = null, ?string $sessionUrl = null): ?string
{
	return TaigaApiConfig::resolveUrl($config, $requestedUrl, $sessionUrl);
}

$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (PHP_SAPI !== 'cli' && !in_array($scriptName, ['login.php', 'api.php'], true)) {
	tttaiga_require_auth();
}
