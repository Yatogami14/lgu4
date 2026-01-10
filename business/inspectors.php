<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('inspectors');

$database = new Database();
$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get all inspectors (only inspector role accounts)
$inspectors = $user->readByRole('inspector');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspectors - Digital Health & Safety Inspection Platform</title>
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

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Inspectors Management</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add Inspector
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Inspectors</p>
                        <p class="text-2xl font-bold"><?php echo count($inspectors); ?></p>
                    </div>
                    <i class="fas fa-users text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Today</p>
                        <p class="text-2xl font-bold">2</p>
                    </div>
                    <i class="fas fa-user-check text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Scheduled</p>
                        <p class="text-2xl font-bold">5</p>
                    </div>
                    <i class="fas fa-calendar text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-bold">12</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>

        <!-- Inspectors Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certification</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($inspectors)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No inspectors found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($inspectors as $inspector): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">
                                    <?php echo substr($inspector['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo $inspector['name']; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $inspector['email']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $inspector['department'] ?: 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $inspector['certification'] ?: 'Not Certified'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $inspector['role'] == 'admin' ? 'bg-purple-100 text-purple-800' : 
                                       ($inspector['role'] == 'inspector' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $inspector['role'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewInspector(<?php echo $inspector['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button onclick="editInspector(<?php echo $inspector['id']; ?>)" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Inspector Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Add New Inspector</h3>
                <form class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" placeholder="Enter full name" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" placeholder="Enter email address" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" placeholder="Enter password" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="inspector">Inspector</option>
                            <option value="admin">Administrator</option>
                            <option value="super_admin">Super Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <input type="text" placeholder="Enter department" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Certification</label>
                        <input type="text" placeholder="Enter certification" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Inspector
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

        function viewInspector(id) {
            alert('View inspector ' + id);
        }

        function editInspector(id) {
            alert('Edit inspector ' + id);
        }
    </script>
</body>
</html>
