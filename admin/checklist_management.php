<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/ChecklistTemplate.php';
require_once '../models/InspectionType.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
requirePermission('checklist_management');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$checklistTemplate = new ChecklistTemplate($database);
$inspectionType = new InspectionType($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Create
    if (isset($_POST['action']) && $_POST['action'] === 'create_template') {
        $checklistTemplate->inspection_type_id = $_POST['inspection_type_id'];
        $checklistTemplate->category = $_POST['category'];
        $checklistTemplate->question = $_POST['question'];
        $checklistTemplate->required = isset($_POST['required']) ? 1 : 0;
        $checklistTemplate->input_type = $_POST['input_type'];
        $checklistTemplate->options = !empty($_POST['options']) ? $_POST['options'] : null;

        if ($checklistTemplate->create()) {
            $_SESSION['success_message'] = 'Checklist template created successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to create checklist template.';
        }
        header('Location: checklist_management.php');
        exit;
    }

    // Handle Update
    if (isset($_POST['action']) && $_POST['action'] === 'update_template') {
        $checklistTemplate->id = $_POST['template_id'];
        $checklistTemplate->inspection_type_id = $_POST['inspection_type_id'];
        $checklistTemplate->category = $_POST['category'];
        $checklistTemplate->question = $_POST['question'];
        $checklistTemplate->required = isset($_POST['required']) ? 1 : 0;
        $checklistTemplate->input_type = $_POST['input_type'];
        $checklistTemplate->options = !empty($_POST['options']) ? $_POST['options'] : null;

        if ($checklistTemplate->update()) {
            $_SESSION['success_message'] = 'Checklist template updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update checklist template.';
        }
        header('Location: checklist_management.php');
        exit;
    }

    // Handle Delete
    if (isset($_POST['action']) && $_POST['action'] === 'delete_template') {
        $checklistTemplate->id = $_POST['template_id'];
        if ($checklistTemplate->delete()) {
            $_SESSION['success_message'] = 'Checklist template deleted successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to delete checklist template.';
        }
        header('Location: checklist_management.php');
        exit;
    }
}

// Get all checklist templates and inspection types
$allTemplates = $checklistTemplate->readAll();
$allInspectionTypes = $inspectionType->readAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Checklist Template Management</h2>
            <button onclick="openTemplateModal('create')" class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-md hover:bg-yellow-500">
                <i class="fas fa-plus mr-2"></i>Add Template
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

        <!-- Templates Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Input Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($allTemplates as $template): ?>
                    <tr data-template='<?php echo htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8'); ?>'>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['inspection_type_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['category']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars(substr($template['question'], 0, 50)) . '...'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($template['input_type']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $template['required'] ? 'Yes' : 'No'; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openTemplateModal('edit', this)" class="text-green-600 hover:text-green-900 mr-3"><i class="fas fa-edit"></i> Edit</button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                <input type="hidden" name="action" value="delete_template">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Template Modal (Create/Edit) -->
    <div id="templateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add New Template</h3>
                <form id="templateForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" id="form_action" value="create_template">
                    <input type="hidden" name="template_id" id="template_id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select name="inspection_type_id" id="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select an Inspection Type</option>
                            <?php foreach ($allInspectionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <input type="text" name="category" id="category" placeholder="e.g., General Cleanliness" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Question</label>
                        <textarea name="question" id="question" rows="3" placeholder="Enter the checklist question" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Input Type</label>
                            <select name="input_type" id="input_type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="checkbox">Checkbox (Yes/No)</option>
                                <option value="text">Text</option>
                                <option value="select">Select (Dropdown)</option>
                                <option value="number">Number</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Required</label>
                            <div class="mt-2">
                                <input type="checkbox" name="required" id="required" value="1" class="rounded border-gray-300">
                                <label for="required" class="ml-2 text-sm">Is this question required?</label>
                            </div>
                        </div>
                    </div>
                    <div id="options_container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Options (JSON format)</label>
                        <textarea name="options" id="options" rows="3" placeholder='["Option 1", "Option 2", "Option 3"]' class="mt-1 block w-full border-gray-300 rounded-md shadow-sm font-mono text-sm"></textarea>
                        <p class="text-xs text-gray-500 mt-1">For 'select' type, provide a JSON array of strings.</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('templateModal')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                        <button type="submit" id="submitButton" class="px-4 py-2 bg-yellow-400 text-gray-900 rounded-md hover:bg-yellow-500">Add Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function openTemplateModal(mode, button = null) {
            const form = document.getElementById('templateForm');
            form.reset();
            
            const modalTitle = document.getElementById('modalTitle');
            const submitButton = document.getElementById('submitButton');
            const formAction = document.getElementById('form_action');
            const templateIdInput = document.getElementById('template_id');
            const optionsContainer = document.getElementById('options_container');

            if (mode === 'create') {
                modalTitle.textContent = 'Add New Template';
                submitButton.textContent = 'Add Template';
                formAction.value = 'create_template';
                templateIdInput.value = '';
            } else if (mode === 'edit' && button) {
                modalTitle.textContent = 'Edit Template';
                submitButton.textContent = 'Update Template';
                formAction.value = 'update_template';
                
                const templateData = JSON.parse(button.closest('tr').dataset.template);
                
                templateIdInput.value = templateData.id;
                document.getElementById('inspection_type_id').value = templateData.inspection_type_id;
                document.getElementById('category').value = templateData.category;
                document.getElementById('question').value = templateData.question;
                document.getElementById('input_type').value = templateData.input_type;
                document.getElementById('required').checked = !!parseInt(templateData.required);
                
                if (templateData.input_type === 'select') {
                    optionsContainer.classList.remove('hidden');
                    document.getElementById('options').value = templateData.options ? JSON.stringify(JSON.parse(templateData.options), null, 2) : '';
                } else {
                    optionsContainer.classList.add('hidden');
                }
            }
            
            openModal('templateModal');
        }

        document.getElementById('input_type').addEventListener('change', function() {
            const optionsContainer = document.getElementById('options_container');
            if (this.value === 'select') {
                optionsContainer.classList.remove('hidden');
            } else {
                optionsContainer.classList.add('hidden');
            }
        });

        // Close modal on outside click
        window.addEventListener('click', function(event) {
            if (event.target.id === 'templateModal') {
                closeModal('templateModal');
            }
        });
    </script>
</body>
</html>