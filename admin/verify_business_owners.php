<?php
// Start session and include necessary configurations
require_once '../utils/session_manager.php';
require_once '../utils/access_control.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/BusinessDocument.php';
require_once '../models/Notification.php';

// 1. Security Check: Ensure only Superadmin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../main_login.php");
    exit();
}

// Ensure user has the required permission
requirePermission('manage_applications');

$database = new Database();
$db = $database->getConnection();
$businessModel = new Business($database);
$notificationModel = new Notification($database);
$userModel = new User($database);

$message = '';
$messageType = '';

// 2. Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($business_id && $action) {
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
                    $message = "Business application has been successfully " . ($new_status === 'verified' ? 'approved.' : 'rejected.');
                    $messageType = 'success';

                    // Create a notification for the business owner
                    $business_name = $business_data['business_name'];
                    $status_text = ($new_status === 'verified') ? 'approved' : 'rejected';
                    $notification_message = "Your business application for \"{$business_name}\" has been {$status_text}.";
                    
                    // Append the reason to the notification message if rejected
                    if ($new_status === 'rejected' && !empty($rejection_reason)) {
                        $notification_message .= " Reason: " . $rejection_reason;
                    }

                    $link = '/lgu4/business/index.php'; // Link to their dashboard

                    $notificationModel->create($owner_id, $notification_message, 'info', 'business', $business_id, $link);
                } else {
                    $message = "Failed to update status. Please check logs.";
                    $messageType = 'error';
                }
            } else {
                $message = "Failed to find business data.";
                $messageType = 'error';
            }
        } else {
            $message = "Invalid action.";
            $messageType = 'error';
        }
    }
}

// 3. Fetch Pending Business Owners with Filtering, Sorting, and Pagination
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10; // Number of records to display per page
$offset = ($page - 1) * $records_per_page;

// Whitelist sortable columns to prevent SQL injection
$sortable_columns = ['business_name', 'owner_name', 'created_at'];
if (!in_array($sort_by, $sortable_columns)) {
    $sort_by = 'created_at';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Build the base query and params for both counting and fetching
$base_query = "FROM businesses b JOIN users u ON b.owner_id = u.id LEFT JOIN business_documents bd ON b.id = bd.business_id WHERE b.status = 'pending'";
$params = [];

if (!empty($search)) {
    $base_query .= " AND (b.name LIKE :search OR u.name LIKE :search OR b.email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($filter_type)) {
    $base_query .= " AND b.business_type = :filter_type";
    $params[':filter_type'] = $filter_type;
}

// Get Total Records for Pagination
$total_query = "SELECT COUNT(DISTINCT b.id) as total " . $base_query;
$total_stmt = $db->prepare($total_query);
$total_stmt->execute($params);
$total_records = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

// Fetch Paginated Records
$query = "SELECT b.id, b.name as business_name, b.address, b.business_type, b.registration_number, b.email as contact_email, b.contact_number, b.created_at, u.name as owner_name, u.email as owner_email,
           GROUP_CONCAT(bd.file_name SEPARATOR '||') as document_names,
           GROUP_CONCAT(bd.file_path SEPARATOR '||') as document_paths,
           GROUP_CONCAT(bd.document_type SEPARATOR '||') as document_types
           " . $base_query . "
           GROUP BY b.id 
           ORDER BY $sort_by $sort_order
           LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);

// Bind values
$stmt->bindValue(':limit', (int) $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
if (!empty($search)) {
    $stmt->bindValue(':search', '%' . $search . '%');
}
if (!empty($filter_type)) {
    $stmt->bindValue(':filter_type', $filter_type);
}

$stmt->execute();
$pendingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

// Helper function for creating sorting links
function getSortLink($column, $current_sort, $current_order, $current_search, $current_filter_type) {
    $order = ($current_sort === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $queryParams = [
        'sort_by' => $column,
        'sort_order' => $order,
        'search' => $current_search,
        'filter_type' => $current_filter_type
    ];
    return '?' . http_build_query(array_filter($queryParams)); // array_filter removes empty values
}

// Helper function for creating pagination links
function getPageLink($page, $current_search, $current_filter_type, $current_sort, $current_order) {
    $queryParams = [
        'page' => $page,
        'search' => $current_search,
        'filter_type' => $current_filter_type,
        'sort_by' => $current_sort,
        'sort_order' => $current_order
    ];
    return '?' . http_build_query(array_filter($queryParams));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Business Owners - Superadmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f9; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-error { background-color: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { vertical-align: top; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: 600; }
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 14px; margin-right: 5px; }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .btn-view { background-color: #007bff; text-decoration: none; display: inline-block; }
        .no-data { text-align: center; padding: 20px; color: #666; }
        .doc-list { list-style: none; padding: 0; margin: 0; }
        .filter-controls { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filter-controls input, .filter-controls select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-controls button { padding: 8px 15px; border: none; border-radius: 4px; background-color: #007bff; color: white; cursor: pointer; }
        .filter-controls a { padding: 8px 15px; border-radius: 4px; background-color: #6c757d; color: white; text-decoration: none; font-size: 14px; }
        th a { text-decoration: none; color: inherit; display: flex; align-items: center; gap: 5px;}
        th a i { color: #007bff; }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-user-check"></i> Verify Business Owner Documents</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filter and Sort Controls -->
    <div class="filter-controls">
        <form method="GET" action="" class="flex items-center gap-2">
            <input type="text" name="search" placeholder="Search name, business, email..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="filter_type">
                <option value="">All Business Types</option>
                <option value="Restaurant" <?php if($filter_type == 'Restaurant') echo 'selected'; ?>>Restaurant</option>
                <option value="Food Establishment" <?php if($filter_type == 'Food Establishment') echo 'selected'; ?>>Food Establishment</option>
                <option value="Hotel" <?php if($filter_type == 'Hotel') echo 'selected'; ?>>Hotel</option>
                <option value="Hospital" <?php if($filter_type == 'Hospital') echo 'selected'; ?>>Hospital</option>
                <option value="School" <?php if($filter_type == 'School') echo 'selected'; ?>>School</option>
                <option value="Factory" <?php if($filter_type == 'Factory') echo 'selected'; ?>>Factory</option>
                <option value="Other" <?php if($filter_type == 'Other') echo 'selected'; ?>>Other</option>
            </select>
            <button type="submit"><i class="fas fa-filter"></i> Filter</button>
            <a href="verify_business_owners.php">Clear</a>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th><a href="<?php echo getSortLink('business_name', $sort_by, $sort_order, $search, $filter_type); ?>">Business Name <?php if ($sort_by == 'business_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                <th><a href="<?php echo getSortLink('owner_name', $sort_by, $sort_order, $search, $filter_type); ?>">Applicant Name <?php if ($sort_by == 'owner_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                <th>Business Type</th>
                <th><a href="<?php echo getSortLink('created_at', $sort_by, $sort_order, $search, $filter_type); ?>">Submitted <?php if ($sort_by == 'created_at') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($pendingUsers) > 0): ?>
                <?php foreach ($pendingUsers as $user): ?>
                    <tr data-details='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'>
                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($user['business_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['owner_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['business_type']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td class="whitespace-nowrap">
                            <button type="button" onclick="openDetailsModal(this)" class="btn btn-view text-xs"><i class="fas fa-eye mr-1"></i> View</button>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this application?');" class="inline-block">
                                <input type="hidden" name="business_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve text-xs">
                                    <i class="fas fa-check mr-1"></i> Approve
                                </button>
                            </form>
                            <button type="button" onclick="openRejectModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['business_name'])); ?>')" class="btn btn-reject text-xs">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-data">No pending business owner approvals found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination Controls -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600">
            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
        </div>
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <!-- Previous Button -->
            <a href="<?php echo getPageLink(max(1, $page - 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <span class="sr-only">Previous</span>
                <i class="fas fa-chevron-left h-5 w-5"></i>
            </a>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo getPageLink($i, $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo ($i == $page) ? 'z-10 bg-yellow-50 border-yellow-500 text-yellow-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>

            <!-- Next Button -->
            <a href="<?php echo getPageLink(min($total_pages, $page + 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <span class="sr-only">Next</span>
                <i class="fas fa-chevron-right h-5 w-5"></i>
            </a>
        </nav>
    </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="index.php" style="color: #007bff; text-decoration: none; display: inline-block; padding: 10px 15px; background: #f0f0f0; border-radius: 5px;">&larr; Back to Dashboard</a>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-medium text-gray-900">Application Details</h3>
            <button onclick="closeModal('detailsModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        <div class="mt-4 space-y-4" id="detailsModalContent">
            <!-- Content will be injected by JavaScript -->
        </div>
        <div class="flex justify-end space-x-3 pt-4 mt-4 border-t">
            <button type="button" onclick="closeModal('detailsModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Close</button>
        </div>
    </div>
</div>

<!-- Document Viewer Modal -->
<div id="documentViewerModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-75 overflow-y-auto h-full w-full z-[60]">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-medium text-gray-900" id="documentViewerTitle">Document Viewer</h3>
            <button onclick="closeModal('documentViewerModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        <div class="mt-4" id="documentViewerContent" style="height: 80vh;">
            <!-- Content (iframe or img) will be injected by JavaScript -->
        </div>
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
    function openDetailsModal(button) {
        const row = button.closest('tr');
        const details = JSON.parse(row.dataset.details);
        const modalContent = document.getElementById('detailsModalContent');

        const doc_names = details.document_names ? details.document_names.split('||') : [];
        const doc_paths = details.document_paths ? details.document_paths.split('||') : [];
        const doc_types = details.document_types ? details.document_types.split('||') : [];
        
        let documentsHtml = '<p class="text-gray-500">No documents uploaded.</p>';
        if (doc_names.length > 0 && doc_names[0]) {
            documentsHtml = '<ul class="list-disc list-inside space-y-1">';
            doc_names.forEach((name, index) => {
                const docTypeFormatted = doc_types[index].replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                documentsHtml += `
                    <li>
                        <a href="#" onclick="openDocumentViewer('<?php echo $base_path; ?>/${doc_paths[index]}', '${docTypeFormatted}', event)" class="text-blue-600 hover:underline">
                            ${docTypeFormatted}
                        </a>
                    </li>`;
            });
            documentsHtml += '</ul>';
        }

        modalContent.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 border-b pb-2 mb-2">Business Information</h4>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="font-medium text-gray-500">Business Name</dt><dd class="text-gray-900">${details.business_name}</dd></div>
                        <div><dt class="font-medium text-gray-500">Business Type</dt><dd class="text-gray-900">${details.business_type}</dd></div>
                        <div><dt class="font-medium text-gray-500">Registration No.</dt><dd class="text-gray-900">${details.registration_number}</dd></div>
                        <div><dt class="font-medium text-gray-500">Address</dt><dd class="text-gray-900">${details.address}</dd></div>
                    </dl>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 border-b pb-2 mb-2">Applicant Details</h4>
                    <dl class="space-y-2 text-sm">
                        <div><dt class="font-medium text-gray-500">Applicant Name</dt><dd class="text-gray-900">${details.owner_name}</dd></div>
                        <div><dt class="font-medium text-gray-500">Applicant Email</dt><dd class="text-gray-900">${details.owner_email}</dd></div>
                        <div><dt class="font-medium text-gray-500">Business Contact Email</dt><dd class="text-gray-900">${details.contact_email}</dd></div>
                        <div><dt class="font-medium text-gray-500">Business Contact Phone</dt><dd class="text-gray-900">${details.contact_number}</dd></div>
                    </dl>
                </div>
                <div class="md:col-span-2">
                     <h4 class="font-semibold text-gray-800 border-b pb-2 mb-2">Uploaded Documents</h4>
                     ${documentsHtml}
                </div>
            </div>
        `;

        document.getElementById('detailsModal').classList.remove('hidden');
    }

    function openRejectModal(businessId, businessName) {
        document.getElementById('reject_business_id').value = businessId;
        document.getElementById('rejectBusinessName').textContent = businessName;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('hidden');

        // If closing the document viewer, clear its content to stop videos/PDFs
        if (modalId === 'documentViewerModal') {
            document.getElementById('documentViewerContent').innerHTML = '';
        }
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function openDocumentViewer(filePath, fileTitle, event) {
        event.preventDefault();
        const viewerContent = document.getElementById('documentViewerContent');
        const viewerTitle = document.getElementById('documentViewerTitle');
        
        viewerTitle.textContent = fileTitle;
        
        const fileExtension = filePath.split('.').pop().toLowerCase();

        if (fileExtension === 'pdf') {
            viewerContent.innerHTML = `<iframe src="${filePath}" class="w-full h-full" frameborder="0"></iframe>`;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            viewerContent.innerHTML = `<div class="flex justify-center items-center h-full"><img src="${filePath}" class="max-w-full max-h-full object-contain"></div>`;
        } else {
            viewerContent.innerHTML = `<div class="text-center p-10"><p>Cannot preview this file type.</p><a href="${filePath}" target="_blank" class="text-blue-600 hover:underline mt-4 inline-block">Download file</a></div>`;
        }

        openModal('documentViewerModal');
    }
</script>

</body>
</html>