<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/InspectionType.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
requirePermission('inspection_types_management');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspectionType = new InspectionType($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Create
    if (isset($_POST['action']) && $_POST['action'] === 'create_type') {
        $inspectionType->name = $_POST['name'];
        $inspectionType->description = $_POST['description'];

        if ($inspectionType->create()) {
            $_SESSION['success_message'] = 'Inspection type created successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to create inspection type. It might already exist.';
        }
        header('Location: inspection_types_management.php');
        exit;
    }

    // Handle Update
    if (isset($_POST['action']) && $_POST['action'] === 'update_type') {
        $inspectionType->id = $_POST['type_id'];
        $inspectionType->name = $_POST['name'];
        $inspectionType->description = $_POST['description'];

        if ($inspectionType->update()) {
            $_SESSION['success_message'] = 'Inspection type updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update inspection type.';
        }
        header('Location: inspection_types_management.php');
        exit;
    }

    // Handle Delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete_type') {
        $inspectionType->id = $_POST['type_id'];
        if ($inspectionType->delete()) {
            $_SESSION['success_message'] = 'Inspection type deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete inspection type. It might be in use.';
        }
        header('Location: inspection_types_management.php');
        exit;
    }
}

$allTypes = $inspectionType->readAll()->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Type Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Inspection Type Management</h2>
            <button onclick="openTypeModal('create')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add Type
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <!-- Types Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($allTypes as $type): ?>
                    <tr data-type='<?php echo htmlspecialchars(json_encode($type), ENT_QUOTES, 'UTF-8'); ?>'>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($type['name']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($type['description']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openTypeModal('edit', this)" class="text-green-600 hover:text-green-900 mr-3"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this inspection type?');">
                                <input type="hidden" name="action" value="delete_type">
                                <input type="hidden" name="type_id" value="<?php echo $type['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Type Modal (Create/Edit) -->
    <div id="typeModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add New Type</h3>
                <form id="typeForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" id="form_action" value="create_type">
                    <input type="hidden" name="type_id" id="type_id" value="">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" placeholder="e.g., Electrical Safety" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Describe what this inspection type covers" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('typeModal')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" id="submitButton" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) { document.getElementById(modalId).classList.remove('hidden'); }
        function closeModal(modalId) { document.getElementById(modalId).classList.add('hidden'); }

        function openTypeModal(mode, button = null) {
            const form = document.getElementById('typeForm');
            form.reset();
            
            if (mode === 'create') {
                document.getElementById('modalTitle').textContent = 'Add New Inspection Type';
                document.getElementById('submitButton').textContent = 'Add Type';
                document.getElementById('form_action').value = 'create_type';
                document.getElementById('type_id').value = '';
            } else if (mode === 'edit' && button) {
                document.getElementById('modalTitle').textContent = 'Edit Inspection Type';
                document.getElementById('submitButton').textContent = 'Update Type';
                document.getElementById('form_action').value = 'update_type';
                
                const typeData = JSON.parse(button.closest('tr').dataset.type);
                document.getElementById('type_id').value = typeData.id;
                document.getElementById('name').value = typeData.name;
                document.getElementById('description').value = typeData.description;
            }
            openModal('typeModal');
        }

        window.addEventListener('click', function(event) {
            if (event.target.id === 'typeModal') { closeModal('typeModal'); }
        });
    </script>
</body>
</html>