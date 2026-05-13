<?php
// api/stats/monthly_interest.php
// GET /api/stats/monthly_interest

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') sendError('Method not allowed', 405);

$rows = $pdo->query(
    'SELECT month, year, COALESCE(SUM(monthlyInterest),0) AS totalInterest
     FROM interest_details
     GROUP BY year, month
     ORDER BY year ASC, month ASC'
)->fetchAll();

$result = array_map(fn($r) => [
    '_id'           => ['month' => (int)$r['month'], 'year' => (int)$r['year']],
    'totalInterest' => (float) $r['totalInterest'],
], $rows);

sendResponse($result);
