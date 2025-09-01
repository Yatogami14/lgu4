<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Inspector ID required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$inspector_id = $_GET['id'];

// Get inspector details
$inspector = new User($db);
$inspector->id = $inspector_id;

if ($inspector->readOne()) {
    // Get inspector specializations
    $specializations = $inspector->getSpecializations();
    $specializations_data = [];
    
    while ($spec = $specializations->fetch(PDO::FETCH_ASSOC)) {
        $specializations_data[] = $spec;
    }
    
    echo json_encode([
        'success' => true,
        'inspector' => [
            'id' => $inspector->id,
            'name' => $inspector->name,
            'email' => $inspector->email,
            'role' => $inspector->role,
            'department' => $inspector->department,
            'certification' => $inspector->certification
        ],
        'specializations' => $specializations_data
    ]);
} else {
    header('HTTP/1.1 404 Not Found');
    echo json_encode(['success' => false, 'message' => 'Inspector not found']);
}
?>
