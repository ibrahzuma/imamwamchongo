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
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            flash('error', 'Please enter both username and password.');
            redirect('index.php?page=login');
        }

        $u = $this->user->login($username, $password);
        if ($u) {
            $_SESSION['user_id']   = $u['id'];
            $_SESSION['username']  = $u['username'];
            $_SESSION['full_name'] = $u['full_name'];
            $_SESSION['role']      = $u['role'];
            $_SESSION['branch_id'] = $u['branch_id'];
            redirect('index.php?page=dashboard');
        }
        flash('error', 'Invalid username or password.');
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
