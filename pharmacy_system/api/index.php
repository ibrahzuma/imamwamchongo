<?php
/**
 * REST API for the Pharmacy Management System (multi-tenant).
 *
 * Authentication is per-pharmacy:
 *   - Pass `X-API-Key: <key>` header (or `?api_key=` query)
 *   - The key resolves to one pharmacy; all results are scoped to it.
 *   - Active session cookies also work; in that case we use the user's
 *     own pharmacy_id.
 *
 * Endpoints (GET):
 *   ?resource=medicines               - list this pharmacy's medicines
 *   ?resource=medicines&id=1          - single medicine (must belong to pharmacy)
 *   ?resource=medicines&search=para   - search
 *   ?resource=low-stock               - items at/below reorder level
 *   ?resource=expiring&days=30        - items expiring within N days
 *   ?resource=sales&from=...&to=...   - sales in date range
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Pharmacy.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$db = (new Database())->getConnection();

// Resolve tenant: prefer API key, fall back to session.
$pharmacyId = null;
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['api_key'] ?? '');

if ($apiKey !== '') {
    $tenant = (new Pharmacy($db))->findByApiKey($apiKey);
    if (!$tenant) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid API key.']);
        exit;
    }
    $pharmacyId = (int)$tenant['id'];
} elseif (isLoggedIn() && currentPharmacyId()) {
    $pharmacyId = (int)currentPharmacyId();
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Provide X-API-Key header or login.']);
    exit;
}

try {
    $resource = $_GET['resource'] ?? '';

    switch ($resource) {
        case 'medicines':
            $model = new Medicine($db, $pharmacyId);
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
            echo json_encode(['data' => (new Medicine($db, $pharmacyId))->lowStock()]);
            break;

        case 'expiring':
            $days = (int)($_GET['days'] ?? 30);
            echo json_encode(['data' => (new Medicine($db, $pharmacyId))->expiring($days)]);
            break;

        case 'sales':
            $sale = new Sale($db, $pharmacyId);
            $from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
            $to   = $_GET['to']   ?? date('Y-m-d');
            echo json_encode(['data' => $sale->byDateRange($from, $to), 'from' => $from, 'to' => $to]);
            break;

        default:
            echo json_encode([
                'service'     => 'Pharmacy Management System API',
                'version'     => '2.0',
                'tenancy'     => 'multi-tenant; results scoped to API key holder',
                'pharmacy_id' => $pharmacyId,
                'endpoints'   => [
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
