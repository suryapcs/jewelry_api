<?php
// api/interests/detail.php
// GET    /api/interests/detail?id=<id>  → getInterestById
// PUT    /api/interests/detail?id=<id>  → updateInterest
// DELETE /api/interests/detail?id=<id>  → deleteInterest

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/cors.php';
require_once __DIR__ . '/../../helpers/response.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) sendError('ID is required', 400);

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM interest_details WHERE id = ?');
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    if (!$record) sendError('Record not found', 404);
    sendResponse($record);

} elseif ($method === 'PUT') {
    $body    = json_decode(file_get_contents('php://input'), true);
    $allowed = ['month','year','paidDate','monthlyInterest','paidAmount'];
    $fields  = []; $vals = [];
    foreach ($allowed as $f) {
        if (isset($body[$f])) {
            $fields[] = "`$f` = ?";
            $vals[]   = $body[$f];
        }
    }
    if (empty($fields)) sendError('No fields to update', 400);
    $vals[] = $id;
    $pdo->prepare('UPDATE interest_details SET ' . implode(', ', $fields) . ' WHERE id = ?')
        ->execute($vals);

    $stmt = $pdo->prepare('SELECT * FROM interest_details WHERE id = ?');
    $stmt->execute([$id]);
    sendResponse($stmt->fetch());

} elseif ($method === 'DELETE') {
    $stmt = $pdo->prepare('DELETE FROM interest_details WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) sendError('Record not found', 404);
    sendResponse(['message' => 'Deleted successfully']);

} else {
    sendError('Method not allowed', 405);
}
