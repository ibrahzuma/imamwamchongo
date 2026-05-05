<?php
/**
 * Reports Controller. Tenant-scoped. Supports HTML view + PDF export.
 */
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Medicine.php';

class ReportController {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /* ---------------- helpers ---------------- */

    private function loadPdfLib() {
        require_once __DIR__ . '/../lib/PdfReport.php';
    }

    private function tableHeader($pdf, array $headers, array $widths, $align = 'L') {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(230, 230, 230);
        foreach ($headers as $i => $h) {
            $pdf->Cell($widths[$i], 7, $h, 1, 0, is_array($align) ? $align[$i] : $align, true);
        }
        $pdf->Ln();
        $pdf->SetFont('helvetica', '', 9);
    }

    public function index() {
        requireLogin();
        require __DIR__ . '/../views/reports/index.php';
    }

    public function sales() {
        requireLogin();
        $from   = sanitize($_GET['from']    ?? date('Y-m-d', strtotime('-7 days')));
        $to     = sanitize($_GET['to']      ?? date('Y-m-d'));
        $userId = (int)($_GET['user_id']    ?? 0) ?: null;
        $pid    = currentPharmacyId();

        $sale   = new Sale($this->db, $pid);
        $sales  = $sale->byDateRange($from, $to, $userId);
        $byDate = $sale->totalsByDate($from, $to, $userId);
        $cashiers = $this->cashiersForPharmacy($pid);

        $totalRevenue = 0; $totalTax = 0; $totalCount = 0;
        foreach ($sales as $s) {
            $totalRevenue += (float)$s['total'];
            $totalTax     += (float)$s['tax_amount'];
            $totalCount++;
        }

        require __DIR__ . '/../views/reports/sales.php';
    }

    private function cashiersForPharmacy($pid) {
        $stmt = $this->db->prepare("
            SELECT id, full_name, role
            FROM users
            WHERE pharmacy_id = ? AND role IN ('admin','pharmacist','cashier')
            ORDER BY full_name ASC
        ");
        $stmt->execute([$pid]);
        return $stmt->fetchAll();
    }

    public function inventory() {
        requireLogin();
        $pid = currentPharmacyId();
        $stmt = $this->db->prepare("
            SELECT m.*, c.name AS category_name, s.name AS supplier_name
            FROM medicines m
            LEFT JOIN categories c ON c.id = m.category_id
            LEFT JOIN suppliers  s ON s.id = m.supplier_id
            WHERE m.is_active = 1 AND m.pharmacy_id = ?
            ORDER BY m.name ASC
        ");
        $stmt->execute([$pid]);
        $medicines = $stmt->fetchAll();

        $totalUnits = 0; $totalCostValue = 0; $totalRetailValue = 0;
        foreach ($medicines as $m) {
            $qty = (int)($m['quantity'] ?? 0);
            $totalUnits       += $qty;
            $totalCostValue   += $qty * (float)($m['cost_price'] ?? 0);
            $totalRetailValue += $qty * (float)($m['selling_price'] ?? 0);
        }

        require __DIR__ . '/../views/reports/inventory.php';
    }

    public function expiry() {
        requireLogin();
        $days = (int)($_GET['days'] ?? 90);
        $medicines = (new Medicine($this->db, currentPharmacyId()))->expiring($days);
        require __DIR__ . '/../views/reports/expiry.php';
    }

    /* ============================================================
     * PDF exports — same data as the HTML reports, rendered via TCPDF.
     * ============================================================ */

    public function exportSalesPdf() {
        requireLogin();
        $this->loadPdfLib();

        $from   = sanitize($_GET['from']    ?? date('Y-m-d', strtotime('-7 days')));
        $to     = sanitize($_GET['to']      ?? date('Y-m-d'));
        $userId = (int)($_GET['user_id']    ?? 0) ?: null;

        $sale  = new Sale($this->db, currentPharmacyId());
        $sales = $sale->byDateRange($from, $to, $userId);

        $totalRevenue = 0; $totalTax = 0;
        foreach ($sales as $s) {
            $totalRevenue += (float)$s['total'];
            $totalTax     += (float)$s['tax_amount'];
        }

        $cashierLabel = '';
        if ($userId) {
            $u = $this->db->prepare("SELECT full_name FROM users WHERE id = ? AND pharmacy_id = ?");
            $u->execute([$userId, currentPharmacyId()]);
            if ($name = $u->fetchColumn()) $cashierLabel = '  ·  Cashier: ' . $name;
        }

        $pdf = PdfReport::make('Sales Report', "From $from  to  $to" . $cashierLabel);

        // Summary
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(60, 6, 'Total transactions:', 0, 0); $pdf->Cell(0, 6, count($sales), 0, 1);
        $pdf->Cell(60, 6, 'Total revenue:',     0, 0); $pdf->Cell(0, 6, money($totalRevenue), 0, 1);
        $pdf->Cell(60, 6, 'Total tax:',         0, 0); $pdf->Cell(0, 6, money($totalTax),     0, 1);
        $avg = count($sales) ? $totalRevenue / count($sales) : 0;
        $pdf->Cell(60, 6, 'Average sale:',      0, 0); $pdf->Cell(0, 6, money($avg), 0, 1);
        $pdf->Ln(3);

        // Table
        $headers = ['Invoice #', 'Date', 'Customer', 'Cashier', 'Subtotal', 'Tax', 'Discount', 'Total'];
        $widths  = [28, 28, 30, 28, 22, 18, 20, 22];
        $aligns  = ['L','L','L','L','R','R','R','R'];
        $this->tableHeader($pdf, $headers, $widths, $aligns);

        if (empty($sales)) {
            $pdf->Cell(array_sum($widths), 8, 'No sales in this period.', 1, 1, 'C');
        } else {
            foreach ($sales as $s) {
                $pdf->Cell($widths[0], 6, $s['invoice_number'], 1, 0, 'L');
                $pdf->Cell($widths[1], 6, dateFmt($s['created_at'], 'Y-m-d H:i'), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, $this->trunc($s['customer_name'] ?? 'Walk-in', 18), 1, 0, 'L');
                $pdf->Cell($widths[3], 6, $this->trunc($s['user_name'] ?? '-', 16), 1, 0, 'L');
                $pdf->Cell($widths[4], 6, money($s['subtotal']),    1, 0, 'R');
                $pdf->Cell($widths[5], 6, money($s['tax_amount']),  1, 0, 'R');
                $pdf->Cell($widths[6], 6, money($s['discount']),    1, 0, 'R');
                $pdf->Cell($widths[7], 6, money($s['total']),       1, 1, 'R');
            }
        }

        $pdf->Output("sales-report-$from-to-$to.pdf", 'I');
        exit;
    }

    public function exportInventoryPdf() {
        requireLogin();
        $this->loadPdfLib();
        $pid = currentPharmacyId();

        $stmt = $this->db->prepare("
            SELECT m.*, c.name AS category_name
            FROM medicines m
            LEFT JOIN categories c ON c.id = m.category_id
            WHERE m.is_active = 1 AND m.pharmacy_id = ?
            ORDER BY m.name ASC
        ");
        $stmt->execute([$pid]);
        $medicines = $stmt->fetchAll();

        $totalUnits = 0; $totalCostValue = 0; $totalRetailValue = 0;
        foreach ($medicines as $m) {
            $qty = (int)($m['quantity'] ?? 0);
            $totalUnits       += $qty;
            $totalCostValue   += $qty * (float)($m['cost_price'] ?? 0);
            $totalRetailValue += $qty * (float)($m['selling_price'] ?? 0);
        }

        $pdf = PdfReport::make('Inventory Report', 'Snapshot as of ' . date('Y-m-d H:i'));

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'Summary', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(60, 6, 'Total items:',         0, 0); $pdf->Cell(0, 6, count($medicines), 0, 1);
        $pdf->Cell(60, 6, 'Total stock units:',   0, 0); $pdf->Cell(0, 6, number_format($totalUnits), 0, 1);
        $pdf->Cell(60, 6, 'Stock value (cost):',  0, 0); $pdf->Cell(0, 6, money($totalCostValue),   0, 1);
        $pdf->Cell(60, 6, 'Stock value (retail):',0, 0); $pdf->Cell(0, 6, money($totalRetailValue), 0, 1);
        $pdf->Ln(3);

        $headers = ['#', 'Medicine', 'Category', 'Batch', 'Qty', 'Reorder', 'Cost', 'Price', 'Stock Value', 'Status'];
        $widths  = [8, 38, 24, 22, 14, 16, 20, 20, 22, 22];
        $aligns  = ['C','L','L','L','R','R','R','R','R','C'];
        $this->tableHeader($pdf, $headers, $widths, $aligns);

        if (empty($medicines)) {
            $pdf->Cell(array_sum($widths), 8, 'No active medicines.', 1, 1, 'C');
        } else {
            $i = 1;
            foreach ($medicines as $m) {
                $qty       = (int)($m['quantity'] ?? 0);
                $reorder   = (int)($m['reorder_level'] ?? 0);
                $isLow     = $qty <= $reorder;
                $stockVal  = $qty * (float)($m['cost_price'] ?? 0);
                $pdf->Cell($widths[0], 6, $i++, 1, 0, 'C');
                $pdf->Cell($widths[1], 6, $this->trunc($m['name'], 22), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, $this->trunc($m['category_name'] ?? '-', 14), 1, 0, 'L');
                $pdf->Cell($widths[3], 6, $this->trunc($m['batch_number'] ?? '-', 12), 1, 0, 'L');
                $pdf->Cell($widths[4], 6, $qty, 1, 0, 'R');
                $pdf->Cell($widths[5], 6, $reorder, 1, 0, 'R');
                $pdf->Cell($widths[6], 6, money($m['cost_price'] ?? 0),    1, 0, 'R');
                $pdf->Cell($widths[7], 6, money($m['selling_price'] ?? 0), 1, 0, 'R');
                $pdf->Cell($widths[8], 6, money($stockVal),                1, 0, 'R');
                $pdf->Cell($widths[9], 6, $isLow ? 'Low Stock' : 'OK',     1, 1, 'C');
            }
        }

        $pdf->Output('inventory-report-' . date('Y-m-d') . '.pdf', 'I');
        exit;
    }

    public function exportExpiryPdf() {
        requireLogin();
        $this->loadPdfLib();

        $days      = (int)($_GET['days'] ?? 90);
        $medicines = (new Medicine($this->db, currentPharmacyId()))->expiring($days);

        $pdf = PdfReport::make('Expiry Report', "Items expiring within $days days");

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, "Items expiring within $days days: " . count($medicines), 0, 1, 'L');
        $pdf->Ln(2);

        $headers = ['#', 'Medicine', 'Batch', 'Expiry Date', 'Days Left', 'Stock', 'Stock Value', 'Status'];
        $widths  = [8, 50, 25, 25, 22, 18, 25, 25];
        $aligns  = ['C','L','L','L','R','R','R','C'];
        $this->tableHeader($pdf, $headers, $widths, $aligns);

        if (empty($medicines)) {
            $pdf->Cell(array_sum($widths), 8, 'No medicines expiring in this period.', 1, 1, 'C');
        } else {
            $i = 1;
            foreach ($medicines as $m) {
                $expiry   = strtotime($m['expiry_date']);
                $daysLeft = (int)floor(($expiry - time()) / 86400);
                $qty      = (int)($m['quantity'] ?? 0);
                $value    = $qty * (float)($m['cost_price'] ?? 0);
                $label    = $daysLeft < 0 ? 'EXPIRED' : ($daysLeft <= 30 ? 'Critical' : 'Warning');

                $pdf->Cell($widths[0], 6, $i++, 1, 0, 'C');
                $pdf->Cell($widths[1], 6, $this->trunc($m['name'], 30), 1, 0, 'L');
                $pdf->Cell($widths[2], 6, $this->trunc($m['batch_number'] ?? '-', 14), 1, 0, 'L');
                $pdf->Cell($widths[3], 6, dateFmt($m['expiry_date'], 'Y-m-d'), 1, 0, 'L');
                $pdf->Cell($widths[4], 6, $daysLeft, 1, 0, 'R');
                $pdf->Cell($widths[5], 6, $qty,     1, 0, 'R');
                $pdf->Cell($widths[6], 6, money($value), 1, 0, 'R');
                $pdf->Cell($widths[7], 6, $label,   1, 1, 'C');
            }
        }

        $pdf->Output('expiry-report-' . date('Y-m-d') . ".pdf", 'I');
        exit;
    }

    private function trunc($s, $n) {
        $s = (string)$s;
        return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '…' : $s;
    }
}
