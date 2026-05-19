<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Allowed origins for CORS
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:3001',
    'https://pcstech.in',
    'https://www.pcstech.in',
];

// Get the Origin header from the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if origin is in allowed list
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // For requests without origin or non-whitelisted origins, allow public access
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}