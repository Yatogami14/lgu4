
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

// Get current user info
$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$message = '';
$messageType = '';

// 2. Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($business_id && $action) {
        $new_status = '';
        if ($action === 'approve') {
            $new_status = 'active';
            $new_user_status = 'active';
        } elseif ($action === 'reject') {
            $new_status = 'rejected';
            $new_user_status = 'active'; // Allow user to log in and see the reason
            $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided.';
        } elseif ($action === 'request_revision') {
            $new_status = 'needs_revision';
            $new_user_status = 'active'; // Allow user to log in to fix it
            
            // Handle individual document feedback
            if (isset($_POST['doc_status']) && is_array($_POST['doc_status'])) {
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
                    $message = "Business application has been successfully " . ($new_status === 'verified' ? 'approved.' : 'rejected.');
                    $messageType = 'success';

                    // Create a notification for the business owner
                    $business_name = $business_data['business_name'];
                    $status_text = ($new_status === 'verified') ? 'approved' : 'rejected';
                    if ($new_status === 'needs_revision') $status_text = 'flagged for revision';

                    $notification_message = "Your business application for \"{$business_name}\" has been {$status_text}.";
                    
                    // Append the reason to the notification message if rejected
                    if ($new_status === 'rejected' && !empty($rejection_reason)) {
                        $notification_message .= " Reason: " . $rejection_reason;
                    } elseif ($new_status === 'needs_revision') {
                        $notification_message .= " Please check your documents and re-upload the requested files.";
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
           GROUP_CONCAT(bd.document_type SEPARATOR '||') as document_types,
           GROUP_CONCAT(bd.id SEPARATOR '||') as document_ids
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
<?php include '../includes/navigation.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Business Owners - Superadmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900">
    <div id="dashboard-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="space-y-8 animate-fade-in">
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl shadow-lg p-8 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-grow">
                        <h1 class="text-3xl font-bold relative z-10">Verify Business Owner Documents</h1>
                        <p class="text-brand-100 mt-2 relative z-10">Review and approve pending business applications</p>
                    </div>
                </div>
            </div>

            <div class="animate-slide-up">
                <!-- Stats Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending Applications</p>
                            <p class="text-3xl font-bold text-brand-600 mt-1"><?php echo $total_records; ?></p>
                        </div>
                        <div class="h-12 w-12 bg-brand-50 rounded-full flex items-center justify-center text-brand-600">
                            <i class="fas fa-user-clock text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="p-4 mb-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?>">
                        <div class="flex items-center">
                            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filter and Sort Controls -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                    <form method="GET" action="" class="flex flex-wrap items-center gap-4">
                        <div class="flex-1 min-w-0">
                            <input type="text" name="search" placeholder="Search name, business, email..." value="<?php echo htmlspecialchars($search); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>
                        <select name="filter_type" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">All Business Types</option>
                            <option value="Restaurant" <?php if($filter_type == 'Restaurant') echo 'selected'; ?>>Restaurant</option>
                            <option value="Food Establishment" <?php if($filter_type == 'Food Establishment') echo 'selected'; ?>>Food Establishment</option>
                            <option value="Hotel" <?php if($filter_type == 'Hotel') echo 'selected'; ?>>Hotel</option>
                            <option value="Hospital" <?php if($filter_type == 'Hospital') echo 'selected'; ?>>Hospital</option>
                            <option value="School" <?php if($filter_type == 'School') echo 'selected'; ?>>School</option>
                            <option value="Factory" <?php if($filter_type == 'Factory') echo 'selected'; ?>>Factory</option>
                            <option value="Other" <?php if($filter_type == 'Other') echo 'selected'; ?>>Other</option>
                        </select>
                        <button type="submit" class="px-6 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        <a href="verify_business_owners.php" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">Clear</a>
                    </form>
                </div>

                <!-- Applications Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <a href="<?php echo getSortLink('business_name', $sort_by, $sort_order, $search, $filter_type); ?>" class="flex items-center hover:text-gray-900">
                                            Business Name
                                            <?php if ($sort_by == 'business_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up ml-1"></i>' : '<i class="fas fa-sort-down ml-1"></i>'; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <a href="<?php echo getSortLink('owner_name', $sort_by, $sort_order, $search, $filter_type); ?>" class="flex items-center hover:text-gray-900">
                                            Applicant Name
                                            <?php if ($sort_by == 'owner_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up ml-1"></i>' : '<i class="fas fa-sort-down ml-1"></i>'; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Business Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <a href="<?php echo getSortLink('created_at', $sort_by, $sort_order, $search, $filter_type); ?>" class="flex items-center hover:text-gray-900">
                                            Submitted
                                            <?php if ($sort_by == 'created_at') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up ml-1"></i>' : '<i class="fas fa-sort-down ml-1"></i>'; ?>
                                        </a>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (count($pendingUsers) > 0): ?>
                                    <?php foreach ($pendingUsers as $user): ?>
                                        <tr data-details='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>' class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['business_name']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['owner_name'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-100 text-brand-800">
                                                    <?php echo htmlspecialchars($user['business_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex justify-start items-center space-x-2">
                                                    <button type="button" onclick="openDetailsModal(this)" class="text-brand-600 hover:text-brand-900 bg-brand-50 hover:bg-brand-100 border border-brand-200 px-3 py-1.5 rounded-md transition-all shadow-sm flex items-center" title="View Details">
                                                        <i class="fas fa-eye mr-1.5"></i> View
                                                    </button>
                                                    <div class="h-4 w-px bg-gray-300 mx-1"></div>
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this application?');" class="inline-block">
                                                        <input type="hidden" name="business_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="action" value="approve" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 border border-green-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" onclick="openRejectModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['business_name'])); ?>')" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button type="button" onclick="openRevisionModal(this)" class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="Request Revision">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-inbox text-gray-400 text-xl"></i>
                                                </div>
                                                <h3 class="text-sm font-medium text-gray-900 mb-1">No pending applications</h3>
                                                <p class="text-sm text-gray-500">All business applications have been reviewed.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($total_pages > 1): ?>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                            </div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <!-- Previous Button -->
                                <a href="<?php echo getPageLink(max(1, $page - 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors <?php echo ($page <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                    <i class="fas fa-chevron-left h-4 w-4"></i>
                                    <span class="sr-only">Previous</span>
                                </a>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="<?php echo getPageLink($i, $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors <?php echo ($i == $page) ? 'z-10 bg-brand-50 border-brand-500 text-brand-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>

                                <!-- Next Button -->
                                <a href="<?php echo getPageLink(min($total_pages, $page + 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors <?php echo ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                    <i class="fas fa-chevron-right h-4 w-4"></i>
                                    <span class="sr-only">Next</span>
                                </a>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Details Modal -->
<div id="detailsModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-xl rounded-2xl bg-white">
        <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-6">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 bg-brand-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-brand-600"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900">Application Details</h3>
                    <p class="text-sm text-gray-600">Review business application information</p>
                </div>
            </div>
            <button onclick="closeModal('detailsModal')" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        <div class="max-h-[70vh] overflow-y-auto" id="detailsModalContent">
            <!-- Content will be injected by JavaScript -->
        </div>
        <div class="flex justify-between items-center pt-6 mt-6 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                Application submitted on <span id="submissionDate" class="font-medium"></span>
            </div>
            <div class="flex space-x-3">
                <button type="button" onclick="closeModal('detailsModal')" class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Close</button>
                <div class="flex space-x-2">
                    <form method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this application?');" class="inline-block">
                        <input type="hidden" name="business_id" id="modal_business_id">
                        <button type="submit" name="action" value="approve" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                            <i class="fas fa-check mr-2"></i> Approve
                        </button>
                    </form>
                    <button type="button" id="modalRejectBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        <i class="fas fa-times mr-2"></i> Reject
                    </button>
                    <button type="button" id="modalRevisionBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors">
                        <i class="fas fa-edit mr-2"></i> Request Revision
                    </button>
                </div>
            </div>
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

<!-- Revision Modal -->
<div id="revisionModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900">Request Document Revision</h3>
            <p class="text-sm text-gray-600 mt-1">For business: <span id="revisionBusinessName" class="font-bold"></span></p>
            
            <form id="revisionForm" method="POST" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="request_revision">
                <input type="hidden" name="business_id" id="revision_business_id">
                
                <div id="documentList" class="space-y-3 max-h-60 overflow-y-auto p-2 border rounded">
                    <!-- Documents will be populated here by JS -->
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal('revisionModal')" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">Send Request</button>
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

        // Set submission date
        const submissionDateElement = document.getElementById('submissionDate');
        const createdDate = new Date(details.created_at);
        submissionDateElement.textContent = createdDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Set business ID for modal forms
        document.getElementById('modal_business_id').value = details.id;

        const doc_names = details.document_names ? details.document_names.split('||') : [];
        const doc_paths = details.document_paths ? details.document_paths.split('||') : [];
        const doc_types = details.document_types ? details.document_types.split('||') : [];

        let documentsHtml = '<div class="text-center py-8"><div class="text-gray-400 mb-2"><i class="fas fa-file-alt text-3xl"></i></div><p class="text-gray-500">No documents uploaded.</p></div>';
        if (doc_names.length > 0 && doc_names[0]) {
            documentsHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">';
            doc_names.forEach((name, index) => {
                let docTypeFormatted = 'Document';
                let docIcon = 'fas fa-file';
                if (doc_types[index]) {
                    if (doc_types[index] === 'mayors_permit') {
                        docTypeFormatted = "Mayor's Permit";
                        docIcon = 'fas fa-certificate';
                    } else {
                        docTypeFormatted = doc_types[index].replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        // Set appropriate icons based on document type
                        if (doc_types[index].includes('permit')) docIcon = 'fas fa-certificate';
                        else if (doc_types[index].includes('license')) docIcon = 'fas fa-id-card';
                        else if (doc_types[index].includes('certificate')) docIcon = 'fas fa-award';
                        else if (doc_types[index].includes('photo')) docIcon = 'fas fa-image';
                    }
                }
                // Escape backticks to prevent breaking the template literal
                docTypeFormatted = docTypeFormatted.replace(/`/g, '\\`');
                const safeName = name.replace(/`/g, '\\`');
                const filePath = '<?php echo $base_path; ?>/' + doc_paths[index];
                // Escape special characters to prevent breaking the onclick HTML attribute and template literal
                const safeFilePath = filePath.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/`/g, '\\`');
                const safeDocType = docTypeFormatted.replace(/'/g, "\\'").replace(/`/g, '\\`');
                documentsHtml += `
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-brand-300 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 bg-brand-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="${docIcon} text-brand-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">${docTypeFormatted}</p>
                                <p class="text-xs text-gray-500 truncate">${safeName}</p>
                            </div>
                            <button onclick="openDocumentViewer('${safeFilePath}', '${safeDocType}', event)" class="text-brand-600 hover:text-brand-700 p-2 rounded-full hover:bg-brand-50 transition-colors">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>`;
            });
            documentsHtml += '</div>';
        }

        modalContent.innerHTML = `
            <div class="space-y-8">
                <!-- Business Information Card -->
                <div class="bg-gradient-to-r from-brand-50 to-blue-50 rounded-xl p-6 border border-brand-100">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="h-12 w-12 bg-brand-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-building text-brand-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900">${details.business_name}</h4>
                            <p class="text-sm text-gray-600">${details.business_type}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Registration No:</span>
                                <span class="font-medium text-gray-900">${details.registration_number || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Address:</span>
                                <span class="font-medium text-gray-900 text-right">${details.address}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Contact Email:</span>
                                <span class="font-medium text-gray-900">${details.contact_email || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Contact Phone:</span>
                                <span class="font-medium text-gray-900">${details.contact_number || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Applicant Information Card -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="h-12 w-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900">Applicant Information</h4>
                            <p class="text-sm text-gray-600">Business owner details</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Full Name:</span>
                                <span class="font-medium text-gray-900">${details.owner_name || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Email:</span>
                                <span class="font-medium text-gray-900">${details.owner_email || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Documents Section -->
                <div class="bg-white rounded-xl p-6 border border-gray-200">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-folder-open text-gray-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900">Uploaded Documents</h4>
                            <p class="text-sm text-gray-600">Review submitted business documents</p>
                        </div>
                    </div>
                    ${documentsHtml}
                </div>
            </div>
        `;

        // Add event listeners for modal buttons
        document.getElementById('modalRejectBtn').onclick = () => openRejectModal(details.id, details.business_name);
        document.getElementById('modalRevisionBtn').onclick = () => openRevisionModal(button);

        document.getElementById('detailsModal').classList.remove('hidden');
    }

    function openRevisionModal(button) {
        const row = button.closest('tr');
        const details = JSON.parse(row.dataset.details);

        const doc_ids = details.document_ids ? details.document_ids.split('||') : [];
        const doc_types = details.document_types ? details.document_types.split('||') : [];

        const documents = [];
        if (doc_ids.length > 0 && doc_ids[0]) {
            doc_ids.forEach((docId, index) => {
                const docType = doc_types[index] ? doc_types[index].replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Document';
                documents.push({id: docId, type: docType});
            });
        }
        
        populateRevisionModal(details.id, details.business_name, documents);
        openModal('revisionModal');
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

    // --- Shared Modal Functions ---
    
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
        // Clear content if it's the document viewer to stop videos/iframes
        if (modalId === 'documentViewerModal') {
            const viewerContent = document.getElementById('documentViewerContent');
            if (viewerContent) viewerContent.innerHTML = '';
        }
    }

    function openRejectModal(businessId, businessName) {
        document.getElementById('reject_business_id').value = businessId;
        document.getElementById('rejectBusinessName').textContent = businessName;
        openModal('rejectModal');
    }

    function populateRevisionModal(businessId, businessName, documents) {
        document.getElementById('revision_business_id').value = businessId;
        document.getElementById('revisionBusinessName').textContent = businessName;
        
        const docList = document.getElementById('documentList');
        docList.innerHTML = '';
        
        if (documents && documents.length > 0) {
            documents.forEach(doc => {
                const html = `
                    <div class="border-b pb-2">
                        <div class="flex items-center justify-between">
                            <label class="font-medium text-sm text-gray-700">${doc.type}</label>
                            <select name="doc_status[${doc.id}]" class="text-sm border-gray-300 rounded" onchange="this.nextElementSibling.classList.toggle('hidden', this.value !== 'rejected')">
                                <option value="pending">OK</option>
                                <option value="rejected">Reject / Request Revision</option>
                            </select>
                        </div>
                        <textarea name="doc_feedback[${doc.id}]" placeholder="Reason for rejection..." class="mt-2 w-full text-sm border-gray-300 rounded hidden" rows="2"></textarea>
                    </div>
                `;
                docList.insertAdjacentHTML('beforeend', html);
            });
        } else {
            docList.innerHTML = '<p class="text-center text-gray-500">No documents found for this application.</p>';
        }
    }
</script>

</body>
</html>
