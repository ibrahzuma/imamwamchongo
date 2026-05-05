<?php
/**
 * Simple REST API for Pharmacy Management System
 *
 * Endpoints:
 *   GET  /api/?resource=medicines              - list all medicines
 *   GET  /api/?resource=medicines&id=1         - get single medicine
 *   GET  /api/?resource=medicines&search=para  - search medicines
 *   GET  /api/?resource=low-stock              - low stock items
 *   GET  /api/?resource=expiring&days=30       - expiring medicines
 *   GET  /api/?resource=sales&from=...&to=...  - sales in date range
 *
 * Authentication: Pass API key as `X-API-Key` header or `?api_key=` parameter.
 * Configure API_KEY in config/config.php (or accept any logged-in session for demo).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../models/Sale.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

// Simple API auth: either logged-in session OR a static API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');
$VALID_KEY = 'demo-api-key-change-me-in-production';

if (!isLoggedIn() && $apiKey !== $VALID_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Provide X-API-Key header or login.']);
    exit;
}

try {
    $db = (new Database())->connect();
    $resource = $_GET['resource'] ?? '';

    switch ($resource) {
        case 'medicines':
            $model = new Medicine($db);
            if (isset($_GET['id'])) {
                $row = $model->find((int)$_GET['id']);
                if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                echo json_encode(['data' => $row]);
            } elseif (isset($_GET['search'])) {
                echo json_encode(['data' => $model->search($_GET['search'])]);
            } else {
                echo json_encode(['data' => $model->all()]);
            }
            break;

        case 'low-stock':
            $model = new Medicine($db);
            echo json_encode(['data' => $model->lowStock()]);
            break;

        case 'expiring':
            $model = new Medicine($db);
            $days = (int)($_GET['days'] ?? 30);
            echo json_encode(['data' => $model->expiring($days)]);
            break;

        case 'sales':
            $model = new Sale($db);
            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
            $to = $_GET['to'] ?? date('Y-m-d');
            echo json_encode(['data' => $model->byDateRange($from, $to), 'from' => $from, 'to' => $to]);
            break;

        default:
            echo json_encode([
                'service' => 'Pharmacy Management System API',
                'version' => '1.0',
                'endpoints' => [
                    '?resource=medicines',
                    '?resource=medicines&id={id}',
                    '?resource=medicines&search={query}',
                    '?resource=low-stock',
                    '?resource=expiring&days={n}',
                    '?resource=sales&from=YYYY-MM-DD&to=YYYY-MM-DD'
                ]
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
