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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Checklist Templates</h2>
                <p class="text-sm text-gray-600 mt-1">Manage inspection questions and categories</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterTemplates()" placeholder="Search templates..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <select id="typeFilter" onchange="filterTemplates()" class="border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 py-2 px-4 text-sm">
                    <option value="">All Inspection Types</option>
                    <?php foreach ($allInspectionTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['name']); ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="openTemplateModal('create')" class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center justify-center text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Add Template
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

        <!-- Templates Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Inspection Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Question</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Input Type</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Required</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($allTemplates as $template): ?>
                    <tr class="hover:bg-gray-50 transition-colors" data-template='<?php echo htmlspecialchars(json_encode($template), ENT_QUOTES, 'UTF-8'); ?>'>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-600"><?php echo htmlspecialchars($template['inspection_type_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><span class="px-2 py-1 bg-gray-100 rounded-full text-xs font-medium text-gray-600"><?php echo htmlspecialchars($template['category']); ?></span></td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($template['question']); ?>"><?php echo htmlspecialchars($template['question']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                <?php echo htmlspecialchars($template['input_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php if ($template['required']): ?>
                                <span class="text-green-600" title="Required"><i class="fas fa-check-circle"></i></span>
                            <?php else: ?>
                                <span class="text-gray-300" title="Optional"><i class="fas fa-minus-circle"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="openTemplateModal('edit', this)" class="text-brand-600 hover:text-brand-900 mr-3 transition-colors"><i class="fas fa-edit"></i></button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                <input type="hidden" name="action" value="delete_template">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 transition-colors"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($allTemplates)): ?>
                <div class="p-12 text-center text-gray-500">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-clipboard-list text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No templates found</h3>
                    <p class="mt-1 text-sm">Get started by creating your first checklist template.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Template Modal (Create/Edit) -->
    <div id="templateModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Template</h3>
                <button onclick="closeModal('templateModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="templateForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" id="form_action" value="create_template">
                    <input type="hidden" name="template_id" id="template_id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select name="inspection_type_id" id="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Select an Inspection Type</option>
                            <?php foreach ($allInspectionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <input type="text" name="category" id="category" placeholder="e.g., General Cleanliness" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Question</label>
                        <textarea name="question" id="question" rows="3" placeholder="Enter the checklist question" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Input Type</label>
                            <select name="input_type" id="input_type" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                                <option value="checkbox">Checkbox (Yes/No)</option>
                                <option value="text">Text</option>
                                <option value="select">Select (Dropdown)</option>
                                <option value="number">Number</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Required</label>
                            <div class="mt-2">
                                <input type="checkbox" name="required" id="required" value="1" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 h-5 w-5">
                                <label for="required" class="ml-2 text-sm">Is this question required?</label>
                            </div>
                        </div>
                    </div>
                    <div id="options_container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Options (JSON format)</label>
                        <textarea name="options" id="options" rows="3" placeholder='["Option 1", "Option 2", "Option 3"]' class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm font-mono text-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                        <p class="text-xs text-gray-500 mt-1">For 'select' type, provide a JSON array of strings.</p>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('templateModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                        <button type="submit" id="submitButton" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">Add Template</button>
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

        function filterTemplates() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const typeFilter = document.getElementById('typeFilter').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const type = row.querySelector('td:first-child').innerText.toLowerCase();
                
                const matchesSearch = text.includes(search);
                const matchesType = typeFilter === '' || type === typeFilter;

                if (matchesSearch && matchesType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Close modal on outside click
        window.addEventListener('click', function(event) {
            if (event.target.id === 'templateModal') {
                closeModal('templateModal');
            }
        });
    </script>
</body>
</html>