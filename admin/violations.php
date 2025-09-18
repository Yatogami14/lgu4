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
$violations = $violationModel->readAll();
$violationStats = $violationModel->getViolationStats();

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">

<!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Violations Management</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                <i class="fas fa-plus mr-2"></i>Report Violation
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Violations</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['total'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Open Violations</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['open'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['in_progress'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-spinner text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Resolved</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['resolved'] ?? 0; ?></p>
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
                            <button onclick='openEditViolationModal(<?php echo json_encode($violation); ?>)' class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($violation['inspection_id'] == 0): ?>
                                <button onclick='openCreateInspectionModal(<?php echo json_encode($violation); ?>)' class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-plus-circle"></i> Create Inspection
                                </button>
                            <?php else: ?>
                                <a href="inspection_view.php?id=<?php echo $violation['inspection_id']; ?>" class="text-green-600 hover:text-green-900"><i class="fas fa-eye"></i> View Inspection</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Violation Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Report New Violation</h3>
                <form method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="create_violation">
                    <div>
                        <label for="create_business_id" class="block text-sm font-medium text-gray-700">Business</label>
                        <select id="create_business_id" name="business_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select a Business</option>
                            <?php foreach ($allBusinesses as $business): ?>
                                <option value="<?php echo $business['id']; ?>"><?php echo htmlspecialchars($business['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="create_inspection_id" class="block text-sm font-medium text-gray-700">Inspection</label>
                        <select id="create_inspection_id" name="inspection_id" required disabled class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100">
                            <option value="">Select a business first</option>
                        </select>
                    </div>
                    <div>
                        <label for="create_description" class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea id="create_description" name="description" rows="3" placeholder="Describe the violation..." required
                                  class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div>
                        <label for="create_severity" class="block text-sm font-medium text-gray-700">Severity</label>
                        <select id="create_severity" name="severity" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div>
                        <label for="create_due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" id="create_due_date" name="due_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Violation Modal -->
    <div id="editViolationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Violation</h3>
                <form id="editViolationForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="update_violation">
                    <input type="hidden" name="violation_id" id="edit_violation_id">

                    <div>
                        <label for="edit_violation_description" class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea id="edit_violation_description" name="description" rows="3" required class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_violation_severity" class="block text-sm font-medium text-gray-700">Severity</label>
                            <select id="edit_violation_severity" name="severity" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_violation_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="edit_violation_status" name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                            <input type="date" id="edit_violation_due_date" name="due_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="edit_violation_resolved_date" class="block text-sm font-medium text-gray-700">Resolved Date</label>
                            <input type="date" id="edit_violation_resolved_date" name="resolved_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('editViolationModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update Violation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Inspection from Violation Modal -->
    <div id="createInspectionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Create Inspection for Violation</h3>
                <p class="text-sm text-gray-600 mt-1">For business: <span id="ci_business_name" class="font-bold"></span></p>
                
                <form id="createInspectionForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="create_inspection_from_violation">
                    <input type="hidden" name="violation_id" id="ci_violation_id">
                    <input type="hidden" name="business_id" id="ci_business_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Violation</label>
                        <p id="ci_violation_description" class="mt-1 text-sm text-gray-600 bg-gray-100 p-2 rounded-md border"></p>
                    </div>

                    <div>
                        <label for="ci_inspection_type_id" class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select id="ci_inspection_type_id" name="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select Type</option>
                            <?php foreach ($allInspectionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ci_inspector_id" class="block text-sm font-medium text-gray-700">Assign Inspector (Optional)</label>
                        <select id="ci_inspector_id" name="inspector_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Unassigned</option>
                            <?php foreach ($all_inspectors as $inspector_user): ?>
                                <option value="<?php echo $inspector_user['id']; ?>"><?php echo htmlspecialchars($inspector_user['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="ci_scheduled_date" class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                        <input type="datetime-local" id="ci_scheduled_date" name="scheduled_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="ci_priority" class="block text-sm font-medium text-gray-700">Priority</label>
                        <select id="ci_priority" name="priority" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div>
                        <label for="ci_notes" class="block text-sm font-medium text-gray-700">Notes for Inspector</label>
                        <textarea id="ci_notes" name="notes" rows="2" placeholder="e.g., Follow up on community report regarding..." class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('createInspectionModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Create & Link Inspection</button>
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
    </script>
</body>
</html>
