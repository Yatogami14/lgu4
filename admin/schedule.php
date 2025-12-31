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
                
                $notification->user_id = $inspection_data['inspector_id'];
                $notification->message = "The inspection for {$business_name} has been cancelled by an admin.";
                $notification->type = "warning";
                $notification->related_entity_type = "inspection";
                $notification->related_entity_id = $inspection->id;
                $notification->create();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Schedule Inspections</h2>
            <button onclick="document.getElementById('scheduleModal').classList.remove('hidden')"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-md hover:bg-yellow-500">
                <i class="fas fa-plus mr-2"></i>New Inspection
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Schedule Inspection Modal -->
        <div id="scheduleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Schedule New Inspection</h3>
                    <form method="POST" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Business</label>
                            <select name="business_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Business</option>
                            <?php foreach ($businesses as $business_row): ?>
                                <option value="<?php echo $business_row['id']; ?>"><?php echo htmlspecialchars($business_row['name']); ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                            <select name="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Select Type</option>
                                <?php foreach ($all_inspection_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assign Inspector (Optional)</label>
                            <select name="inspector_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Unassigned</option>
                                <?php foreach ($all_inspectors as $inspector_user): ?>
                                    <option value="<?php echo $inspector_user['id']; ?>"><?php echo htmlspecialchars($inspector_user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                            <input type="datetime-local" name="scheduled_date" required min="<?php echo date('Y-m-d\TH:i'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <select name="priority" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="document.getElementById('scheduleModal').classList.add('hidden')" 
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </button>
                            <button type="submit" name="schedule_inspection"
                                    class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-md hover:bg-yellow-500">
                                Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reschedule Inspection Modal -->
        <div id="rescheduleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900">Reschedule Inspection</h3>
                    <p class="text-sm text-gray-600 mt-1">For: <span id="rescheduleBusinessName" class="font-bold"></span></p>
                    <form id="rescheduleForm" method="POST" class="mt-4 space-y-4">
                        <input type="hidden" name="action" value="reschedule_inspection">
                        <input type="hidden" name="inspection_id" id="reschedule_inspection_id">
                        <input type="hidden" name="original_inspector_id" id="original_inspector_id">
                        <input type="hidden" name="original_scheduled_date" id="original_scheduled_date">

                        <div>
                            <label for="reschedule_date" class="block text-sm font-medium text-gray-700">New Scheduled Date</label>
                            <input type="datetime-local" name="scheduled_date" id="reschedule_date" required min="<?php echo date('Y-m-d\TH:i'); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="reschedule_inspector" class="block text-sm font-medium text-gray-700">Assign Inspector</label>
                            <select name="inspector_id" id="reschedule_inspector" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">Unassigned</option>
                                <?php foreach ($all_inspectors as $inspector_user): ?>
                                    <option value="<?php echo $inspector_user['id']; ?>"><?php echo htmlspecialchars($inspector_user['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeModal('rescheduleModal')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-md hover:bg-yellow-500">Reschedule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Scheduled Inspections Table -->
        <div class="mt-8">
            <h3 class="text-xl font-bold mb-4">All Inspections</h3>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status & Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($all_inspections)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No inspections found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_inspections as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['business_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['business_address']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['inspection_type']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M j, Y H:i', strtotime($row['scheduled_date'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($row['inspector_name'] ?? 'Unassigned'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $row['status'] == 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                                   ($row['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($row['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                            <?php echo str_replace('_', ' ', $row['status']); ?>
                                        </span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $row['priority'] == 'high' ? 'bg-red-100 text-red-800' : 
                                                   ($row['priority'] == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['priority'])); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="inspection_view.php?id=<?php echo $row['id']; ?>" class="text-yellow-600 hover:text-yellow-800 mr-3" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="openRescheduleModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['business_name'])); ?>', '<?php echo $row['scheduled_date']; ?>', '<?php echo $row['inspector_id'] ?? ''; ?>')" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Reschedule">
                                        <i class="fas fa-calendar-alt"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this inspection?');" class="inline-block">
                                        <input type="hidden" name="action" value="cancel_inspection">
                                        <input type="hidden" name="inspection_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Cancel Inspection">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
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
                
                document.getElementById('rescheduleModal').classList.remove('hidden');
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('scheduleModal');
                if (event.target == modal) {
                    modal.classList.add('hidden');
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
