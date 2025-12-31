<?php
header('Content-Type: application/json');

require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

// Only allow access to logged-in users, ideally admins/super_admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission Denied.']);
    exit;
}

try {
    $database = new Database();
    $user = new User($database);

    // Fetch all users with the 'inspector' role
    $inspectors = $user->readByRole('inspector');

    echo json_encode(['success' => true, 'inspectors' => $inspectors]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error fetching inspectors: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An internal server error occurred.']);
}
?>