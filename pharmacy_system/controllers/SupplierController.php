<?php
/**
 * Supplier Controller.
 */
require_once __DIR__ . '/../models/Supplier.php';

class SupplierController {
    private $db;
    private $supplier;

    public function __construct($db) {
        $this->db       = $db;
        $this->supplier = new Supplier($db, currentPharmacyId());
    }

    public function index() {
        requireLogin();
        $suppliers = $this->supplier->all();
        require __DIR__ . '/../views/suppliers/index.php';
    }

    public function create() {
        requireRole(['admin','pharmacist']);
        require __DIR__ . '/../views/suppliers/create.php';
    }

    public function store() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=suppliers');
        }
        if (empty(trim($_POST['name'] ?? ''))) {
            flash('error', 'Supplier name is required.');
            redirect('index.php?page=suppliers&action=create');
        }
        $this->supplier->create($_POST);
        flash('success', 'Supplier added.');
        redirect('index.php?page=suppliers');
    }

    public function edit() {
        requireRole(['admin','pharmacist']);
        $id = (int)($_GET['id'] ?? 0);
        $supplier = $this->supplier->find($id);
        if (!$supplier) { flash('error', 'Supplier not found.'); redirect('index.php?page=suppliers'); }
        require __DIR__ . '/../views/suppliers/edit.php';
    }

    public function update() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=suppliers');
        }
        $id = (int)($_POST['id'] ?? 0);
        $this->supplier->update($id, $_POST);
        flash('success', 'Supplier updated.');
        redirect('index.php?page=suppliers');
    }

    public function delete() {
        requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('index.php?page=suppliers');
        }
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->supplier->delete($id);
            flash('success', 'Supplier deleted.');
        } catch (Exception $e) {
            flash('error', 'Cannot delete: supplier has related records.');
        }
        redirect('index.php?page=suppliers');
    }
}
