<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] != 'business_owner' && $_SESSION['user_role'] != 'community_user')) {
    header('Location: public_login.php');
    exit;
}

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($database);
$business = new Business($database);

// Get user-specific data based on role
if ($_SESSION['user_role'] == 'business_owner') {
    $userBusinesses = $business->readByOwnerId($_SESSION['user_id']);
    $business_ids = [];
    if (!empty($userBusinesses)) {
        $business_ids = array_column($userBusinesses, 'id');
    }
    $userInspections = [];
    if (!empty($business_ids)) {
        $userInspections = $inspection->readByBusinessIds($business_ids);
    }
} else {
    // For community users, show public data
    // The readRecent() method is causing a PDO error because it likely tries to bind the LIMIT parameter, which is not supported.
    // As a workaround, we fetch all and slice the first 5. This assumes readAll() returns data in a recent-first order.
    $allInspections = $inspection->readAll();
    $recentInspections = array_slice($allInspections, 0, 5);
    $businessesArray = $business->readAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <h2 class="text-2xl font-bold mb-4">Welcome, <?php echo $user->name; ?></h2>
        
        <?php if ($_SESSION['user_role'] == 'business_owner' && !empty($userBusinesses)): ?>
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

        <?php if ($_SESSION['user_role'] == 'community_user'): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-blue-800 mb-2">Community Portal</h3>
                <p class="text-blue-700">Welcome to the community portal! Here you can view public inspection data and stay informed about local business compliance.</p>
            </div>

            <h3 class="text-lg font-bold mb-4">Local Businesses</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <?php if (!empty($businessesArray)): ?>
                    <?php foreach ($businessesArray as $business): ?>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h4 class="font-bold"><?php echo $business['name']; ?></h4>
                            <p class="text-sm text-gray-600"><?php echo $business['address']; ?></p>
                            <p class="text-sm text-gray-600"><?php echo $business['business_type']; ?></p>
                            <p class="text-sm text-gray-500">Owner: <?php echo $business['owner_name']; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500">No businesses registered yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h3 class="text-lg font-bold mb-4">Recent Inspections</h3>
        <div class="bg-white rounded-lg shadow p-6">
            <?php if ($_SESSION['user_role'] == 'business_owner'): ?>
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
            <?php else: ?>
                <?php if (!empty($recentInspections)): ?>
                    <ul>
                        <?php foreach ($recentInspections as $inspection): ?>
                            <li class="py-2 border-b border-gray-100">
                                <span class="font-medium"><?php echo $inspection['inspection_type']; ?></span> - 
                                <span class="text-sm text-gray-600"><?php echo $inspection['business_name']; ?> - <?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></span>
                                <?php if ($inspection['status'] == 'completed'): ?>
                                    <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">Completed</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500">No recent inspections available.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($_SESSION['user_role'] == 'community_user'): ?>
            <div class="mt-8 bg-gray-50 rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">Community Resources</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="businesses.php" class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                        <h4 class="font-bold text-blue-600">Browse All Businesses</h4>
                        <p class="text-sm text-gray-600">View all registered businesses in our community</p>
                    </a>
                    <a href="violations.php" class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                        <h4 class="font-bold text-blue-600">View Violations</h4>
                        <p class="text-sm text-gray-600">Check recent health and safety violations</p>
                    </a>
                    <a href="inspections.php" class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                        <h4 class="font-bold text-blue-600">All Inspections</h4>
                        <p class="text-sm text-gray-600">See complete inspection history</p>
                    </a>
                    <a href="index.php" class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                        <h4 class="font-bold text-blue-600">Public Dashboard</h4>
                        <p class="text-sm text-gray-600">Access general community information</p>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
