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

$body = json_decode(file_get_contents('php://input'), true) ?? [];

// Support both PascalCase and lowercase
$email    = trim($body['Email']    ?? $body['email']    ?? '');
$phone    = trim($body['Phone']    ?? $body['phone']    ?? '');
$password = $body['Password'] ?? $body['password'] ?? '';

if ((empty($email) && empty($phone)) || empty($password)) {
    sendError('Please provide Email/Phone and Password', 400);
}

if (!empty($email)) {
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE Email = ?');
    $stmt->execute([$email]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE Phone = ?');
    $stmt->execute([$phone]);
}

$admin = $stmt->fetch();

if (!$admin) {
    sendError('Invalid credentials: Admin not found', 400);
}

if (!password_verify($password, $admin['Password'])) {
    sendError('Invalid credentials: Incorrect password', 400);
}

$_SESSION['adminId'] = $admin['id'];

sendResponse([
    'message' => 'Admin logged in successfully',
    'admin'   => [
        'id'    => (int) $admin['id'],
        'name'  => $admin['Name']  ?? (($admin['FirstName'] ?? '') . ' ' . ($admin['LastName'] ?? '')),
        'email' => $admin['Email'] ?? null,
        'phone' => $admin['Phone'] ?? null,
        'role'  => $admin['Role']  ?? 'admin',
    ],
]);
