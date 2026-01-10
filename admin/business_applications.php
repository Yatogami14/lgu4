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
    elseif ($action === 'request_revision') {
        $new_status = 'needs_revision';
        $new_user_status = 'active'; // Allow user to log in to fix it
        
        // Handle individual document feedback
        if (isset($_POST['doc_status']) && is_array($_POST['doc_status'])) {
            require_once '../models/BusinessDocument.php';
            $docModel = new BusinessDocument($database);
            foreach ($_POST['doc_status'] as $doc_id => $status) {
                $feedback = $_POST['doc_feedback'][$doc_id] ?? '';
                // If rejected, set status to rejected and save feedback
                if ($status === 'rejected') {
                    $docModel->updateStatus($doc_id, 'rejected', $feedback);
                } else {
                    // Reset to pending or verified if needed
                    $docModel->updateStatus($doc_id, 'pending', null);
                }
            }
        }
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
                if ($new_status === 'needs_revision') $status_text = 'flagged for revision';
                
                $message = "Your business application for \"{$business_name}\" has been {$status_text}.";
                
                // Append the reason to the notification message if rejected
                if ($new_status === 'rejected' && !empty($rejection_reason)) {
                    $message .= " Reason: " . $rejection_reason;
                } elseif ($new_status === 'needs_revision') {
                    $message .= " Please check your documents and re-upload the requested files.";
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
            GROUP_CONCAT(bd.file_path SEPARATOR '||') as permit_paths,
            GROUP_CONCAT(bd.document_type SEPARATOR '||') as permit_types,
            GROUP_CONCAT(bd.id SEPARATOR '||') as permit_ids
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Pending Business Applications</h2>
            <p class="text-sm text-gray-600 mt-1">Review and approve or reject new business registrations.</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <p><?php echo $success_message; ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p><?php echo $error_message; ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if (empty($pending_businesses)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-check text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">All Caught Up!</h3>
                    <p class="text-gray-500 mt-1">There are no pending business applications to review.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_businesses as $business): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow duration-200">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center text-brand-600 font-bold text-lg">
                                            <?php echo strtoupper(substr($business['business_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($business['business_name']); ?></h3>
                                            <p class="text-sm text-gray-500">Submitted by <span class="font-medium text-gray-700"><?php echo htmlspecialchars($business['user_name']); ?></span> on <?php echo date('M j, Y, g:i a', strtotime($business['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 w-full md:w-auto">
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to approve this business?');">
                                        <input type="hidden" name="business_id" value="<?php echo $business['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                            <i class="fas fa-check mr-2"></i> Approve
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal(<?php echo $business['id']; ?>, '<?php echo htmlspecialchars(addslashes($business['business_name'])); ?>')" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                        <i class="fas fa-times mr-2"></i> Reject
                                    </button>
                                    <button type="button" onclick="openRevisionModal(<?php echo $business['id']; ?>, '<?php echo htmlspecialchars(addslashes($business['business_name'])); ?>', this)" class="inline-flex items-center px-4 py-2 bg-yellow-500 text-white text-sm font-medium rounded-lg hover:bg-yellow-600 transition-colors shadow-sm">
                                        <i class="fas fa-edit mr-2"></i> Request Revision
                                    </button>
                                </div>
                            </div>
                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <div class="space-y-3">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Contact Details</h4>
                                    <div class="flex items-start gap-3 text-sm">
                                        <i class="fas fa-user mt-1 text-gray-400 w-4"></i>
                                        <div>
                                            <span class="block text-gray-900 font-medium"><?php echo htmlspecialchars($business['owner_name']); ?></span>
                                            <span class="text-gray-500">Owner</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 text-sm">
                                        <i class="fas fa-envelope mt-1 text-gray-400 w-4"></i>
                                        <span class="text-gray-600"><?php echo htmlspecialchars($business['contact_email']); ?></span>
                                    </div>
                                    <div class="flex items-start gap-3 text-sm">
                                        <i class="fas fa-phone mt-1 text-gray-400 w-4"></i>
                                        <span class="text-gray-600"><?php echo htmlspecialchars($business['contact_phone'] ?: 'N/A'); ?></span>
                                    </div>
                                </div>
                                
                                <div class="space-y-3">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Business Info</h4>
                                    <div class="flex items-start gap-3 text-sm">
                                        <i class="fas fa-id-card mt-1 text-gray-400 w-4"></i>
                                        <div>
                                            <span class="block text-gray-900 font-medium"><?php echo htmlspecialchars($business['license_number']); ?></span>
                                            <span class="text-gray-500">License Number</span>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 text-sm">
                                        <i class="fas fa-store mt-1 text-gray-400 w-4"></i>
                                        <span class="text-gray-600 capitalize"><?php echo htmlspecialchars(str_replace('_', ' ', $business['business_type'])); ?></span>
                                    </div>
                                    <div class="flex items-start gap-3 text-sm">
                                        <i class="fas fa-map-marker-alt mt-1 text-gray-400 w-4"></i>
                                        <span class="text-gray-600"><?php echo htmlspecialchars($business['address']); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                    <i class="fas fa-folder-open text-brand-500 mr-2"></i> Uploaded Documents
                                </h4>
                                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <?php
                                        $files = $business['permit_files'] ? explode('||', $business['permit_files']) : [];
                                        $paths = $business['permit_paths'] ? explode('||', $business['permit_paths']) : [];
                                        $types = $business['permit_types'] ? explode('||', $business['permit_types']) : [];
                                        $ids = $business['permit_ids'] ? explode('||', $business['permit_ids']) : [];
                                        
                                        if (!empty($files[0])) {
                                            foreach ($files as $index => $file) {
                                                $typeLabel = 'Document';
                                                if (isset($types[$index])) {
                                                    $typeLabel = ($types[$index] === 'mayors_permit') ? "Mayor's Permit" : ucwords(str_replace('_', ' ', $types[$index]));
                                                }
                                                // Use the root path to construct the correct URL
                                                $filePath = htmlspecialchars($root_path . '/' . $paths[$index], ENT_QUOTES, 'UTF-8');
                                                $fileTitle = htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <li data-id="<?php echo $ids[$index]; ?>" data-type="<?php echo $typeLabel; ?>" class="group">
                                                    <a href="#" onclick="openDocumentViewer('<?php echo $filePath; ?>', '<?php echo $fileTitle; ?>', event)" class="flex items-center p-3 rounded-lg border border-gray-200 hover:border-brand-300 hover:bg-brand-50 transition-all">
                                                        <div class="w-10 h-10 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center flex-shrink-0">
                                                            <i class="fas fa-file-alt"></i>
                                                        </div>
                                                        <div class="ml-3 overflow-hidden">
                                                            <p class="text-sm font-medium text-gray-900 group-hover:text-brand-700 truncate"><?php echo $typeLabel; ?></p>
                                                            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($file); ?></p>
                                                        </div>
                                                        <div class="ml-auto">
                                                            <i class="fas fa-external-link-alt text-gray-400 group-hover:text-brand-500 text-sm"></i>
                                                        </div>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                        } else {
                                            echo '<li class="text-sm text-gray-500 italic col-span-2">No documents uploaded.</li>';
                                        }
                                        ?>
                                    </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-red-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Reason for Rejection</h3>
                <button onclick="closeModal('rejectModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">For business: <span id="rejectBusinessName" class="font-bold text-gray-900"></span></p>
                
                <form id="rejectForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="business_id" id="reject_business_id">
                    
                    <div>
                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Please provide a clear reason for rejecting this application.</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4" required class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., Missing building permit, incorrect registration number..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium shadow-sm">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-lg shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-yellow-500 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Request Document Revision</h3>
                <button onclick="closeModal('revisionModal')" class="text-white hover:text-yellow-100 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">For business: <span id="revisionBusinessName" class="font-bold text-gray-900"></span></p>
                
                <form id="revisionForm" method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="request_revision">
                    <input type="hidden" name="business_id" id="revision_business_id">
                    
                    <div id="documentList" class="space-y-3 max-h-60 overflow-y-auto p-2 border border-gray-200 rounded-lg bg-gray-50">
                        <!-- Documents will be populated here -->
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-2">
                        <button type="button" onclick="closeModal('revisionModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-medium shadow-sm">Send Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div id="documentViewerModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full z-[60] backdrop-blur-sm transition-opacity">
        <div class="relative top-5 mx-auto p-0 border w-full max-w-5xl shadow-2xl rounded-xl bg-white transform transition-all h-[90vh] flex flex-col">
            <div class="bg-gray-800 px-6 py-4 rounded-t-xl flex justify-between items-center flex-shrink-0">
                <h3 class="text-lg font-bold text-white" id="documentViewerTitle">Document Viewer</h3>
                <button onclick="closeModal('documentViewerModal')" class="text-gray-400 hover:text-white focus:outline-none transition-colors">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            <div class="flex-grow bg-gray-100 p-4 overflow-hidden relative" id="documentViewerContent">
                <!-- Content (iframe or img) will be injected by JavaScript -->
            </div>
        </div>
    </div>

    <script src="js/application_actions.js"></script>
    <script>
        function openDocumentViewer(filePath, fileTitle, event) {
            event.preventDefault();
            const viewerContent = document.getElementById('documentViewerContent');
            const viewerTitle = document.getElementById('documentViewerTitle');
            
            viewerTitle.textContent = fileTitle;
            
            const fileExtension = filePath.split('.').pop().toLowerCase();

            if (fileExtension === 'pdf') {
                viewerContent.innerHTML = `<iframe src="${filePath}" class="w-full h-full border-0 rounded-lg" frameborder="0"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
                viewerContent.innerHTML = `<div class="flex justify-center items-center h-full"><img src="${filePath}" class="max-w-full max-h-full object-contain rounded-lg shadow-sm"></div>`;
            } else {
                viewerContent.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-gray-500"><i class="fas fa-file-download text-4xl mb-4"></i><p>Cannot preview this file type.</p><a href="${filePath}" target="_blank" class="text-brand-600 hover:underline mt-4 font-medium">Download file</a></div>`;
            }

            openModal('documentViewerModal');
        }

        function openRevisionModal(businessId, businessName, btn) {
            // Find the UL containing documents in the same card
            const card = btn.closest('.bg-white'); // This finds the card container
            const listItems = card.querySelectorAll('ul li'); // Updated selector for the new list structure
            const documents = [];
            
            listItems.forEach(li => {
                const docId = li.getAttribute('data-id');
                const docType = li.getAttribute('data-type');
                if(docId) {
                    documents.push({id: docId, type: docType});
                }
            });
            
            populateRevisionModal(businessId, businessName, documents);
            openModal('revisionModal');
        }
    </script>
</body>
</html>