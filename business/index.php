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

// Get businesses owned by current user
$userBusinesses = $business->readByOwnerId($_SESSION['user_id']);
$totalInspections = $inspection->countAll();
$activeViolations = $inspection->countActiveViolations();
$complianceRate = $inspection->getAverageCompliance();
$activeInspectors = $user->countActiveInspectors();

$recentInspections = $inspection->readRecent(5);
$recentNotifications = $notification->readByUser($_SESSION['user_id'], 5);

// Get upcoming inspections for user's businesses
$upcomingInspections = [];
foreach ($userBusinesses as $userBusiness) {
    $business->id = $userBusiness['id'];
    $businessInspections = $business->getRecentInspections($userBusiness['id'], 10);
    foreach ($businessInspections as $insp) {
        if ($insp['status'] == 'scheduled' && strtotime($insp['scheduled_date']) > time()) {
            $upcomingInspections[] = $insp;
        }
    }
}

// Sort upcoming inspections by date
usort($upcomingInspections, function($a, $b) {
    return strtotime($a['scheduled_date']) - strtotime($b['scheduled_date']);
});

// Get compliance status for user's businesses
$complianceStats = [];
foreach ($userBusinesses as $userBusiness) {
    $business->id = $userBusiness['id'];
    $stats = $business->getComplianceStats($userBusiness['id']);
    $complianceStats[$userBusiness['id']] = $stats;
}

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
            <!-- Your Businesses Section -->
            <?php if (!empty($userBusinesses)): ?>
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Your Businesses</h3>
                    <p class="text-gray-600 text-sm">Manage your registered businesses and compliance status</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($userBusinesses as $userBusiness): ?>
                        <?php $stats = $complianceStats[$userBusiness['id']] ?? []; ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-bold text-lg"><?php echo $userBusiness['name']; ?></h4>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php 
                                    echo $userBusiness['is_compliant'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; 
                                ?>">
                                    <?php echo $userBusiness['is_compliant'] ? 'Compliant' : 'Non-Compliant'; ?>
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-2"><?php echo $userBusiness['business_type']; ?></p>
                            <p class="text-sm text-gray-600 mb-3"><?php echo $userBusiness['address']; ?></p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span>Compliance Score:</span>
                                    <span class="font-medium <?php echo $userBusiness['compliance_score'] >= 80 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $userBusiness['compliance_score']; ?>%
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Next Inspection:</span>
                                    <span class="font-medium">
                                        <?php echo $userBusiness['next_inspection_date'] ? date('M j, Y', strtotime($userBusiness['next_inspection_date'])) : 'Not scheduled'; ?>
                                    </span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Inspection Frequency:</span>
                                    <span class="font-medium"><?php echo ucfirst($userBusiness['inspection_frequency']); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <button onclick="requestInspection(<?php echo $userBusiness['id']; ?>)" 
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                    Request Inspection
                                </button>
                                <a href="business_view.php?id=<?php echo $userBusiness['id']; ?>" 
                                   class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Your Businesses</p>
                            <p class="text-2xl font-bold"><?php echo count($userBusinesses); ?></p>
                            <p class="text-xs text-blue-600">Registered businesses</p>
                        </div>
                        <i class="fas fa-building text-3xl text-blue-500"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Compliant Businesses</p>
                            <p class="text-2xl font-bold">
                                <?php 
                                $compliantCount = 0;
                                foreach ($userBusinesses as $business) {
                                    if ($business['is_compliant']) $compliantCount++;
                                }
                                echo $compliantCount; 
                                ?>
                            </p>
                            <p class="text-xs text-green-600">Good standing</p>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Upcoming Inspections</p>
                            <p class="text-2xl font-bold"><?php echo count($upcomingInspections); ?></p>
                            <p class="text-xs text-yellow-600">Scheduled</p>
                        </div>
                        <i class="fas fa-calendar-alt text-3xl text-yellow-500"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Average Compliance</p>
                            <p class="text-2xl font-bold">
                                <?php 
                                $totalScore = 0;
                                $count = 0;
                                foreach ($userBusinesses as $business) {
                                    if ($business['compliance_score'] > 0) {
                                        $totalScore += $business['compliance_score'];
                                        $count++;
                                    }
                                }
                                echo $count > 0 ? round($totalScore / $count) : 0; 
                                ?>%
                            </p>
                            <p class="text-xs text-blue-600">Overall performance</p>
                        </div>
                        <i class="fas fa-chart-line text-3xl text-purple-500"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Inspections -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Recent Inspections</h3>
                    <p class="text-gray-600 text-sm">Latest inspection activities and status updates</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recentInspections as $inspection): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 border rounded-lg space-y-3 sm:space-y-0">
                            <div class="flex items-start space-x-3 flex-1 min-w-0">
                                <div class="w-3 h-3 rounded-full mt-1 flex-shrink-0 
                                    <?php echo $inspection['priority'] == 'high' ? 'bg-red-500' : 
                                           ($inspection['priority'] == 'medium' ? 'bg-yellow-500' : 'bg-green-500'); ?>">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium truncate"><?php echo $inspection['business_name']; ?></p>
                                    <p class="text-sm text-gray-600 break-words"><?php echo $inspection['inspection_type']; ?></p>
                                    <p class="text-sm text-gray-600 break-words"><?php echo $inspection['business_address']; ?></p>
                                    <p class="text-xs text-gray-500">Inspector: <?php echo $inspection['inspector_name']; ?></p>
                                </div>
                            </div>
                            <div class="flex flex-row sm:flex-col lg:flex-row items-start sm:items-end lg:items-center space-x-3 sm:space-x-0 lg:space-x-4 sm:space-y-2 lg:space-y-0 flex-shrink-0">
                                <?php if ($inspection['compliance_score']): ?>
                                <div class="text-left sm:text-right">
                                    <p class="text-xs sm:text-sm">Compliance</p>
                                    <p class="font-bold text-green-600 text-sm sm:text-base"><?php echo $inspection['compliance_score']; ?>%</p>
                                </div>
                                <?php endif; ?>
                                <div class="flex flex-col space-y-1">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
                                        <?php echo $inspection['status'] == 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                               ($inspection['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($inspection['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')); ?>">
                                        <?php echo str_replace('_', ' ', $inspection['status']); ?>
                                    </span>
                                    <p class="text-xs sm:text-sm text-gray-500"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-bold">Recent Notifications</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <?php foreach ($recentNotifications as $notification): ?>
                        <div class="flex items-start p-4 border rounded-lg">
                            <i class="fas fa-bell text-blue-500 mt-1 mr-3"></i>
                            <div class="flex-1">
                                <p class="text-sm"><?php echo $notification['message']; ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo $notification['created_at']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
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
