<?php
/**
 * Medicine Controller.
 */
require_once __DIR__ . '/../models/Medicine.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Supplier.php';

class MedicineController {
    private $db;
    private $medicine;

    public function __construct($db) {
        $this->db       = $db;
        $this->medicine = new Medicine($db);
    }

    public function index() {
        requireLogin();
        $search    = sanitize($_GET['search'] ?? '');
        $medicines = $this->medicine->all($search);
        require __DIR__ . '/../views/medicines/index.php';
    }

    public function create() {
        requireRole(['admin','pharmacist']);
        $categories = (new Category($this->db))->all();
        $suppliers  = (new Supplier($this->db))->all();
        require __DIR__ . '/../views/medicines/create.php';
    }

    public function store() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=medicines');
        }

        $errors = $this->validate($_POST);
        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect('index.php?page=medicines&action=create');
        }

        try {
            $this->medicine->create($_POST);
            flash('success', 'Medicine added successfully.');
        } catch (Exception $e) {
            flash('error', 'Failed to add medicine: ' . $e->getMessage());
        }
        redirect('index.php?page=medicines');
    }

    public function edit() {
        requireRole(['admin','pharmacist']);
        $id        = (int)($_GET['id'] ?? 0);
        $medicine  = $this->medicine->find($id);
        if (!$medicine) { flash('error', 'Medicine not found.'); redirect('index.php?page=medicines'); }
        $categories = (new Category($this->db))->all();
        $suppliers  = (new Supplier($this->db))->all();
        require __DIR__ . '/../views/medicines/edit.php';
    }

    public function update() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=medicines');
        }
        $id = (int)($_POST['id'] ?? 0);
        $errors = $this->validate($_POST);
        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect("index.php?page=medicines&action=edit&id=$id");
        }
        try {
            $this->medicine->update($id, $_POST);
            flash('success', 'Medicine updated successfully.');
        } catch (Exception $e) {
            flash('error', 'Update failed: ' . $e->getMessage());
        }
        redirect('index.php?page=medicines');
    }

    public function delete() {
        requireRole(['admin']);
        $id = (int)($_GET['id'] ?? 0);
        try {
            $this->medicine->delete($id);
            flash('success', 'Medicine deleted.');
        } catch (Exception $e) {
            flash('error', 'Delete failed (referenced by other records).');
        }
        redirect('index.php?page=medicines');
    }

    /**
     * AJAX endpoint — search medicines for POS autocomplete.
     */
    public function ajaxSearch() {
        requireLogin();
        $term  = sanitize($_GET['q'] ?? '');
        $items = $this->medicine->search($term);
        jsonResponse($items);
    }

    /**
     * AJAX — find by barcode (for barcode scanner).
     */
    public function ajaxBarcode() {
        requireLogin();
        $barcode = sanitize($_GET['barcode'] ?? '');
        $m = $this->medicine->findByBarcode($barcode);
        if (!$m) jsonResponse(['error' => 'Not found'], 404);
        jsonResponse($m);
    }

    private function validate($data) {
        $errors = [];
        if (empty(trim($data['name'] ?? '')))          $errors[] = 'Name is required';
        if (!is_numeric($data['cost_price'] ?? null))  $errors[] = 'Valid cost price required';
        if (!is_numeric($data['selling_price'] ?? null))$errors[] = 'Valid selling price required';
        if (!is_numeric($data['quantity'] ?? null))    $errors[] = 'Valid quantity required';
        return $errors;
    }
}
