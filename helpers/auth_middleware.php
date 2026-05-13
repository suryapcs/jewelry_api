<?php
// helpers/auth_middleware.php
// Checks session-based admin authentication

require_once __DIR__ . '/../config/db.php';

function requireAdminAuth(PDO $pdo): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['adminId'])) {
        http_response_code(401);
        echo json_encode(['message' => 'Admin not authenticated']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['adminId']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        http_response_code(401);
        echo json_encode(['message' => 'Invalid session. Admin not found']);
        exit;
    }

    return $admin;
}
