<?php
require_once '../utils/session_manager.php';

// This script handles logout for the admin portal.

// Clear "Remember Me" token from the database
if (isset($_SESSION['user_id'])) {
    require_once '../config/database.php';
    require_once '../models/User.php';
    
    $database = new Database();
    $db_core = $database->getConnection(Database::DB_CORE);
    $user = new User($db_core); // This is needed to call the method
    $user->id = $_SESSION['user_id']; // Set the user ID
    $user->clearRememberMeToken(); // Clear the token
}

// Clear the "Remember Me" cookie from the browser
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Unset all session variables and destroy the session
session_unset();
session_destroy();

// Redirect to the admin login page
header('Location: ../main_login.php');
exit;
