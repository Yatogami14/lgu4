<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('violations');

header('Content-Type: application/json');

if (!isset($_GET['business_id']) || empty($_GET['business_id'])) {
    echo json_encode(['success' => false, 'message' => 'Business ID is required']);
    exit;
}

$business_id = $_GET['business_id'];

try {
    $database = new Database();
    $inspection = new Inspection($database);

    // Get inspections for the business
    $inspections = $inspection->readByBusinessId($business_id);

    // Format the response
    $formatted_inspections = array_map(function($insp) {
        return [
            'id' => $insp['id'],
            'inspection_type' => $insp['inspection_type'],
            'scheduled_date' => $insp['scheduled_date']
        ];
    }, $inspections);

    echo json_encode([
        'success' => true,
        'inspections' => $formatted_inspections
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching inspections: ' . $e->getMessage()
    ]);
}
?>
