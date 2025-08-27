<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get all inspectors
$inspectors = $user->readAll();

// Get all inspections for assignment
$inspection = new Inspection($db);
$allInspections = $inspection->readAll();

// Get all businesses for assignment
$business = new Business($db);
$businesses = $business->readAll();

// Handle inspector assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_inspector'])) {
    $inspection_id = $_POST['inspection_id'];
    $inspector_id = $_POST['inspector_id'];
    
    $inspection->id = $inspection_id;
    $inspection->inspector_id = $inspector_id;
    
    if ($inspection->assignInspector()) {
        $_SESSION['success_message'] = 'Inspector assigned successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to assign inspector.';
    }
    
    header('Location: inspectors.php');
    exit;
}
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
                        <p class="text-2xl font-bold">3</p>
                    </div>
                    <i class="fas fa-users text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Today</p>
                        <p class="text-2xl font-bold">2</p>
                    </div>
                    <i class="fas fa-user-check text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Scheduled</p>
                        <p class="text-2xl font-bold">5</p>
                    </div>
                    <i class="fas fa-calendar text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Completed</p>
                        <p class="text-2xl font-bold">12</p>
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
                            <?php if ($inspector['role'] === 'inspector'): ?>
                            <button onclick="assignInspector(<?php echo $inspector['id']; ?>, '<?php echo $inspector['name']; ?>')" 
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
                <form class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" placeholder="Enter full name" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" placeholder="Enter email address" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" placeholder="Enter password" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="inspector">Inspector</option>
                            <option value="admin">Administrator</option>
                            <option value="super_admin">Super Administrator</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <input type="text" placeholder="Enter department" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Certification</label>
                        <input type="text" placeholder="Enter certification" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add Inspector
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
                <h3 class="text-lg font-medium text-gray-900" id="assignModalTitle">Assign Inspector</h3>
                <form method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="assign_inspector" value="1">
                    <input type="hidden" name="inspector_id" id="assignInspectorId">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Select Inspection</label>
                        <select name="inspection_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">Select an inspection</option>
                            <?php while ($inspection = $allInspections->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $inspection['id']; ?>">
                                <?php echo $inspection['business_name'] . ' - ' . $inspection['inspection_type'] . ' (' . date('M j, Y', strtotime($inspection['scheduled_date'])) . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
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
            const modal = document.getElementById('createModal');
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
            
            const assignModal = document.getElementById('assignModal');
            if (event.target == assignModal) {
                assignModal.classList.add('hidden');
            }
        }

        function viewInspector(id) {
            alert('View inspector ' + id);
        }

        function editInspector(id) {
            alert('Edit inspector ' + id);
        }

        function assignInspector(id, name) {
            document.getElementById('assignInspectorId').value = id;
            document.getElementById('assignModalTitle').textContent = 'Assign Inspector: ' + name;
            document.getElementById('assignModal').classList.remove('hidden');
        }
    </script>
</body>
</html>
