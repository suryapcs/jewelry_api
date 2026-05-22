<?php

// Disable display errors in production
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Allowed origins
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:3001',
    'https://pcstech.in',
    'https://www.pcstech.in'
];

// Get request origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Set CORS headers FIRST, before any redirects
if ($origin && in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else if ($origin === '') {
    // Allow same-origin requests
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

// Handle preflight request IMMEDIATELY - must come before any redirects
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}