<?php
/**
 * Front Controller — single entry point for the Pharmacy Management System.
 *
 * Routes via ?page=<name>&action=<action>.
 * Keeps things simple and dependency-free.
 */

require_once __DIR__ . '/config/config.php';

$db = (new Database())->getConnection();

$page   = $_GET['page']   ?? 'login';
$action = $_GET['action'] ?? 'index';

// Map of page → controller
$routes = [
    'login'       => 'AuthController',
    'logout'      => 'AuthController',
    'dashboard'   => 'DashboardController',
    'medicines'   => 'MedicineController',
    'suppliers'   => 'SupplierController',
    'sales'       => 'SaleController',
    'pos'         => 'SaleController',
    'purchases'   => 'PurchaseController',
    'inventory'   => 'InventoryController',
    'reports'     => 'ReportController',
    'users'       => 'UserController',
    'pharmacies'  => 'PharmacyController',
    'categories'  => 'CategoryController',
];

// Auth shortcuts
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/controllers/AuthController.php';
    (new AuthController($db))->login();
    exit;
}
if ($page === 'logout') {
    require_once __DIR__ . '/controllers/AuthController.php';
    (new AuthController($db))->logout();
    exit;
}

if (!isset($routes[$page])) {
    http_response_code(404);
    echo "Page not found.";
    exit;
}

// Load and dispatch
$controllerName = $routes[$page];
require_once __DIR__ . "/controllers/$controllerName.php";
$controller = new $controllerName($db);

// Special: pos page → call pos()
if ($page === 'pos') {
    $controller->pos();
    exit;
}

if ($page === 'login') {
    $controller->showLogin();
    exit;
}

// Map action → method
$methodMap = [
    'index'         => 'index',
    'create'        => 'create',
    'store'         => 'store',
    'edit'          => 'edit',
    'update'        => 'update',
    'delete'        => 'delete',
    'show'          => 'show',
    'invoice'       => 'invoice',
    'pos'           => 'pos',
    'sales'         => 'sales',
    'inventory'     => 'inventory',
    'expiry'        => 'expiry',
    'adjust'        => 'adjust',
    'storeAdjust'   => 'storeAdjust',
    'ajaxSearch'    => 'ajaxSearch',
    'ajaxBarcode'   => 'ajaxBarcode',
    'toggle'        => 'toggle',
    'rotateKey'     => 'rotateKey',
    'profile'           => 'profile',
    'updateProfile'     => 'updateProfile',
    'exportSalesPdf'    => 'exportSalesPdf',
    'exportInventoryPdf'=> 'exportInventoryPdf',
    'exportExpiryPdf'   => 'exportExpiryPdf',
    'bulkImport'        => 'bulkImport',
    'downloadTemplate'  => 'downloadTemplate',
    'bulkStore'         => 'bulkStore',
];

$method = $methodMap[$action] ?? 'index';
if (!method_exists($controller, $method)) {
    http_response_code(404);
    echo "Action not found.";
    exit;
}

$controller->$method();
