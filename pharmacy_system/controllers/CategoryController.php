<?php
/**
 * Category Controller — per-pharmacy CRUD.
 * Both pharmacist and admin can manage categories within their own pharmacy;
 * deleting is admin-only.
 */
require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $db;
    private $category;

    public function __construct($db) {
        $this->db       = $db;
        $this->category = new Category($db, currentPharmacyId());
    }

    public function index() {
        requireRole(['admin','pharmacist']);
        $categories = $this->category->all();
        require __DIR__ . '/../views/categories/index.php';
    }

    public function create() {
        requireRole(['admin','pharmacist']);
        require __DIR__ . '/../views/categories/create.php';
    }

    public function store() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=categories');
        }
        if (empty(trim($_POST['name'] ?? ''))) {
            flash('error', 'Category name is required.');
            redirect('index.php?page=categories&action=create');
        }
        $this->category->create($_POST);
        flash('success', 'Category added.');
        redirect('index.php?page=categories');
    }

    public function edit() {
        requireRole(['admin','pharmacist']);
        $id = (int)($_GET['id'] ?? 0);
        $category = $this->category->find($id);
        if (!$category) { flash('error', 'Category not found.'); redirect('index.php?page=categories'); }
        require __DIR__ . '/../views/categories/edit.php';
    }

    public function update() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=categories');
        }
        $id = (int)($_POST['id'] ?? 0);
        if (empty(trim($_POST['name'] ?? ''))) {
            flash('error', 'Category name is required.');
            redirect("index.php?page=categories&action=edit&id=$id");
        }
        $this->category->update($id, $_POST);
        flash('success', 'Category updated.');
        redirect('index.php?page=categories');
    }

    public function delete() {
        requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('index.php?page=categories');
        }
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->category->delete($id);
            flash('success', 'Category deleted.');
        } catch (Exception $e) {
            flash('error', 'Cannot delete: category is referenced by medicines.');
        }
        redirect('index.php?page=categories');
    }
}
