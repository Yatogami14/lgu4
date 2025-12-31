<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute paths
$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/utils/access_control.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    http_response_code(401);
    exit;
}

$userId = $_SESSION['user_id'];
$database = new Database();

try {
    // Fetch unread notification count
    $unreadCount = $database->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);

    // Fetch recent notifications (e.g., last 5)
    $query = "SELECT id, message, link, is_read, created_at 
              FROM notifications 
              WHERE user_id = ? 
              ORDER BY created_at DESC 
              LIMIT 5";
    
    $notifications = $database->fetchAll($query, [$userId]);

    echo json_encode(['success' => true, 'unread_count' => $unreadCount, 'notifications' => $notifications]);

} catch (Exception $e) {
    error_log('Notification fetch error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to fetch notifications.']);
    http_response_code(500);
}
?>