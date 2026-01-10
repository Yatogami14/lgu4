<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

try {
    $database = new Database();
    $user = new User($database);
    $user->id = $_SESSION['user_id'];
    $user->readOne();

    // Determine dashboard URL based on role
    $dashboard_url = 'main_login.php'; // Default fallback
    $role_label = 'Dashboard';

    switch ($_SESSION['user_role']) {
        case 'super_admin':
        case 'admin':
            $dashboard_url = 'admin/index.php';
            $role_label = 'Admin Panel';
            break;
        case 'inspector':
            $dashboard_url = 'inspector/index.php';
            $role_label = 'Inspector Panel';
            break;
        case 'business_owner':
            $dashboard_url = 'business/index.php';
            $role_label = 'Business Panel';
            break;
        case 'community_user':
            $dashboard_url = 'community/index.php';
            $role_label = 'Community Panel';
            break;
    }

    echo json_encode([
        'logged_in' => true,
        'user_name' => $user->name,
        'role' => $_SESSION['user_role'],
        'dashboard_url' => $dashboard_url,
        'role_label' => $role_label
    ]);

} catch (Exception $e) {
    error_log('Session check error: ' . $e->getMessage());
    echo json_encode(['logged_in' => false, 'error' => 'Database error']);
}
?>
