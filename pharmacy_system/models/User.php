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
     * Returns the user row (joined with pharmacy) on success, false otherwise.
     * Login is rejected if the user's pharmacy is disabled (except for superadmin).
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.name AS pharmacy_name, p.is_active AS pharmacy_active
            FROM users u
            LEFT JOIN pharmacies p ON p.id = u.pharmacy_id
            WHERE u.username = ? AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Non-superadmin must belong to an active pharmacy
        if ($user['role'] !== 'superadmin') {
            if (empty($user['pharmacy_id']) || (int)$user['pharmacy_active'] !== 1) {
                return false;
            }
        }

        $up = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $up->execute([$user['id']]);
        return $user;
    }

    /**
     * List users. Scoped to a pharmacy unless $pharmacyId is null
     * (superadmin sees everyone).
     */
    public function all($pharmacyId = null) {
        $sql = "
            SELECT u.*, b.name AS branch_name, p.name AS pharmacy_name
            FROM users u
            LEFT JOIN branches  b ON b.id = u.branch_id
            LEFT JOIN pharmacies p ON p.id = u.pharmacy_id
        ";
        $params = [];
        if ($pharmacyId !== null) {
            $sql .= " WHERE u.pharmacy_id = ?";
            $params[] = $pharmacyId;
        }
        $sql .= " ORDER BY u.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find user by id. Pass $pharmacyId to enforce tenant ownership;
     * pass null only for superadmin.
     */
    public function find($id, $pharmacyId = null) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $params = [$id];
        if ($pharmacyId !== null) {
            $sql .= " AND pharmacy_id = ?";
            $params[] = $pharmacyId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Create a user. pharmacy_id MUST be set in $data (or null only
     * when creating a superadmin).
     */
    public function create($data) {
        $sql = "INSERT INTO users (pharmacy_id, branch_id, username, password, full_name, email, phone, role, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['pharmacy_id'] ?? null,
            !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['full_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['role'],
            isset($data['is_active']) ? 1 : 0,
        ]);
    }

    /**
     * Update a user. Tenant scoping: pass $pharmacyId to constrain
     * the WHERE clause so an admin can only update users in their
     * own pharmacy. pharmacy_id itself is NOT modifiable here —
     * moving a user between tenants is intentionally not supported.
     */
    public function update($id, $data, $pharmacyId = null) {
        if (!empty($data['password'])) {
            $sql = "UPDATE users SET branch_id=?, username=?, password=?, full_name=?, email=?, phone=?, role=?, is_active=? WHERE id=?";
            $params = [
                !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
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
                !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
                $data['username'],
                $data['full_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['role'],
                isset($data['is_active']) ? 1 : 0,
                $id,
            ];
        }
        if ($pharmacyId !== null) {
            $sql .= " AND pharmacy_id = ?";
            $params[] = $pharmacyId;
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id, $pharmacyId = null) {
        $sql = "DELETE FROM users WHERE id = ?";
        $params = [$id];
        if ($pharmacyId !== null) {
            $sql .= " AND pharmacy_id = ?";
            $params[] = $pharmacyId;
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
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
