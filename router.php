<?php
// router.php – PHP built-in server router
// Run: php -S localhost:8080 router.php

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/helpers/cors.php';

$uri = $_SERVER['REQUEST_URI'];

// Strip base path if needed
$uri = parse_url($uri, PHP_URL_PATH);

// Remove trailing slash
$uri = rtrim($uri, '/');

// ─── Static uploads ──────────────────────────────────────────────────────────
if (preg_match('#^/uploads/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/uploads/' . $m[1];
    if (file_exists($file)) return false; // serve file
    http_response_code(404); exit;
}

// ─── Route map ───────────────────────────────────────────────────────────────
$routes = [
    // Admin
    ['POST', '#^/api/admin/create$#',     'api/admin/create.php'],
    ['POST', '#^/api/admin/login$#',      'api/admin/login.php'],
    ['POST', '#^/api/admin/logout$#',     'api/admin/logout.php'],
    ['GET',  '#^/api/admin/dashboard$#',  'api/admin/dashboard.php'],

    // Customer – specific routes FIRST (before /:id catch-all)
    ['GET',  '#^/api/customer/existing$#',               'api/customer/existing.php'],
    ['GET',  '#^/api/customer/next-item-code/([^/]+)$#', 'api/customer/next_item_code.php', ['customerId'=>1]],
    ['GET',  '#^/api/customer/next-item-code$#',         'api/customer/next_item_code.php'],
    ['GET',  '#^/api/customer/monthly-customer-count$#', 'api/customer/monthly_count.php'],
    ['GET',  '#^/api/customer/dashboard/stats$#',        'api/customer/dashboard_stats.php'],
    ['GET',  '#^/api/customer/dashboard_stats$#',        'api/customer/dashboard_stats.php'],

    // Upload
    ['POST', '#^/api/customer/upload/([^/]+)$#', 'api/customer/upload_image.php', ['id' => 1]],

    // Restore item
    ['PUT',  '#^/api/customer/restore-item/([^/]+)/([^/]+)$#', 'api/customer/restore_item.php', ['customerId'=>1,'itemCode'=>2]],

    // Restore customer
    ['PUT',  '#^/api/customer/restore/([^/]+)$#', 'api/customer/restore.php', ['id'=>1]],

    // Invoice
    ['GET',  '#^/api/customer/invoice/([^/]+)/([^/]+)$#', 'api/customer/invoice.php', ['customerId'=>1,'itemCode'=>2]],

    // Close loan
    ['POST', '#^/api/customer/customer/([^/]+)/item/([^/]+)/close-loan$#', 'api/customer/close_loan.php', ['customerId'=>1,'itemCode'=>2]],

    // Item detail
    ['GET',  '#^/api/customer/([^/]+)/item/([^/]+)$#', 'api/customer/item_detail.php', ['customerId'=>1,'itemCode'=>2]],

    // Customer CRUD (catch-all /:id last)
    ['GET',    '#^/api/customer$#',          'api/customer/index.php'],
    ['POST',   '#^/api/customer$#',          'api/customer/index.php'],
    ['GET',    '#^/api/customer/([^/]+)$#',  'api/customer/detail.php', ['id'=>1]],
    ['PUT',    '#^/api/customer/([^/]+)$#',  'api/customer/detail.php', ['id'=>1]],
    ['DELETE', '#^/api/customer/([^/]+)$#',  'api/customer/detail.php', ['id'=>1]],
    ['POST',   '#^/api/customer/logout$#',   'api/customer/index.php'],

    // Interests
    ['GET',    '#^/api/interests$#',                              'api/interests/index.php'],
    ['POST',   '#^/api/interests$#',                              'api/interests/index.php'],
    ['GET',    '#^/api/interests/single/([^/]+)$#',              'api/interests/detail.php', ['id'=>1]],
    ['PUT',    '#^/api/interests/([^/]+)$#',                     'api/interests/detail.php', ['id'=>1]],
    ['DELETE', '#^/api/interests/([^/]+)$#',                     'api/interests/detail.php', ['id'=>1]],
    ['GET',    '#^/api/interests/summary/([^/]+)/([^/]+)$#',     'api/interests/summary.php', ['customerId'=>1,'itemCode'=>2]],
    ['GET',    '#^/api/interests/([^/]+)$#',                     'api/interests/by_customer.php', ['customerId'=>1]],

    // Jewellery
    ['GET',  '#^/api/jewellery/summary$#',           'api/jewellery/summary.php'],
    ['POST', '#^/api/jewellery/([^/]+)/add$#',       'api/jewellery/add.php', ['customerId'=>1]],

    // Stats
    ['GET', '#^/api/stats/loan-by-item$#',       'api/stats/loan_by_item.php'],
    ['GET', '#^/api/stats/loan-gold-by-item$#',  'api/stats/loan_gold_by_item.php'],
    ['GET', '#^/api/stats/monthly-interest$#',   'api/stats/monthly_interest.php'],
    ['GET', '#^/api/stats/item-distribution$#',  'api/stats/item_distribution.php'],
];

$method = $_SERVER['REQUEST_METHOD'];

foreach ($routes as $route) {
    [$routeMethod, $pattern, $file, $params] = array_pad($route, 4, []);

    // Method match (OPTIONS handled by cors.php)
    if ($routeMethod !== $method) continue;

    if (preg_match($pattern, $uri, $matches)) {
        // Inject path params into $_GET
        if (!empty($params)) {
            foreach ($params as $name => $idx) {
                $_GET[$name] = $matches[$idx];
            }
        }
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            http_response_code(404);
            echo json_encode(['error' => "Handler not found: $file"]);
        }
        exit;
    }
}

// No route matched
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => "Route not found: $method $uri"]);
