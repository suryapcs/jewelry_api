<?php
// helpers/cors.php – sets CORS headers for every request

$allowedOrigins = [
    getenv('CLIENT_URL') ?: 'http://localhost:3000',
    'http://localhost:3000',
    'http://localhost:3001',
    'https://pcstech.in',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} elseif (strpos($origin, 'http://localhost') === 0 || strpos($origin, 'http://127.0.0.1') === 0) {
    // Also allow local development origins dynamically
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Default to first allowed origin for safety or nothing
    header("Access-Control-Allow-Origin: " . ($allowedOrigins[0] ?? '*'));
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
