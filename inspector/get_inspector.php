<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to manage inspectors
if (!currentUserHasPermission('inspectors')) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Get inspector ID from query parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid inspector ID']);
    exit;
}

$inspector_id = $_GET['id'];

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$user = new User($db_core);
$user->id = $inspector_id;

// Read the inspector details
if ($user->readOne()) {
    // Return inspector details as JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'inspector' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'department' => $user->department,
            'certification' => $user->certification,
            'created_at' => $user->created_at
        ]
    ]);
} else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'Inspector not found']);
}
?>
