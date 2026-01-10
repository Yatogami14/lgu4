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

$allTypes = $inspectionType->readAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Type Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Inspection Types</h2>
                <p class="text-sm text-gray-600 mt-1">Manage the different categories of inspections</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterTypes()" placeholder="Search types..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <button onclick="openTypeModal('create')" class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center justify-center text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Add Type
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

        <!-- Types Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($allTypes as $type): ?>
                    <tr class="hover:bg-gray-50 transition-colors" data-type='<?php echo htmlspecialchars(json_encode($type), ENT_QUOTES, 'UTF-8'); ?>'>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-600"><?php echo htmlspecialchars($type['name']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($type['description']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openTypeModal('edit', this)" class="text-brand-600 hover:text-brand-900 mr-3 transition-colors"><i class="fas fa-edit"></i></button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this inspection type?');">
                                <input type="hidden" name="action" value="delete_type">
                                <input type="hidden" name="type_id" value="<?php echo $type['id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($allTypes)): ?>
                <div class="p-12 text-center text-gray-500">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-tags text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No inspection types found</h3>
                    <p class="mt-1 text-sm">Get started by creating your first inspection type.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Type Modal (Create/Edit) -->
    <div id="typeModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Type</h3>
                <button onclick="closeModal('typeModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="typeForm" method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="form_action" value="create_type">
                    <input type="hidden" name="type_id" id="type_id" value="">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" placeholder="e.g., Electrical Safety" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3" placeholder="Describe what this inspection type covers" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('typeModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                        <button type="submit" id="submitButton" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">Add Type</button>
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

        function filterTypes() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                if (text.includes(search)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target.id === 'typeModal') { closeModal('typeModal'); }
        });
    </script>
</body>
</html>