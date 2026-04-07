<?php
/**
 * Taiga API Proxy
 * 
 * This file acts as a proxy for the Taiga API.
 * It forwards requests to the Taiga API and returns the response.
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Taiga-Api-Url');
header('Access-Control-Expose-Headers: *');



// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	exit();
}

if (isset($_SESSION['taiga_token']) && empty($_SERVER['HTTP_AUTHORIZATION'])) {
	$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $_SESSION['taiga_token'];
}

// Get the configuration
$config = include 'app/configs/taiga.php';
$apiUrl = isset($_SERVER['HTTP_X_TAIGA_API_URL']) ? $_SERVER['HTTP_X_TAIGA_API_URL'] : $config['servers']['default']['api_url'];

// Get the request path
$apiPath = '';
if (isset($_SERVER['PATH_INFO'])) {
	$apiPath = $_SERVER['PATH_INFO'];
} else {
	$requestUri = $_SERVER['REQUEST_URI'];
	$scriptName = $_SERVER['SCRIPT_NAME'];

	if (str_starts_with($requestUri, $scriptName)) {
		$apiPath = substr($requestUri, strlen($scriptName));
	} else {
		// Fallback for when script name is omitted in some rewrite configs
		$apiPath = $requestUri;
	}
}

// Remove query parameters from path
$apiPath = strtok($apiPath, '?');
if (!$apiPath)
	$apiPath = '/';

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Cache configuration
$cacheablePaths = [
	'/epic-statuses',
	'/userstory-statuses',
	'/task-statuses',
	'/issue-statuses',
	'/memberships',
	'/users'
];

$isCacheable = ($method === 'GET' && in_array($apiPath, $cacheablePaths));
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

// Get headers from the original request
$headers = [
	'Content-Type: application/json',
	'X-Api-Cache-Key: ' . $cacheKey,
];

// Forward Authorization header if present
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
} else {
	// Fallback for Apache which sometimes strips the Authorization header
	if (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		if (isset($requestHeaders['Authorization'])) {
			$headers[] = 'Authorization: ' . $requestHeaders['Authorization'];
		}
	}
}

// Get request body
$input = file_get_contents('php://input');

// Extract user ID from Authorization header if possible
$autoMemberId = null;
if (isset($headers)) {
	foreach ($headers as $h) {
		if (str_starts_with($h, 'Authorization: Bearer ')) {
			$token = substr($h, strlen('Authorization: Bearer '));
			$parts = explode('.', $token);
			if (count($parts) === 3) {
				$payload = json_decode(base64_decode($parts[1]), true);
				$autoMemberId = $payload['user_id'] ?? $payload['id'] ?? null;
			}
		}
	}
}

// Build the target URL
$targetUrl = $apiUrl . $apiPath;

// Prepare query parameters
$queryArray = [];
if (!empty($_SERVER['QUERY_STRING'])) {
	parse_str($_SERVER['QUERY_STRING'], $queryArray);
}

// Auto-append member ID for projects list if missing
if ($apiPath === '/projects' && $method === 'GET' && !isset($queryArray['member']) && $autoMemberId) {
	$queryArray['member'] = $autoMemberId;
}

if (!empty($queryArray)) {
	$targetUrl .= '?' . http_build_query($queryArray);
}

// Initialize cURL via php-skit
$curl = new \anovsiradj\skit\CURL('', $headers, [
	CURLOPT_URL => $targetUrl,
	CURLOPT_CUSTOMREQUEST => $method,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_ENCODING => '', // Handle gzip/deflate automatically
]);

// Add request body for POST/PUT requests
if (!empty($input)) {
	$curl->opt(CURLOPT_POSTFIELDS, $input);
}

// Execute the request
$curl->exec();


// Check for cURL errors
if (curl_errno($curl->handle)) {
	http_response_code(500);
	echo json_encode([
		'error' => 'Proxy error: ' . curl_error($curl->handle)
	]);
	exit();
}

// Forward HTTP status code
http_response_code($curl->code());



// Save to cache if successful
if ($isCacheable && $curl->code() === 200 && !empty($curl->data)) {
	file_put_contents($cacheFile, $curl->data);
}

// Forward X-* headers
foreach ($curl->resHeaders as $header) {
	if (stripos($header, 'X-') === 0) {
		header($header);
	}
}

header('Content-Type: application/json');
// Forward the response
echo $curl->data;

