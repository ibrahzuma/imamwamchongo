<?php
/**
 * Core configuration & helper functions.
 * Bootstraps every request.
 */

// Show errors during development. In production, log instead of display.
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session for auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Application constants
define('APP_NAME', 'PharmaCare Plus');
define('BASE_URL', getBaseUrl());
define('CURRENCY', 'TZS');

require_once __DIR__ . '/database.php';

/* -----------------------------------------------------------
 *  URL & PATH HELPERS
 * ---------------------------------------------------------*/
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME']);
    $script   = rtrim(str_replace('\\', '/', $script), '/');
    return $protocol . '://' . $host . $script;
}

function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

/* -----------------------------------------------------------
 *  SECURITY / SANITIZATION
 * ---------------------------------------------------------*/
function sanitize($value) {
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/* -----------------------------------------------------------
 *  AUTHENTICATION HELPERS
 * ---------------------------------------------------------*/
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'branch_id' => $_SESSION['branch_id'] ?? 1,
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php?page=login');
    }
}

/**
 * Role-based access control.
 * Pass an array of allowed roles. Anything else = redirect to dashboard.
 */
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        $_SESSION['error'] = 'You do not have permission to access that page.';
        redirect('index.php?page=dashboard');
    }
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (!is_array($roles)) $roles = [$roles];
    return in_array($_SESSION['role'] ?? '', $roles, true);
}

/* -----------------------------------------------------------
 *  FORMATTERS / FLASH MESSAGES
 * ---------------------------------------------------------*/
function money($amount) {
    return CURRENCY . ' ' . number_format((float)$amount, 2);
}

function dateFmt($date, $format = 'd M Y') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

function flash($type, $msg = null) {
    if ($msg === null) {
        $val = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $val;
    }
    $_SESSION['flash'][$type] = $msg;
}

/* -----------------------------------------------------------
 *  AJAX RESPONSE
 * ---------------------------------------------------------*/
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/* -----------------------------------------------------------
 *  GENERATORS
 * ---------------------------------------------------------*/
function generateInvoiceNumber() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}

function generatePurchaseNumber() {
    return 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
}
