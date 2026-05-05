<?php
/**
 * Supplier Model.
 */
class Supplier {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function all() {
        return $this->db->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($d) {
        $stmt = $this->db->prepare("
            INSERT INTO suppliers (name, contact_person, phone, email, address, tax_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
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
        $stmt = $this->db->prepare("
            UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, tax_id=?, is_active=?
            WHERE id=?
        ");
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
        $stmt = $this->db->prepare("DELETE FROM suppliers WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
