<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';

// Check if user is logged in and is a business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'business_owner') {
    header('Location: public_login.php');
    exit;
}

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($db_scheduling);
$business = new Business($db_core);

// Get user-specific data for business owner
$userInspections = $inspection->readByUserId($_SESSION['user_id'], 5);
$userBusinesses = $business->readByOwnerId($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Owner Dashboard - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <h2 class="text-2xl font-bold mb-4">Welcome, <?php echo $user->name; ?></h2>
        
        <?php if (!empty($userBusinesses)): ?>
            <h3 class="text-lg font-bold mb-4">Your Businesses</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <?php foreach ($userBusinesses as $business): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h4 class="font-bold"><?php echo $business['name']; ?></h4>
                        <p class="text-sm text-gray-600"><?php echo $business['address']; ?></p>
                        <p class="text-sm text-gray-600"><?php echo $business['business_type']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h3 class="text-lg font-bold mb-4">Recent Inspections</h3>
        <div class="bg-white rounded-lg shadow p-6">
            <?php if (empty($userInspections)): ?>
                <p class="text-gray-500">No inspections recorded yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($userInspections as $inspection): ?>
                        <li class="py-2 border-b border-gray-100">
                            <span class="font-medium"><?php echo $inspection['inspection_type']; ?></span> - 
                            <span class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></span>
                            <?php if ($inspection['status'] == 'completed'): ?>
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Completed</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
