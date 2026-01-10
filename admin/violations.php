<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Violation.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('violations');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get all inspectors for the modal
$inspectorUser = new User($database);
$all_inspectors = $inspectorUser->readByRole('inspector');

// Get all inspection types for the modal
require_once '../models/InspectionType.php';
$inspectionTypeModel = new InspectionType($database);
$allInspectionTypes = $inspectionTypeModel->readAll();

// --- Handle Violation Create/Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $violationModel = new Violation($database);

    // Handle Create Inspection from Violation
    if (isset($_POST['action']) && $_POST['action'] === 'create_inspection_from_violation') {
        $inspection = new Inspection($database);
        $inspection->business_id = $_POST['business_id'];
        $inspection->inspector_id = !empty($_POST['inspector_id']) ? $_POST['inspector_id'] : null;
        $inspection->inspection_type_id = $_POST['inspection_type_id'];
        $inspection->scheduled_date = $_POST['scheduled_date'];
        $inspection->status = 'scheduled';
        $inspection->priority = $_POST['priority'];
        $inspection->notes = $_POST['notes'];

        if ($inspection->create()) {
            $new_inspection_id = $inspection->id;
            $violationModel->id = $_POST['violation_id'];
            if ($violationModel->linkToInspection($new_inspection_id)) {
                $_SESSION['success_message'] = 'Inspection created and linked to violation successfully!';
            } else {
                $_SESSION['error_message'] = 'Inspection was created, but failed to link to the violation.';
            }
        } else {
            $_SESSION['error_message'] = 'Failed to create new inspection.';
        }
        header('Location: violations.php');
        exit;
    }

    // Handle Create
    if (isset($_POST['action']) && $_POST['action'] === 'create_violation') {
        $violationModel->inspection_id = $_POST['inspection_id'];
        $violationModel->business_id = $_POST['business_id'];
        $violationModel->description = $_POST['description'];
        $violationModel->severity = $_POST['severity'];
        $violationModel->due_date = $_POST['due_date'];
        $violationModel->created_by = $_SESSION['user_id'];
        $violationModel->status = 'open';

        if ($violationModel->create()) {
            $_SESSION['success_message'] = 'Violation reported successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to report violation.';
        }
        header('Location: violations.php');
        exit;
    }

    // Handle Update
    if (isset($_POST['action']) && $_POST['action'] === 'update_violation') {
        $violationModel->id = $_POST['violation_id'];
        $violationModel->description = $_POST['description'];
        $violationModel->severity = $_POST['severity'];
        $violationModel->status = $_POST['status'];
        $violationModel->due_date = $_POST['due_date'];
        $violationModel->resolved_date = !empty($_POST['resolved_date']) ? $_POST['resolved_date'] : null;

        if ($violationModel->update()) {
            $_SESSION['success_message'] = 'Violation updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update violation.';
        }
        header('Location: violations.php');
        exit;
    }
}

// Get all violations from database
$violationModel = new Violation($database);
$violationStats = $violationModel->getViolationStats();

// Handle filters
$filter_severity = $_GET['severity'] ?? null;

if ($filter_severity) {
    $violations = $violationModel->readBySeverity($filter_severity);
} else {
    $violations = $violationModel->readAll();
}

// Get businesses for create modal
$businessModel = new Business($database);
$allBusinesses = $businessModel->readAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violations - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">

<!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Violations Management</h2>
                <p class="text-sm text-gray-600 mt-1">Track and manage compliance violations</p>
            </div>
            <div class="flex gap-3">
                <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                        class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center justify-center text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Report Violation
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Violations</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $violationStats['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-600">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Open Violations</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $violationStats['open'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-brand-100 rounded-full flex items-center justify-center text-brand-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $violationStats['in_progress'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                        <i class="fas fa-spinner text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Resolved</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $violationStats['resolved'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Violations Cards Grid -->
        <div class="mt-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h3 class="text-xl font-bold text-gray-900">All Violations</h3>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <div class="relative">
                        <select id="severityFilter" onchange="filterViolations()" class="pl-3 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 text-sm w-full sm:w-auto">
                            <option value="">All Severities</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="relative">
                        <select id="statusFilter" onchange="filterViolations()" class="pl-3 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 text-sm w-full sm:w-auto">
                            <option value="">All Statuses</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="violationsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($violations)): ?>
                    <div class="col-span-full">
                        <div class="text-center py-12">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-check-circle text-3xl text-green-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No violations found</h3>
                            <p class="text-gray-600 mb-6">All businesses are compliant or filters are too restrictive.</p>
                            <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                                    class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Report Violation
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($violations as $violation):
                        $severity_color = ($violation['severity'] == 'critical' || $violation['severity'] == 'high') ? 'red' :
                                         ($violation['severity'] == 'medium' ? 'yellow' : 'green');
                        $status_color = $violation['status'] == 'open' ? 'red' :
                                       ($violation['status'] == 'in_progress' ? 'blue' :
                                       ($violation['status'] == 'resolved' ? 'green' : 'gray'));
                        $is_overdue = strtotime($violation['due_date']) < time() && $violation['status'] !== 'resolved' && $violation['status'] !== 'closed';
                    ?>
                    <div class="violation-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300
                         <?php echo $is_overdue ? 'ring-2 ring-red-300' : ''; ?>"
                         data-severity="<?php echo $violation['severity']; ?>"
                         data-status="<?php echo $violation['status']; ?>">
                        <!-- Card Header -->
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($violation['business_name']); ?></h4>
                                    <p class="text-sm text-gray-600 flex items-center mb-2">
                                        <i class="fas fa-building mr-1 text-gray-400"></i>
                                        Business Violation
                                    </p>
                                    <p class="text-sm text-gray-600 flex items-center">
                                        <i class="fas fa-calendar-times mr-1 text-gray-400"></i>
                                        Due: <?php echo date('M j, Y', strtotime($violation['due_date'])); ?>
                                        <?php if ($is_overdue): ?>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Overdue</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <!-- Severity Badge -->
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        <?php echo $severity_color == 'red' ? 'bg-red-100 text-red-800 border border-red-200' :
                                               ($severity_color == 'yellow' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 'bg-green-100 text-green-800 border border-green-200'); ?>">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <?php echo ucfirst($violation['severity']); ?>
                                    </span>
                                    <!-- Status Badge -->
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                                        <?php echo $status_color == 'red' ? 'bg-red-100 text-red-800 border border-red-200' :
                                               ($status_color == 'blue' ? 'bg-blue-100 text-blue-800 border border-blue-200' :
                                               ($status_color == 'green' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-gray-100 text-gray-800 border border-gray-200')); ?>">
                                        <i class="fas fa-circle mr-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $violation['status'])); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Violation Description -->
                            <div class="mb-4">
                                <p class="text-sm text-gray-700 leading-relaxed"><?php echo htmlspecialchars($violation['description']); ?></p>
                            </div>

                            <!-- Inspection Link -->
                            <?php if ($violation['inspection_id'] > 0): ?>
                            <div class="flex items-center text-sm text-brand-600 mb-3">
                                <i class="fas fa-link mr-2"></i>
                                <span>Linked to Inspection #<?php echo $violation['inspection_id']; ?></span>
                            </div>
                            <?php endif; ?>

                            <!-- Created Date -->
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-clock mr-2"></i>
                                <span>Created: <?php echo date('M j, Y', strtotime($violation['created_at'])); ?></span>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="p-4 bg-gray-50 rounded-b-xl">
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-2">
                                    <button onclick='openEditViolationModal(<?php echo json_encode($violation); ?>)'
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors duration-200">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <?php if ($violation['inspection_id'] == 0): ?>
                                        <button onclick='openCreateInspectionModal(<?php echo json_encode($violation); ?>)'
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                                            <i class="fas fa-plus-circle mr-1"></i>
                                            Create Inspection
                                        </button>
                                    <?php else: ?>
                                        <a href="inspection_view.php?id=<?php echo $violation['inspection_id']; ?>"
                                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 transition-colors duration-200">
                                            <i class="fas fa-eye mr-1"></i>
                                            View Inspection
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_overdue): ?>
                                <div class="flex items-center text-red-600">
                                    <i class="fas fa-exclamation-circle text-lg"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Violation Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Report New Violation</h3>
                <button onclick="closeModal('createModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_violation">
                    <div>
                        <label for="create_business_id" class="block text-sm font-medium text-gray-700">Business</label>
                        <select id="create_business_id" name="business_id" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Select a Business</option>
                            <?php foreach ($allBusinesses as $business): ?>
                                <option value="<?php echo $business['id']; ?>"><?php echo htmlspecialchars($business['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="create_inspection_id" class="block text-sm font-medium text-gray-700">Inspection</label>
                        <select id="create_inspection_id" name="inspection_id" required disabled class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Select a business first</option>
                        </select>
                    </div>
                    <div>
                        <label for="create_description" class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea id="create_description" name="description" rows="3" placeholder="Describe the violation..." required
                                  class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                    </div>
                    <div>
                        <label for="create_severity" class="block text-sm font-medium text-gray-700">Severity</label>
                        <select id="create_severity" name="severity" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label for="create_due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" id="create_due_date" name="due_date" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('createModal')" 
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">
                            Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Violation Modal -->
    <div id="editViolationModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Edit Violation</h3>
                <button onclick="closeModal('editViolationModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="editViolationForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_violation">
                    <input type="hidden" name="violation_id" id="edit_violation_id">

                    <div>
                        <label for="edit_violation_description" class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea id="edit_violation_description" name="description" rows="3" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_violation_severity" class="block text-sm font-medium text-gray-700">Severity</label>
                            <select id="edit_violation_severity" name="severity" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_violation_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="edit_violation_status" name="status" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_violation_due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" id="edit_violation_due_date" name="due_date" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label for="edit_violation_resolved_date" class="block text-sm font-medium text-gray-700">Resolved Date</label>
                            <input type="date" id="edit_violation_resolved_date" name="resolved_date" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('editViolationModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">Update Violation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Inspection from Violation Modal -->
    <div id="createInspectionModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Create Inspection for Violation</h3>
                <button onclick="closeModal('createInspectionModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">For business: <span id="ci_business_name" class="font-bold text-gray-900"></span></p>
                
                <form id="createInspectionForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create_inspection_from_violation">
                    <input type="hidden" name="violation_id" id="ci_violation_id">
                    <input type="hidden" name="business_id" id="ci_business_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation</label>
                        <p id="ci_violation_description" class="mt-1 text-sm text-gray-600 bg-gray-50 p-3 rounded-lg border border-gray-200"></p>
                    </div>

                    <div>
                        <label for="ci_inspection_type_id" class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select id="ci_inspection_type_id" name="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Select Type</option>
                            <?php foreach ($allInspectionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ci_inspector_id" class="block text-sm font-medium text-gray-700">Assign Inspector (Optional)</label>
                        <select id="ci_inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Unassigned</option>
                            <?php foreach ($all_inspectors as $inspector_user): ?>
                                <option value="<?php echo $inspector_user['id']; ?>"><?php echo htmlspecialchars($inspector_user['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ci_scheduled_date" class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                        <input type="datetime-local" id="ci_scheduled_date" name="scheduled_date" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label for="ci_priority" class="block text-sm font-medium text-gray-700">Priority</label>
                        <select id="ci_priority" name="priority" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div>
                        <label for="ci_notes" class="block text-sm font-medium text-gray-700">Notes for Inspector</label>
                        <textarea id="ci_notes" name="notes" rows="2" placeholder="e.g., Follow up on community report regarding..." class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('createInspectionModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">Create & Link Inspection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        // --- Create Violation Modal Logic ---
        document.getElementById('create_business_id').addEventListener('change', function() {
            const businessId = this.value;
            const inspectionSelect = document.getElementById('create_inspection_id');
            inspectionSelect.innerHTML = '<option value="">Loading...</option>';
            inspectionSelect.disabled = true;

            if (!businessId) {
                inspectionSelect.innerHTML = '<option value="">Select a business first</option>';
                return;
            }

            fetch(`get_inspections_by_business.php?business_id=${businessId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.inspections.length > 0) {
                        inspectionSelect.innerHTML = '<option value="">Select an inspection</option>';
                        data.inspections.forEach(inspection => {
                            const option = document.createElement('option');
                            option.value = inspection.id;
                            option.textContent = `ID: ${inspection.id} - ${inspection.inspection_type} on ${new Date(inspection.scheduled_date).toLocaleDateString()}`;
                            inspectionSelect.appendChild(option);
                        });
                        inspectionSelect.disabled = false;
                        inspectionSelect.classList.remove('bg-gray-100');
                    } else {
                        inspectionSelect.innerHTML = '<option value="">No inspections found for this business</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching inspections:', error);
                    inspectionSelect.innerHTML = '<option value="">Error loading inspections</option>';
                });
        });

        // --- Edit Violation Modal Logic ---
        function openEditViolationModal(violation) {
            document.getElementById('edit_violation_id').value = violation.id;
            document.getElementById('edit_violation_description').value = violation.description;
            document.getElementById('edit_violation_severity').value = violation.severity;
            document.getElementById('edit_violation_status').value = violation.status;
            document.getElementById('edit_violation_due_date').value = violation.due_date;
            document.getElementById('edit_violation_resolved_date').value = violation.resolved_date;
            document.getElementById('editViolationModal').classList.remove('hidden');
        }

        // --- Create Inspection from Violation Modal Logic ---
        function openCreateInspectionModal(violation) {
            document.getElementById('ci_violation_id').value = violation.id;
            document.getElementById('ci_business_id').value = violation.business_id;
            document.getElementById('ci_business_name').textContent = violation.business_name;
            document.getElementById('ci_violation_description').textContent = violation.description;
            // Pre-fill notes
            document.getElementById('ci_notes').value = `Follow-up on violation (ID: ${violation.id}): "${violation.description}"`;
            // Set a default date for tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            tomorrow.setHours(9, 0, 0, 0);
            const formattedDate = tomorrow.toISOString().slice(0, 16);
            document.getElementById('ci_scheduled_date').value = formattedDate;

            openModal('createInspectionModal');
        }

        // --- Filter Violations ---
        function filterViolations() {
            const severityFilter = document.getElementById('severityFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const cards = document.querySelectorAll('.violation-card');

            cards.forEach(card => {
                const severity = card.getAttribute('data-severity');
                const status = card.getAttribute('data-status');

                const severityMatch = !severityFilter || severity === severityFilter;
                const statusMatch = !statusFilter || status === statusFilter;

                if (severityMatch && statusMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Check if any cards are visible
            const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
            const emptyState = document.querySelector('.col-span-full');

            if (visibleCards.length === 0 && !emptyState) {
                // Add empty state if no cards are visible
                const grid = document.getElementById('violationsGrid');
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'col-span-full';
                emptyDiv.innerHTML = `
                    <div class="text-center py-12">
                        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-filter text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No violations match your filters</h3>
                        <p class="text-gray-600 mb-6">Try adjusting your filter criteria.</p>
                        <button onclick="clearFilters()" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-times mr-2"></i>
                            Clear Filters
                        </button>
                    </div>
                `;
                grid.appendChild(emptyDiv);
            } else if (visibleCards.length > 0 && emptyState) {
                // Remove empty state if cards are visible
                emptyState.remove();
            }
        }

        function clearFilters() {
            document.getElementById('severityFilter').value = '';
            document.getElementById('statusFilter').value = '';
            filterViolations();
        }
    </script>
</body>
</html>
