<?php
/**
 * Pharmacy Model — manages tenants.
 *
 * Used by superadmin for CRUD across all pharmacies, and by a
 * pharmacy admin to read/update their own pharmacy profile.
 */
class Pharmacy {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function all() {
        return $this->db->query("
            SELECT p.*,
                   (SELECT COUNT(*) FROM users     u WHERE u.pharmacy_id = p.id) AS user_count,
                   (SELECT COUNT(*) FROM medicines m WHERE m.pharmacy_id = p.id) AS medicine_count
            FROM pharmacies p
            ORDER BY p.name ASC
        ")->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM pharmacies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM pharmacies WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function findByApiKey($apiKey) {
        $stmt = $this->db->prepare("SELECT * FROM pharmacies WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$apiKey]);
        return $stmt->fetch();
    }

    public function slugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM pharmacies WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public function create($d) {
        $stmt = $this->db->prepare("
            INSERT INTO pharmacies (name, slug, license_number, address, phone, email, api_key, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $d['name'],
            $d['slug'],
            $d['license_number'] ?? null,
            $d['address']        ?? null,
            $d['phone']          ?? null,
            $d['email']          ?? null,
            $d['api_key']        ?? null,
            isset($d['is_active']) ? 1 : 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update($id, $d) {
        $stmt = $this->db->prepare("
            UPDATE pharmacies SET name=?, slug=?, license_number=?, address=?, phone=?, email=?, is_active=?
            WHERE id=?
        ");
        return $stmt->execute([
            $d['name'],
            $d['slug'],
            $d['license_number'] ?? null,
            $d['address']        ?? null,
            $d['phone']          ?? null,
            $d['email']          ?? null,
            isset($d['is_active']) ? 1 : 0,
            $id,
        ]);
    }

    /**
     * Pharmacy admins use this to edit their own profile — no slug or
     * is_active changes (only superadmin can flip those).
     */
    public function updateProfile($id, $d) {
        $stmt = $this->db->prepare("
            UPDATE pharmacies SET name=?, license_number=?, address=?, phone=?, email=?
            WHERE id=?
        ");
        return $stmt->execute([
            $d['name'],
            $d['license_number'] ?? null,
            $d['address']        ?? null,
            $d['phone']          ?? null,
            $d['email']          ?? null,
            $id,
        ]);
    }

    public function setActive($id, $active) {
        $stmt = $this->db->prepare("UPDATE pharmacies SET is_active=? WHERE id=?");
        return $stmt->execute([$active ? 1 : 0, $id]);
    }

    public function rotateApiKey($id) {
        $key = 'pk_' . bin2hex(random_bytes(20));
        $stmt = $this->db->prepare("UPDATE pharmacies SET api_key=? WHERE id=?");
        $stmt->execute([$key, $id]);
        return $key;
    }
}
