<?php
/**
 * User Model — handles authentication & user CRUD.
 */
class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Authenticate a user with username + plain password.
     * Returns the user row on success, false otherwise.
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $up = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $up->execute([$user['id']]);
            return $user;
        }
        return false;
    }

    public function all() {
        return $this->db->query("
            SELECT u.*, b.name AS branch_name
            FROM users u
            LEFT JOIN branches b ON b.id = u.branch_id
            ORDER BY u.id DESC
        ")->fetchAll();
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO users (branch_id, username, password, full_name, email, phone, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['branch_id'] ?? 1,
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['full_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['role'],
            isset($data['is_active']) ? 1 : 0,
        ]);
    }

    public function update($id, $data) {
        // If password supplied, hash and include; otherwise leave unchanged.
        if (!empty($data['password'])) {
            $sql = "UPDATE users SET branch_id=?, username=?, password=?, full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?";
            $params = [
                $data['branch_id'] ?? 1,
                $data['username'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'],
                isset($data['is_active']) ? 1 : 0,
                $id,
            ];
        } else {
            $sql = "UPDATE users SET branch_id=?, username=?, full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?";
            $params = [
                $data['branch_id'] ?? 1,
                $data['username'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'],
                isset($data['is_active']) ? 1 : 0,
                $id,
            ];
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }
}
