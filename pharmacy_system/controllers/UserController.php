<?php
/**
 * User Controller — admin-scoped user management.
 *
 * Tenant model: a pharmacy admin can only CRUD users belonging to
 * their own pharmacy. The superadmin uses PharmacyController to
 * provision the initial admin of a new pharmacy.
 */
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db   = $db;
        $this->user = new User($db);
    }

    public function index() {
        requireRole(['admin']);
        $users = $this->user->all(currentPharmacyId());
        require __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        requireRole(['admin']);
        $branches = $this->branchesForPharmacy(currentPharmacyId());
        require __DIR__ . '/../views/users/create.php';
    }

    public function store() {
        requireRole(['admin']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error','Invalid security token.');
            redirect('index.php?page=users');
        }

        $errors = [];
        if (empty(trim($_POST['username'] ?? '')))   $errors[] = 'Username required';
        if (empty(trim($_POST['full_name'] ?? '')))  $errors[] = 'Full name required';
        if (strlen($_POST['password'] ?? '') < 6)    $errors[] = 'Password min 6 chars';
        if (($_POST['password'] ?? '') !== ($_POST['password_confirm'] ?? '')) $errors[] = 'Passwords do not match';
        // Pharmacy admins cannot create superadmins
        if (!in_array($_POST['role'] ?? '', ['admin','pharmacist','cashier'])) $errors[] = 'Invalid role';
        if ($this->user->usernameExists($_POST['username'] ?? '')) $errors[] = 'Username already exists';

        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect('index.php?page=users&action=create');
        }

        // Force the new user into the current admin's pharmacy
        $payload = $_POST;
        $payload['pharmacy_id'] = currentPharmacyId();
        // Validate branch (if provided) belongs to this pharmacy
        if (!empty($payload['branch_id']) && !$this->branchBelongsTo($payload['branch_id'], $payload['pharmacy_id'])) {
            flash('error', 'Selected branch does not belong to your pharmacy.');
            redirect('index.php?page=users&action=create');
        }

        $this->user->create($payload);
        flash('success', 'User created.');
        redirect('index.php?page=users');
    }

    public function edit() {
        requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->user->find($id, currentPharmacyId());
        if (!$user) { flash('error','User not found'); redirect('index.php?page=users'); }
        $branches = $this->branchesForPharmacy(currentPharmacyId());
        require __DIR__ . '/../views/users/edit.php';
    }

    public function update() {
        requireRole(['admin']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error','Invalid security token.');
            redirect('index.php?page=users');
        }
        $id = (int)($_POST['id'] ?? 0);
        $errors = [];
        if (empty(trim($_POST['username'] ?? '')))   $errors[] = 'Username required';
        if (empty(trim($_POST['full_name'] ?? '')))  $errors[] = 'Full name required';
        if (!empty($_POST['password']) && strlen($_POST['password']) < 6) $errors[] = 'Password min 6 chars';
        if (!empty($_POST['password']) && $_POST['password'] !== ($_POST['password_confirm'] ?? '')) $errors[] = 'Passwords do not match';
        if (!in_array($_POST['role'] ?? '', ['admin','pharmacist','cashier'])) $errors[] = 'Invalid role';
        if ($this->user->usernameExists($_POST['username'] ?? '', $id)) $errors[] = 'Username already exists';

        $pid = currentPharmacyId();
        if (!empty($_POST['branch_id']) && !$this->branchBelongsTo($_POST['branch_id'], $pid)) {
            $errors[] = 'Selected branch does not belong to your pharmacy';
        }

        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect("index.php?page=users&action=edit&id=$id");
        }
        $this->user->update($id, $_POST, $pid);
        flash('success','User updated.');
        redirect('index.php?page=users');
    }

    public function delete() {
        requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('index.php?page=users');
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            flash('error','You cannot delete your own account.');
            redirect('index.php?page=users');
        }
        try {
            $this->user->delete($id, currentPharmacyId());
            flash('success','User deleted.');
        } catch (Exception $e) {
            flash('error','Cannot delete user (has related records).');
        }
        redirect('index.php?page=users');
    }

    private function branchesForPharmacy($pid) {
        $stmt = $this->db->prepare("SELECT * FROM branches WHERE is_active=1 AND pharmacy_id=? ORDER BY name ASC");
        $stmt->execute([$pid]);
        return $stmt->fetchAll();
    }

    private function branchBelongsTo($branchId, $pid) {
        $stmt = $this->db->prepare("SELECT 1 FROM branches WHERE id=? AND pharmacy_id=?");
        $stmt->execute([(int)$branchId, $pid]);
        return (bool)$stmt->fetchColumn();
    }
}
