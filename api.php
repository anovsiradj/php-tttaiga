<?php
/**
 * Taiga API Proxy
 * 
 * This file acts as a proxy for the Taiga API.
 * It forwards requests to the Taiga API and returns the response.
 */

require __DIR__ . '/vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Taiga-Api-Url');
header('Access-Control-Expose-Headers: *');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the configuration
$config = include 'app/configs/taiga.php';
$apiUrl = isset($_SERVER['HTTP_X_TAIGA_API_URL']) ? $_SERVER['HTTP_X_TAIGA_API_URL'] : $config['servers']['default']['api_url'];

// Get the request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/api.php';
$apiPath = substr($requestUri, strpos($requestUri, $basePath) + strlen($basePath));
// var_dump($apiPath);
// die;

// Remove query parameters from path
$apiPath = strtok($apiPath, '?');

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Get headers from the original request
$headers = [
    'Content-Type: application/json',
];

// Forward Authorization header if present
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

// Get request body
$input = file_get_contents('php://input');

// Build the target URL
$targetUrl = $apiUrl . $apiPath;

// Add query parameters if present
if (!empty($_SERVER['QUERY_STRING'])) {
    $targetUrl .= '?' . $_SERVER['QUERY_STRING'];
}
// var_dump($targetUrl);
// die;
// Initialize cURL via php-skit
$curl = new \anovsiradj\skit\CURL('', $headers, [
    CURLOPT_URL => $targetUrl,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_FOLLOWLOCATION => true,
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

// Get HTTP status code
http_response_code($curl->code());

// Forward X-* headers
foreach ($curl->resHeaders as $header) {
    if (stripos($header, 'X-') === 0) {
        header($header);
    }
}

// Forward the response
echo $curl->data;
