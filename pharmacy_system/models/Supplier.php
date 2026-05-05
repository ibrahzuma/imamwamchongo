<?php
/**
 * Supplier Model. Tenant-scoped by pharmacy_id.
 */
class Supplier {
    private $db;
    private $pharmacyId;

    public function __construct($db, $pharmacyId = null) {
        $this->db         = $db;
        $this->pharmacyId = $pharmacyId === null ? null : (int)$pharmacyId;
    }

    private function tenantClause() {
        return $this->pharmacyId === null ? '' : " AND pharmacy_id = " . $this->pharmacyId;
    }

    public function all() {
        $sql = "SELECT * FROM suppliers WHERE 1=1" . $this->tenantClause() . " ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function find($id) {
        $sql = "SELECT * FROM suppliers WHERE id = ?" . $this->tenantClause();
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($d) {
        if ($this->pharmacyId === null) {
            throw new Exception('Cannot create a supplier without a pharmacy context.');
        }
        $stmt = $this->db->prepare("
            INSERT INTO suppliers (pharmacy_id, name, contact_person, phone, email, address, tax_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $this->pharmacyId,
            $d['name'],
            $d['contact_person'] ?? null,
            $d['phone'] ?? null,
            $d['email'] ?? null,
            $d['address'] ?? null,
            $d['tax_id'] ?? null,
            isset($d['is_active']) ? 1 : 1,
        ]);
    }

    public function update($id, $d) {
        $sql = "UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, tax_id=?, is_active=?
                WHERE id=?" . $this->tenantClause();
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $d['name'],
            $d['contact_person'] ?? null,
            $d['phone'] ?? null,
            $d['email'] ?? null,
            $d['address'] ?? null,
            $d['tax_id'] ?? null,
            isset($d['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM suppliers WHERE id = ?" . $this->tenantClause();
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
