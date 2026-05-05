<?php
/**
 * Category Model.
 */
class Category {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function all() {
        return $this->db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($d) {
        $stmt = $this->db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        return $stmt->execute([$d['name'], $d['description'] ?? null]);
    }

    public function update($id, $d) {
        $stmt = $this->db->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        return $stmt->execute([$d['name'], $d['description'] ?? null, $id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
