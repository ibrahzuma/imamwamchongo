<?php
/**
 * Purchase Controller.
 */
require_once __DIR__ . '/../models/Purchase.php';
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../models/Supplier.php';
require_once __DIR__ . '/../lib/RealtimeHub.php';

class PurchaseController {
    private $db;
    private $purchase;

    public function __construct($db) {
        $this->db       = $db;
        $this->purchase = new Purchase($db, currentPharmacyId());
    }

    public function index() {
        requireLogin();
        $purchases = $this->purchase->all();
        require __DIR__ . '/../views/purchases/index.php';
    }

    public function create() {
        requireRole(['admin','pharmacist']);
        $suppliers = (new Supplier($this->db, currentPharmacyId()))->all();
        $medicines = (new Medicine($this->db, currentPharmacyId()))->all();
        require __DIR__ . '/../views/purchases/create.php';
    }

    public function store() {
        requireRole(['admin','pharmacist']);
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];

        if (!verifyCsrf($data['csrf_token'] ?? '')) {
            jsonResponse(['success'=>false, 'message'=>'Invalid security token'], 400);
        }
        if (empty($data['supplier_id'])) {
            jsonResponse(['success'=>false, 'message'=>'Supplier is required'], 400);
        }
        if (empty($data['items']) || !is_array($data['items'])) {
            jsonResponse(['success'=>false, 'message'=>'No items'], 400);
        }

        try {
            $payload = [
                'supplier_id' => (int)$data['supplier_id'],
                'user_id'     => $_SESSION['user_id'],
                'branch_id'   => $_SESSION['branch_id'] ?? null,
                'tax_amount'  => $data['tax_amount'] ?? 0,
                'discount'    => $data['discount']   ?? 0,
                'notes'       => $data['notes']      ?? null,
            ];
            $result = $this->purchase->create($payload, $data['items']);

            Cache::bumpPharmacyVersion(currentPharmacyId());

            RealtimeHub::publish(currentPharmacyId(), 'purchase.created', [
                'reference' => $result['reference_number'],
                'total'     => money($result['total']),
                'items'     => count($data['items']),
                'by'        => $_SESSION['full_name'] ?? '',
            ]);

            jsonResponse(['success'=>true, 'purchase'=>$result]);
        } catch (Exception $e) {
            jsonResponse(['success'=>false, 'message'=>$e->getMessage()], 400);
        }
    }

    public function show() {
        requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $purchase = $this->purchase->find($id);
        if (!$purchase) { flash('error','Not found'); redirect('index.php?page=purchases'); }
        $items = $this->purchase->items($id);
        require __DIR__ . '/../views/purchases/show.php';
    }
}
