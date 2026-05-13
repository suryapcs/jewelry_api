<?php
// api/admin/dashboard.php
// GET /api/admin/dashboard  (requires auth)

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/auth_middleware.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed', 405);
}

$admin = requireAdminAuth($pdo);

$totalAdmins    = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
$totalCustomers = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();

$recentAdmins = $pdo
    ->query('SELECT id, FirstName, LastName, Email, Role, created_at FROM admins ORDER BY created_at DESC LIMIT 5')
    ->fetchAll();

sendResponse([
    'message' => "Welcome, {$admin['FirstName']}",
    'dashboardData' => [
        'totalAdmins'    => $totalAdmins,
        'totalCustomers' => $totalCustomers,
        'recentAdmins'   => $recentAdmins,
    ],
]);
