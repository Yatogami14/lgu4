<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
require_once '../models/InspectionType.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('schedule');

$database = new Database();

$inspection = new Inspection($database);
$business = new Business($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['schedule_inspection'])) {
        $inspection->business_id = $_POST['business_id'];
        $inspection->inspector_id = !empty($_POST['inspector_id']) ? $_POST['inspector_id'] : null;
        $inspection->inspection_type_id = $_POST['inspection_type_id'];
        $inspection->scheduled_date = $_POST['scheduled_date'];
        $inspection->status = 'scheduled';
        $inspection->priority = $_POST['priority'];
        $inspection->notes = $_POST['notes'];

        if ($inspection->create()) {
            // Create notification if inspector is assigned
            if (!empty($inspection->inspector_id)) {
                $notification = new Notification($database);
                $business->id = $inspection->business_id;
                $business_data = $business->readOne();
                $business_name = $business_data['name'] ?? 'a business';
                $notification->createAssignmentNotification($inspection->inspector_id, $business_name, $inspection->id);
            }
            header('Location: schedule.php?success=Inspection scheduled successfully');
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'reschedule_inspection') {
        $inspection->id = $_POST['inspection_id'];
        $new_scheduled_date = $_POST['scheduled_date'];
        $new_inspector_id = !empty($_POST['inspector_id']) ? $_POST['inspector_id'] : null;
        $original_inspector_id = !empty($_POST['original_inspector_id']) ? $_POST['original_inspector_id'] : null;
        $original_scheduled_date = $_POST['original_scheduled_date'];

        // Convert to a common format for comparison
        $new_date_ts = strtotime($new_scheduled_date);
        $original_date_ts = strtotime($original_scheduled_date);

        $date_changed = ($new_date_ts !== $original_date_ts);
        $inspector_changed = ($new_inspector_id != $original_inspector_id);

        if ($inspection->reschedule($new_scheduled_date, $new_inspector_id)) {
            // Only send notifications if something actually changed
            if ($date_changed || $inspector_changed) {
                $notification = new Notification($database);
                $inspection_data = $inspection->readOne();
                $business_name = $inspection_data['business_name'] ?? 'an inspection';
                $formatted_date = date('M j, Y H:i', $new_date_ts);

                if ($inspector_changed) {
                    // Notify new inspector
                    if ($new_inspector_id) {
                        $reason = $date_changed ? "rescheduled to {$formatted_date} and assigned to you" : "assigned to you";
                        $notification->createAssignmentNotification($new_inspector_id, $business_name, $inspection->id, $reason);
                    }
                    // Notify old inspector
                    if ($original_inspector_id) {
                        $reason = $date_changed ? "rescheduled and assigned to someone else" : "re-assigned to someone else";
                        $notification->createUnassignmentNotification($original_inspector_id, $business_name, $inspection->id, $reason);
                    }
                } elseif ($date_changed) { // Inspector is the same, but date changed
                    if ($new_inspector_id) { // Only notify if there is an inspector
                        $notification->createRescheduleNotification($new_inspector_id, $business_name, $inspection->id, $formatted_date);
                    }
                }
            }
            header('Location: schedule.php?success=Inspection rescheduled successfully.');
            exit;
        } else {
            header('Location: schedule.php?error=Failed to reschedule inspection.');
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'cancel_inspection') {
        $inspection->id = $_POST['inspection_id'];
        if ($inspection->updateStatus('cancelled')) {
            // Get inspection data to find the inspector to notify
            $inspection_data = $inspection->readOne();
            if ($inspection_data && !empty($inspection_data['inspector_id'])) {
                $notification = new Notification($database);
                $business_name = $inspection_data['business_name'] ?? 'an inspection';
                
                $notification->create(
                    $inspection_data['inspector_id'],
                    "The inspection for {$business_name} has been cancelled by an admin.",
                    "warning",
                    "inspection",
                    $inspection->id
                );
            }
            header('Location: schedule.php?success=Inspection cancelled successfully.');
            exit;
        } else {
            header('Location: schedule.php?error=Failed to cancel inspection.');
            exit;
        }
    }
}

// Get all businesses
$businesses = $business->readAll();

// Get all inspectors for the dropdown
$inspectorUser = new User($database);
$all_inspectors = $inspectorUser->readByRole('inspector');

// Get all inspection types for the dropdown
$inspectionTypeModel = new InspectionType($database);
$all_inspection_types = $inspectionTypeModel->readAll();

// Get all inspections to display them
$all_inspections = $inspection->readAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Inspections - Digital Health & Safety Inspection Platform</title>
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
                <h2 class="text-2xl font-bold text-gray-900">Schedule Inspections</h2>
                <p class="text-sm text-gray-600 mt-1">Plan and manage upcoming inspections</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterInspections()" placeholder="Search schedules..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <button onclick="openModal('scheduleModal')" class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center justify-center text-sm font-medium">
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
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <p><?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Schedule Inspection Modal -->
        <div id="scheduleModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
            <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
                <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Schedule New Inspection</h3>
                    <button onclick="closeModal('scheduleModal')" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business</label>
                            <select name="business_id" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Select Business</option>
                            <?php foreach ($businesses as $business_row): ?>
                                <option value="<?php echo $business_row['id']; ?>"><?php echo htmlspecialchars($business_row['name']); ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                            <select name="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Select Type</option>
                                <?php foreach ($all_inspection_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assign Inspector (Optional)</label>
                            <select name="inspector_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Unassigned</option>
                                <?php foreach ($all_inspectors as $inspector_user): ?>
                                    <option value="<?php echo $inspector_user['id']; ?>"><?php echo htmlspecialchars($inspector_user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                            <input type="datetime-local" name="scheduled_date" required min="<?php echo date('Y-m-d\TH:i'); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <select name="priority" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                            <button type="button" onclick="closeModal('scheduleModal')" 
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                                Cancel
                            </button>
                            <button type="submit" name="schedule_inspection"
                                    class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">
                                Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reschedule Inspection Modal -->
        <div id="rescheduleModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
            <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
                <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white">Reschedule Inspection</h3>
                    <button onclick="closeModal('rescheduleModal')" class="text-white hover:text-gray-200 focus:outline-none">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">For: <span id="rescheduleBusinessName" class="font-bold text-gray-900"></span></p>
                    <form id="rescheduleForm" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="reschedule_inspection">
                        <input type="hidden" name="inspection_id" id="reschedule_inspection_id">
                        <input type="hidden" name="original_inspector_id" id="original_inspector_id">
                        <input type="hidden" name="original_scheduled_date" id="original_scheduled_date">

                        <div>
                            <label for="reschedule_date" class="block text-sm font-medium text-gray-700">New Scheduled Date</label>
                            <input type="datetime-local" name="scheduled_date" id="reschedule_date" required min="<?php echo date('Y-m-d\TH:i'); ?>" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label for="reschedule_inspector" class="block text-sm font-medium text-gray-700">Assign Inspector</label>
                            <select name="inspector_id" id="reschedule_inspector" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Unassigned</option>
                                <?php foreach ($all_inspectors as $inspector_user): ?>
                                    <option value="<?php echo $inspector_user['id']; ?>"><?php echo htmlspecialchars($inspector_user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                            <button type="button" onclick="closeModal('rescheduleModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">Reschedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Scheduled Inspections Cards Grid -->
        <div class="mt-8">
            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                <h3 class="text-xl font-bold text-gray-900">All Inspections</h3>
                <div class="w-full sm:w-auto">
                    <div class="relative">
                        <select id="statusFilter" onchange="filterInspections()" class="pl-3 pr-8 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 text-sm w-full sm:w-auto">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="inspectionsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($all_inspections)): ?>
                    <div class="col-span-full">
                        <div class="text-center py-12">
                            <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-calendar-times text-3xl text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No inspections found</h3>
                            <p class="text-gray-600 mb-6">Get started by scheduling your first inspection.</p>
                            <button onclick="openModal('scheduleModal')"
                                    class="inline-flex items-center px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Schedule Inspection
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_inspections as $row): ?>
                    <div class="inspection-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300"
                         data-status="<?php echo $row['status']; ?>"
                         data-search-content="<?php echo htmlspecialchars(strtolower($row['business_name'] . ' ' . $row['business_address'] . ' ' . $row['inspection_type'] . ' ' . ($row['inspector_name'] ?? ''))); ?>">
                        <!-- Card Header -->
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($row['business_name']); ?></h4>
                                    <p class="text-sm text-gray-500 flex items-center">
                                        <i class="fas fa-map-marker-alt mr-1.5 text-gray-400"></i>
                                        <?php echo htmlspecialchars($row['business_address']); ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <!-- Status Badge -->
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full
                                        <?php echo $row['status'] == 'scheduled' ? 'bg-blue-100 text-blue-800' :
                                               ($row['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' :
                                               ($row['status'] == 'completed' ? 'bg-green-100 text-green-800' :
                                               ($row['status'] == 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800'))); ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($row['status'])); ?>
                                    </span>
                                    <!-- Priority Badge -->
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full
                                        <?php echo $row['priority'] == 'high' ? 'bg-red-100 text-red-800' :
                                               ($row['priority'] == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                        <?php echo ucfirst($row['priority']); ?>
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
                                    <a href="inspection_view.php?id=<?php echo $row['id']; ?>"
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-eye mr-1.5"></i>
                                        View
                                    </a>
                                    <button onclick="openRescheduleModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['business_name'])); ?>', '<?php echo $row['scheduled_date']; ?>', '<?php echo $row['inspector_id'] ?? ''; ?>')"
                                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">
                                        <i class="fas fa-calendar-alt mr-1.5"></i>
                                        Reschedule
                                    </button>
                                </div>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this inspection?');" class="inline-block">
                                    <input type="hidden" name="action" value="cancel_inspection">
                                    <input type="hidden" name="inspection_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Cancel Inspection">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <script>
            function openModal(modalId) {
                document.getElementById(modalId).classList.remove('hidden');
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
            }

            function openRescheduleModal(inspectionId, businessName, scheduledDate, inspectorId) {
                document.getElementById('reschedule_inspection_id').value = inspectionId;
                document.getElementById('rescheduleBusinessName').textContent = businessName;

                // Format date for datetime-local input
                const date = new Date(scheduledDate);
                const formattedDate = date.getFullYear() + '-' +
                                      ('0' + (date.getMonth() + 1)).slice(-2) + '-' +
                                      ('0' + date.getDate()).slice(-2) + 'T' +
                                      ('0' + date.getHours()).slice(-2) + ':' +
                                      ('0' + date.getMinutes()).slice(-2);
                document.getElementById('reschedule_date').value = formattedDate;

                document.getElementById('reschedule_inspector').value = inspectorId || '';
                document.getElementById('original_inspector_id').value = inspectorId || '';
                document.getElementById('original_scheduled_date').value = scheduledDate;

                openModal('rescheduleModal');
            }

            function filterInspections() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const filterValue = document.getElementById('statusFilter').value;
                const cards = document.querySelectorAll('.inspection-card');

                cards.forEach(card => {
                    const status = card.getAttribute('data-status');
                    const content = card.getAttribute('data-search-content');
                    
                    const matchesStatus = filterValue === '' || status === filterValue;
                    const matchesSearch = content.includes(search);

                    if (matchesStatus && matchesSearch) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('scheduleModal');
                if (event.target == modal) {
                    closeModal('scheduleModal');
                }
                const rescheduleModal = document.getElementById('rescheduleModal');
                if (event.target == rescheduleModal) {
                    closeModal('rescheduleModal');
                }
            }
        </script>
    </div>
</body>
</html>
