<?php
/**
 * Sale Controller — POS / Invoicing.
 */
require_once __DIR__ . '/../models/Sale.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../lib/RealtimeHub.php';

class SaleController {
    private $db;
    private $sale;

    public function __construct($db) {
        $this->db   = $db;
        $this->sale = new Sale($db, currentPharmacyId());
    }

    public function index() {
        requireLogin();
        $sales = $this->sale->recent(50);
        require __DIR__ . '/../views/sales/index.php';
    }

    /**
     * POS screen.
     */
    public function pos() {
        requireRole(['admin','pharmacist','cashier']);
        // Default tax rate (per-pharmacy override, fallback to global)
        $pid = currentPharmacyId();
        $stmt = $this->db->prepare("
            SELECT setting_value FROM settings
            WHERE setting_key='default_tax_rate' AND (pharmacy_id = ? OR pharmacy_id IS NULL)
            ORDER BY pharmacy_id IS NULL ASC LIMIT 1
        ");
        $stmt->execute([$pid]);
        $taxRate = (float)$stmt->fetchColumn();
        require __DIR__ . '/../views/sales/pos.php';
    }

    /**
     * AJAX — submit sale (POS).
     * Expects JSON body: { customer_name, customer_phone, items:[...], discount, tax_rate, payment_method, paid }.
     */
    public function store() {
        requireRole(['admin','pharmacist','cashier']);
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];

        if (!verifyCsrf($data['csrf_token'] ?? '')) {
            jsonResponse(['success'=>false, 'message'=>'Invalid security token'], 400);
        }
        if (empty($data['items']) || !is_array($data['items'])) {
            jsonResponse(['success'=>false, 'message'=>'No items in cart'], 400);
        }

        try {
            $payload = [
                'user_id'        => $_SESSION['user_id'],
                'branch_id'      => $_SESSION['branch_id'] ?? null,
                'customer_name'  => $data['customer_name']  ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'discount'       => $data['discount']       ?? 0,
                'tax_rate'       => $data['tax_rate']       ?? 0,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'paid'           => $data['paid']           ?? 0,
                'notes'          => $data['notes']          ?? null,
            ];
            $result = $this->sale->create($payload, $data['items']);

            // Broadcast to every browser bound to this pharmacy
            RealtimeHub::publish(currentPharmacyId(), 'sale.created', [
                'invoice' => $result['invoice_number'],
                'total'   => money($result['total']),
                'cashier' => $_SESSION['full_name'] ?? '',
                'items'   => count($data['items']),
            ]);

            // Fire low-stock alerts for any item that just dipped at/below reorder
            $ids = array_map(function ($it) { return (int)$it['medicine_id']; }, $data['items']);
            $ids = array_filter($ids);
            if ($ids) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->db->prepare("
                    SELECT name, quantity FROM medicines
                    WHERE pharmacy_id = ? AND quantity <= reorder_level AND id IN ($placeholders)
                ");
                $stmt->execute(array_merge([currentPharmacyId()], $ids));
                foreach ($stmt->fetchAll() as $low) {
                    RealtimeHub::publish(currentPharmacyId(), 'low.stock', [
                        'medicine' => $low['name'],
                        'quantity' => (int)$low['quantity'],
                    ]);
                }
            }

            jsonResponse(['success'=>true, 'sale'=>$result]);
        } catch (Exception $e) {
            jsonResponse(['success'=>false, 'message'=>$e->getMessage()], 400);
        }
    }

    /**
     * Invoice / receipt view (printable).
     */
    public function invoice() {
        requireLogin();
        $id   = (int)($_GET['id'] ?? 0);
        $sale = $this->sale->find($id);
        if (!$sale) { flash('error', 'Sale not found'); redirect('index.php?page=sales'); }
        $items = $this->sale->items($id);
        require __DIR__ . '/../views/sales/invoice.php';
    }
}
