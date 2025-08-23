<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Inspection.php';
require_once 'models/Business.php';
require_once 'models/Notification.php';

require_once 'utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('violations');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get violations from database (mock data for now)
$violations = [
    [
        'id' => 1,
        'business_name' => 'ABC Restaurant',
        'description' => 'Fire exit blocked by storage boxes',
        'severity' => 'high',
        'status' => 'open',
        'due_date' => '2024-01-25',
        'created_at' => '2024-01-15 10:30:00'
    ],
    [
        'id' => 2,
        'business_name' => 'XYZ Mall',
        'description' => 'Missing fire extinguishers in food court',
        'severity' => 'medium',
        'status' => 'in_progress',
        'due_date' => '2024-01-28',
        'created_at' => '2024-01-16 14:20:00'
    ],
    [
        'id' => 3,
        'business_name' => 'Tech Hub Office',
        'description' => 'Poor waste management practices',
        'severity' => 'low',
        'status' => 'resolved',
        'due_date' => '2024-01-20',
        'created_at' => '2024-01-14 09:15:00'
    ]
];
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
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Violations Management</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                <i class="fas fa-plus mr-2"></i>Report Violation
            </button>
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
                            <button onclick="editViolation(<?php echo $violation['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="viewViolation(<?php echo $violation['id']; ?>)" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Violation Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Report New Violation</h3>
                <form class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business</label>
                        <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option>ABC Restaurant</option>
                            <option>XYZ Mall</option>
                            <option>Tech Hub Office</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea rows="3" placeholder="Describe the violation..." 
                                  class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Severity</label>
                        <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="button" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        }

        function editViolation(id) {
            alert('Edit violation ' + id);
        }

        function viewViolation(id) {
            alert('View violation ' + id);
        }
    </script>
</body>
</html>
