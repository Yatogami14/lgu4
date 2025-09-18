<?php
require_once '../utils/session_manager.php';

// This script handles logout for the community portal.

// Clear "Remember Me" token from the database
if (isset($_SESSION['user_id'])) {
    require_once '../config/database.php';
    require_once '../models/User.php';
    
    $database = new Database();
    $user = new User($database);
    $user->clearRememberMeToken($_SESSION['user_id']);
}

// Clear the "Remember Me" cookie from the browser
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

session_unset();
session_destroy();
header('Location: ../main_login.php');
exit;
