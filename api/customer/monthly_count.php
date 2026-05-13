<?php
// api/customer/monthly_count.php
// GET /api/customer/monthly_count

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$rows = $pdo->query(
    'SELECT YEAR(created_at) AS yr, MONTH(created_at) AS mo, COUNT(*) AS cnt
     FROM customers
     GROUP BY yr, mo
     ORDER BY yr ASC, mo ASC'
)->fetchAll();

$months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

$formatted = array_map(function($r) use ($months) {
    $name = $months[(int)$r['mo'] - 1] . ' ' . $r['yr'];
    return ['name' => $name, 'value' => (int)$r['cnt']];
}, $rows);

sendResponse($formatted);
