<?php
// api/admin/create.php
// POST /api/admin/create

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$body = json_decode(file_get_contents('php://input'), true);

$firstName = trim($body['FirstName'] ?? '');
$lastName  = trim($body['LastName']  ?? '');
$email     = trim($body['Email']     ?? '');
$password  = $body['Password']       ?? '';

// Basic validation
if (strlen($firstName) < 2 || strlen($firstName) > 50) {
    sendError('"FirstName" must be 2-50 characters', 400);
}
if (strlen($lastName) < 1 || strlen($lastName) > 50) {
    sendError('"LastName" must be 1-50 characters', 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError('Invalid email address', 400);
}
if (empty($password)) {
    sendError('"Password" is required', 400);
}

// Check duplicate
$stmt = $pdo->prepare('SELECT id FROM admins WHERE Email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    sendError('Admin already exists', 400);
}

// Hash password
$hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

// Insert
$stmt = $pdo->prepare(
    'INSERT INTO admins (FirstName, LastName, Email, Password, Role) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute([$firstName, $lastName, $email, $hashed, 'admin']);

// Send welcome email (best-effort)
$emailUser = getenv('EMAIL_USER');
$emailPass = getenv('EMAIL_PASS');
if ($emailUser && $emailPass) {
    $subject = 'Welcome to the Admin Panel';
    $message = "Hello $firstName, welcome to the admin panel!";
    $headers = "From: $emailUser\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($email, $subject, $message, $headers);
}

sendResponse(['message' => 'Admin registered successfully'], 201);
