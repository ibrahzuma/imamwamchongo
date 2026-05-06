<?php
/**
 * Inventory Controller — stock movements & adjustments.
 */
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../lib/RealtimeHub.php';

class InventoryController {
    private $db;
    private $medicine;

    public function __construct($db) {
        $this->db       = $db;
        $this->medicine = new Medicine($db, currentPharmacyId());
    }

    public function index() {
        requireLogin();
        $movements = $this->medicine->movements(null, 100);
        $lowStock  = $this->medicine->lowStock();
        require __DIR__ . '/../views/inventory/index.php';
    }

    public function adjust() {
        requireRole(['admin','pharmacist']);
        $medicines = $this->medicine->all();
        require __DIR__ . '/../views/inventory/adjust.php';
    }

    public function storeAdjust() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=inventory');
        }

        $medicineId = (int)($_POST['medicine_id'] ?? 0);
        $type       = $_POST['movement_type'] ?? 'in';
        $qty        = (int)($_POST['quantity'] ?? 0);
        $notes      = sanitize($_POST['notes'] ?? '');

        if ($medicineId <= 0 || $qty <= 0 || !in_array($type, ['in','out','adjustment'])) {
            flash('error', 'Please supply medicine, type and a positive quantity.');
            redirect('index.php?page=inventory&action=adjust');
        }

        $this->medicine->adjustStock($medicineId, $qty, $type, 'manual', null, $_SESSION['user_id'], $notes);

        Cache::bumpPharmacyVersion(currentPharmacyId());

        // Look up the medicine name + new quantity for the realtime event
        $stmt = $this->db->prepare("SELECT name, quantity FROM medicines WHERE id = ? AND pharmacy_id = ?");
        $stmt->execute([$medicineId, currentPharmacyId()]);
        $med = $stmt->fetch();
        if ($med) {
            $delta = ($type === 'in' ? '+' : ($type === 'out' ? '-' : '±')) . $qty;
            RealtimeHub::publish(currentPharmacyId(), 'stock.adjusted', [
                'medicine' => $med['name'],
                'delta'    => $delta,
                'quantity' => (int)$med['quantity'],
                'by'       => $_SESSION['full_name'] ?? '',
            ]);
        }

        flash('success', 'Stock adjusted successfully.');
        redirect('index.php?page=inventory');
    }
}
