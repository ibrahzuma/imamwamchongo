<?php
/**
 * Purchase Model — record purchases & auto-update stock. Tenant-scoped.
 */
class Purchase {
    private $db;
    private $pharmacyId;

    public function __construct($db, $pharmacyId = null) {
        $this->db         = $db;
        $this->pharmacyId = $pharmacyId === null ? null : (int)$pharmacyId;
    }

    private function tenantClause($alias = 'p') {
        return $this->pharmacyId === null ? '' : " AND $alias.pharmacy_id = " . $this->pharmacyId;
    }

    public function create($data, $items) {
        if ($this->pharmacyId === null) {
            throw new Exception('Cannot create a purchase without a pharmacy context.');
        }
        if (empty($items)) {
            throw new Exception('Cannot create a purchase with no items.');
        }
        // Verify supplier belongs to this pharmacy
        $sup = $this->db->prepare("SELECT 1 FROM suppliers WHERE id = ? AND pharmacy_id = ?");
        $sup->execute([$data['supplier_id'], $this->pharmacyId]);
        if (!$sup->fetchColumn()) {
            throw new Exception('Supplier does not belong to this pharmacy.');
        }
        $this->db->beginTransaction();
        try {
            $subtotal = 0;
            foreach ($items as &$it) {
                $it['quantity']  = (int)$it['quantity'];
                $it['unit_cost'] = (float)$it['unit_cost'];
                $it['subtotal']  = $it['quantity'] * $it['unit_cost'];
                $subtotal += $it['subtotal'];

                // Verify medicine belongs to this pharmacy
                $check = $this->db->prepare("SELECT 1 FROM medicines WHERE id = ? AND pharmacy_id = ?");
                $check->execute([$it['medicine_id'], $this->pharmacyId]);
                if (!$check->fetchColumn()) {
                    throw new Exception("Medicine ID {$it['medicine_id']} does not belong to this pharmacy");
                }
            }
            unset($it);

            $tax      = (float)($data['tax_amount'] ?? 0);
            $discount = (float)($data['discount']   ?? 0);
            $total    = $subtotal + $tax - $discount;
            $ref      = generatePurchaseNumber();

            $stmt = $this->db->prepare("
                INSERT INTO purchases (pharmacy_id, reference_number, supplier_id, user_id, branch_id, subtotal, tax_amount, discount, total, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)
            ");
            $stmt->execute([
                $this->pharmacyId,
                $ref,
                $data['supplier_id'],
                $data['user_id'],
                !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
                $subtotal,
                $tax,
                $discount,
                $total,
                $data['notes'] ?? null,
            ]);
            $purchaseId = (int)$this->db->lastInsertId();

            $itemStmt   = $this->db->prepare("
                INSERT INTO purchase_items (purchase_id, medicine_id, quantity, unit_cost, subtotal, batch_number, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stockStmt  = $this->db->prepare("UPDATE medicines SET quantity = quantity + ?, cost_price = ? WHERE id = ?");
            $batchStmt  = $this->db->prepare("UPDATE medicines SET batch_number = ?, expiry_date = ? WHERE id = ?");
            $movStmt    = $this->db->prepare("
                INSERT INTO stock_movements (pharmacy_id, medicine_id, movement_type, quantity, reference_type, reference_id, notes, user_id)
                VALUES (?, ?, 'in', ?, 'purchase', ?, ?, ?)
            ");

            foreach ($items as $it) {
                $itemStmt->execute([
                    $purchaseId,
                    $it['medicine_id'],
                    $it['quantity'],
                    $it['unit_cost'],
                    $it['subtotal'],
                    $it['batch_number'] ?? null,
                    $it['expiry_date']  ?: null,
                ]);
                $stockStmt->execute([$it['quantity'], $it['unit_cost'], $it['medicine_id']]);
                if (!empty($it['batch_number']) || !empty($it['expiry_date'])) {
                    $batchStmt->execute([$it['batch_number'] ?? null, $it['expiry_date'] ?: null, $it['medicine_id']]);
                }
                $movStmt->execute([$this->pharmacyId, $it['medicine_id'], $it['quantity'], $purchaseId, "Purchase $ref", $data['user_id']]);
            }

            $this->db->commit();
            return ['id' => $purchaseId, 'reference_number' => $ref, 'total' => $total];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function all() {
        $sql = "SELECT p.*, s.name AS supplier_name, u.full_name AS user_name
                FROM purchases p
                JOIN suppliers s ON s.id = p.supplier_id
                JOIN users u     ON u.id = p.user_id
                WHERE 1=1" . $this->tenantClause('p') . "
                ORDER BY p.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function find($id) {
        $sql = "SELECT p.*, s.name AS supplier_name, s.contact_person, s.phone AS supplier_phone,
                       s.email AS supplier_email, u.full_name AS user_name
                FROM purchases p
                JOIN suppliers s ON s.id = p.supplier_id
                JOIN users u     ON u.id = p.user_id
                WHERE p.id = ?" . $this->tenantClause('p');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function items($purchaseId) {
        $sql = "SELECT pi.*, m.name AS medicine_name, m.unit
                FROM purchase_items pi
                JOIN purchases p ON p.id = pi.purchase_id
                JOIN medicines m ON m.id = pi.medicine_id
                WHERE pi.purchase_id = ?" . $this->tenantClause('p');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$purchaseId]);
        return $stmt->fetchAll();
    }
}
