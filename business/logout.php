<?php
require_once '../utils/session_manager.php';

// This script handles logout for the business portal.

// Clear "Remember Me" token from the database
if (isset($_SESSION['user_id'])) {
    require_once '../config/database.php';
    require_once '../models/User.php';
    
    $database = new Database();
    $db_core = $database->getConnection(Database::DB_CORE);
    $user = new User($db_core);
    $user->id = $_SESSION['user_id'];
    $user->clearRememberMeToken();
}

session_unset();
session_destroy();

header('Location: ../main_login.php');
exit;