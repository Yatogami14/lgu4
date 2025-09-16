<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('businesses');

$database = new Database();
$user = new User($database);

// Get all inspectors
$inspectorsStmt = $user->readByRole('inspector');
$inspectors = $inspectorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'inspectors' => $inspectors
]);
?>
