<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('schedule');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($db_scheduling);
$business = new Business($db_core);

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
            header('Location: schedule.php?success=Inspection scheduled successfully');
            exit;
        }
    }
}

// Get all businesses
$businesses = $business->readAll();

// Get all inspectors for the dropdown
$inspectorUser = new User($db_core);
$all_inspectors = $inspectorUser->readByRole('inspector')->fetchAll(PDO::FETCH_ASSOC);
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
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>New Inspection
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded-md mb-4">
                <?php echo $_GET['success']; ?>
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
                            <input type="datetime-local" name="scheduled_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
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
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('scheduleModal');
                if (event.target == modal) {
                    modal.classList.add('hidden');
                }
            }
        </script>
    </div>
</body>
</html>
