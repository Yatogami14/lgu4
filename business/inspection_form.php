<?php
require_once '../utils/session_manager.php';

require_once '../config/ai_config.php';
require_once '../utils/ai_analyzer.php';
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../models/InspectionMedia.php';
require_once '../models/Violation.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../models/ChecklistTemplate.php';
require_once '../models/InspectionType.php';
require_once '../utils/access_control.php';

$database = new Database();

// --- AI Analysis Endpoint ---
// This block handles AJAX requests for text analysis.
if (isset($_POST['action']) && $_POST['action'] === 'analyze_text') {
    header('Content-Type: application/json');
    $analyzer = new GeminiAnalyzer(GEMINI_API_KEY);
    $result = $analyzer->analyzeText($_POST['text'] ?? '');
    echo json_encode($result);
    exit;
}
// --- End AI Analysis Endpoint ---

// --- AI Media Analysis Endpoint ---
if (isset($_POST['action']) && $_POST['action'] === 'analyze_media') {
    header('Content-Type: application/json');

    if (!isset($_FILES['media_file']) || !isset($_POST['inspection_id'])) {
        echo json_encode(['error' => 'No file or inspection ID uploaded.']);
        exit;
    }

    $file = $_FILES['media_file'];
    $inspection_id = $_POST['inspection_id'];
    $uploadSubDir = 'inspections/' . $inspection_id . '/';
    $uploadDir = '../uploads/' . $uploadSubDir;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = uniqid() . '-' . basename($file['name']);
    $filePath = $uploadDir . $fileName;
    $dbPath = $uploadSubDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['error' => 'Failed to save uploaded file.']);
        exit;
    }

    $media = new InspectionMedia($database);
    $media->inspection_id = $inspection_id;
    $media->file_path = $dbPath;
    $media->filename = $fileName;
    $media->file_size = $file['size'];
    $media->uploaded_by = $_SESSION['user_id'];

    // Map MIME type to ENUM('image', 'video')
    $mime_type = $file['type'];
    $media->file_type = (strpos($mime_type, 'image') !== false) ? 'image' : ((strpos($mime_type, 'video') !== false) ? 'video' : 'image');
    
    if (!$media->create()) {
        unlink($filePath);
        echo json_encode(['error' => 'Failed to create media record in database.']);
        exit;
    }

    $analyzer = new GeminiAnalyzer(GEMINI_API_KEY);
    $result = $analyzer->analyzeMedia($filePath);

    // Always save the result, whether it's a successful analysis or an error report from the analyzer.
    $media->ai_analysis = json_encode($result);
    $media->updateAiAnalysis();

    echo json_encode($result);
    exit;
}
// --- End AI Media Analysis Endpoint ---

// --- Create Violation Endpoint ---
if (isset($_POST['action']) && $_POST['action'] === 'create_violation') {
    header('Content-Type: application/json');

    if (empty($_POST['inspection_id'])) {
        echo json_encode(['success' => false, 'message' => 'Cannot create violation for an unsaved inspection. Please save a draft first.']);
        exit;
    }
    
    $violation = new Violation($database);
    $violation->inspection_id = $_POST['inspection_id'];
    $violation->business_id = $_POST['business_id'];
    $violation->description = $_POST['description'];
    $violation->severity = $_POST['severity'];
    $violation->due_date = $_POST['due_date'];
    $violation->status = 'open'; // Default status

    if ($violation->create()) {
        echo json_encode(['success' => true, 'message' => 'Violation created successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create violation.']);
    }
    exit;
}
// --- End Create Violation Endpoint ---

// --- Save Draft Endpoint ---
if (isset($_POST['action']) && $_POST['action'] === 'save_draft') {
    header('Content-Type: application/json');

    if (empty($_POST['inspection_id'])) {
        echo json_encode(['success' => false, 'message' => 'Cannot save draft for a new inspection. This should not happen.']);
        exit;
    }
    
    $inspection = new Inspection($database);
    $inspection->id = $_POST['inspection_id'];
    
    $draft_responses = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'response_') === 0) {
            $draft_responses[$key] = $value;
        }
    }
    $draft_data_json = json_encode($draft_responses);
    $notes = $_POST['inspection_notes'] ?? '';

    if ($inspection->saveDraft($draft_data_json, $notes)) {
        echo json_encode(['success' => true, 'message' => 'Draft saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save draft.']);
    }
    exit;
}
// --- End Save Draft Endpoint ---

// Check if user is logged in and has permission to access this page
requirePermission('inspections');

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($database);
$business = new Business($database);
$checklistTemplateModel = new ChecklistTemplate($database);
$inspectionTypeModel = new InspectionType($database);

// Get inspection ID from URL
$inspection_data = []; // Initialize
$inspection_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($inspection_id) {
    $inspection->id = $inspection_id;
    $inspection_data = $inspection->readOne();
    if (!$inspection_data) {
        header('Location: inspections.php?error=not_found');
        exit;
    }
}

// Get all inspection types for the dropdown
$allInspectionTypes = $inspectionTypeModel->readAll()->fetchAll(PDO::FETCH_ASSOC);

// Get the checklist for the current inspection
$current_checklist = [];
if ($inspection_id) {
    $current_checklist = $checklistTemplateModel->readByInspectionType($inspection_data['inspection_type_id']);
}
// Load draft data if it exists
$draft_responses = [];
if (!empty($inspection_data['draft_data'])) {
    $draft_responses = json_decode($inspection_data['draft_data'], true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_inspection'])) {
        // Handle inspection form submission
        $inspection->id = $inspection_id;
        $inspection->compliance_score = $_POST['compliance_score'];
        $inspection->total_violations = $_POST['total_violations'];
        $inspection->notes = $_POST['inspection_notes'];
        $inspection->status = 'completed';
        $inspection->completed_date = date('Y-m-d H:i:s');

        if (!empty($inspection->notes)) {
            $analyzer = new GeminiAnalyzer(GEMINI_API_KEY);
            $notes_analysis = $analyzer->analyzeText($inspection->notes);
            $inspection->notes_ai_analysis = json_encode($notes_analysis);
        } else {
            $inspection->notes_ai_analysis = null;
        }

        if ($inspection->complete()) {
            header('Location: inspections.php?success=Inspection completed successfully');
            exit;
        }
    }
}

// Checklist templates
$checklist_templates = [
    'health' => [
        [
            'id' => '1',
            'category' => 'General Cleanliness',
            'question' => 'Are the premises generally clean and well-maintained?',
            'required' => true,
            'type' => 'checkbox'
        ],
        [
            'id' => '2',
            'category' => 'Waste Management',
            'question' => 'Describe the waste disposal system and its condition',
            'required' => true,
            'type' => 'text'
        ],
        [
            'id' => '3',
            'category' => 'Water Supply',
            'question' => 'Rate the water supply quality',
            'required' => true,
            'type' => 'select',
            'options' => ['Excellent', 'Good', 'Fair', 'Poor', 'Not Available']
        ],
        [
            'id' => '4',
            'category' => 'Pest Control',
            'question' => 'Evidence of pest control measures?',
            'required' => true,
            'type' => 'checkbox'
        ],
        [
            'id' => '5',
            'category' => 'Food Storage',
            'question' => 'Number of food storage violations observed',
            'required' => false,
            'type' => 'number'
        ],
        [
            'id' => '6',
            'category' => 'Employee Hygiene',
            'question' => 'Describe employee hygiene practices and compliance',
            'required' => true,
            'type' => 'text'
        ]
    ],
    'fire' => [
        [
            'id' => '1',
            'category' => 'Fire Exits',
            'question' => 'Are all fire exits clearly marked and unobstructed?',
            'required' => true,
            'type' => 'checkbox'
        ],
        [
            'id' => '2',
            'category' => 'Fire Extinguishers',
            'question' => 'Number of functional fire extinguishers present',
            'required' => true,
            'type' => 'number'
        ],
        [
            'id' => '3',
            'category' => 'Smoke Detectors',
            'question' => 'Condition of smoke detection systems',
            'required' => true,
            'type' => 'select',
            'options' => ['Fully Functional', 'Partially Working', 'Not Working', 'Not Present']
        ],
        [
            'id' => '4',
            'category' => 'Emergency Lighting',
            'question' => 'Are emergency lights operational?',
            'required' => true,
            'type' => 'checkbox'
        ],
        [
            'id' => '5',
            'category' => 'Fire Safety Plan',
            'question' => 'Describe the fire safety plan and evacuation procedures',
            'required' => true,
            'type' => 'text'
        ]
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Form - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center space-y-4 lg:space-y-0 mb-6">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold">Digital Inspection Form</h2>
                <p class="text-gray-600 text-sm sm:text-base">AI-enhanced compliance evaluation and media analysis</p>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                <div class="text-left sm:text-right">
                    <p class="text-sm text-gray-600">Compliance Score</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600"><?php echo $compliance_score; ?>%</p>
                </div>
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
                    <button id="saveDraftBtn" type="button" class="bg-white border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50">
                        <i class="fas fa-save mr-2"></i>Save Draft
                    </button>
                    <button type="submit" form="inspectionForm" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Inspection Details -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Inspection Details</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select name="inspection_type_id" id="inspection_type_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" disabled>
                            <?php foreach ($allInspectionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo (isset($inspection_data['inspection_type_id']) && $inspection_data['inspection_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business Name</label>
                        <input type="text" value="<?php echo $inspection_data['business_name'] ?? 'ABC Restaurant'; ?>" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date & Time</label>
                        <input type="datetime-local" value="<?php echo date('Y-m-d\TH:i'); ?>" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Indicator -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">Inspection Progress</span>
                    <span class="text-sm text-gray-600">3 of 6 completed</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 50%"></div>
                </div>
            </div>
        </div>

        <!-- Checklist Content -->
        <form id="inspectionForm" method="POST">
            <div class="space-y-4">
                <?php if (!empty($current_checklist)): ?>
                    <?php foreach ($current_checklist as $category => $items): ?>
                        <div class="bg-white rounded-lg shadow">
                            <div class="px-6 py-4 border-b">
                                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($category); ?></h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <?php foreach ($items as $item): 
                                    $response_name = 'response_' . $item['id'];
                                    $saved_value = $draft_responses[$response_name] ?? '';
                                ?>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 checklist-item">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <?php if ($item['required']): ?>
                                                <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded float-right ml-4">Required</span>
                                            <?php endif; ?>
                                            <p class="font-medium"><?php echo htmlspecialchars($item['question']); ?></p>
                                        </div>
                                    </div>

                                    <!-- Response Input -->
                                    <div class="mb-4">
                                        <?php if ($item['input_type'] === 'checkbox'): ?>
                                            <div class="flex items-center space-x-2">
                                                <input type="checkbox" name="<?php echo $response_name; ?>" value="1" <?php echo !empty($saved_value) ? 'checked' : ''; ?>
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <label class="text-sm">Yes, compliant</label>
                                            </div>
                                        <?php elseif ($item['input_type'] === 'text'): ?>
                                            <textarea name="<?php echo $response_name; ?>" 
                                                      placeholder="Enter detailed observations..." 
                                                      rows="3" 
                                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"><?php echo htmlspecialchars($saved_value); ?></textarea>
                                        <?php elseif ($item['input_type'] === 'select' && isset($item['options'])): ?>
                                            <select name="<?php echo $response_name; ?>" 
                                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                                <option value="">Select an option</option>
                                                <?php foreach ($item['options'] as $option): ?>
                                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($saved_value == $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ($item['input_type'] === 'number'): ?>
                                            <input type="number" name="<?php echo $response_name; ?>" value="<?php echo htmlspecialchars($saved_value); ?>"
                                                   placeholder="Enter number" 
                                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                        <?php endif; ?>
                                    </div>

                                    <!-- AI Analysis & Violation Reporting -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mt-4">
                                        <div class="flex items-center space-x-2 text-blue-600 mb-2">
                                            <i class="fas fa-bolt"></i>
                                            <span class="font-medium">AI Analysis Preview</span>
                                        </div>
                                        <div class="text-sm text-blue-800">
                                            <p class="mt-2">Suggestions will appear after text analysis</p>
                                        </div>
                                        <div class="mt-4 pt-4 border-t border-blue-200 flex justify-end">
                                            <button type="button" onclick="openViolationModal('<?php echo $item['id']; ?>', '<?php echo htmlspecialchars(addslashes($item['question'])); ?>')" id="violation-btn-<?php echo $item['id']; ?>" class="bg-red-100 text-red-700 px-3 py-1 rounded-md text-sm hover:bg-red-200 transition-colors">
                                                <i class="fas fa-flag mr-2"></i>Report Violation
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                        <i class="fas fa-list-alt text-4xl mb-3"></i>
                        <p>No checklist found for this inspection type.</p>
                        <p class="text-sm mt-2">An administrator needs to configure a checklist for "<?php echo htmlspecialchars($inspection_data['inspection_type'] ?? 'Unknown'); ?>".</p>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Media Upload Section -->
        <div class="bg-white rounded-lg shadow mt-6">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold flex items-center space-x-2">
                    <i class="fas fa-camera"></i>
                    <span>Photo & Video Upload</span>
                </h3>
                <p class="text-sm text-gray-600">Upload inspection photos and videos for AI-powered hazard detection</p>
            </div>
            <div class="p-6">
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <input type="file" id="mediaUpload" multiple accept="image/*,video/*" class="hidden">
                    <label for="mediaUpload" class="cursor-pointer flex flex-col items-center space-y-4">
                        <i class="fas fa-upload text-4xl text-gray-400"></i>
                        <div>
                            <p class="text-lg font-medium">Upload Photos & Videos</p>
                            <p class="text-gray-600">Click to browse or drag and drop files</p>
                            <p class="text-sm text-gray-500">Supports JPG, PNG, MP4, MOV files</p>
                        </div>
                    </label>
                </div>
                <!-- Media Analysis Results Container -->
                <div id="mediaAnalysisResults" class="mt-6 space-y-4"></div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="bg-white rounded-lg shadow mt-6">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-bold">Inspection Summary</h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div id="summaryComplianceScore" class="text-3xl font-bold text-green-600">100%</div>
                        <p class="text-sm text-gray-600">Overall Compliance</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">3</div>
                        <p class="text-sm text-gray-600">Items Completed</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600">0</div>
                        <p class="text-sm text-gray-600">Media Files</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Inspector Notes</label>
                    <textarea name="inspection_notes" rows="4" 
                              placeholder="Add final inspection notes and recommendations..." 
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"><?php echo htmlspecialchars($inspection_data['notes'] ?? ''); ?></textarea>
                </div>

                <input type="hidden" name="compliance_score" id="compliance_score_hidden" value="100">
                <input type="hidden" name="total_violations" value="0">
                <input type="hidden" name="save_inspection" value="1">
            </div>
        </div>
    </form>
    </div>

    <!-- Create Violation Modal -->
    <div id="violationModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Report a New Violation</h3>
                <form id="violationForm" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="create_violation">
                    <input type="hidden" name="inspection_id" value="<?php echo $inspection_id ?? ''; ?>">
                    <input type="hidden" name="business_id" value="<?php echo $inspection_data['business_id'] ?? ''; ?>">
                    <input type="hidden" name="checklist_item_id" id="modal_checklist_item_id">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Checklist Item</label>
                        <p id="modal_checklist_question" class="mt-1 text-sm text-gray-600 bg-gray-100 p-2 rounded-md"></p>
                    </div>

                    <div>
                        <label for="violation_description" class="block text-sm font-medium text-gray-700">Violation Description</label>
                        <textarea id="violation_description" name="description" rows="3" placeholder="Describe the specific violation observed..." required class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="violation_severity" class="block text-sm font-medium text-gray-700">Severity</label>
                            <select id="violation_severity" name="severity" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div>
                            <label for="violation_due_date" class="block text-sm font-medium text-gray-700">Due Date for Correction</label>
                            <input type="date" id="violation_due_date" name="due_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('violationModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Create Violation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-5 z-50 px-4 py-3 rounded-md shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} mr-2"></i> ${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.transition = 'opacity 0.5s ease';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }

        async function handleFetch(url, options) {
            const response = await fetch(url, options);
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                throw new Error("Server returned an invalid response: " + text.substring(0, 200));
            }
        }
        // AI analysis on text input using Gemini
        document.querySelectorAll('.checklist-item textarea').forEach(textarea => {
            textarea.addEventListener('blur', function() {
                if (this.value.length > 10) {
                    const card = this.closest('.checklist-item');
                    const analysisSection = card.querySelector('.bg-blue-50');
                    const textToAnalyze = this.value;
                    
                    // Show processing state
                    analysisSection.innerHTML = `
                        <div class="flex items-center space-x-2 text-blue-600 mb-2">
                            <i class="fas fa-bolt animate-pulse"></i>
                            <span class="font-medium">AI Analyzing...</span>
                        </div>
                    `;

                    const formData = new FormData();
                    formData.append('action', 'analyze_text');
                    formData.append('text', textToAnalyze);

                    fetch(window.location.href, { // Post to the same page
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) { throw new Error(data.error); }

                        let suggestionsList = '';
                        if (data.suggestions && data.suggestions.length > 0) {
                            suggestionsList = '<ul class="text-sm list-disc ml-4">' + data.suggestions.map(s => `<li>${s}</li>`).join('') + '</ul>';
                        } else {
                            suggestionsList = '<p class="text-sm">No suggestions provided.</p>';
                        }

                        let complianceColor = 'text-yellow-600';
                        let complianceBg = 'bg-yellow-100';
                        let complianceText = 'Needs Review';
                        if (data.compliance === 'compliant') {
                            complianceColor = 'text-green-600';
                            complianceBg = 'bg-green-100';
                            complianceText = 'Compliant';
                        } else if (data.compliance === 'non_compliant') {
                            complianceColor = 'text-red-600';
                            complianceBg = 'bg-red-100';
                            complianceText = 'Non-Compliant';
                        } else if (data.compliance === 'error') {
                            complianceColor = 'text-red-600';
                            complianceBg = 'bg-red-100';
                            complianceText = 'Analysis Error';
                        }
                        
                        analysisSection.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium ${complianceColor}">AI Analysis: ${complianceText}</span>
                                <span class="px-2 py-1 ${complianceBg} ${complianceColor.replace('600', '800')} text-xs rounded">${Math.round(data.confidence * 100)}% confidence</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium">Suggestions:</p>
                                ${suggestionsList}
                            </div>
                        `;
                    })
                    .catch(error => {
                        console.error('Error during AI analysis:', error);
                        analysisSection.innerHTML = `
                            <div class="flex items-center space-x-2 text-red-600 mb-2"><i class="fas fa-exclamation-triangle"></i><span class="font-medium">Analysis Failed</span></div>
                            <p class="text-sm text-red-800">${error.message}</p>
                        `;
                    });
                }
            });
        });

        // Handle file upload and AI analysis
        document.getElementById('mediaUpload').addEventListener('change', function(e) {
            const files = e.target.files;
            const resultsContainer = document.getElementById('mediaAnalysisResults');

            if (files.length === 0) return;

            for (const file of files) {
                // Create a preview card for the file being analyzed
                const fileId = 'file-' + Math.random().toString(36).substr(2, 9);
                const previewCard = document.createElement('div');
                previewCard.id = fileId;
                previewCard.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4 flex items-start space-x-4';
                previewCard.innerHTML = `
                    <div>
                        <img src="${URL.createObjectURL(file)}" alt="Preview" class="w-24 h-24 object-cover rounded-md">
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm">${file.name}</p>
                        <div class="mt-2" data-analysis-content>
                            <div class="flex items-center space-x-2 text-blue-600">
                                <i class="fas fa-camera-retro animate-pulse"></i>
                                <span class="font-medium text-sm">AI Analyzing Media...</span>
                            </div>
                        </div>
                    </div>
                `;
                resultsContainer.appendChild(previewCard);

                // Prepare form data for upload
                const formData = new FormData();
                formData.append('action', 'analyze_media');
                formData.append('media_file', file);
                formData.append('inspection_id', '<?php echo $inspection_id; ?>');

                // Send to the server for analysis
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const analysisContent = document.querySelector(`#${fileId} [data-analysis-content]`);
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    let hazardsList = '<p class="text-sm text-gray-600">No specific hazards detected.</p>';
                    if (data.hazards && data.hazards.length > 0) {
                        hazardsList = '<ul class="text-sm list-disc ml-4 text-red-700">' + data.hazards.map(h => `<li>${h}</li>`).join('') + '</ul>';
                    }

                    let positiveList = '<p class="text-sm text-gray-600">No specific positive observations noted.</p>';
                    if (data.positive_observations && data.positive_observations.length > 0) {
                        positiveList = '<ul class="text-sm list-disc ml-4 text-green-700">' + data.positive_observations.map(p => `<li>${p}</li>`).join('') + '</ul>';
                    }

                    let complianceColor = 'text-yellow-600';
                    let complianceBg = 'bg-yellow-100';
                    let complianceText = 'Needs Review';
                    if (data.compliance === 'compliant') {
                        complianceColor = 'text-green-600';
                        complianceBg = 'bg-green-100';
                        complianceText = 'Compliant';
                    } else if (data.compliance === 'non_compliant') {
                        complianceColor = 'text-red-600';
                        complianceBg = 'bg-red-100';
                        complianceText = 'Non-Compliant';
                    }

                    analysisContent.innerHTML = `
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium ${complianceColor}">AI Analysis: ${complianceText}</span>
                            <span class="px-2 py-1 ${complianceBg} ${complianceColor.replace('600', '800')} text-xs rounded">${Math.round(data.confidence * 100)}% confidence</span>
                        </div>
                        <div class="mt-2">
                            <p class="text-sm font-medium">Positive Observations:</p>
                            ${positiveList}
                        </div>
                        <div class="mt-2">
                            <p class="text-sm font-medium">Detected Hazards:</p>
                            ${hazardsList}
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Error during media analysis:', error);
                    const analysisContent = document.querySelector(`#${fileId} [data-analysis-content]`);
                    analysisContent.innerHTML = `
                        <div class="flex items-center space-x-2 text-red-600"><i class="fas fa-exclamation-triangle"></i><span class="font-medium text-sm">Analysis Failed</span></div>
                        <p class="text-xs text-red-700 mt-1">${error.message}</p>
                    `;
                });
            }
        });

        // Violation Modal Logic
        function openViolationModal(itemId, itemQuestion) {
            document.getElementById('modal_checklist_item_id').value = itemId;
            document.getElementById('modal_checklist_question').textContent = itemQuestion;
            document.getElementById('violation_description').value = `Violation related to: ${itemQuestion}`;
            document.getElementById('violationModal').classList.remove('hidden');
        }

        function closeModal(modalId) { // Make sure this is defined globally if not already
            document.getElementById(modalId).classList.add('hidden');
        }

        document.getElementById('violationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            try {
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                submitButton.disabled = true;
                const data = await handleFetch('', { method: 'POST', body: formData });

                if (data.success) {
                    showNotification('Violation created successfully!');
                    closeModal('violationModal');
                    const checklistItemId = document.getElementById('modal_checklist_item_id').value;
                    const violationBtn = document.getElementById('violation-btn-' + checklistItemId);
                    if (violationBtn) {
                        violationBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Violation Logged';
                        violationBtn.disabled = true;
                        violationBtn.classList.remove('bg-red-100', 'text-red-700', 'hover:bg-red-200');
                        violationBtn.classList.add('bg-green-100', 'text-green-700', 'cursor-not-allowed');
                    }
                } else {
                    throw new Error(data.message || 'Failed to create violation.');
                }
            } catch (error) {
                showNotification(error.message, 'error');
            } finally {
                submitButton.innerHTML = originalButtonText;
                submitButton.disabled = false;
            }
        });

        // Save Draft Logic
        document.getElementById('saveDraftBtn').addEventListener('click', async function(e) {
            e.preventDefault();
            const btn = this;
            const originalHtml = btn.innerHTML;

            const form = document.getElementById('inspectionForm');
            const formData = new FormData(form);
            formData.append('action', 'save_draft');
            formData.append('inspection_id', '<?php echo $inspection_id; ?>');

            try {
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                btn.disabled = true;
                const data = await handleFetch('', { method: 'POST', body: formData });

                if (data.success) {
                    showNotification('Draft saved successfully!');
                } else {
                    throw new Error(data.message || 'Failed to save draft.');
                }
            } catch (error) {
                showNotification(error.message, 'error');
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
