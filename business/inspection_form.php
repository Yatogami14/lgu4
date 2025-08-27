<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';

require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('inspections');

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($db);
$business = new Business($db);

// Get inspection ID from URL
$inspection_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($inspection_id) {
    $inspection->id = $inspection_id;
    $inspection_data = $inspection->readOne();
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

        if ($inspection->update()) {
            header('Location: inspections.php?success=Inspection completed successfully');
            exit;
        }
    }
}

// Simulate AI analysis (mock function)
function simulateNLPAnalysis($text) {
    // Simulate API call delay
    sleep(1);

    // Mock NLP analysis
    $keywords = ['clean', 'dirty', 'good', 'bad', 'poor', 'excellent', 'violation', 'compliant'];
    $foundKeywords = array_filter($keywords, function($keyword) use ($text) {
        return stripos($text, $keyword) !== false;
    });

    $compliance = "needs_review";
    $confidence = 0.5;
    $suggestions = [];

    if (in_array('violation', $foundKeywords) || in_array('bad', $foundKeywords) || in_array('poor', $foundKeywords)) {
        $compliance = "non_compliant";
        $confidence = 0.85;
        $suggestions = ["Consider immediate corrective action", "Schedule follow-up inspection"];
    } else if (in_array('clean', $foundKeywords) || in_array('good', $foundKeywords) || in_array('excellent', $foundKeywords)) {
        $compliance = "compliant";
        $confidence = 0.9;
        $suggestions = ["Continue current practices", "Maintain high standards"];
    }

    return [
        'compliance' => $compliance,
        'confidence' => $confidence,
        'suggestions' => $suggestions
    ];
}

// Simulate media analysis (mock function)
function simulateMediaAnalysis($filename) {
    // Mock OpenCV analysis
    $hazards = ["Fire Exit Blocked", "Missing PPE", "Overcrowding"];
    $randomHazards = array_filter($hazards, function() { return rand(0, 1) > 0.7; });

    return [
        'hazards' => array_values($randomHazards),
        'confidence' => 0.85,
        'compliance' => count($randomHazards) > 0 ? "fail" : "pass"
    ];
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

$current_checklist = $checklist_templates['health']; // Default to health inspection
$compliance_score = 75; // Default score
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
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1">
                    <i class="fas fa-shield-alt text-blue-600 text-xl sm:text-2xl"></i>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm sm:text-xl font-bold text-gray-900 truncate">LGU Health & Safety</h1>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Digital Inspection Platform</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-1 sm:space-x-4 flex-shrink-0">
                    <a href="index.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
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
                    <button class="bg-white border border-gray-300 px-4 py-2 rounded-md hover:bg-gray-50">
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
                        <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option>Health & Sanitation</option>
                            <option>Fire Safety</option>
                            <option>Building Safety</option>
                            <option>Environmental</option>
                            <option>Food Safety</option>
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
                <?php foreach ($current_checklist as $item): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-sm rounded"><?php echo $item['category']; ?></span>
                                <?php if ($item['required']): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-700 text-xs rounded">Required</span>
                                <?php endif; ?>
                            </div>
                            <p class="font-medium"><?php echo $item['question']; ?></p>
                        </div>
                    </div>

                    <!-- Response Input -->
                    <div class="mb-4">
                        <?php if ($item['type'] === 'checkbox'): ?>
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" name="response_<?php echo $item['id']; ?>" 
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label class="text-sm">Yes, compliant</label>
                            </div>
                        <?php elseif ($item['type'] === 'text'): ?>
                            <textarea name="response_<?php echo $item['id']; ?>" 
                                      placeholder="Enter detailed observations..." 
                                      rows="3" 
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"></textarea>
                        <?php elseif ($item['type'] === 'select' && isset($item['options'])): ?>
                            <select name="response_<?php echo $item['id']; ?>" 
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                <option value="">Select an option</option>
                                <?php foreach ($item['options'] as $option): ?>
                                    <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($item['type'] === 'number'): ?>
                            <input type="number" name="response_<?php echo $item['id']; ?>" 
                                   placeholder="Enter number" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <?php endif; ?>
                    </div>

                    <!-- AI Analysis Results (Simulated) -->
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                        <div class="flex items-center space-x-2 text-blue-600 mb-2">
                            <i class="fas fa-bolt"></i>
                            <span class="font-medium">AI Analysis Preview</span>
                        </div>
                        <div class="text-sm text-blue-800">
                            <p>Compliance: Needs Review</p>
                            <p>Confidence: 75%</p>
                            <p class="mt-2">Suggestions will appear after text analysis</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </form>

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
                        <div class="text-3xl font-bold text-green-600"><?php echo $compliance_score; ?>%</div>
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
                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"></textarea>
                </div>

                <input type="hidden" name="compliance_score" value="<?php echo $compliance_score; ?>">
                <input type="hidden" name="total_violations" value="0">
                <input type="hidden" name="save_inspection" value="1">
            </div>
        </div>
    </div>

    <script>
        // Simulate AI analysis on text input
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('blur', function() {
                if (this.value.length > 10) {
                    const card = this.closest('.bg-white');
                    const analysisSection = card.querySelector('.bg-blue-50');
                    
                    // Simulate AI processing
                    analysisSection.innerHTML = `
                        <div class="flex items-center space-x-2 text-blue-600 mb-2">
                            <i class="fas fa-bolt animate-pulse"></i>
                            <span class="font-medium">AI Analyzing...</span>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        // Mock analysis results
                        analysisSection.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-green-600">AI Analysis: Compliant</span>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">85% confidence</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium">Suggestions:</p>
                                <ul class="text-sm list-disc ml-4">
                                    <li>Continue current practices</li>
                                    <li>Maintain high standards</li>
                                </ul>
                            </div>
                        `;
                    }, 1500);
                }
            });
        });

        // Handle file upload preview
        document.getElementById('mediaUpload').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                alert(files.length + ' file(s) selected for upload. AI analysis will process them.');
            }
        });
    </script>
</body>
</html>
