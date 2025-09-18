<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Violation.php';
require_once '../models/InspectionMedia.php';
require_once '../utils/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../main_login.php');
    exit;
}

requirePermission('inspections');

$database = new Database();