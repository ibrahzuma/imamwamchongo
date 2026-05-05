<?php
/**
 * Medicine Model.
 * All queries are scoped by pharmacy_id (tenant isolation).
 * Pass pharmacyId = null only for superadmin contexts.
 */
class Medicine {
    private $db;
    private $pharmacyId;

    public function __construct($db, $pharmacyId = null) {
        $this->db         = $db;
        $this->pharmacyId = $pharmacyId === null ? null : (int)$pharmacyId;
    }

    private function tenantClause($alias = 'm') {
        return $this->pharmacyId === null ? '' : " AND $alias.pharmacy_id = " . $this->pharmacyId;
    }

    public function all($search = '') {
        $sql = "SELECT m.*, c.name AS category_name, s.name AS supplier_name
                FROM medicines m
                LEFT JOIN categories c ON c.id = m.category_id
                LEFT JOIN suppliers  s ON s.id = m.supplier_id
                WHERE 1=1" . $this->tenantClause('m');
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
        $sql = "SELECT * FROM medicines WHERE id = ?" . $this->tenantClause('medicines');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByBarcode($barcode) {
        $sql = "SELECT * FROM medicines WHERE barcode = ? AND is_active = 1"
             . $this->tenantClause('medicines');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }

    public function search($term) {
        $sql = "SELECT id, name, generic_name, barcode, selling_price, quantity, unit
                FROM medicines
                WHERE is_active = 1 AND quantity > 0"
             . $this->tenantClause('medicines') . "
                  AND (name LIKE ? OR generic_name LIKE ? OR barcode LIKE ?)
                ORDER BY name LIMIT 15";
        $stmt = $this->db->prepare($sql);
        $like = "%$term%";
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    public function create($d) {
        if ($this->pharmacyId === null) {
            throw new Exception('Cannot create a medicine without a pharmacy context.');
        }
        $sql = "INSERT INTO medicines
            (pharmacy_id, barcode, name, generic_name, category_id, supplier_id, unit, batch_number, expiry_date,
             cost_price, selling_price, quantity, reorder_level, description, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $this->pharmacyId,
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
                WHERE id=?" . $this->tenantClause('medicines');
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
        $sql = "DELETE FROM medicines WHERE id = ?" . $this->tenantClause('medicines');
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Adjust stock & log a movement. Stamps pharmacy_id on the movement row.
     */
    public function adjustStock($medicineId, $qty, $type, $refType = 'manual', $refId = null, $userId = null, $notes = '') {
        if ($this->pharmacyId === null) {
            throw new Exception('Cannot adjust stock without a pharmacy context.');
        }
        // Confirm the medicine actually belongs to this tenant before mutating
        $check = $this->db->prepare("SELECT 1 FROM medicines WHERE id = ? AND pharmacy_id = ?");
        $check->execute([$medicineId, $this->pharmacyId]);
        if (!$check->fetchColumn()) {
            throw new Exception('Medicine does not belong to this pharmacy.');
        }
        if ($type === 'in') {
            $this->db->prepare("UPDATE medicines SET quantity = quantity + ? WHERE id = ?")
                     ->execute([$qty, $medicineId]);
        } else {
            $this->db->prepare("UPDATE medicines SET quantity = GREATEST(0, quantity - ?) WHERE id = ?")
                     ->execute([$qty, $medicineId]);
        }
        $this->db->prepare("
            INSERT INTO stock_movements (pharmacy_id, medicine_id, movement_type, quantity, reference_type, reference_id, notes, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$this->pharmacyId, $medicineId, $type, $qty, $refType, $refId, $notes, $userId]);
    }

    public function lowStock($limit = null) {
        $sql = "SELECT m.*, c.name AS category_name
                FROM medicines m
                LEFT JOIN categories c ON c.id = m.category_id
                WHERE m.is_active = 1 AND m.quantity <= m.reorder_level"
             . $this->tenantClause('m') . "
                ORDER BY m.quantity ASC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll();
    }

    public function expiring($days = 60) {
        $sql = "SELECT m.*, c.name AS category_name
                FROM medicines m
                LEFT JOIN categories c ON c.id = m.category_id
                WHERE m.is_active = 1
                  AND m.expiry_date IS NOT NULL
                  AND m.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)"
             . $this->tenantClause('m') . "
                ORDER BY m.expiry_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function movements($medicineId = null, $limit = 50) {
        $sql = "SELECT sm.*, m.name AS medicine_name, u.full_name AS user_name
                FROM stock_movements sm
                JOIN medicines m ON m.id = sm.medicine_id
                LEFT JOIN users u ON u.id = sm.user_id
                WHERE 1=1" . $this->tenantClause('sm');
        $params = [];
        if ($medicineId) {
            $sql .= " AND sm.medicine_id = ?";
            $params[] = $medicineId;
        }
        $sql .= " ORDER BY sm.created_at DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
