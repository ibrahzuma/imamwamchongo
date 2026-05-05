<?php
/**
 * Pharmacy Controller.
 *
 * Superadmin actions: list / create / edit / toggle / rotate-key
 * Pharmacy admin actions: profile / updateProfile (own pharmacy only)
 */
require_once __DIR__ . '/../models/Pharmacy.php';
require_once __DIR__ . '/../models/User.php';

class PharmacyController {
    private $db;
    private $pharmacy;

    public function __construct($db) {
        $this->db       = $db;
        $this->pharmacy = new Pharmacy($db);
    }

    /* ---------------- Superadmin ---------------- */

    public function index() {
        requireSuperadmin();
        $pharmacies = $this->pharmacy->all();
        require __DIR__ . '/../views/pharmacies/index.php';
    }

    public function create() {
        requireSuperadmin();
        require __DIR__ . '/../views/pharmacies/create.php';
    }

    public function store() {
        requireSuperadmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=pharmacies&action=create');
        }

        $errors = $this->validatePharmacy($_POST);
        // The initial admin user is required when provisioning a new pharmacy
        if (empty(trim($_POST['admin_username'] ?? ''))) $errors[] = 'Admin username required';
        if (empty(trim($_POST['admin_full_name'] ?? ''))) $errors[] = 'Admin full name required';
        if (strlen($_POST['admin_password'] ?? '') < 6)  $errors[] = 'Admin password min 6 chars';
        $userModel = new User($this->db);
        if ($userModel->usernameExists($_POST['admin_username'] ?? '')) $errors[] = 'Admin username already exists';
        if ($this->pharmacy->slugExists($_POST['slug'] ?? '')) $errors[] = 'Slug already in use';

        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect('index.php?page=pharmacies&action=create');
        }

        $this->db->beginTransaction();
        try {
            $payload = $_POST;
            $payload['api_key'] = 'pk_' . bin2hex(random_bytes(20));
            $payload['is_active'] = 1;
            $pharmacyId = $this->pharmacy->create($payload);

            $userModel->create([
                'pharmacy_id' => $pharmacyId,
                'branch_id'   => null,
                'username'    => $_POST['admin_username'],
                'password'    => $_POST['admin_password'],
                'full_name'   => $_POST['admin_full_name'],
                'email'       => $_POST['admin_email'] ?? null,
                'phone'       => $_POST['admin_phone'] ?? null,
                'role'        => 'admin',
                'is_active'   => 1,
            ]);

            $this->db->commit();
            flash('success', 'Pharmacy created. API key: ' . $payload['api_key']);
        } catch (Exception $e) {
            $this->db->rollBack();
            flash('error', 'Failed to create pharmacy: ' . $e->getMessage());
        }
        redirect('index.php?page=pharmacies');
    }

    public function edit() {
        requireSuperadmin();
        $id = (int)($_GET['id'] ?? 0);
        $pharmacy = $this->pharmacy->find($id);
        if (!$pharmacy) { flash('error', 'Pharmacy not found.'); redirect('index.php?page=pharmacies'); }
        require __DIR__ . '/../views/pharmacies/edit.php';
    }

    public function update() {
        requireSuperadmin();
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=pharmacies');
        }
        $id = (int)($_POST['id'] ?? 0);
        $errors = $this->validatePharmacy($_POST);
        if ($this->pharmacy->slugExists($_POST['slug'] ?? '', $id)) $errors[] = 'Slug already in use';
        if ($errors) {
            flash('error', implode(' | ', $errors));
            redirect("index.php?page=pharmacies&action=edit&id=$id");
        }
        $this->pharmacy->update($id, $_POST);
        flash('success', 'Pharmacy updated.');
        redirect('index.php?page=pharmacies');
    }

    public function toggle() {
        requireSuperadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('index.php?page=pharmacies');
        }
        $id = (int)($_POST['id'] ?? 0);
        $p  = $this->pharmacy->find($id);
        if (!$p) { flash('error', 'Pharmacy not found.'); redirect('index.php?page=pharmacies'); }
        $this->pharmacy->setActive($id, !$p['is_active']);
        flash('success', $p['is_active'] ? 'Pharmacy disabled.' : 'Pharmacy enabled.');
        redirect('index.php?page=pharmacies');
    }

    public function rotateKey() {
        requireSuperadmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid request.');
            redirect('index.php?page=pharmacies');
        }
        $id  = (int)($_POST['id'] ?? 0);
        $key = $this->pharmacy->rotateApiKey($id);
        flash('success', 'New API key: ' . $key);
        redirect('index.php?page=pharmacies');
    }

    /* ---------------- Pharmacy admin (own profile) ---------------- */

    public function profile() {
        requireRole(['admin']);
        $pid = currentPharmacyId();
        $pharmacy = $this->pharmacy->find($pid);
        if (!$pharmacy) { flash('error', 'Pharmacy profile not available.'); redirect('index.php?page=dashboard'); }
        require __DIR__ . '/../views/pharmacies/profile.php';
    }

    public function updateProfile() {
        requireRole(['admin']);
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token.');
            redirect('index.php?page=pharmacies&action=profile');
        }
        $pid = currentPharmacyId();
        if (empty(trim($_POST['name'] ?? ''))) {
            flash('error', 'Pharmacy name is required.');
            redirect('index.php?page=pharmacies&action=profile');
        }
        $this->pharmacy->updateProfile($pid, $_POST);
        // Reflect updated name in the session for the header
        $_SESSION['pharmacy_name'] = $_POST['name'];
        flash('success', 'Profile updated.');
        redirect('index.php?page=pharmacies&action=profile');
    }

    /* ---------------- helpers ---------------- */

    private function validatePharmacy($d) {
        $errors = [];
        if (empty(trim($d['name'] ?? '')))              $errors[] = 'Name is required';
        $slug = $d['slug'] ?? '';
        if (!preg_match('/^[a-z0-9][a-z0-9-]{1,79}$/', $slug)) {
            $errors[] = 'Slug must be lowercase alphanumeric with dashes (2–80 chars)';
        }
        return $errors;
    }
}
