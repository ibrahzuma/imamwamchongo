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
        $this->medicine = new Medicine($db, currentPharmacyId());
    }

    public function index() {
        requireLogin();
        $search    = sanitize($_GET['search'] ?? '');
        $medicines = $this->medicine->all($search);
        require __DIR__ . '/../views/medicines/index.php';
    }

    public function create() {
        requireRole(['admin','pharmacist']);
        $categories = (new Category($this->db, currentPharmacyId()))->all();
        $suppliers  = (new Supplier($this->db, currentPharmacyId()))->all();
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
            Cache::bumpPharmacyVersion(currentPharmacyId());
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
        $categories = (new Category($this->db, currentPharmacyId()))->all();
        $suppliers  = (new Supplier($this->db, currentPharmacyId()))->all();
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
            Cache::bumpPharmacyVersion(currentPharmacyId());
            flash('success', 'Medicine updated successfully.');
        } catch (Exception $e) {
            flash('error', 'Update failed: ' . $e->getMessage());
        }
        redirect('index.php?page=medicines');
    }

    public function delete() {
        requireRole(['admin']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('index.php?page=medicines');
        }
        $id = (int)($_POST['id'] ?? 0);
        try {
            $this->medicine->delete($id);
            Cache::bumpPharmacyVersion(currentPharmacyId());
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

    /* -----------------------------------------------------------
     *  BULK IMPORT (CSV — opens natively in Excel)
     * ---------------------------------------------------------*/

    private static $importColumns = [
        'name', 'generic_name', 'category', 'supplier', 'barcode', 'unit',
        'batch_number', 'expiry_date', 'cost_price', 'selling_price',
        'quantity', 'reorder_level', 'description',
    ];

    public function bulkImport() {
        requireRole(['admin','pharmacist']);
        require __DIR__ . '/../views/medicines/bulk.php';
    }

    public function downloadTemplate() {
        requireRole(['admin','pharmacist']);

        $filename = 'medicines_import_template.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM so Excel reads non-ASCII characters correctly
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, self::$importColumns);
        // One example row to make the format obvious
        fputcsv($out, [
            'Paracetamol 500mg', 'Paracetamol', 'Painkillers', 'ABC Pharma Ltd',
            '1234567890123', 'tablet', 'BATCH-001', '2027-12-31',
            '50.00', '100.00', '200', '20', 'Sample row — delete before importing',
        ]);
        fclose($out);
        exit;
    }

    public function bulkStore() {
        requireRole(['admin','pharmacist']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=medicines&action=bulkImport');
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Please choose a CSV file to upload.');
            redirect('index.php?page=medicines&action=bulkImport');
        }

        $tmp = $_FILES['csv_file']['tmp_name'];
        $size = (int)$_FILES['csv_file']['size'];
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            flash('error', 'File is empty or larger than 5 MB.');
            redirect('index.php?page=medicines&action=bulkImport');
        }

        $fh = fopen($tmp, 'r');
        if (!$fh) {
            flash('error', 'Could not read uploaded file.');
            redirect('index.php?page=medicines&action=bulkImport');
        }

        $header = fgetcsv($fh);
        if (!$header) {
            fclose($fh);
            flash('error', 'CSV file is empty.');
            redirect('index.php?page=medicines&action=bulkImport');
        }
        // Strip UTF-8 BOM from first cell if present
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $header = array_map(fn($h) => strtolower(trim((string)$h)), $header);

        $required = ['name', 'cost_price', 'selling_price', 'quantity'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                fclose($fh);
                flash('error', "Missing required column: $col. Download the template for the correct format.");
                redirect('index.php?page=medicines&action=bulkImport');
            }
        }

        $pharmacyId = currentPharmacyId();
        $categoryMap = $this->buildLookupMap('categories', $pharmacyId);
        $supplierMap = $this->buildLookupMap('suppliers', $pharmacyId);

        $imported = 0;
        $rowErrors = [];
        $rowNum = 1;

        try {
            $this->db->beginTransaction();

            while (($row = fgetcsv($fh)) !== false) {
                $rowNum++;
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue; // skip empty lines
                }
                $data = [];
                foreach ($header as $i => $col) {
                    $data[$col] = isset($row[$i]) ? trim((string)$row[$i]) : '';
                }

                $errors = $this->validate($data);
                if ($errors) {
                    $rowErrors[] = "Row $rowNum: " . implode('; ', $errors);
                    continue;
                }

                $payload = [
                    'name'          => $data['name'],
                    'generic_name'  => $data['generic_name'] ?? null,
                    'barcode'       => $data['barcode'] ?? null,
                    'unit'          => $data['unit'] !== '' ? $data['unit'] : 'pcs',
                    'batch_number'  => $data['batch_number'] ?? null,
                    'expiry_date'   => $data['expiry_date'] !== '' ? $data['expiry_date'] : null,
                    'cost_price'    => $data['cost_price'],
                    'selling_price' => $data['selling_price'],
                    'quantity'      => $data['quantity'],
                    'reorder_level' => $data['reorder_level'] !== '' ? $data['reorder_level'] : 10,
                    'description'   => $data['description'] ?? null,
                    'category_id'   => $this->resolveLookup($categoryMap, $data['category'] ?? ''),
                    'supplier_id'   => $this->resolveLookup($supplierMap, $data['supplier'] ?? ''),
                    'is_active'     => 1,
                ];

                try {
                    $this->medicine->create($payload);
                    $imported++;
                } catch (Exception $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'uniq_pharmacy_barcode') !== false || stripos($msg, 'Duplicate') !== false) {
                        $msg = 'Duplicate barcode (already exists in this pharmacy)';
                    }
                    $rowErrors[] = "Row $rowNum: $msg";
                }
            }

            if ($rowErrors) {
                $this->db->rollBack();
            } else {
                $this->db->commit();
                Cache::bumpPharmacyVersion($pharmacyId);
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            fclose($fh);
            flash('error', 'Import failed: ' . $e->getMessage());
            redirect('index.php?page=medicines&action=bulkImport');
        }
        fclose($fh);

        if ($rowErrors) {
            $preview = array_slice($rowErrors, 0, 10);
            $more = count($rowErrors) > 10 ? ' (+' . (count($rowErrors) - 10) . ' more)' : '';
            flash('error', 'No medicines imported — fix errors and re-upload: ' . implode(' | ', $preview) . $more);
            redirect('index.php?page=medicines&action=bulkImport');
        }

        flash('success', "Imported $imported medicine(s) successfully.");
        redirect('index.php?page=medicines');
    }

    /**
     * Build a case-insensitive name → id map for the given tenant-scoped table.
     */
    private function buildLookupMap($table, $pharmacyId) {
        $allowed = ['categories' => true, 'suppliers' => true];
        if (!isset($allowed[$table])) return [];
        $stmt = $this->db->prepare("SELECT id, name FROM $table WHERE pharmacy_id = ?");
        $stmt->execute([$pharmacyId]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[mb_strtolower(trim($row['name']))] = (int)$row['id'];
        }
        return $map;
    }

    private function resolveLookup($map, $name) {
        $key = mb_strtolower(trim((string)$name));
        if ($key === '') return null;
        return $map[$key] ?? null;
    }
}
