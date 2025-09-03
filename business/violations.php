<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('violations');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get user's businesses first
$business = new Business($db);
$userBusinesses = $business->readByOwnerId($_SESSION['user_id']);
$userBusinessIds = array_column($userBusinesses, 'id');

// Get violations only for user's businesses from database (mock data for now)
// In a real implementation, this would query the database with proper filtering
$allViolations = [
    [
        'id' => 1,
        'business_id' => 1,
        'business_name' => 'ABC Restaurant',
        'description' => 'Fire exit blocked by storage boxes',
        'severity' => 'high',
        'status' => 'open',
        'due_date' => '2024-01-25',
        'created_at' => '2024-01-15 10:30:00'
    ],
    [
        'id' => 2,
        'business_id' => 2,
        'business_name' => 'XYZ Mall',
        'description' => 'Missing fire extinguishers in food court',
        'severity' => 'medium',
        'status' => 'in_progress',
        'due_date' => '2024-01-28',
        'created_at' => '2024-01-16 14:20:00'
    ],
    [
        'id' => 3,
        'business_id' => 3,
        'business_name' => 'Tech Hub Office',
        'description' => 'Poor waste management practices',
        'severity' => 'low',
        'status' => 'resolved',
        'due_date' => '2024-01-20',
        'created_at' => '2024-01-14 09:15:00'
    ]
];

// Filter violations to only show those belonging to current user's businesses
$violations = array_filter($allViolations, function($violation) use ($userBusinessIds) {
    return in_array($violation['business_id'], $userBusinessIds);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violations - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">

<!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Violations Management</h2>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Violations</p>
                        <p class="text-2xl font-bold"><?php echo count($violations); ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Open Violations</p>
                        <p class="text-2xl font-bold"><?php echo count(array_filter($violations, fn($v) => $v['status'] === 'open')); ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold"><?php echo count(array_filter($violations, fn($v) => $v['status'] === 'in_progress')); ?></p>
                    </div>
                    <i class="fas fa-spinner text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Resolved</p>
                        <p class="text-2xl font-bold"><?php echo count(array_filter($violations, fn($v) => $v['status'] === 'resolved')); ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>
        </div>

        <!-- Violations Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($violations as $violation): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $violation['business_name']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo $violation['description']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $violation['severity'] == 'high' ? 'bg-red-100 text-red-800' : 
                                       ($violation['severity'] == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo $violation['severity']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $violation['status'] == 'open' ? 'bg-red-100 text-red-800' : 
                                       ($violation['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo str_replace('_', ' ', $violation['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('M j, Y', strtotime($violation['due_date'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <?php if ($_SESSION['user_role'] != 'business_owner'): ?>
                            <button onclick="editViolation(<?php echo $violation['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="viewViolation(<?php echo $violation['id']; ?>)" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
