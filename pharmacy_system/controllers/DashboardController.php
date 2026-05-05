<?php
/**
 * Dashboard Controller — KPIs and quick stats. Tenant-scoped.
 */
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../models/Sale.php';

class DashboardController {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function index() {
        requireLogin();

        // Superadmin lands on a different page (pharmacy management overview)
        if (isSuperadmin()) {
            redirect('index.php?page=pharmacies');
        }

        $pid      = currentPharmacyId();
        $medicine = new Medicine($this->db, $pid);
        $sale     = new Sale($this->db, $pid);

        // Quick stats — all scoped to current pharmacy
        $totalMedicines = (int)$this->q("SELECT COUNT(*) FROM medicines  WHERE is_active=1 AND pharmacy_id=?", [$pid])->fetchColumn();
        $totalSuppliers = (int)$this->q("SELECT COUNT(*) FROM suppliers  WHERE is_active=1 AND pharmacy_id=?", [$pid])->fetchColumn();
        $totalUsers     = (int)$this->q("SELECT COUNT(*) FROM users      WHERE is_active=1 AND pharmacy_id=?", [$pid])->fetchColumn();

        $todaySales = $this->q("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total
            FROM sales WHERE DATE(created_at) = CURDATE() AND status='completed' AND pharmacy_id=?
        ", [$pid])->fetch();

        $weekSales = $this->q("
            SELECT COALESCE(SUM(total),0) AS total
            FROM sales WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status='completed' AND pharmacy_id=?
        ", [$pid])->fetchColumn();

        $monthSales = $this->q("
            SELECT COALESCE(SUM(total),0) AS total
            FROM sales WHERE MONTH(created_at) = MONTH(CURDATE())
              AND YEAR(created_at) = YEAR(CURDATE())
              AND status='completed' AND pharmacy_id=?
        ", [$pid])->fetchColumn();

        $inventoryValue = (float)$this->q("
            SELECT COALESCE(SUM(quantity * cost_price), 0) FROM medicines WHERE is_active=1 AND pharmacy_id=?
        ", [$pid])->fetchColumn();

        $lowStock      = $medicine->lowStock(5);
        $expiringSoon  = $medicine->expiring(60);
        $recentSales   = $sale->recent(8);

        $chartRows = $this->q("
            SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS revenue
            FROM sales
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status='completed' AND pharmacy_id=?
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ", [$pid])->fetchAll();

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

    private function q($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
