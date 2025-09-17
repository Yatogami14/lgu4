<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Violation.php';
require_once '../models/InspectionMedia.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
requirePermission('inspections');

$database = new Database();
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);
$db_violations = $database->getConnection(Database::DB_VIOLATIONS);
$db_media = $database->getConnection(Database::DB_MEDIA);
$db_core = $database->getConnection(Database::DB_CORE); // For potential user/business lookups

// --- Handle Violation Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_violation') {
    header('Content-Type: application/json');

    // Admins and super_admins can update violations
    if (!in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
        echo json_encode(['success' => false, 'message' => 'Permission Denied.']);
        exit;
    }

    $violation = new Violation($database);
    $violation->id = $_POST['violation_id'];
    $violation->description = $_POST['description'];
    $violation->severity = $_POST['severity'];
    $violation->status = $_POST['status'];
    $violation->due_date = $_POST['due_date'];
    $violation->resolved_date = !empty($_POST['resolved_date']) ? $_POST['resolved_date'] : null;

    if ($violation->update()) {
        echo json_encode(['success' => true, 'message' => 'Violation updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update violation.']);
    }
    exit;
}

// Get inspection ID from URL
$inspection_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($inspection_id <= 0) {
    header('Location: inspections.php?error=Invalid ID');
    exit;
}

// Fetch inspection details
$inspection = new Inspection($database);
$inspection->id = $inspection_id;
$inspection_data = $inspection->readOne();

if (!$inspection_data) {
    header('Location: inspections.php?error=Inspection not found');
    exit;
}

// Fetch associated media
$media_model = new InspectionMedia($database);
$media_files = $media_model->readByInspectionId($inspection_id);

// Fetch associated violations
$violation_model = new Violation($database);
$violations = $violation_model->readByInspectionId($inspection_id);

// Decode AI analysis JSON
$notes_analysis = !empty($inspection_data['notes_ai_analysis']) ? json_decode($inspection_data['notes_ai_analysis'], true) : null;

function getComplianceColor($status, $prefix = 'text') {
    switch ($status) {
        case 'compliant': return "{$prefix}-green-600";
        case 'non_compliant': return "{$prefix}-red-600";
        case 'needs_review': return "{$prefix}-yellow-600";
        case 'error': return "{$prefix}-red-700";
        default: return "{$prefix}-gray-500";
    }
}

function getComplianceBgColor($status) {
    switch ($status) {
        case 'compliant': return 'bg-green-100';
        case 'non_compliant': return 'bg-red-100';
        case 'needs_review': return 'bg-yellow-100';
        case 'error': return 'bg-red-100';
        default: return 'bg-gray-100';
    }
}

function formatComplianceText($status) {
    return ucwords(str_replace('_', ' ', $status));
}

function getSeverityBorderColor($severity) {
    switch (strtolower($severity)) {
        case 'critical': return 'border-red-700';
        case 'high': return 'border-red-500';
        case 'medium': return 'border-yellow-500';
        case 'low': return 'border-blue-500';
        default: return 'border-gray-400';
    }
}

function getSeverityBgColor($severity) {
    switch (strtolower($severity)) {
        case 'critical': return 'bg-red-200';
        case 'high': return 'bg-red-100';
        case 'medium': return 'bg-yellow-100';
        case 'low': return 'bg-blue-100';
        default: return 'bg-gray-100';
    }
}

function getSeverityTextColor($severity) {
    switch (strtolower($severity)) {
        case 'critical': return 'text-red-800';
        case 'high': return 'text-red-700';
        case 'medium': return 'text-yellow-800';
        case 'low': return 'text-blue-800';
        default: return 'text-gray-700';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inspection Report - <?php echo htmlspecialchars($inspection_data['business_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-20">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0 mb-6">
            <div>
                <h2 class="text-2xl font-bold">Inspection Report</h2>
                <p class="text-gray-600">AI-Powered Analysis for <span class="font-medium"><?php echo htmlspecialchars($inspection_data['business_name']); ?></span></p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="inspections.php" class="bg-white border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50 text-sm">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Inspections
                </a>
                <?php if (in_array($_SESSION['user_role'], ['admin', 'super_admin', 'inspector'])): ?>
                <a href="inspection_form.php?id=<?php echo $inspection_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                    <i class="fas fa-edit mr-2"></i>Edit Report
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Inspection Details -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold border-b pb-3 mb-4">Inspection Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-sm">
                <div>
                    <label class="block font-medium text-gray-500">Business</label>
                    <p class="mt-1 text-gray-900 font-semibold"><?php echo htmlspecialchars($inspection_data['business_name']); ?></p>
                </div>
                <div>
                    <label class="block font-medium text-gray-500">Inspection Type</label>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($inspection_data['inspection_type']); ?></p>
                </div>
                <div>
                    <label class="block font-medium text-gray-500">Date Completed</label>
                    <p class="mt-1 text-gray-900"><?php echo date('M j, Y', strtotime($inspection_data['completed_date'] ?? $inspection_data['scheduled_date'])); ?></p>
                </div>
                <div>
                    <label class="block font-medium text-gray-500">Inspector</label>
                    <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($inspection_data['inspector_name']); ?></p>
                </div>
                <div>
                    <label class="block font-medium text-gray-500">Status</label>
                    <p class="mt-1"><span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getComplianceBgColor($inspection_data['status'] == 'completed' ? 'compliant' : 'needs_review'); ?> <?php echo getComplianceColor($inspection_data['status'] == 'completed' ? 'compliant' : 'needs_review'); ?>"><?php echo formatComplianceText($inspection_data['status']); ?></span></p>
                </div>
                <div>
                    <label class="block font-medium text-gray-500">Compliance Score</label>
                    <p class="mt-1 text-gray-900 font-bold text-lg <?php echo getComplianceColor($inspection_data['compliance_score'] >= 80 ? 'compliant' : 'non_compliant'); ?>"><?php echo htmlspecialchars($inspection_data['compliance_score'] ?? 'N/A'); ?>%</p>
                </div>
            </div>
        </div>

        <!-- Inspector Notes & AI Analysis -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold border-b pb-3 mb-4">Inspector Notes & AI Analysis</h3>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Final Observations</label>
                    <div class="bg-gray-50 p-4 rounded-md border min-h-[120px]">
                        <p class="text-gray-800 whitespace-pre-wrap"><?php echo !empty($inspection_data['notes']) ? htmlspecialchars($inspection_data['notes']) : 'No final notes were provided.'; ?></p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">AI Text Analysis</label>
                    <?php if ($notes_analysis): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium <?php echo getComplianceColor($notes_analysis['compliance']); ?>">AI Analysis: <?php echo formatComplianceText($notes_analysis['compliance']); ?></span>
                                <span class="px-2 py-1 <?php echo getComplianceBgColor($notes_analysis['compliance']); ?> <?php echo getComplianceColor($notes_analysis['compliance'], 'text'); ?> text-xs rounded"><?php echo round(($notes_analysis['confidence'] ?? 0) * 100); ?>% confidence</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium">Suggestions:</p>
                                <?php if (!empty($notes_analysis['suggestions'])): ?>
                                    <ul class="text-sm list-disc ml-4">
                                        <?php foreach ($notes_analysis['suggestions'] as $suggestion): ?>
                                            <li><?php echo htmlspecialchars($suggestion); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-sm">No suggestions provided.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-100 p-4 rounded-md border text-center text-sm text-gray-500">
                            <i class="fas fa-comment-slash mr-2"></i>No AI analysis available for notes.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Logged Violations -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold border-b pb-3 mb-4">Logged Violations (<?php echo count($violations); ?>)</h3>
            <?php if (empty($violations)): ?>
                <div class="text-center py-10 text-gray-500">
                    <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                    <p>No violations were logged during this inspection.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($violations as $violation): ?>
                        <div class="border-l-4 <?php echo getSeverityBorderColor($violation['severity']); ?> pl-4 py-2 bg-gray-50 rounded-r-lg">
                            <div class="flex justify-between items-center">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($violation['description']); ?></p>
                                <div class="flex items-center space-x-3 flex-shrink-0 ml-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getSeverityBgColor($violation['severity']); ?> <?php echo getSeverityTextColor($violation['severity']); ?>">
                                        <?php echo ucfirst($violation['severity']); ?>
                                    </span>
                                    <?php if (in_array($_SESSION['user_role'], ['admin', 'super_admin'])): ?>
                                        <button onclick='openEditViolationModal(<?php echo json_encode($violation); ?>)' class="text-blue-600 hover:text-blue-800 text-sm" title="Edit Violation"><i class="fas fa-edit"></i></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">
                                <span>Due Date: <?php echo date('M j, Y', strtotime($violation['due_date'])); ?></span> |
                                <span>Status: <span class="font-medium"><?php echo ucfirst($violation['status']); ?></span></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Attached Media & AI Analysis -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold border-b pb-3 mb-4">Attached Media & Vision Analysis</h3>
            <?php if (empty($media_files)): ?>
                <div class="text-center py-10 text-gray-500">
                    <i class="fas fa-camera-slash text-4xl mb-3"></i>
                    <p>No media files were uploaded for this inspection.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($media_files as $media): 
                        $media_analysis = !empty($media['ai_analysis']) ? json_decode($media['ai_analysis'], true) : null;
                    ?>
                        <div class="border rounded-lg overflow-hidden">
                            <div class="bg-gray-200 flex items-center justify-center h-48">
                                <img src="../uploads/<?php echo htmlspecialchars($media['file_path']); ?>" alt="<?php echo htmlspecialchars($media['filename']); ?>" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 truncate mb-3" title="<?php echo htmlspecialchars($media['filename']); ?>"><?php echo htmlspecialchars($media['filename']); ?></p>
                                <?php if ($media_analysis):
                                    if (isset($media_analysis['compliance']) && $media_analysis['compliance'] === 'error'): ?>
                                        <div class="bg-red-50 border border-red-200 rounded-md p-3 text-xs text-red-700">
                                            <p class="font-bold"><i class="fas fa-exclamation-triangle mr-1"></i> AI Analysis Failed</p>
                                            <?php if (!empty($media_analysis['hazards'])): ?>
                                                <p class="mt-1"><?php echo htmlspecialchars(str_replace('AI Vision API Error: ', '', $media_analysis['hazards'][0])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-medium text-sm <?php echo getComplianceColor($media_analysis['compliance']); ?>">AI: <?php echo formatComplianceText($media_analysis['compliance']); ?></span>
                                            <span class="px-2 py-1 <?php echo getComplianceBgColor($media_analysis['compliance']); ?> <?php echo getComplianceColor($media_analysis['compliance']); ?> text-xs rounded"><?php echo round(($media_analysis['confidence'] ?? 0) * 100); ?>% conf.</span>
                                        </div>
                                        <div class="space-y-2">
                                            <div>
                                                <p class="text-xs font-medium">Positive Observations:</p>
                                                <?php if (!empty($media_analysis['positive_observations'])): ?>
                                                    <ul class="text-xs list-disc ml-4 text-green-700">
                                                        <?php foreach ($media_analysis['positive_observations'] as $obs): ?>
                                                            <li><?php echo htmlspecialchars($obs); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-xs text-gray-600">None noted.</p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium">Detected Hazards:</p>
                                                <?php if (!empty($media_analysis['hazards'])): ?>
                                                    <ul class="text-xs list-disc ml-4 text-red-700">
                                                        <?php foreach ($media_analysis['hazards'] as $hazard): ?>
                                                            <li><?php echo htmlspecialchars($hazard); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <p class="text-xs text-gray-600">None detected.</p>
                                                <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="bg-gray-100 p-3 rounded-md border text-center text-xs text-gray-500">
                                        <i class="fas fa-camera-slash mr-1"></i>No AI analysis available.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Violation Modal -->
    <div id="editViolationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Edit Violation</h3>
                <form id="editViolationForm" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="update_violation">
                    <input type="hidden" name="violation_id" id="edit_violation_id">

                    <div>
                        <label for="edit_violation_description" class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea id="edit_violation_description" name="description" rows="3" required class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_violation_severity" class="block text-sm font-medium text-gray-700">Severity</label>
                            <select id="edit_violation_severity" name="severity" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_violation_status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="edit_violation_status" name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit_violation_due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" id="edit_violation_due_date" name="due_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label for="edit_violation_resolved_date" class="block text-sm font-medium text-gray-700">Resolved Date</label>
                            <input type="date" id="edit_violation_resolved_date" name="resolved_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('editViolationModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update Violation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditViolationModal(violation) {
            document.getElementById('edit_violation_id').value = violation.id;
            document.getElementById('edit_violation_description').value = violation.description;
            document.getElementById('edit_violation_severity').value = violation.severity;
            document.getElementById('edit_violation_status').value = violation.status;
            document.getElementById('edit_violation_due_date').value = violation.due_date;
            document.getElementById('edit_violation_resolved_date').value = violation.resolved_date;
            document.getElementById('editViolationModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        document.getElementById('editViolationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Violation updated successfully!');
                    location.reload();
                } else { throw new Error(data.message || 'Failed to update violation.'); }
            })
            .catch(error => alert('Error: ' + error.message))
            .finally(() => closeModal('editViolationModal'));
        });
    </script>
</body>
</html>