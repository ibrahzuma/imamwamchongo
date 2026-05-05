<?php
/**
 * Category Model. Tenant-scoped by pharmacy_id.
 */
class Category {
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
        $sql = "SELECT * FROM categories WHERE 1=1" . $this->tenantClause() . " ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function find($id) {
        $sql = "SELECT * FROM categories WHERE id = ?" . $this->tenantClause();
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($d) {
        if ($this->pharmacyId === null) {
            throw new Exception('Cannot create a category without a pharmacy context.');
        }
        $stmt = $this->db->prepare("INSERT INTO categories (pharmacy_id, name, description) VALUES (?, ?, ?)");
        return $stmt->execute([$this->pharmacyId, $d['name'], $d['description'] ?? null]);
    }

    public function update($id, $d) {
        $sql = "UPDATE categories SET name=?, description=? WHERE id=?" . $this->tenantClause();
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$d['name'], $d['description'] ?? null, $id]);
    }

    public function delete($id) {
        $sql = "DELETE FROM categories WHERE id = ?" . $this->tenantClause();
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
}
