<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Inspection.php';
require_once 'models/Business.php';
require_once 'models/Notification.php';

require_once 'utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('analytics');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($db);
$business = new Business($db);

// Get analytics data
$totalInspections = $inspection->countAll();
$totalBusinesses = $business->countAll();
$totalActiveViolations = $inspection->countActiveViolations();
$averageCompliance = $inspection->getAverageCompliance();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1">
                    <i class="fas fa-shield-alt text-blue-600 text-xl sm:text-2xl"></i>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm sm:text-xl font-bold text-gray-900 truncate">LGU Health & Safety</h1>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Digital Inspection Platform</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-1 sm:space-x-4 flex-shrink-0">
                    <a href="index.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <h2 class="text-2xl font-bold mb-6">Analytics Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Inspections</p>
                        <p class="text-2xl font-bold"><?php echo $totalInspections; ?></p>
                    </div>
                    <i class="fas fa-file-alt text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Businesses</p>
                        <p class="text-2xl font-bold"><?php echo $totalBusinesses; ?></p>
                    </div>
                    <i class="fas fa-building text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Violations</p>
                        <p class="text-2xl font-bold"><?php echo $totalActiveViolations; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Average Compliance</p>
                        <p class="text-2xl font-bold"><?php echo $averageCompliance; ?>%</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Recent Inspections</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $recent_inspections = $inspection->readRecent(5);
                    foreach ($recent_inspections as $recent): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $recent['business_name']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $recent['inspector_name']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($recent['scheduled_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $recent['status'] == 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($recent['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($recent['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
