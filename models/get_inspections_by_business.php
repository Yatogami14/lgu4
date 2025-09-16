<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

header('Content-Type: application/json');

// Only admins and super_admins can access this
if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permission Denied.']);
    exit;
}

if (!isset($_GET['business_id']) || !is_numeric($_GET['business_id'])) {
    echo json_encode(['success' => false, 'message' => 'Business ID not provided or invalid.']);
    exit;
}

$database = new Database();
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$inspection = new Inspection($database);
$stmt = $inspection->readByBusinessId($_GET['business_id']);
$inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'inspections' => $inspections]);
?>