<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('businesses');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$user = new User($db_core);

// Get all inspectors
$query = "SELECT id, name, email, department, certification FROM users WHERE role = 'inspector' ORDER BY name";
$stmt = $db_core->prepare($query);
$stmt->execute();

$inspectors = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $inspectors[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'inspectors' => $inspectors
]);
?>
