 <!-- <?php
// helpers/cors.php – sets CORS headers for every request

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// 1. Set basic CORS headers
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 2. Define allowed origins
$allowedOrigins = [
    getenv('CLIENT_URL') ?: 'http://localhost:3000',
    'http://localhost:3000',
    'http://localhost:3001',
    'http://127.0.0.1:3000',
    'https://pcstech.in',
];

// 3. Match origin
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (preg_match('/^http:\/\/(localhost|127\.0\.0\.1|192\.168\.\d+\.\d+):/i', $origin)) {
    // Allow local development (localhost, 127.0.0.1, and local IP like 192.168.x.x)
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Fallback to first allowed origin for safety when no origin header or mismatch
    // (Note: * is not allowed with Allow-Credentials: true)
    header("Access-Control-Allow-Origin: " . ($allowedOrigins[0] ?? 'http://localhost:3000'));
}

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}  -->




<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}