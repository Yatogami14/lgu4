<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('inspections');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($database);
$business = new Business($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_inspection'])) {
        $inspection->business_id = $_POST['business_id'];
        $inspection->inspector_id = $_POST['inspector_id'];
        $inspection->inspection_type_id = $_POST['inspection_type_id'];
        $inspection->scheduled_date = $_POST['scheduled_date'];
        $inspection->status = $_POST['status'];
        $inspection->priority = $_POST['priority'];
        $inspection->notes = $_POST['notes'];

        if ($inspection->create()) {
            header('Location: inspections.php?success=Inspection created successfully');
            exit;
        }
    }

    // Handle re-assignment from modal
    if (isset($_POST['action']) && $_POST['action'] === 'reassign_inspector') {
        header('Content-Type: application/json');

        // Only admins can re-assign
        if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
            echo json_encode(['success' => false, 'message' => 'Permission Denied.']);
            exit;
        }

        $inspection->id = $_POST['inspection_id'];
        $inspection->inspector_id = $_POST['inspector_id'];

        if ($inspection->assignInspector()) {
            // Create a notification for the newly assigned inspector
            require_once '../models/Notification.php';
            $notification = new Notification($database);            
            $businessName = $_POST['business_name'] ?? 'a business';
            $notification->createAssignmentNotification(
                $inspection->inspector_id, 
                $businessName, 
                $inspection->id
            );
            echo json_encode(['success' => true, 'message' => 'Inspector re-assigned successfully and notified.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to re-assign inspector.']);
        }
        exit;
    }

    // Handle deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete_inspection') {
        // Only admins can delete
        if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
            $_SESSION['error_message'] = 'Permission Denied.';
        } else {
            $inspection->id = $_POST['inspection_id'];
            if ($inspection->delete()) {
                $_SESSION['success_message'] = 'Inspection deleted successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to delete inspection. It might be referenced by other records (e.g., violations).';
            }
        }
        header('Location: inspections.php');
        exit;
    }
}

// Get inspections based on filter
$filter_priority = $_GET['priority'] ?? null;
$filter_status = $_GET['status'] ?? null;

if ($filter_priority) {
    $inspections = $inspection->readByPriority($filter_priority);
} elseif ($filter_status) {
    // The status from the chart label might have spaces (e.g., "In Progress"), so we convert it back to the database format.
    $inspections = $inspection->readByStatus(strtolower(str_replace(' ', '_', $filter_status)));
}else {
    $inspections = $inspection->readAll();
}

$businesses = $business->readAll();
$inspectors = $user->readByRole('inspector');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspections - Digital Health & Safety Inspection Platform</title>
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
                <h2 class="text-2xl font-bold text-gray-900">Inspections Management</h2>
                <p class="text-sm text-gray-600 mt-1">Manage and track safety inspections</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterInspections()" placeholder="Search inspections..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                        class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center justify-center text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>New Inspection
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p><?php echo htmlspecialchars($_GET['success']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Inspections Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="inspectionsGrid">
            <?php foreach ($inspections as $row): ?>
            <div class="inspection-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300" data-search-content="<?php echo htmlspecialchars(strtolower($row['business_name'] . ' ' . $row['business_address'] . ' ' . $row['inspection_type'] . ' ' . ($row['inspector_name'] ?? ''))); ?>">
                <!-- Card Header -->
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($row['business_name']); ?></h3>
                            <p class="text-sm text-gray-500 flex items-center">
                                <i class="fas fa-map-marker-alt mr-1.5 text-gray-400"></i>
                                <?php echo htmlspecialchars($row['business_address']); ?>
                            </p>
                        </div>
                        <div class="flex flex-col items-end space-y-2">
                            <!-- Priority Badge -->
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full
                                <?php echo $row['priority'] == 'high' ? 'bg-red-100 text-red-800' :
                                       ($row['priority'] == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo ucfirst($row['priority']); ?>
                            </span>
                            <!-- Status Badge -->
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full
                                <?php echo $row['status'] == 'scheduled' ? 'bg-blue-100 text-blue-800' :
                                       ($row['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' :
                                       ($row['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                <?php echo str_replace('_', ' ', ucfirst($row['status'])); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Inspection Type -->
                    <div class="flex items-center text-sm text-gray-600 mb-2">
                        <i class="fas fa-clipboard-check mr-2 text-brand-500"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($row['inspection_type']); ?></span>
                    </div>

                    <!-- Scheduled Date -->
                    <div class="flex items-center text-sm text-gray-600 mb-2">
                        <i class="fas fa-calendar-alt mr-2 text-brand-500"></i>
                        <span><?php echo date('M j, Y \a\t g:i A', strtotime($row['scheduled_date'])); ?></span>
                    </div>

                    <!-- Inspector -->
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-user mr-2 text-brand-500"></i>
                        <span><?php echo htmlspecialchars($row['inspector_name'] ?? 'Unassigned'); ?></span>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="p-4 bg-gray-50 rounded-b-xl border-t border-gray-100">
                    <div class="flex justify-between items-center">
                        <div class="flex space-x-2">
                            <a href="inspection_form.php?id=<?php echo $row['id']; ?>"
                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">
                                <i class="fas fa-edit mr-1.5"></i>
                                Edit
                            </a>
                            <a href="inspection_view.php?id=<?php echo $row['id']; ?>"
                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-eye mr-1.5"></i>
                                View
                            </a>
                        </div>
                        <div class="flex space-x-1">
                            <button onclick="openReassignModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['business_name'])); ?>')"
                                    class="p-2 text-gray-500 hover:text-brand-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="Re-assign Inspector">
                                <i class="fas fa-user-edit"></i>
                            </button>
                            <button onclick="deleteInspection(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['business_name'])); ?>')"
                                    class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Delete Inspection">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty State -->
        <?php if (empty($inspections)): ?>
        <div class="text-center py-12">
            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-clipboard-list text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No inspections found</h3>
            <p class="text-gray-600 mb-6">Get started by creating your first inspection.</p>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors duration-200">
                <i class="fas fa-plus mr-2"></i>
                Create Inspection
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Re-assign Inspector Modal -->
    <div id="reassignModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-md shadow-xl rounded-xl bg-white transform transition-all" id="reassignModalContent">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Re-assign Inspector</h3>
                <button onclick="closeModal('reassignModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">For inspection at: <span id="reassignBusinessName" class="font-bold text-gray-900"></span></p>
                <form id="reassignForm" class="space-y-4">
                    <input type="hidden" name="action" value="reassign_inspector">
                    <input type="hidden" name="inspection_id" id="reassign_inspection_id">
                    <input type="hidden" name="business_name" id="reassign_business_name_input">
                    <div>
                        <label for="reassign_inspector_select" class="block text-sm font-medium text-gray-700 mb-1">New Inspector</label>
                        <select name="inspector_id" id="reassign_inspector_select" required
                                class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Loading inspectors...</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('reassignModal')"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">
                            Re-assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Inspection Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all" id="createModalContent">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Create New Inspection</h3>
                <button onclick="closeModal('createModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Business</label>
                            <select name="business_id" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Select Business</option>
                                <?php foreach ($businesses as $business_row): ?>
                                    <option value="<?php echo $business_row['id']; ?>"><?php echo htmlspecialchars($business_row['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Inspection Type</label>
                            <select name="inspection_type_id" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="1">Health & Sanitation</option>
                                <option value="2">Fire Safety</option>
                                <option value="3">Building Safety</option>
                                <option value="4">Environmental</option>
                                <option value="5">Food Safety</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled Date & Time</label>
                        <input type="datetime-local" name="scheduled_date" required min="<?php echo date('Y-m-d\TH:i'); ?>"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="scheduled">Scheduled</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                            <select name="priority" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Inspector</label>
                        <select name="inspector_id" required
                                class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Select Inspector</option>
                            <?php foreach ($inspectors as $inspector): ?>
                                <option value="<?php echo $inspector['id']; ?>"><?php echo htmlspecialchars($inspector['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea name="notes" rows="3"
                                  class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"
                                  placeholder="Add any additional notes..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('createModal')"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" name="create_inspection"
                                class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">
                            Create Inspection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function filterInspections() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.inspection-card');
            
            cards.forEach(card => {
                const content = card.getAttribute('data-search-content');
                if (content.includes(search)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function openReassignModal(inspectionId, businessName) {
            document.getElementById('reassign_inspection_id').value = inspectionId;
            document.getElementById('reassignBusinessName').textContent = businessName;
            document.getElementById('reassign_business_name_input').value = businessName;

            const inspectorSelect = document.getElementById('reassign_inspector_select');
            inspectorSelect.innerHTML = '<option value="">Loading inspectors...</option>';

            // Fetch available inspectors
            fetch('get_inspectors.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        inspectorSelect.innerHTML = '<option value="">Select an inspector</option>';
                        data.inspectors.forEach(inspector => {
                            const option = document.createElement('option');
                            option.value = inspector.id;
                            option.textContent = inspector.name;
                            inspectorSelect.appendChild(option);
                        });
                    } else {
                        inspectorSelect.innerHTML = '<option value="">Failed to load inspectors</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching inspectors:', error);
                    inspectorSelect.innerHTML = '<option value="">Error loading inspectors</option>';
                });

            openModal('reassignModal');
        }

        // Open create modal with animation
        document.addEventListener('DOMContentLoaded', function() {
            const createButtons = document.querySelectorAll('[onclick*="createModal"]');
            createButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    openModal('createModal');
                });
            });
        });

        function deleteInspection(inspectionId, businessName) {
            if (confirm(`Are you sure you want to delete the inspection for "${businessName}" (ID: ${inspectionId})? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'inspections.php';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_inspection';
                form.appendChild(actionInput);

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'inspection_id';
                idInput.value = inspectionId;
                form.appendChild(idInput);

                document.body.appendChild(form);
                form.submit();
            }
        }

        document.getElementById('reassignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Re-assigning...';
            submitButton.disabled = true;

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Inspector re-assigned successfully!');
                    location.reload(); // Reload the page to see the change
                } else {
                    throw new Error(data.message || 'Failed to re-assign inspector.');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
                closeModal('reassignModal');
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
            const reassignModal = document.getElementById('reassignModal');
            if (event.target == reassignModal) {
                closeModal('reassignModal');
            }
        }
    </script>
</body>
</html>
