<?php
session_start();
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to manage inspectors
requirePermission('inspectors', 'index.php');

$database = new Database();
$db = $database->getConnection();
$inspection = new Inspection($db);

try {
    // Get inspections that don't have an assigned inspector
    $availableInspections = $inspection->getAvailableInspections();

    $inspections = [];
    while ($row = $availableInspections->fetch(PDO::FETCH_ASSOC)) {
        $inspections[] = [
            'id' => $row['id'],
            'business_name' => $row['business_name'],
            'inspection_type' => $row['inspection_type'],
            'scheduled_date' => $row['scheduled_date'],
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'success' => true,
        'inspections' => $inspections
    ]);

} catch (Exception $e) {
    error_log("Error fetching available inspections: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch available inspections'
    ]);
}
?>
