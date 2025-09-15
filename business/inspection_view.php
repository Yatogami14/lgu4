<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Violation.php';
require_once '../models/InspectionMedia.php';
require_once '../models/Business.php';
require_once '../utils/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../main_login.php');
    exit;
}

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);
$db_media = $database->getConnection(Database::DB_MEDIA);
$db_violations = $database->getConnection(Database::DB_VIOLATIONS);


// Get inspection ID from URL
$inspection_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($inspection_id <= 0) {
    header('Location: inspections.php?error=Invalid ID');
    exit;
}

// Fetch inspection details
$inspection = new Inspection($db_scheduling);
$inspection->id = $inspection_id;
$inspection_data = $inspection->readOne();

if (!$inspection_data) {
    header('Location: inspections.php?error=Inspection not found');
    exit;
}

// Security Check: Ensure business owner can only see their own inspections
if ($_SESSION['user_role'] === 'business_owner') {
    $business = new Business($db_core);
    $user_businesses = $business->readByOwnerId($_SESSION['user_id']);
    $owned_business_ids = array_column($user_businesses, 'id');
    if (!in_array($inspection_data['business_id'], $owned_business_ids)) {
        header('Location: index.php?error=Access Denied');
        exit;
    }
}

// Fetch associated media
$media_model = new InspectionMedia($db_media);
$media_files_stmt = $media_model->readByInspectionId($inspection_id);
$media_files = $media_files_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch associated violations
$violation_model = new Violation($db_violations);
$violations_stmt = $violation_model->readByInspectionId($inspection_id);
$violations = $violations_stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
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
                            <div class="flex justify-between items-start">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($violation['description']); ?></p>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getSeverityBgColor($violation['severity']); ?> <?php echo getSeverityTextColor($violation['severity']); ?>">
                                    <?php echo ucfirst($violation['severity']); ?>
                                </span>
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
                                <img src="../uploads/<?php echo htmlspecialchars($media['filename']); ?>" alt="<?php echo htmlspecialchars($media['filename']); ?>" class="max-h-full max-w-full object-contain">
                            </div>
                            <div class="p-4">
                                <p class="text-xs text-gray-500 truncate mb-3" title="<?php echo htmlspecialchars($media['filename']); ?>"><?php echo htmlspecialchars($media['filename']); ?></p>
                                <?php if ($media_analysis): ?>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium text-sm <?php echo getComplianceColor($media_analysis['compliance']); ?>">AI: <?php echo formatComplianceText($media_analysis['compliance']); ?></span>
                                        <span class="px-2 py-1 <?php echo getComplianceBgColor($media_analysis['compliance']); ?> <?php echo getComplianceColor($media_analysis['compliance']); ?> text-xs rounded"><?php echo round(($media_analysis['confidence'] ?? 0) * 100); ?>% conf.</span>
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
                                            <p class="text-xs text-green-700">No hazards detected.</p>
                                        <?php endif; ?>
                                    </div>
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
</body>
</html>