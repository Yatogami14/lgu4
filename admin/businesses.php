<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('businesses');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);
$db_reports = $database->getConnection(Database::DB_REPORTS);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

$business = new Business($db_core);
$inspection = new Inspection($db_scheduling);

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
        $business->owner_id = $_POST['owner_id'] ?? null;

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
    elseif (isset($_POST['assign_inspector'])) {
        // Assign inspector to business
        $business->id = $_POST['business_id'];
        $business->inspector_id = $_POST['inspector_id'];

        $default_assigned = $business->assignInspector();
        $inspections_assigned_count = 0;

        // The button on this page should assign an inspector to all available inspections
        // for this business, and also set them as the default inspector.
        if ($default_assigned) {
            // Now, find all unassigned inspections for this business and assign the inspector.
            $inspectionForAssignment = new Inspection($db_scheduling);
            $unassigned_inspections = $inspectionForAssignment->findAllUnassignedForBusiness($_POST['business_id']);
            
            if ($unassigned_inspections) {
                require_once '../models/Notification.php';
                $notification = new Notification($db_reports);
                $tempBusiness = new Business($db_core);
                $tempBusiness->id = $_POST['business_id'];
                $tempBusinessData = $tempBusiness->readOne();
                $businessName = $tempBusinessData['name'] ?? 'a business';

                foreach($unassigned_inspections as $unassigned_inspection) {
                    $inspectionForAssignment->id = $unassigned_inspection['id'];
                    $inspectionForAssignment->inspector_id = $_POST['inspector_id'];
                    if ($inspectionForAssignment->assignInspector()) {
                        $inspections_assigned_count++;
                        // Create a notification for the inspector for each assignment
                        $notification->createAssignmentNotification($_POST['inspector_id'], $businessName, $unassigned_inspection['id']);
                    }
                }
            }
            
            $message = 'Inspector set as default for the business. ';
            if ($inspections_assigned_count > 0) {
                $plural = $inspections_assigned_count > 1 ? 'inspections' : 'inspection';
                $message .= "They have also been assigned to {$inspections_assigned_count} upcoming unassigned {$plural}.";
            } else {
                $message .= 'No upcoming unassigned inspections were found to assign.';
            }
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = 'Failed to assign inspector. Please try again.';
        }
        header('Location: businesses.php');
        exit();
    }
}

// Get all businesses
$businesses = $business->readAll();
$businessStats = $business->getBusinessStats();

// Get all business owners for the create modal
$ownerUser = new User($db_core);
$all_owners = $ownerUser->readByRole('business_owner')->fetchAll(PDO::FETCH_ASSOC);

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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
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
                        <p class="text-2xl font-bold"><?php echo $businessStats['total'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-building text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">High Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['high_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Medium Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['medium_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Low Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['low_risk'] ?? 0; ?></p>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php 
                            $inspector = $business->getInspector($business_row['id']);
                            echo $inspector ? htmlspecialchars($inspector['name']) : 'Unassigned';
                            ?>
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
                            $last_inspection_date = $business->getLastCompletedInspectionDate($business_row['id']);
                            echo $last_inspection_date ? date('M j, Y', strtotime($last_inspection_date)) : 'Never';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="business_view.php?id=<?php echo $business_row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button onclick="assignInspector(<?php echo $business_row['id']; ?>)" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-user-plus"></i> Assign Inspector
                            </button>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Assign Owner</label>
                        <select name="owner_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select an Owner</option>
                            <?php foreach ($all_owners as $owner): ?>
                                <option value="<?php echo $owner['id']; ?>"><?php echo htmlspecialchars($owner['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <label class="block text-sm font-medium text-gray-700">Business Name</label>
                        <input type="text" name="name" id="edit_name" placeholder="Enter business name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" id="edit_address" rows="3" placeholder="Enter full address" required
                                  class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business Type</label>
                        <select name="business_type" id="edit_business_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
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
                        <input type="text" name="registration_number" id="edit_registration_number" placeholder="Enter registration number" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="tel" name="contact_number" id="edit_contact_number" placeholder="Enter contact number" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="edit_email" placeholder="Enter email address" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
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

    <!-- Assign Inspector Modal -->
    <div id="assignInspectorModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Assign Inspector</h3>
                <form method="POST" action="businesses.php" class="mt-4 space-y-4">
                    <input type="hidden" name="business_id" id="assign_business_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Select Inspector</label>
                        <select name="inspector_id" id="inspector_select" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select inspector</option>
                            <!-- Inspectors will be loaded here -->
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('assignInspectorModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="assign_inspector" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Assign Inspector
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
            const assignInspectorModal = document.getElementById('assignInspectorModal');

            if (event.target == createModal) {
                createModal.classList.add('hidden');
            }
            if (event.target == editModal) {
                editModal.classList.add('hidden');
            }
            if (event.target == assignInspectorModal) {
                assignInspectorModal.classList.add('hidden');
            }
        }

        function assignInspector(businessId) {
            // Set the business ID
            document.getElementById('assign_business_id').value = businessId;

            // Fetch available inspectors
            fetch('get_inspectors.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const inspectorSelect = document.getElementById('inspector_select');
                        inspectorSelect.innerHTML = '<option value="">Select inspector</option>';

                        data.inspectors.forEach(inspector => {
                            const option = document.createElement('option');
                            option.value = inspector.id;
                            option.textContent = inspector.name;
                            inspectorSelect.appendChild(option);
                        });

                        document.getElementById('assignInspectorModal').classList.remove('hidden');
                    } else {
                        alert('Failed to load inspectors');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading inspectors');
                });
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
                        document.getElementById('edit_email').value = data.business.email;
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
