<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('businesses');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

$business = new Business($db);
$inspection = new Inspection($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        // Create new business
        $business->name = $_POST['name'];
        $business->address = $_POST['address'];
        $business->business_type = $_POST['business_type'];
        $business->registration_number = $_POST['registration_number'];
        $business->contact_number = $_POST['contact_number'];
        $business->email = $_POST['email'];
        $business->owner_id = $user->id; // Set current user as owner
        
        if ($business->create()) {
            $_SESSION['success_message'] = 'Business created successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to create business. Please try again.';
        }
        header('Location: businesses.php');
        exit();
    }
    elseif (isset($_POST['update'])) {
        // Update existing business
        $business->id = $_POST['id'];
        $business->name = $_POST['name'];
        $business->address = $_POST['address'];
        $business->business_type = $_POST['business_type'];
        $business->registration_number = $_POST['registration_number'];
        $business->contact_number = $_POST['contact_number'];
        $business->email = $_POST['email'];
        
        if ($business->update()) {
            $_SESSION['success_message'] = 'Business updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update business. Please try again.';
        }
        header('Location: businesses.php');
        exit();
    }
    elseif (isset($_POST['delete'])) {
        // Delete business
        $business->id = $_POST['id'];
        
        if ($business->delete()) {
            $_SESSION['success_message'] = 'Business deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete business. Please try again.';
        }
        header('Location: businesses.php');
        exit();
    }
}

// Get all businesses
$businesses = $business->readAll();

// Display success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Businesses - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Business Management</h2>
                <button onclick="document.getElementById('createModal').classList.remove('hidden'); clearForm();" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add Business
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Businesses</p>
                        <p class="text-2xl font-bold">3</p>
                    </div>
                    <i class="fas fa-building text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">High Risk</p>
                        <p class="text-2xl font-bold">1</p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Medium Risk</p>
                        <p class="text-2xl font-bold">1</p>
                    </div>
                    <i class="fas fa-exclamation text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Low Risk</p>
                        <p class="text-2xl font-bold">1</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>
        </div>

        <!-- Businesses Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Inspection</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($business_row = $businesses->fetch(PDO::FETCH_ASSOC)): 
                        $stats = $business->getComplianceStats($business_row['id']);
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $business_row['name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $business_row['registration_number']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo substr($business_row['address'], 0, 50) . '...'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $business_row['business_type'] ?: 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $stats['avg_compliance']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $stats['avg_compliance']; ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php 
                            $recent = $business->getRecentInspections($business_row['id'], 1);
                            echo $recent ? date('M j, Y', strtotime($recent[0]['scheduled_date'])) : 'Never';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="business_view.php?id=<?php echo $business_row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Business Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Add New Business</h3>
                <form method="POST" action="businesses.php" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business Name</label>
                        <input type="text" name="name" placeholder="Enter business name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" rows="3" placeholder="Enter full address" required
                                  class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business Type</label>
                        <select name="business_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select type</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Retail">Retail Store</option>
                            <option value="Office">Office Building</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Healthcare">Healthcare Facility</option>
                            <option value="Education">Educational Institution</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Registration Number</label>
                        <input type="text" name="registration_number" placeholder="Enter registration number" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="tel" name="contact_number" placeholder="Enter contact number" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" placeholder="Enter email address" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="create" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Business
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Business Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Business</h3>
                <form method="POST" action="businesses.php" class="mt-4 space-y-4">
                    <input type="hidden" name="id" id="edit_id">
                    <div>
                        <label class="block极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果
                        <input type="text" name="name" id="edit_name" placeholder="Enter business name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" id="edit_address" rows="3" placeholder="Enter full address" required
                                  class="w-full极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business Type</label>
                        <select name="business_type" id="edit_business_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select type</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Retail">Retail Store</option>
                            <option value极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Healthcare">Healthcare Facility</option>
                            <option value="Education">Educational Institution</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Registration Number</label>
                        <input type="text" name="registration_number" id="edit_registration_number" placeholder="Enter registration number" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果
                        <input type="tel" name="极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果" id="edit_contact_number" placeholder="Enter contact number" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="edit_email" placeholder="Enter email address" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" 
                                class="px-4极速赛车开奖直播极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果
                            Cancel
                        </button>
                        <button type="submit" name="update" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Update Business
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == createModal) {
                createModal.classList.add('hidden');
            }
            if (event.target == editModal) {
                editModal.classList.add('hidden');
            }
        }

        function editBusiness(id) {
            // Fetch business data via AJAX and populate the edit form
            fetch(`../api/business.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.business.id;
                        document.getElementById('edit_name').value = data.business.name;
                        document.getElementById('edit_address').value = data.business.address;
                        document.getElementById('edit_business_type').value = data.business.business_type;
                        document.getElementById('edit_registration_number').value = data.business.registration_number;
                        document.getElementById('edit_contact_number').value = data.business.contact_number;
                        document.getElementById('极速赛车开奖直播记录+历史结果查询平台 168极速赛车官网开奖结果').value = data.business.email;
                        document.getElementById('editModal').classList.remove('hidden');
                    } else {
                        alert('Failed to load business data');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading business data');
                });
        }

        function clearForm() {
            document.querySelector('input[name="name"]').value = '';
            document.querySelector('textarea[name="address"]').value = '';
            document.querySelector('select[name="business_type"]').value = '';
            document.querySelector('input[name="registration_number"]').value = '';
            document.querySelector('input[name="contact_number"]').value = '';
            document.querySelector('input[name="email"]').value = '';
        }
    </script>
</body>
</html>
