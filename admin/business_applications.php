<?php
require_once '../utils/session_manager.php';
require_once '../utils/access_control.php';
require_once '../config/database.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../models/User.php';

// Ensure user has the required permission
requirePermission('manage_applications');

$database = new Database();
$conn = $database->getConnection();
$businessModel = new Business($database);
$notificationModel = new Notification($database);
$userModel = new User($database);

// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['business_id'])) {
    $business_id = $_POST['business_id'];
    $action = $_POST['action'] ?? '';

    $new_status = '';
    if ($action === 'approve') {
        $new_status = 'verified';
        $new_user_status = 'active';
    } elseif ($action === 'reject') {
        $new_status = 'rejected';
        $new_user_status = 'active'; // Allow user to log in and see the reason
        $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided.';
    }

    if ($new_status) {
        // First, find the business to get the owner's user_id
        $business_data = $businessModel->findById($business_id);

        if ($business_data) {
            $owner_id = $business_data['user_id'];
            // Pass the rejection reason to the updateStatus method
            if ($businessModel->updateStatus($business_id, $new_status, $rejection_reason ?? null) && $userModel->updateStatus($owner_id, $new_user_status)) {
                $_SESSION['success_message'] = "Business application has been successfully " . ($new_status === 'verified' ? 'approved.' : 'rejected.');

                // Create a notification for the business owner
                $business_name = $business_data['business_name'];
                $status_text = ($new_status === 'verified') ? 'approved' : 'rejected';
                $message = "Your business application for \"{$business_name}\" has been {$status_text}.";
                
                // Append the reason to the notification message if rejected
                if ($new_status === 'rejected' && !empty($rejection_reason)) {
                    $message .= " Reason: " . $rejection_reason;
                }

                $link = '/lgu4/business/index.php'; // Link to their dashboard

                $notificationModel->create($owner_id, $message, 'info', 'business', $business_id, $link);
            } else {
                $_SESSION['error_message'] = "Failed to update status. Please check logs.";
            }

        } else {
            $_SESSION['error_message'] = "Failed to find business data.";
        }
    }
    header("Location: business_applications.php");
    exit();
}

// Fetch pending business applications with their permits
$pending_businesses = [];
try {
    $query = "
        SELECT 
            b.id, b.name as business_name, u.name as owner_name, b.email as contact_email, b.contact_number as contact_phone, 
            b.registration_number as license_number, b.business_type, b.address, b.created_at,
            u.name as user_name, b.owner_id as user_id,
            GROUP_CONCAT(bd.file_name SEPARATOR '||') as permit_files,
            GROUP_CONCAT(bd.file_path SEPARATOR '||') as permit_paths
        FROM 
            businesses b
        JOIN 
            users u ON b.owner_id = u.id
        LEFT JOIN 
            business_documents bd ON b.id = bd.business_id
        WHERE 
            b.status = 'pending'
        GROUP BY
            b.id
        ORDER BY 
            b.created_at ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pending_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$root_path = str_replace('/admin', '', $base_path);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Applications Review</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Pending Business Applications</h1>
            <p class="mt-1 text-sm text-gray-600">Review and approve or reject new business registrations.</p>
        </header>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?php echo $success_message; ?></p></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?php echo $error_message; ?></p></div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if (empty($pending_businesses)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">All Caught Up!</h3>
                    <p class="text-gray-500 mt-1">There are no pending business applications to review.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_businesses as $business): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($business['business_name']); ?></h3>
                                    <p class="text-sm text-gray-600">Submitted by: <?php echo htmlspecialchars($business['user_name']); ?> on <?php echo date('F j, Y, g:i a', strtotime($business['created_at'])); ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to approve this business?');">
                                        <input type="hidden" name="business_id" value="<?php echo $business['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="px-4 py-2 bg-green-500 text-white text-sm font-semibold rounded-md hover:bg-green-600 transition-colors">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal(<?php echo $business['id']; ?>, '<?php echo htmlspecialchars(addslashes($business['business_name'])); ?>')" class="px-4 py-2 bg-red-500 text-white text-sm font-semibold rounded-md hover:bg-red-600 transition-colors">
                                        <i class="fas fa-times mr-1"></i> Reject
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                                <p><strong>Owner/Contact:</strong> <?php echo htmlspecialchars($business['owner_name']); ?></p>
                                <p><strong>License Number:</strong> <?php echo htmlspecialchars($business['license_number']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($business['contact_email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($business['contact_phone'] ?: 'N/A'); ?></p>
                                <p class="md:col-span-2"><strong>Address:</strong> <?php echo htmlspecialchars($business['address']); ?></p>
                                <p class="md:col-span-2"><strong>Business Type:</strong> <span class="capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $business['business_type'])); ?></span></p>
                                <div>
                                    <strong>Uploaded Documents:</strong>
                                    <ul class="list-disc list-inside mt-1">
                                        <?php
                                        $files = $business['permit_files'] ? explode('||', $business['permit_files']) : [];
                                        $paths = $business['permit_paths'] ? explode('||', $business['permit_paths']) : [];
                                        if (!empty($files[0])) {
                                            foreach ($files as $index => $file) {
                                                // Use the root path to construct the correct URL
                                                echo '<li><a href="' . $root_path . '/' . $paths[$index] . '" target="_blank" class="text-blue-600 hover:underline">' . htmlspecialchars($file) . '</a></li>';
                                            }
                                        } else {
                                            echo '<li>No documents uploaded.</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Reason for Rejection</h3>
                <p class="text-sm text-gray-600 mt-1">For business: <span id="rejectBusinessName" class="font-bold"></span></p>
                
                <form id="rejectForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="business_id" id="reject_business_id">
                    
                    <div>
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Please provide a clear reason for rejecting this application.</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="e.g., Missing building permit, incorrect registration number..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectModal(businessId, businessName) {
            document.getElementById('reject_business_id').value = businessId;
            document.getElementById('rejectBusinessName').textContent = businessName;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>