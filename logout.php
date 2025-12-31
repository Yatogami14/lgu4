<?php
require_once __DIR__ . '/utils/session_manager.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Auth.php';

// The session is already started by session_manager.php

// If a user is logged in, clear their remember_me token from the database.
if (isset($_SESSION['user_id'])) {
    try {
        $database = new Database();
        $auth = new Auth($database);
        $auth->clearRememberMeToken($_SESSION['user_id']);
    } catch (Exception $e) {
        // Log error but continue with logout
        error_log('Logout Error: Could not clear remember_me token. ' . $e->getMessage());
    }
}

// Unset all of the session variables.
$_SESSION = [];

// Clear the "Remember Me" cookie from the browser
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, "/");
}

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Determine the base path for redirection
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

// Redirect to the main home page
header('Location: ' . $base_path . '/index.html');
exit;