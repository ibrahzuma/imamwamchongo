<?php
/**
 * Dashboard Controller — KPIs and quick stats.
 */
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../models/Sale.php';

class DashboardController {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function index() {
        requireLogin();
        $medicine = new Medicine($this->db);
        $sale     = new Sale($this->db);

        // Quick stats
        $totalMedicines = (int)$this->db->query("SELECT COUNT(*) FROM medicines WHERE is_active=1")->fetchColumn();
        $totalSuppliers = (int)$this->db->query("SELECT COUNT(*) FROM suppliers WHERE is_active=1")->fetchColumn();
        $totalUsers     = (int)$this->db->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();

        // Sales today
        $todaySales = $this->db->query("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total
            FROM sales WHERE DATE(created_at) = CURDATE() AND status='completed'
        ")->fetch();

        // Sales last 7 days
        $weekSales = $this->db->query("
            SELECT COALESCE(SUM(total),0) AS total
            FROM sales WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status='completed'
        ")->fetchColumn();

        // Sales this month
        $monthSales = $this->db->query("
            SELECT COALESCE(SUM(total),0) AS total
            FROM sales WHERE MONTH(created_at) = MONTH(CURDATE())
              AND YEAR(created_at) = YEAR(CURDATE())
              AND status='completed'
        ")->fetchColumn();

        // Inventory value
        $inventoryValue = (float)$this->db->query("
            SELECT COALESCE(SUM(quantity * cost_price), 0) FROM medicines WHERE is_active=1
        ")->fetchColumn();

        // Alerts
        $lowStock      = $medicine->lowStock(5);
        $expiringSoon  = $medicine->expiring(60);
        $recentSales   = $sale->recent(8);

        // Last 7 day sales chart data
        $chartRows = $this->db->query("
            SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS revenue
            FROM sales
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status='completed'
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ")->fetchAll();

        // Build a continuous 7-day series
        $chartLabels = [];
        $chartData   = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $chartLabels[] = date('D', strtotime($d));
            $found = 0;
            foreach ($chartRows as $r) {
                if ($r['day'] === $d) { $found = (float)$r['revenue']; break; }
            }
            $chartData[] = $found;
        }

        require __DIR__ . '/../views/dashboard/index.php';
    }
}
