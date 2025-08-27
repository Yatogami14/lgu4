    <?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../utils/logger.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    logError("Unauthorized access attempt to index.php");
    header('Location: public_login.php');
    exit;
}

// Get current user
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get dashboard data
$inspection = new Inspection($db);
$business = new Business($db);
$notification = new Notification($db);

$totalInspections = $inspection->countAll();
$activeViolations = $inspection->countActiveViolations();
$complianceRate = $inspection->getAverageCompliance();
$activeInspectors = $user->countActiveInspectors();

$recentInspections = $inspection->readRecent(5);
$recentNotifications = $notification->readByUser($_SESSION['user_id'], 5);

// Handle tab navigation
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <!-- Dashboard Content -->
        <?php if ($activeTab == 'dashboard'): ?>
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Inspections</p>
                            <p class="text-2xl font-bold"><?php echo $totalInspections; ?></p>
                            <p class="text-xs text-green-600">+12% from last month</p>
                        </div>
                        <i class="fas fa-file-alt text-3xl text-blue-500"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Active Violations</p>
                            <p class="text-2xl font-bold"><?php echo $activeViolations; ?></p>
                            <p class="text-xs text-red-600">+3 new today</p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Compliance Rate</p>
                            <p class="text-2xl font-bold"><?php echo $complianceRate; ?>%</p>
                            <p class="text-xs text-green-600">+2% improvement</p>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Active Inspectors</p>
                            <p class="text-2xl font-bold"><?php echo $activeInspectors; ?></p>
                            <p class="text-xs text-blue-600">All certified</p>
                        </div>
                        <i class="fas fa-users text-3xl text-purple-500"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Inspections -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Recent Inspections</h3>
                    <p class="text-gray-600 text-sm">Latest inspection activities and status updates</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recentInspections as $inspection): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border rounded-lg space-y-3 sm:space-y-0">
                            <div class="flex items-start space-x-3 flex-1 min-w-0">
                                <div class="w-3 h-3 rounded-full mt-1 flex-shrink-0 
                                    <?php echo $inspection['priority'] == 'high' ? 'bg-red-500' : 
                                           ($inspection['priority'] == 'medium' ? 'bg-yellow-500' : 'bg-green-500'); ?>">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium truncate"><?php echo $inspection['business_name']; ?></p>
                                    <p class="text-sm text-gray-600 break-words"><?php echo $inspection['inspection_type']; ?></p>
                                    <p class="text-sm text-gray-600 break-words"><?php echo $inspection['business_address']; ?></p>
                                    <p class="text-xs text-gray-500">Inspector: <?php echo $inspection['inspector_name']; ?></p>
                                </div>
                            </div>
                            <div class="flex flex-row sm:flex-col lg:flex-row items-start sm:items-end lg:items-center space-x-3 sm:space-x-0 lg:space-x-4 sm:space-y-2 lg:space-y-0 flex-shrink-0">
                                <?php if ($inspection['compliance_score']): ?>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs sm:text-sm">Compliance</p>
                                    <p class="font-bold text-green-600 text-sm sm:text-base"><?php echo $inspection['compliance_score']; ?>%</p>
                                </div>
                                <?php endif; ?>
                                <div class="flex flex-col space-y-1">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        <?php echo $inspection['status'] == 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                               ($inspection['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($inspection['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                        <?php echo str_replace('_', ' ', $inspection['status']); ?>
                                    </span>
                                    <p class="text-xs sm:text-sm text-gray-500"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Recent Notifications</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <?php foreach ($recentNotifications as $notification): ?>
                        <div class="flex items-start p-4 border rounded-lg">
                            <i class="fas fa-bell text-blue-500 mt-1 mr-3"></i>
                            <div class="flex-1">
                                <p class="text-sm"><?php echo $notification['message']; ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo $notification['created_at']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Simple tab functionality
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
        }

        // Show initial tab
        showTab('<?php echo $activeTab; ?>');
    </script>
</body>
</html>
