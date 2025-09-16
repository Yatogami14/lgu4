<?php
session_start();
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../models/Notification.php';
require_once '../models/Business.php';
require_once '../models/InspectionType.php';

// Check if user is logged in and is a business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'business_owner') {
    header('Location: public_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $inspection_type_id = $_POST['inspection_type_id'] ?? null;
    $preferred_date = $_POST['preferred_date'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (!$business_id || !$inspection_type_id || !$preferred_date) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: index.php');
        exit;
    }

    $database = new Database();

    // Validate that the business belongs to the current user
    $business = new Business($database);
    $business->id = $business_id;
    $business->readOne();

    if ($business->owner_id != $_SESSION['user_id']) {
        $_SESSION['error'] = 'You do not have permission to request inspections for this business.';
        header('Location: index.php');
        exit;
    }

    $inspection = new Inspection($database);
    $notification = new Notification($database);

    // Create new inspection request
    $inspection->business_id = $business_id;
    $inspection->inspection_type_id = $inspection_type_id;
    $inspection->scheduled_date = $preferred_date;
    $inspection->status = 'requested';
    $inspection->notes = $notes;

    if ($inspection->create()) {
        // Fetch inspection type name for the notification message
        $inspectionType = new InspectionType($database);
        $inspectionType->id = $inspection_type_id;
        $typeData = $inspectionType->readOne();
        $inspection_type_name = $typeData['name'] ?? 'Unknown Type';

        // Create notification for business owner
        $notification->user_id = $_SESSION['user_id'];
        $notification->message = "Inspection request for {$inspection_type_name} on {$preferred_date} has been submitted.";
        $notification->type = 'info';
        $notification->related_entity_type = 'inspection';
        $notification->related_entity_id = $inspection->id;
        $notification->create();

        $_SESSION['success'] = 'Inspection request submitted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to submit inspection request. Please try again.';
    }
} else {
    $_SESSION['error'] = 'Invalid request method.';
}

header('Location: index.php');
exit;
?>
