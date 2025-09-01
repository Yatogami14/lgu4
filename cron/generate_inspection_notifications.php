<?php
require_once '../config/database.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create business instance
$business = new Business($db);
$notification = new Notification($db);

// Get businesses due for inspection in the next 7 days
$due_businesses = $business->getBusinessesDueForInspection(50);

echo "Starting inspection notification generation...\n";
echo "Found " . count($due_businesses) . " businesses due for inspection.\n";

$notifications_created = 0;

foreach ($due_businesses as $business_data) {
    // Load business data into business object
    $business->id = $business_data['id'];
    $business->name = $business_data['name'];
    $business->owner_id = $business_data['owner_id'];
    $business->next_inspection_date = $business_data['next_inspection_date'];
    
    // Create inspection reminder notification
    if ($business->createInspectionReminderNotification(7)) {
        $notifications_created++;
        echo "Created notification for: " . $business_data['name'] . " (Owner ID: " . $business_data['owner_id'] . ")\n";
    }
}

// Get overdue inspections
$overdue_businesses = $business->getOverdueInspections(50);
echo "Found " . count($overdue_businesses) . " overdue businesses.\n";

foreach ($overdue_businesses as $business_data) {
    // Create urgent notification for overdue inspections
    $notification->user_id = $business_data['owner_id'];
    $notification->message = "URGENT: Inspection overdue for " . $business_data['name'] . ". Please schedule immediately.";
    $notification->type = 'alert';
    $notification->related_entity_type = 'business';
    $notification->related_entity_id = $business_data['id'];
    
    if ($notification->create()) {
        $notifications_created++;
        echo "Created URGENT notification for overdue: " . $business_data['name'] . "\n";
    }
}

echo "Notification generation completed. Created " . $notifications_created . " notifications.\n";

// Clean up old notifications (older than 90 days)
$old_notifications_deleted = $notification->deleteOld(90);
echo "Cleaned up " . $old_notifications_deleted . " old notifications.\n";

echo "Process completed successfully.\n";
?>
