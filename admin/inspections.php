<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
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
            echo json_encode(['success' => true, 'message' => 'Inspector re-assigned successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to re-assign inspector.']);
        }
        exit;
    }
}

// Get all inspections
$inspections = $inspection->readAll();
$businesses = $business->readAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspections - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-20">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Inspections Management</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>New Inspection
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-md mb-4">
                <?php echo $_GET['success']; ?>
            </div>
        <?php endif; ?>

        <!-- Inspections Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($row = $inspections->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $row['business_name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $row['business_address']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['inspection_type']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M j, Y H:i', strtotime($row['scheduled_date'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $row['status'] == 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                       ($row['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($row['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                <?php echo str_replace('_', ' ', $row['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($row['inspector_name'] ?? 'Unassigned'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $row['priority'] == 'high' ? 'bg-red-100 text-red-800' : 
                                       ($row['priority'] == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                <?php echo $row['priority']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="inspection_form.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="inspection_view.php?id=<?php echo $row['id']; ?>" class="text-green-600 hover:text-green-900">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button onclick="openReassignModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['business_name'])); ?>')" class="text-purple-600 hover:text-purple-900 ml-3">
                                <i class="fas fa-random"></i> Re-assign
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Re-assign Inspector Modal -->
    <div id="reassignModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Re-assign Inspector</h3>
                <p class="text-sm text-gray-600 mt-1">For inspection at: <span id="reassignBusinessName" class="font-bold"></span></p>
                <form id="reassignForm" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="reassign_inspector">
                    <input type="hidden" name="inspection_id" id="reassign_inspection_id">
                    <div>
                        <label for="reassign_inspector_id" class="block text-sm font-medium text-gray-700">New Inspector</label>
                        <select name="inspector_id" id="reassign_inspector_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Loading inspectors...</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('reassignModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">Re-assign</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Inspection Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Create New Inspection</h3>
                <form method="POST" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business</label>
                        <select name="business_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select Business</option>
                            <?php while ($business_row = $businesses->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $business_row['id']; ?>"><?php echo $business_row['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select name="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="1">Health & Sanitation</option>
                            <option value="2">Fire Safety</option>
                            <option value="3">Building Safety</option>
                            <option value="4">Environmental</option>
                            <option value="5">Food Safety</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                        <input type="datetime-local" name="scheduled_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="overdue">Overdue</option>
                        </select>
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
                    <input type="hidden" name="inspector_id" value="<?php echo $user->id; ?>">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="create_inspection" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openReassignModal(inspectionId, businessName) {
            const modal = document.getElementById('reassignModal');
            document.getElementById('reassign_inspection_id').value = inspectionId;
            document.getElementById('reassignBusinessName').textContent = businessName;

            const inspectorSelect = document.getElementById('reassign_inspector_id');
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

            modal.classList.remove('hidden');
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
                    alert('Inspector re-assigned successfully!');
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
