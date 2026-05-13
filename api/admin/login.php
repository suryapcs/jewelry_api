<?php
// api/admin/login.php
// POST /api/admin/login

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$body    = json_decode(file_get_contents('php://input'), true);
$email   = trim($body['Email']    ?? '');
$password = $body['Password'] ?? '';

if (!$email || !$password) {
    sendError('Please provide both email and password', 400);
}

$stmt = $pdo->prepare('SELECT * FROM admins WHERE Email = ?');
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin) {
    sendError('Invalid credentials: Admin not found', 400);
}

if (!password_verify($password, $admin['Password'])) {
    sendError('Invalid credentials: Incorrect password', 400);
}

$_SESSION['adminId'] = $admin['id'];

sendResponse(['message' => 'Admin logged in successfully']);
