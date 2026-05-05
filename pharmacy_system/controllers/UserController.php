<?php
/**
 * User Controller — admin-only user management.
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
        $users = $this->user->all();
        require __DIR__ . '/../views/users/index.php';
    }

    public function create() {
        requireRole(['admin']);
        $branches = $this->db->query("SELECT * FROM branches WHERE is_active=1")->fetchAll();
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
        if (!in_array($_POST['role'] ?? '', ['admin','pharmacist','cashier'])) $errors[] = 'Invalid role';
        if ($this->user->usernameExists($_POST['username'] ?? '')) $errors[] = 'Username already exists';

        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect('index.php?page=users&action=create');
        }

        $this->user->create($_POST);
        flash('success', 'User created.');
        redirect('index.php?page=users');
    }

    public function edit() {
        requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->user->find($id);
        if (!$user) { flash('error','User not found'); redirect('index.php?page=users'); }
        $branches = $this->db->query("SELECT * FROM branches WHERE is_active=1")->fetchAll();
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
        if ($this->user->usernameExists($_POST['username'] ?? '', $id)) $errors[] = 'Username already exists';
        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect("index.php?page=users&action=edit&id=$id");
        }
        $this->user->update($id, $_POST);
        flash('success','User updated.');
        redirect('index.php?page=users');
    }

    public function delete() {
        requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        if ($id === (int)$_SESSION['user_id']) {
            flash('error','You cannot delete your own account.');
            redirect('index.php?page=users');
        }
        try {
            $this->user->delete($id);
            flash('success','User deleted.');
        } catch (Exception $e) {
            flash('error','Cannot delete user (has related records).');
        }
        redirect('index.php?page=users');
    }
}
