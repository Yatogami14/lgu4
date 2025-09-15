<?php
session_start();
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../models/Notification.php';
require_once '../models/Business.php';

// Check if user is logged in and is a business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'business_owner') {
    header('Location: public_login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $inspection_type = $_POST['inspection_type'] ?? null;
    $preferred_date = $_POST['preferred_date'] ?? null;
    $notes = $_POST['notes'] ?? '';

    if (!$business_id || !$inspection_type || !$preferred_date) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: index.php');
        exit;
    }

    $database = new Database();
    $db_core = $database->getConnection(Database::DB_CORE);
    $db_scheduling = $database->getConnection(Database::DB_SCHEDULING);
    $db_reports = $database->getConnection(Database::DB_REPORTS);

    // Validate that the business belongs to the current user
    $business = new Business($db_core);
    $business->id = $business_id;
    $business->readOne();

    if ($business->owner_id != $_SESSION['user_id']) {
        $_SESSION['error'] = 'You do not have permission to request inspections for this business.';
        header('Location: index.php');
        exit;
    }

    $inspection = new Inspection($db_scheduling);
    $notification = new Notification($db_reports);

    // Create new inspection request
    $inspection->business_id = $business_id;
    $inspection->inspection_type = $inspection_type;
    $inspection->scheduled_date = $preferred_date;
    $inspection->status = 'requested';
    $inspection->notes = $notes;
    $inspection->created_by = $_SESSION['user_id'];

    if ($inspection->create()) {
        // Create notification for business owner
        $notification->user_id = $_SESSION['user_id'];
        $notification->message = "Inspection request for {$inspection_type} on {$preferred_date} has been submitted.";
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
