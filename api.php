<?php

/**
 * Taiga API Proxy
 * 
 * This file acts as a proxy for the Taiga API.
 * It forwards requests to the Taiga API and returns the response.
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/init.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
	$originParts = parse_url($origin);
	$hostParts = parse_url((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? ''));
	if (($originParts['host'] ?? null) === ($hostParts['host'] ?? null)) {
		header('Access-Control-Allow-Origin: ' . $origin);
		header('Vary: Origin');
	}
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Taiga-Api-Url');
header('Access-Control-Expose-Headers: *');



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit();
}

$config = include 'app/configs/taiga.php';
$method = $_SERVER['REQUEST_METHOD'];

$apiPath = $_SERVER['PATH_INFO'] ?? '';
if ($apiPath === '') {
	$requestUri = $_SERVER['REQUEST_URI'] ?? '';
	$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

	if (str_starts_with($requestUri, $scriptName)) {
		$apiPath = substr($requestUri, strlen($scriptName));
	} else {
		$apiPath = $requestUri;
	}
}

$apiPath = strtok($apiPath, '?') ?: '/';
$isAuthRequest = ($apiPath === '/auth' && $method === 'POST');
$apiUrl = tttaiga_resolve_api_url(
	$config,
	$isAuthRequest ? ($_SERVER['HTTP_X_TAIGA_API_URL'] ?? null) : null,
	$_SESSION['taiga_api_url'] ?? null
);

if (!$apiUrl) {
	tttaiga_json_error(400, 'Invalid Taiga API server');
}

if (!$isAuthRequest && !tttaiga_is_authenticated()) {
	tttaiga_json_error(401, 'Session expired');
}

$cacheablePaths = [
	'/epic-statuses',
	'/userstory-statuses',
	'/task-statuses',
	'/issue-statuses',
	'/memberships',
	'/users'
];

$isCacheable = ($method === 'GET' && in_array($apiPath, $cacheablePaths, true));
$cacheKey = null;
$cacheFile = null;

if ($isCacheable) {
	$cacheDir = __DIR__ . '/storage/tmp';
	if (!is_dir($cacheDir)) {
		mkdir($cacheDir, 0777, true);
	}

	$queryString = $_SERVER['QUERY_STRING'] ?? '';
	$cacheQuery = [];
	if ($queryString !== '') {
		parse_str($queryString, $cacheQuery);
	}

	if (is_array($cacheQuery)) {
		ksort($cacheQuery);
	}

	if (in_array($apiPath, ['/memberships', '/epic-statuses', '/userstory-statuses', '/task-statuses', '/issue-statuses'])) {
		$projectId = $cacheQuery['project'] ?? null;
		$cacheQuery = ['project' => $projectId];
	}

	$cacheKey = md5($apiUrl . $apiPath . json_encode($cacheQuery));
	$cacheFile = $cacheDir . '/' . $cacheKey . '.json';

	$cacheTtl = 3600;
	if ($apiPath === '/memberships') $cacheTtl = 300;
	if ($apiPath === '/users') $cacheTtl = 300;

	if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
		header('X-Cache: HIT');
		header("X-Api-Cache-Key: {$cacheKey}");
		echo file_get_contents($cacheFile);
		exit();
	}
	header('X-Cache: MISS');
}

$headers = [
	'Content-Type: application/json',
];

if ($isCacheable && $cacheKey) {
	$headers[] = 'X-Api-Cache-Key: ' . $cacheKey;
}

if (!$isAuthRequest) {
	$headers[] = 'Authorization: Bearer ' . $_SESSION['taiga_token'];
}

$input = file_get_contents('php://input');
$input = $input === false ? '' : $input;

$autoMemberId = null;
foreach ($headers as $h) {
	if (str_starts_with($h, 'Authorization: Bearer ')) {
		$token = substr($h, strlen('Authorization: Bearer '));
		$parts = explode('.', $token);
		if (count($parts) === 3) {
			$payload64 = $parts[1];
			$payload64 = strtr($payload64, '-_', '+/');
			$payload64 .= str_repeat('=', (4 - (strlen($payload64) % 4)) % 4);

			$payloadJson = base64_decode($payload64, true);
			if ($payloadJson !== false) {
				$payload = json_decode($payloadJson, true);
				if (is_array($payload)) {
					$autoMemberId = $payload['user_id'] ?? $payload['id'] ?? null;
				}
			}
		}
	}
}

$targetUrl = $apiUrl . $apiPath;

$queryArray = [];
if (!empty($_SERVER['QUERY_STRING'])) {
	parse_str($_SERVER['QUERY_STRING'], $queryArray);
}

if ($apiPath === '/projects' && $method === 'GET' && !isset($queryArray['member']) && $autoMemberId) {
	$queryArray['member'] = $autoMemberId;
}

if (!empty($queryArray)) {
	$targetUrl .= '?' . http_build_query($queryArray);
}

$curl = new \anovsiradj\skit\CURL('', $headers, [
	CURLOPT_URL => $targetUrl,
	CURLOPT_CUSTOMREQUEST => $method,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_ENCODING => '', // Handle gzip/deflate automatically
]);

if ($input !== '') {
	$curl->opt(CURLOPT_POSTFIELDS, $input);
}

$curl->exec();

$statusCode = $curl->code();
$responseBody = $curl->data;

if (!$isAuthRequest && in_array($statusCode, [401, 403], true)) {
	unset($_SESSION['taiga_token'], $_SESSION['taiga_user'], $_SESSION['taiga_api_url']);
}

if ($responseBody === false || $responseBody === null) {
	http_response_code(502);
	header('Content-Type: application/json');
	echo json_encode(['error' => 'Proxy error']);
	exit();
}

http_response_code($statusCode);

if ($isCacheable && $statusCode === 200 && $responseBody) {
	$decoded = json_decode($responseBody, true);
	$isApiError = is_array($decoded) && (
		array_key_exists('error', $decoded)
		|| array_key_exists('_error_message', $decoded)
		|| array_key_exists('_error_type', $decoded)
	);

	if ($decoded && !$isApiError) {
		file_put_contents($cacheFile, $responseBody);
	}
}

foreach ($curl->resHeaders as $header) {
	if (stripos($header, 'X-') === 0) {
		header($header);
	}
}

header('Content-Type: application/json');
echo $responseBody;
