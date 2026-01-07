<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/ChecklistTemplate.php';
require_once '../models/InspectionType.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
// requirePermission('checklist_management'); // Uncomment if access control is implemented

$database = new Database();
$checklist = new ChecklistTemplate($database);
$inspectionType = new InspectionType($database);

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'create') {
                $checklist->inspection_type_id = $_POST['inspection_type_id'];
                $checklist->category = $_POST['category'];
                $checklist->question = $_POST['question'];
                $checklist->required = isset($_POST['required']) ? 1 : 0;
                $checklist->input_type = $_POST['input_type'];
                $checklist->options = !empty($_POST['options']) ? $_POST['options'] : null;

                if ($checklist->create()) {
                    $_SESSION['success_message'] = "Checklist item created successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to create checklist item.";
                }
            } elseif ($_POST['action'] === 'update') {
                $checklist->id = $_POST['id'];
                $checklist->inspection_type_id = $_POST['inspection_type_id'];
                $checklist->category = $_POST['category'];
                $checklist->question = $_POST['question'];
                $checklist->required = isset($_POST['required']) ? 1 : 0;
                $checklist->input_type = $_POST['input_type'];
                $checklist->options = !empty($_POST['options']) ? $_POST['options'] : null;

                if ($checklist->update()) {
                    $_SESSION['success_message'] = "Checklist item updated successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to update checklist item.";
                }
            } elseif ($_POST['action'] === 'delete') {
                $checklist->id = $_POST['id'];
                if ($checklist->delete()) {
                    $_SESSION['success_message'] = "Checklist item deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete checklist item.";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "An error occurred: " . $e->getMessage();
        }
        header("Location: checklist_management.php");
        exit;
    }
}

$templates = $checklist->readAll();
$types = $inspectionType->readAll();
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
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Checklist Templates</h1>
            <button onclick="openModal('create')" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-150">
                <i class="fas fa-plus mr-2"></i> Add Question
            </button>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Question</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($templates as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo htmlspecialchars($item['inspection_type_name'] ?? 'N/A'); ?>
                            <?php if(isset($item['department'])): ?>
                                <br><span class="text-xs text-gray-500"><?php echo htmlspecialchars($item['department']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($item['category']); ?></td>
                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($item['question']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                <?php echo htmlspecialchars($item['input_type']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($item['required']): ?>
                                <span class="text-green-600"><i class="fas fa-check"></i> Yes</span>
                            <?php else: ?>
                                <span class="text-gray-400"><i class="fas fa-times"></i> No</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick='openModal("edit", <?php echo json_encode($item); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                            <form method="POST" class="inline-block" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Add Question</h3>
                <form method="POST" class="mt-4" id="checklistForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="itemId">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="inspection_type_id">Inspection Type</label>
                        <select name="inspection_type_id" id="inspection_type_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="category">Category</label>
                        <input type="text" name="category" id="category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required placeholder="e.g. General Sanitation">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="question">Question</label>
                        <textarea name="question" id="question" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required rows="3"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="input_type">Input Type</label>
                            <select name="input_type" id="input_type" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="toggleOptions()">
                                <option value="checkbox">Checkbox (Pass/Fail)</option>
                                <option value="text">Text Input</option>
                                <option value="number">Number Input</option>
                                <option value="select">Dropdown Select</option>
                            </select>
                        </div>
                        <div class="flex items-center mt-6">
                            <input type="checkbox" name="required" id="required" class="form-checkbox h-5 w-5 text-blue-600" checked>
                            <span class="ml-2 text-gray-700">Required</span>
                        </div>
                    </div>

                    <div class="mb-4 hidden" id="optionsContainer">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="options">Options (JSON format)</label>
                        <input type="text" name="options" id="options" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder='["Option 1", "Option 2"]'>
                        <p class="text-xs text-gray-500 mt-1">For dropdowns, enter options as a JSON array.</p>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('modal');
            const form = document.getElementById('checklistForm');
            const title = document.getElementById('modalTitle');
            const action = document.getElementById('formAction');
            
            modal.classList.remove('hidden');
            
            if (mode === 'edit' && data) {
                title.textContent = 'Edit Question';
                action.value = 'update';
                document.getElementById('itemId').value = data.id;
                document.getElementById('inspection_type_id').value = data.inspection_type_id;
                document.getElementById('category').value = data.category;
                document.getElementById('question').value = data.question;
                document.getElementById('input_type').value = data.input_type;
                document.getElementById('required').checked = data.required == 1;
                
                // Handle options
                let optionsVal = data.options;
                if (typeof optionsVal === 'object' && optionsVal !== null) {
                    optionsVal = JSON.stringify(optionsVal);
                }
                document.getElementById('options').value = optionsVal || '';
            } else {
                title.textContent = 'Add Question';
                action.value = 'create';
                form.reset();
                document.getElementById('itemId').value = '';
            }
            toggleOptions();
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        function toggleOptions() {
            const type = document.getElementById('input_type').value;
            const container = document.getElementById('optionsContainer');
            if (type === 'select') {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('modal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>