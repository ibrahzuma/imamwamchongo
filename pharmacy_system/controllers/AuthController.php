<?php
/**
 * Authentication Controller — login / logout.
 */
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db   = $db;
        $this->user = new User($db);
    }

    public function showLogin() {
        if (isLoggedIn()) redirect('index.php?page=dashboard');
        $error = flash('error');
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login() {
        if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
            flash('error', 'Invalid security token. Please try again.');
            redirect('index.php?page=login');
        }

        // Brute-force throttle: 5 failed attempts -> 10-minute cool-down.
        // Tracked per session (per browser/cookie) — not perfect across IPs,
        // but stops the casual attempt-spammer without an extra table.
        $now      = time();
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lockTill = $_SESSION['login_locked_until'] ?? 0;
        if ($lockTill > $now) {
            $wait = (int)ceil(($lockTill - $now) / 60);
            flash('error', "Too many failed attempts. Try again in $wait minute(s).");
            redirect('index.php?page=login');
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Please enter both username and password.');
            redirect('index.php?page=login');
        }

        $u = $this->user->login($username, $password);
        if ($u) {
            // Defeat session fixation: regenerate the ID so a pre-set
            // attacker cookie can't ride the just-authenticated session.
            session_regenerate_id(true);

            unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);

            $_SESSION['user_id']       = $u['id'];
            $_SESSION['username']      = $u['username'];
            $_SESSION['full_name']     = $u['full_name'];
            $_SESSION['role']          = $u['role'];
            $_SESSION['branch_id']     = $u['branch_id'] ?? null;
            $_SESSION['pharmacy_id']   = $u['pharmacy_id'] ?? null;
            $_SESSION['pharmacy_name'] = $u['pharmacy_name'] ?? '';
            redirect('index.php?page=dashboard');
        }

        $_SESSION['login_attempts'] = $attempts + 1;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_locked_until'] = $now + 600; // 10 minutes
            $_SESSION['login_attempts'] = 0;
        }
        flash('error', 'Invalid credentials, account disabled, or pharmacy inactive.');
        redirect('index.php?page=login');
    }

    public function logout() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('index.php?page=login');
    }
}
