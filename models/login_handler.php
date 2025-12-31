<?php
require_once __DIR__ . '/../utils/session_manager.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Auth.php';
require_once __DIR__ . '/../utils/access_control.php'; // For ROLE_BASE_PATHS

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /main_login.php?error=invalid_request');
    exit;
}

// Determine where to redirect on failure
$login_page = '/main_login.php'; // Default login page
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
    if (in_array(basename($referer_path), ['main_login.php', 'admin_login.php', 'public_login.php'])) {
        $login_page = $referer_path;
    }
}

if (empty($_POST['email']) || empty($_POST['password'])) {
    header('Location: ' . $login_page . '?error=missing_credentials');
    exit;
}

$database = new Database();
$auth = new Auth($database);

$email = $_POST['email'];
$password = $_POST['password'];
$remember_me = isset($_POST['remember_me']);

$user_data = $auth->login($email, $password);

if ($user_data) {
    // Login successful
    session_regenerate_id(true); // Prevent session fixation

    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['user_name'] = $user_data['name'];
    $_SESSION['user_role'] = $user_data['role'];

    if ($remember_me) {
        $token_data = $auth->generateRememberMeToken($user_data['id']);
        if ($token_data) {
            $cookie_value = $token_data['selector'] . ':' . $token_data['validator'];
            setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/", "", isset($_SERVER['HTTPS']), true); // 30-day HttpOnly cookie
        }
    }

    // Redirect to the appropriate dashboard based on role
    $role = $user_data['role'];
    header('Location: ' . (ROLE_BASE_PATHS[$role] ?? '/'));
    exit;
} else {
    // Login failed
    header('Location: ' . $login_page . '?error=login_failed');
    exit;
}