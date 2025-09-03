<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../utils/logger.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in and is a business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'business_owner') {
    logError("Unauthorized access attempt to index.php");
    header('Location: public_login.php');
    exit;
}

// Get current user
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get dashboard data
$inspection = new Inspection($db);
$business = new Business($db);
$notification = new Notification($db);

// Get businesses owned by current user (should be only one or none)
$userBusinesses = $business->readByOwnerId($_SESSION['user_id']);
$userBusiness = !empty($userBusinesses) ? $userBusinesses[0] : null;

// Get violations for user's business (using mock data like violations.php)
$violations = [];
$complianceStats = null;
$recentInspections = [];
$upcomingInspections = [];

// Mock violations data - only show violations that result from actual inspections
// In a real system, these would be created by inspectors during/after inspections
$allViolations = [
    // Only include violations for businesses that have had inspections
    // For now, we'll show fewer violations to simulate realistic scenario
    [
        'id' => 1,
        'business_id' => 1,
        'inspection_id' => 1, // Links to an actual inspection
        'business_name' => 'ABC Restaurant',
        'description' => 'Fire exit blocked by storage boxes - found during routine inspection',
        'severity' => 'high',
        'status' => 'open',
        'due_date' => '2024-01-25',
        'created_at' => '2024-01-15 10:30:00'
    ],
    [
        'id' => 2,
        'business_id' => 2,
        'inspection_id' => 2, // Links to an actual inspection
        'business_name' => 'XYZ Mall',
        'description' => 'Missing fire extinguishers in food court - identified during safety audit',
        'severity' => 'medium',
        'status' => 'in_progress',
        'due_date' => '2024-01-28',
        'created_at' => '2024-01-16 14:20:00'
    ]
    // Note: No violations for business ID 5 (Downtown Cafe) since no inspection has occurred yet
    // This simulates the realistic scenario where violations only exist after inspections
];

if ($userBusiness) {
    // Filter violations to only show those belonging to current user's business
    $violations = array_filter($allViolations, function($violation) use ($userBusiness) {
        return $violation['business_id'] == $userBusiness['id'];
    });

    // Get compliance stats
    $complianceStats = $business->getComplianceStats($userBusiness['id']);

    // Get recent inspections for this business
    $recentInspections = $business->getRecentInspections($userBusiness['id'], 5);

    // Get upcoming inspections
    foreach ($recentInspections as $insp) {
        if ($insp['status'] == 'scheduled' && strtotime($insp['scheduled_date']) > time()) {
            $upcomingInspections[] = $insp;
        }
    }
}

// Get recent notifications
$recentNotifications = $notification->readByUser($_SESSION['user_id'], 5);

// Handle tab navigation
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

        <!-- Dashboard Content -->
        <?php if ($activeTab == 'dashboard'): ?>
        <div class="space-y-6">
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-lg shadow-lg p-6 text-white">
                <h1 class="text-2xl font-bold mb-2">Welcome back, <?php echo $user->name; ?>!</h1>
                <p class="text-blue-100">Here's an overview of your business compliance and inspection status.</p>
            </div>

            <?php if ($userBusiness): ?>
            <!-- Your Business Overview -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h3 class="text-lg font-bold">Your Business</h3>
                    <p class="text-gray-600 text-sm">Current status and compliance information</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Business Info -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-building text-2xl text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold"><?php echo $userBusiness['name']; ?></h4>
                                    <p class="text-gray-600"><?php echo $userBusiness['business_type']; ?></p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-map-marker-alt text-gray-400 w-4"></i>
                                    <span class="text-sm text-gray-600"><?php echo $userBusiness['address']; ?></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-calendar-alt text-gray-400 w-4"></i>
                                    <span class="text-sm text-gray-600">Next Inspection: <?php echo $userBusiness['next_inspection_date'] ? date('M j, Y', strtotime($userBusiness['next_inspection_date'])) : 'Not scheduled'; ?></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-clock text-gray-400 w-4"></i>
                                    <span class="text-sm text-gray-600">Frequency: <?php echo ucfirst($userBusiness['inspection_frequency']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Compliance Status -->
                        <div class="space-y-4">
                            <div class="text-center">
                                <div class="inline-flex items-center space-x-2 px-4 py-2 rounded-full <?php echo $userBusiness['is_compliant'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <i class="fas <?php echo $userBusiness['is_compliant'] ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                    <span class="font-medium"><?php echo $userBusiness['is_compliant'] ? 'Compliant' : 'Non-Compliant'; ?></span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>Compliance Score</span>
                                        <span class="font-medium"><?php echo $userBusiness['compliance_score']; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 h-2 rounded-full transition-all duration-300"
                                             style="width: <?php echo $userBusiness['compliance_score']; ?>%"></div>
                                    </div>
                                </div>

                                <?php if ($complianceStats): ?>
                                <div class="grid grid-cols-2 gap-4 text-center">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="text-2xl font-bold text-blue-600"><?php echo $complianceStats['total_inspections']; ?></div>
                                        <div class="text-xs text-gray-600">Total Inspections</div>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="text-2xl font-bold text-red-600"><?php echo $complianceStats['total_violations']; ?></div>
                                        <div class="text-xs text-gray-600">Active Violations</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Violations Section -->
            <?php if (!empty($violations)): ?>
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b bg-red-50">
                    <h3 class="text-lg font-bold text-red-800">Active Violations</h3>
                    <p class="text-red-600 text-sm">Issues that need your attention</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($violations as $violation): ?>
                        <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-red-800"><?php echo $violation['description']; ?></h4>
                                    <p class="text-sm text-red-600 mt-1"><?php echo $violation['severity']; ?> Priority</p>
                                    <div class="flex items-center space-x-4 mt-2 text-xs text-gray-600">
                                        <span>Status: <span class="font-medium"><?php echo ucfirst(str_replace('_', ' ', $violation['status'])); ?></span></span>
                                        <span>Due: <span class="font-medium"><?php echo $violation['due_date'] ? date('M j, Y', strtotime($violation['due_date'])) : 'N/A'; ?></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- No Violations - Good News -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b bg-green-50">
                    <h3 class="text-lg font-bold text-green-800">No Active Violations</h3>
                    <p class="text-green-600 text-sm">Great job! Your business is in good standing</p>
                </div>
                <div class="p-6">
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-3xl text-green-600"></i>
                        </div>
                        <h4 class="text-lg font-medium text-green-800 mb-2">All Clear!</h4>
                        <p class="text-gray-600">Your business has no outstanding violations. Keep up the good work!</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Inspections -->
            <?php if (!empty($recentInspections)): ?>
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Recent Inspections</h3>
                    <p class="text-gray-600 text-sm">Your latest inspection history</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recentInspections as $inspection): ?>
                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php
                                    echo $inspection['status'] == 'completed' ? 'bg-green-100 text-green-600' :
                                         ($inspection['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600');
                                ?>">
                                    <i class="fas <?php
                                        echo $inspection['status'] == 'completed' ? 'fa-check' :
                                             ($inspection['status'] == 'in_progress' ? 'fa-clock' : 'fa-calendar');
                                    ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium"><?php echo $inspection['inspection_type']; ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></p>
                                    <p class="text-xs text-gray-500">Status: <?php echo ucfirst(str_replace('_', ' ', $inspection['status'])); ?></p>
                                </div>
                            </div>
                            <?php if ($inspection['compliance_score']): ?>
                            <div class="text-right">
                                <div class="text-2xl font-bold <?php echo $inspection['compliance_score'] >= 80 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $inspection['compliance_score']; ?>%
                                </div>
                                <div class="text-xs text-gray-600">Compliance</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Quick Actions</h3>
                    <p class="text-gray-600 text-sm">Common tasks and requests</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <button onclick="requestInspection(<?php echo $userBusiness['id']; ?>)"
                                class="flex items-center space-x-3 p-4 border-2 border-dashed border-blue-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar-plus text-blue-600"></i>
                            </div>
                            <div class="text-left">
                                <h4 class="font-medium text-blue-800">Request Inspection</h4>
                                <p class="text-sm text-blue-600">Schedule a new inspection</p>
                            </div>
                        </button>

                        <a href="profile.php"
                           class="flex items-center space-x-3 p-4 border-2 border-dashed border-green-300 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-edit text-green-600"></i>
                            </div>
                            <div class="text-left">
                                <h4 class="font-medium text-green-800">Update Profile</h4>
                                <p class="text-sm text-green-600">Manage your account</p>
                            </div>
                        </a>

                        <a href="violations.php"
                           class="flex items-center space-x-3 p-4 border-2 border-dashed border-orange-300 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-colors">
                            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-clipboard-list text-orange-600"></i>
                            </div>
                            <div class="text-left">
                                <h4 class="font-medium text-orange-800">View Violations</h4>
                                <p class="text-sm text-orange-600">Check violation history</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- No Business Found -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-building text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Business Registered</h3>
                    <p class="text-gray-600 mb-4">You don't have any registered businesses yet.</p>
                    <p class="text-sm text-gray-500">Contact your local government office to register your business for inspections.</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Notifications -->
            <?php if (!empty($recentNotifications)): ?>
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Recent Notifications</h3>
                    <p class="text-gray-600 text-sm">Latest updates and messages</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <?php foreach ($recentNotifications as $notification): ?>
                        <div class="flex items-start p-4 border rounded-lg">
                            <i class="fas fa-bell text-blue-500 mt-1 mr-3"></i>
                            <div class="flex-1">
                                <p class="text-sm"><?php echo $notification['message']; ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Simple tab functionality
        function showTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabId).classList.add('active');
        }

        // Show initial tab
        showTab('<?php echo $activeTab; ?>');

        // Request inspection function
        function requestInspection(businessId) {
            if (confirm('Request an inspection for this business? An inspector will be assigned and you will be notified of the schedule.')) {
                // Create a form to submit the request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'request_inspection.php';
                
                const businessIdInput = document.createElement('input');
                businessIdInput.type = 'hidden';
                businessIdInput.name = 'business_id';
                businessIdInput.value = businessId;
                
                form.appendChild(businessIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Show inspection request modal
        function showInspectionRequestModal(businessId, businessName) {
            const modal = document.getElementById('inspectionRequestModal');
            document.getElementById('modalBusinessName').textContent = businessName;
            document.getElementById('businessId').value = businessId;
            modal.classList.remove('hidden');
        }

        // Close modal
        function closeModal() {
            document.getElementById('inspectionRequestModal').classList.add('hidden');
        }
    </script>

    <!-- Inspection Request Modal -->
    <div id="inspectionRequestModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">Request Inspection</h3>
            <p class="text-sm text-gray-600 mb-4">Requesting inspection for: <span id="modalBusinessName"></span></p>
            
            <form method="POST" action="request_inspection.php">
                <input type="hidden" id="businessId" name="business_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Inspection Type</label>
                    <select name="inspection_type" class="w-full border border-gray-300 rounded-md px-3 py-2" required>
                        <option value="">Select Inspection Type</option>
                        <option value="Health & Sanitation">Health & Sanitation</option>
                        <option value="Fire Safety">Fire Safety</option>
                        <option value="Building Safety">Building Safety</option>
                        <option value="Environmental">Environmental</option>
                        <option value="Food Safety">Food Safety</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
                    <input type="date" name="preferred_date" class="w-full border border-gray-300 rounded-md px-3 py-2" 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                    <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2" 
                              placeholder="Any specific concerns or details..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
