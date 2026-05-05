<?php
/**
 * Medicine Model.
 * Handles CRUD plus stock movement helpers.
 */
class Medicine {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function all($search = '') {
        $sql = "SELECT m.*, c.name AS category_name, s.name AS supplier_name
                FROM medicines m
                LEFT JOIN categories c ON c.id = m.category_id
                LEFT JOIN suppliers  s ON s.id = m.supplier_id
                WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (m.name LIKE ? OR m.generic_name LIKE ? OR m.barcode LIKE ?)";
            $like = "%$search%";
            $params = [$like, $like, $like];
        }
        $sql .= " ORDER BY m.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM medicines WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByBarcode($barcode) {
        $stmt = $this->db->prepare("SELECT * FROM medicines WHERE barcode = ? AND is_active = 1");
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }

    public function search($term) {
        $stmt = $this->db->prepare("
            SELECT id, name, generic_name, barcode, selling_price, quantity, unit
            FROM medicines
            WHERE is_active = 1 AND quantity > 0
              AND (name LIKE ? OR generic_name LIKE ? OR barcode LIKE ?)
            ORDER BY name LIMIT 15
        ");
        $like = "%$term%";
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function create($d) {
        $sql = "INSERT INTO medicines
            (barcode, name, generic_name, category_id, supplier_id, unit, batch_number, expiry_date,
             cost_price, selling_price, quantity, reorder_level, description, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $d['barcode']        ?: null,
            $d['name'],
            $d['generic_name']   ?? null,
            $d['category_id']    ?: null,
            $d['supplier_id']    ?: null,
            $d['unit']           ?? 'pcs',
            $d['batch_number']   ?? null,
            $d['expiry_date']    ?: null,
            $d['cost_price']     ?? 0,
            $d['selling_price']  ?? 0,
            $d['quantity']       ?? 0,
            $d['reorder_level']  ?? 10,
            $d['description']    ?? null,
            isset($d['is_active']) ? 1 : 1,
        ]);
    }

    public function update($id, $d) {
        $sql = "UPDATE medicines SET
                barcode=?, name=?, generic_name=?, category_id=?, supplier_id=?, unit=?,
                batch_number=?, expiry_date=?, cost_price=?, selling_price=?, quantity=?,
                reorder_level=?, description=?, is_active=?
                WHERE id=?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $d['barcode']        ?: null,
            $d['name'],
            $d['generic_name']   ?? null,
            $d['category_id']    ?: null,
            $d['supplier_id']    ?: null,
            $d['unit']           ?? 'pcs',
            $d['batch_number']   ?? null,
            $d['expiry_date']    ?: null,
            $d['cost_price']     ?? 0,
            $d['selling_price']  ?? 0,
            $d['quantity']       ?? 0,
            $d['reorder_level']  ?? 10,
            $d['description']    ?? null,
            isset($d['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM medicines WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Adjust stock & log a movement in the same transaction-friendly fashion.
     */
    public function adjustStock($medicineId, $qty, $type, $refType = 'manual', $refId = null, $userId = null, $notes = '') {
        // Update quantity
        if ($type === 'in') {
            $this->db->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?")
                     ->execute([$qty, $medicineId]);
        } else {
            $this->db->prepare("UPDATE medicines SET quantity = GREATEST(0, quantity - ?) WHERE id = ?")
                     ->execute([$qty, $medicineId]);
        }
        // Log movement
        $this->db->prepare("
            INSERT INTO stock_movements (medicine_id, movement_type, quantity, reference_type, reference_id, notes, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$medicineId, $type, $qty, $refType, $refId, $notes, $userId]);
    }

    public function lowStock($limit = null) {
        $sql = "SELECT m.*, c.name AS category_name
                FROM medicines m
                LEFT JOIN categories c ON c.id = m.category_id
                WHERE m.is_active = 1 AND m.quantity <= m.reorder_level
                ORDER BY m.quantity ASC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }

    public function expiring($days = 60) {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name AS category_name
            FROM medicines m
            LEFT JOIN categories c ON c.id = m.category_id
            WHERE m.is_active = 1
              AND m.expiry_date IS NOT NULL
              AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY m.expiry_date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function movements($medicineId = null, $limit = 50) {
        $sql = "SELECT sm.*, m.name AS medicine_name, u.full_name AS user_name
                FROM stock_movements sm
                JOIN medicines m ON m.id = sm.medicine_id
                LEFT JOIN users u ON u.id = sm.user_id";
        $params = [];
        if ($medicineId) {
            $sql .= " WHERE sm.medicine_id = ?";
            $params[] = $medicineId;
        }
        $sql .= " ORDER BY sm.created_at DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
