<?php
/**
 * Reports Controller.
 */
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Medicine.php';

class ReportController {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function index() {
        requireLogin();
        require __DIR__ . '/../views/reports/index.php';
    }

    public function sales() {
        requireLogin();
        $from = sanitize($_GET['from'] ?? date('Y-m-d', strtotime('-7 days')));
        $to   = sanitize($_GET['to']   ?? date('Y-m-d'));

        $sale     = new Sale($this->db);
        $sales    = $sale->byDateRange($from, $to);
        $byDate   = $sale->totalsByDate($from, $to);

        $totalRevenue = 0; $totalCount = 0;
        foreach ($sales as $s) { $totalRevenue += (float)$s['total']; $totalCount++; }

        require __DIR__ . '/../views/reports/sales.php';
    }

    public function inventory() {
        requireLogin();
        $rows = $this->db->query("
            SELECT m.*, c.name AS category_name, s.name AS supplier_name,
                   (m.quantity * m.cost_price) AS stock_value
            FROM medicines m
            LEFT JOIN categories c ON c.id = m.category_id
            LEFT JOIN suppliers  s ON s.id = m.supplier_id
            WHERE m.is_active = 1
            ORDER BY m.name ASC
        ")->fetchAll();

        $totalValue = 0;
        foreach ($rows as $r) $totalValue += (float)$r['stock_value'];

        require __DIR__ . '/../views/reports/inventory.php';
    }

    public function expiry() {
        requireLogin();
        $days = (int)($_GET['days'] ?? 90);
        $rows = (new Medicine($this->db))->expiring($days);
        require __DIR__ . '/../views/reports/expiry.php';
    }
}
