<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/InspectionType.php';
require_once '../utils/logger.php'; // Include logger
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if user has permission to manage inspectors
requirePermission('inspectors', 'index.php');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get all inspectors (only inspector role accounts)
$inspectors = $user->readByRole('inspector');

// Handle inspector creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_inspector'])) {
    $newInspector = new User($database);
    $newInspector->name = $_POST['name'];
    $newInspector->email = $_POST['email'];
    $newInspector->password = $_POST['password'];
    $newInspector->role = 'inspector';
    $newInspector->department = $_POST['department'];
    $newInspector->certification = $_POST['certification'];

    // Check if email already exists
    if ($newInspector->emailExists()) {
        $_SESSION['error_message'] = 'Email already exists. Please use a different email.';
    } else if ($newInspector->create()) {
        $_SESSION['success_message'] = 'Inspector created successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to create inspector.';
        logError("Failed to create inspector: " . $newInspector->email);
    }

    header('Location: inspectors.php');
    exit;
}

// Handle inspector update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inspector'])) {
    $updateInspector = new User($database);
    $updateInspector->id = $_POST['inspector_id'];
    $updateInspector->name = $_POST['name'];
    $updateInspector->email = $_POST['email'];
    $updateInspector->department = $_POST['department'];
    $updateInspector->certification = $_POST['certification'];

    if ($updateInspector->update()) {
        $_SESSION['success_message'] = 'Inspector updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update inspector.';
        logError("Failed to update inspector ID: " . $updateInspector->id);
    }

    header('Location: inspectors.php');
    exit;
}

// Handle inspector deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inspector'])) {
    $deleteInspector = new User($database);
    $deleteInspector->id = $_POST['inspector_id'];

    if ($deleteInspector->delete()) {
        $_SESSION['success_message'] = 'Inspector deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete inspector.';
        logError("Failed to delete inspector ID: " . $deleteInspector->id); // Log error
    }

    header('Location: inspectors.php');
    exit;
}

// Get all inspection types
$inspectionType = new InspectionType($database);
$inspectionTypes = $inspectionType->readAll();

// Handle inspector assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_inspector'])) {
    $inspection_id = $_POST['inspection_id'];
    $inspector_id = $_POST['inspector_id'];

    $inspection = new Inspection($database);
    $inspection->id = $inspection_id;
    $inspection->inspector_id = $inspector_id;

    if ($inspection->assignInspector()) {
        $_SESSION['success_message'] = 'Inspector assigned successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to assign inspector.';
        logError("Failed to assign inspector ID: " . $inspector_id); // Log error
    }

    header('Location: inspectors.php');
    exit;
}

// Get stats for dashboard cards
$totalInspectors = $user->countActiveInspectors();
$activeToday = $user->countActiveInspectorsToday();

$inspectionModel = new Inspection($database);
$inspectionStats = $inspectionModel->getInspectionStatsByStatus();
$scheduledInspections = ($inspectionStats['scheduled'] ?? 0) + ($inspectionStats['in_progress'] ?? 0);
$completedInspections = $inspectionStats['completed'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspectors - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
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
                        <p class="text-2xl font-bold"><?php echo $totalInspectors; ?></p>
                    </div>
                    <i class="fas fa-users text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Today</p>
                        <p class="text-2xl font-bold"><?php echo $activeToday; ?></p>
                    </div>
                    <i class="fas fa-user-check text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Scheduled Inspections</p>
                        <p class="text-2xl font-bold"><?php echo $scheduledInspections; ?></p>
                    </div>
                    <i class="fas fa-calendar text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Completed Inspections</p>
                        <p class="text-2xl font-bold"><?php echo $completedInspections; ?></p>
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
                    <?php while ($inspector = $inspectors->fetch(PDO::FETCH_ASSOC)): ?>
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
                            <button onclick="editInspector(<?php echo $inspector['id']; ?>)" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="deleteInspector(<?php echo $inspector['id']; ?>, '<?php echo addslashes($inspector['name']); ?>')"
                                    class="text-red-600 hover:text-red-900 mr-3">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php if ($inspector['role'] === 'inspector'): ?>
                            <button onclick="assignInspector(<?php echo $inspector['id']; ?>, '<?php echo addslashes($inspector['name']); ?>')"
                                    class="text-purple-600 hover:text-purple-900">
                                <i class="fas fa-tasks"></i> Assign
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Inspector Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Add New Inspector</h3>
                <form method="POST" action="inspectors.php" class="mt-4 space-y-4">
                    <input type="hidden" name="create_inspector" value="1">

                    <div>
                        <label for="createName" class="block text-sm font-medium text-gray-700">Full Name *</label>
                        <input type="text" id="createName" name="name" required placeholder="Enter full name"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="createEmail" class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" id="createEmail" name="email" required placeholder="Enter email address"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="createPassword" class="block text-sm font-medium text-gray-700">Password *</label>
                        <input type="password" id="createPassword" name="password" required placeholder="Enter password"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="createDepartment" class="block text-sm font-medium text-gray-700">Department</label>
                        <input type="text" id="createDepartment" name="department" placeholder="Enter department"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="createCertification" class="block text-sm font-medium text-gray-700">Certification</label>
                        <input type="text" id="createCertification" name="certification" placeholder="Enter certification"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Inspector
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Enhanced modal management
        const modals = ['createModal', 'viewModal', 'editModal', 'assignModal'];

        // Close modal when clicking outside or pressing ESC
        function setupModalHandlers() {
            // Click outside to close
            window.onclick = function(event) {
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            }

            // ESC key to close
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    modals.forEach(modalId => {
                        if (!document.getElementById(modalId).classList.contains('hidden')) {
                            closeModal(modalId);
                        }
                    });
                }
            });
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function assignInspector(id, name) {
            // Set inspector details in modal
            document.getElementById('assignInspectorId').value = id;
            document.getElementById('assignInspectorName').textContent = name;

            // Load available inspections for assignment
            loadAvailableInspections();

            // Show modal
            document.getElementById('assignModal').classList.remove('hidden');
        }

        function loadAvailableInspections() {
            fetch('get_available_inspections.php')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('assignInspectionId');
                    select.innerHTML = '<option value="">Select Inspection</option>';

                    if (data.success && data.inspections.length > 0) {
                        data.inspections.forEach(inspection => {
                            const option = document.createElement('option');
                            option.value = inspection.id;
                            option.textContent = `${inspection.business_name} - ${inspection.inspection_type} (${inspection.scheduled_date})`;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">No available inspections</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading inspections:', error);
                    showNotification('Failed to load available inspections.', 'error');
                });
        }

        // Enhanced view inspector with loading state
        function viewInspector(id) {
            const viewBtn = event.target.closest('button');
            const originalText = viewBtn.innerHTML;
            viewBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            viewBtn.disabled = true;

            fetch(`get_inspector.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewName').textContent = data.inspector.name;
                        document.getElementById('viewEmail').textContent = data.inspector.email;
                        document.getElementById('viewRole').textContent = data.inspector.role.charAt(0).toUpperCase() + data.inspector.role.slice(1).replace('_', ' ');
                        document.getElementById('viewDepartment').textContent = data.inspector.department || 'N/A';
                        document.getElementById('viewCertification').textContent = data.inspector.certification || 'Not Certified';
                        document.getElementById('viewCreatedAt').textContent = new Date(data.inspector.created_at).toLocaleDateString();

                        openModal('viewModal');
                    } else {
                        showNotification('Failed to load inspector details.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to load inspector details.', 'error');
                })
                .finally(() => {
                    viewBtn.innerHTML = originalText;
                    viewBtn.disabled = false;
                });
        }

        // Enhanced edit inspector with loading state
        function editInspector(id) {
            const editBtn = event.target.closest('button');
            const originalText = editBtn.innerHTML;
            editBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            editBtn.disabled = true;

            fetch(`get_inspector.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editInspectorId').value = data.inspector.id;
                        document.getElementById('editName').value = data.inspector.name;
                        document.getElementById('editEmail').value = data.inspector.email;
                        document.getElementById('editDepartment').value = data.inspector.department || '';
                        document.getElementById('editCertification').value = data.inspector.certification || '';

                        openModal('editModal');
                    } else {
                        showNotification('Failed to load inspector details for editing.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to load inspector details for editing.', 'error');
                })
                .finally(() => {
                    editBtn.innerHTML = originalText;
                    editBtn.disabled = false;
                });
        }

        // Enhanced delete inspector with better confirmation
        function deleteInspector(id, name) {
            if (confirm(`Are you sure you want to delete inspector "${name}"?\n\nThis action cannot be undone and will permanently remove the inspector from the system.`)) {
                const deleteBtn = event.target.closest('button');
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                deleteBtn.disabled = true;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'inspectors.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'inspector_id';
                input.value = id;

                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_inspector';
                deleteInput.value = '1';

                form.appendChild(input);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Show notification function
        function showNotification(message, type = 'success') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `custom-notification fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Form validation for create modal
        function validateCreateForm() {
            const name = document.getElementById('createName').value.trim();
            const email = document.getElementById('createEmail').value.trim();
            const password = document.getElementById('createPassword').value;

            if (!name) {
                showNotification('Please enter a name.', 'error');
                return false;
            }

            if (!email) {
                showNotification('Please enter an email address.', 'error');
                return false;
            }

            if (!validateEmail(email)) {
                showNotification('Please enter a valid email address.', 'error');
                return false;
            }

            if (!password) {
                showNotification('Please enter a password.', 'error');
                return false;
            }

            if (password.length < 6) {
                showNotification('Password must be at least 6 characters long.', 'error');
                return false;
            }

            return true;
        }

        // Email validation helper
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Initialize modal handlers
        setupModalHandlers();

        // Add form validation to create form
        document.querySelector('form[action="inspectors.php"]').addEventListener('submit', function(e) {
            if (!validateCreateForm()) {
                e.preventDefault();
            }
        });
    </script>

    <!-- View Inspector Modal -->
    <div id="viewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Inspector Details</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name:</label>
                        <p id="viewName" class="text-sm text-gray-900 mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email:</label>
                        <p id="viewEmail" class="text-sm text-gray-900 mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role:</label>
                        <p id="viewRole" class="text-sm text-gray-900 mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department:</label>
                        <p id="viewDepartment" class="text-sm text-gray-900 mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Certification:</label>
                        <p id="viewCertification" class="text-sm text-gray-900 mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created:</label>
                        <p id="viewCreatedAt" class="text-sm text-gray-900 mt-1"></p>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" onclick="document.getElementById('viewModal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Inspector Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Inspector</h3>
                <form method="POST" action="inspectors.php" class="mt-4 space-y-4">
                    <input type="hidden" name="inspector_id" id="editInspectorId">
                    <input type="hidden" name="update_inspector" value="1">

                    <div>
                        <label for="editName" class="block text-sm font-medium text-gray-700">Full Name *</label>
                        <input type="text" id="editName" name="name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="editEmail" class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" id="editEmail" name="email" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="editDepartment" class="block text-sm font-medium text-gray-700">Department</label>
                        <input type="text" id="editDepartment" name="department"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="editCertification" class="block text-sm font-medium text-gray-700">Certification</label>
                        <input type="text" id="editCertification" name="certification"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update Inspector
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Inspector Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Assign Inspector to Inspection</h3>
                <div class="mt-4 mb-4">
                    <p class="text-sm text-gray-600">Assigning inspector: <span id="assignInspectorName" class="font-medium"></span></p>
                </div>
                <form method="POST" action="inspectors.php" class="space-y-4">
                    <input type="hidden" name="inspector_id" id="assignInspectorId">
                    <input type="hidden" name="assign_inspector" value="1">

                    <div>
                        <label for="assignInspectionId" class="block text-sm font-medium text-gray-700">Select Inspection *</label>
                        <select name="inspection_id" id="assignInspectionId" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Loading inspections...</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                            <i class="fas fa-tasks mr-2"></i>Assign Inspector
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
