A<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('businesses');

$database = new Database();

$business = new Business($database);
$inspection = new Inspection($database);

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
        $business->owner_id = $_POST['owner_id'] ?? null;

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
            $inspectionForAssignment = new Inspection($database);
            $unassigned_inspections = $inspectionForAssignment->findAllUnassignedForBusiness($_POST['business_id']);
            
            if ($unassigned_inspections) {
                require_once '../models/Notification.php';
                $notification = new Notification($database);
                $tempBusiness = new Business($database);
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
                $plural = $inspections_assigned_count > 1 ? 's' : '';
                $message .= "They have also been assigned to {$inspections_assigned_count} unassigned inspection{$plural}.";
            } else {
                $message .= 'No unassigned inspections were found for this business.';
            }
            $_SESSION['success_message'] = $message;
        } else {
            $_SESSION['error_message'] = 'Failed to assign inspector. Please try again.';
        }
        header('Location: businesses.php');
        exit();
    }
}

// Get all active businesses (only approved businesses appear in management)
$businesses = $business->readAllActive();
$businessStats = $business->getBusinessStats();

// Get all business owners for the create modal
$ownerUser = new User($database);
$all_owners = $ownerUser->readByRole('business_owner');

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Business Management</h2>
                <button onclick="document.getElementById('createModal').classList.remove('hidden'); clearForm();"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-md hover:bg-yellow-500">
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
                    <i class="fas fa-building text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">High Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['high_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Medium Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['medium_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation text-3xl text-yellow-600"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Low Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['low_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Businesses Cards Grid -->
        <div class="mt-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">All Businesses</h3>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Filter by type:</span>
                        <select id="typeFilter" onchange="filterBusinesses()" class="text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="Restaurant">Restaurant</option>
                            <option value="Retail">Retail Store</option>
                            <option value="Office">Office Building</option>
                            <option value="Manufacturing">Manufacturing</option>
                            <option value="Healthcare">Healthcare Facility</option>
                            <option value="Education">Educational Institution</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Sort by:</span>
                        <select id="sortFilter" onchange="sortBusinesses()" class="text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500">
                            <option value="name">Name</option>
                            <option value="compliance">Compliance</option>
                            <option value="last_inspection">Last Inspection</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="businessesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($businesses)): ?>
                    <div class="col-span-full">
                        <div class="text-center py-12">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-building text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No businesses found</h3>
                            <p class="text-gray-600 mb-6">Get started by adding your first business.</p>
                            <button onclick="document.getElementById('createModal').classList.remove('hidden'); clearForm();"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-400 text-gray-900 rounded-lg hover:bg-yellow-500 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Add Business
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($businesses as $business_row):
                        $stats = $business->getComplianceStats($business_row['id']);
                        $inspector = $business->getInspector($business_row['id']);
                        $last_inspection_date = $business->getLastCompletedInspectionDate($business_row['id']);
                        $compliance_percentage = $stats['avg_compliance'];
                        $compliance_color = $compliance_percentage >= 80 ? 'green' : ($compliance_percentage >= 60 ? 'yellow' : 'red');
                    ?>
                    <div class="business-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1"
                         data-type="<?php echo $business_row['business_type']; ?>"
                         data-compliance="<?php echo $compliance_percentage; ?>"
                         data-last-inspection="<?php echo $last_inspection_date ? strtotime($last_inspection_date) : 0; ?>">
                        <!-- Card Header -->
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($business_row['name']); ?></h4>
                                    <p class="text-sm text-gray-600 flex items-center mb-2">
                                        <i class="fas fa-id-card mr-1 text-gray-400"></i>
                                        <?php echo htmlspecialchars($business_row['registration_number']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                        <?php echo htmlspecialchars(substr($business_row['address'], 0, 40) . (strlen($business_row['address']) > 40 ? '...' : '')); ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <!-- Business Type Badge -->
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                        <i class="fas fa-building mr-1"></i>
                                        <?php echo htmlspecialchars($business_row['business_type'] ?: 'N/A'); ?>
                                    </span>
                                    <!-- Compliance Badge -->
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        <?php echo $compliance_color == 'green' ? 'bg-green-100 text-green-800 border border-green-200' :
                                               ($compliance_color == 'yellow' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 'bg-red-100 text-red-800 border border-red-200'); ?>">
                                        <i class="fas fa-chart-line mr-1"></i>
                                        <?php echo $compliance_percentage; ?>%
                                    </span>
                                </div>
                            </div>

                            <!-- Compliance Progress Bar -->
                            <div class="mb-3">
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                    <span>Compliance Rate</span>
                                    <span class="font-medium"><?php echo $compliance_percentage; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-<?php echo $compliance_color; ?>-600 h-2 rounded-full transition-all duration-300"
                                         style="width: <?php echo $compliance_percentage; ?>%"></div>
                                </div>
                            </div>

                            <!-- Inspector Info -->
                            <div class="flex items-center text-sm text-gray-600 mb-3">
                                <i class="fas fa-user mr-2 text-green-500"></i>
                                <span><?php echo $inspector ? htmlspecialchars($inspector['name']) : '<span class="text-gray-400">Unassigned</span>'; ?></span>
                            </div>

                            <!-- Last Inspection -->
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-calendar-check mr-2 text-blue-500"></i>
                                <span>Last: <?php echo $last_inspection_date ? date('M j, Y', strtotime($last_inspection_date)) : '<span class="text-gray-400">Never</span>'; ?></span>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="p-4 bg-gray-50 rounded-b-xl">
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-2">
                                    <a href="business_view.php?id=<?php echo $business_row['id']; ?>"
                                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-yellow-700 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors duration-200">
                                        <i class="fas fa-eye mr-1"></i>
                                        View
                                    </a>
                                    <button onclick="assignInspector(<?php echo $business_row['id']; ?>)"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                                        <i class="fas fa-user-plus mr-1"></i>
                                        Assign
                                    </button>
                                </div>
                                <button onclick="editBusiness(<?php echo $business_row['id']; ?>)"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors duration-200">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
                        <button type="submit" name="create" class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-md hover:bg-yellow-500">
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
                        <button type="submit" name="update" class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-md hover:bg-yellow-500">
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
                        <button type="submit" name="assign_inspector" class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-md hover:bg-yellow-500">
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

        function filterBusinesses() {
            const filterValue = document.getElementById('typeFilter').value;
            const cards = document.querySelectorAll('.business-card');

            cards.forEach(card => {
                const type = card.getAttribute('data-type');
                if (filterValue === '' || type === filterValue) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function sortBusinesses() {
            const sortValue = document.getElementById('sortFilter').value;
            const grid = document.getElementById('businessesGrid');
            const cards = Array.from(document.querySelectorAll('.business-card'));

            cards.sort((a, b) => {
                if (sortValue === 'name') {
                    const nameA = a.querySelector('h4').textContent.toLowerCase();
                    const nameB = b.querySelector('h4').textContent.toLowerCase();
                    return nameA.localeCompare(nameB);
                } else if (sortValue === 'compliance') {
                    const complianceA = parseInt(a.getAttribute('data-compliance'));
                    const complianceB = parseInt(b.getAttribute('data-compliance'));
                    return complianceB - complianceA; // Higher compliance first
                } else if (sortValue === 'last_inspection') {
                    const dateA = parseInt(a.getAttribute('data-last-inspection'));
                    const dateB = parseInt(b.getAttribute('data-last-inspection'));
                    return dateB - dateA; // Most recent first
                }
                return 0;
            });

            // Re-append sorted cards to the grid
            cards.forEach(card => grid.appendChild(card));
        }
    </script>
</body>
</html>
