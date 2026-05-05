<?php
/**
 * Purchase Model — record purchases & auto-update stock.
 */
class Purchase {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Create a purchase record + items + add stock atomically.
     */
    public function create($data, $items) {
        if (empty($items)) {
            throw new Exception('Cannot create a purchase with no items.');
        }
        $this->db->beginTransaction();
        try {
            $subtotal = 0;
            foreach ($items as &$it) {
                $it['quantity']  = (int)$it['quantity'];
                $it['unit_cost'] = (float)$it['unit_cost'];
                $it['subtotal']  = $it['quantity'] * $it['unit_cost'];
                $subtotal += $it['subtotal'];
            }
            unset($it);

            $tax      = (float)($data['tax_amount'] ?? 0);
            $discount = (float)($data['discount']   ?? 0);
            $total    = $subtotal + $tax - $discount;
            $ref      = generatePurchaseNumber();

            $stmt = $this->db->prepare("
                INSERT INTO purchases (reference_number, supplier_id, user_id, branch_id, subtotal, tax_amount, discount, total, status, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)
            ");
            $stmt->execute([
                $ref,
                $data['supplier_id'],
                $data['user_id'],
                $data['branch_id'] ?? 1,
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
            // Optionally update batch & expiry on the medicine record itself
            $batchStmt  = $this->db->prepare("UPDATE medicines SET batch_number = ?, expiry_date = ? WHERE id = ?");
            $movStmt    = $this->db->prepare("
                INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_type, reference_id, notes, user_id)
                VALUES (?, 'in', ?, 'purchase', ?, ?, ?)
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
                $movStmt->execute([$it['medicine_id'], $it['quantity'], $purchaseId, "Purchase $ref", $data['user_id']]);
            }

            $this->db->commit();
            return ['id' => $purchaseId, 'reference_number' => $ref, 'total' => $total];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function all() {
        return $this->db->query("
            SELECT p.*, s.name AS supplier_name, u.full_name AS user_name
            FROM purchases p
            JOIN suppliers s ON s.id = p.supplier_id
            JOIN users u ON u.id = p.user_id
            ORDER BY p.created_at DESC
        ")->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, s.name AS supplier_name, s.phone AS supplier_phone,
                   s.email AS supplier_email, u.full_name AS user_name
            FROM purchases p
            JOIN suppliers s ON s.id = p.supplier_id
            JOIN users u ON u.id = p.user_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function items($purchaseId) {
        $stmt = $this->db->prepare("
            SELECT pi.*, m.name AS medicine_name, m.unit
            FROM purchase_items pi
            JOIN medicines m ON m.id = pi.medicine_id
            WHERE pi.purchase_id = ?
        ");
        $stmt->execute([$purchaseId]);
        return $stmt->fetchAll();
    }
}
