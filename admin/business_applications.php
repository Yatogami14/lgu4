<?php
// Start session and include necessary configurations
require_once '../utils/session_manager.php';
require_once '../utils/access_control.php';
require_once '../config/database.php';
require_once '../models/Business.php';
require_once '../models/BusinessDocument.php';
require_once '../models/Notification.php';
require_once '../models/User.php';

// Ensure user has the required permission
requirePermission('manage_applications');

$database = new Database();
$db = $database->getConnection();
$businessModel = new Business($database);
$notificationModel = new Notification($database);
$userModel = new User($database);

$message = '';
$messageType = '';

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($business_id && $action) {
        $new_status = '';
        $new_user_status = '';
        $rejection_reason = null;
        if ($action === 'approve') {
            $new_status = 'active';
            $new_user_status = 'active';
        } elseif ($action === 'reject') {
            $new_status = 'rejected';
            $new_user_status = 'active';
            $rejection_reason = $_POST['rejection_reason'] ?? 'No reason provided.';
        } elseif ($action === 'request_revision') {
            $new_status = 'needs_revision';
            $new_user_status = 'active';

            // Handle individual document feedback
            if (isset($_POST['doc_status']) && is_array($_POST['doc_status'])) {
                $docModel = new BusinessDocument($database);
                foreach ($_POST['doc_status'] as $doc_id => $status) {
                    $feedback = $_POST['doc_feedback'][$doc_id] ?? '';
                    if ($status === 'rejected') {
                        $docModel->updateStatus($doc_id, 'rejected', $feedback);
                    } else {
                        $docModel->updateStatus($doc_id, 'pending', null);
                    }
                }
            }
        }

        if ($new_status) {
            try {
                $business_data = $businessModel->findById($business_id);

                if ($business_data) {
                    $owner_id = $business_data['user_id'];
                    if ($businessModel->updateStatus($business_id, $new_status, $rejection_reason ?? null) && $userModel->updateStatus($owner_id, $new_user_status)) {
                        $message = "Business application has been successfully " . ($new_status === 'active' ? 'approved.' : ($new_status === 'rejected' ? 'rejected.' : 'processed.'));
                        $messageType = 'success';

                        $business_name = $business_data['business_name'];
                        $status_text = ($new_status === 'active') ? 'approved' : 'rejected';
                        if ($new_status === 'needs_revision') $status_text = 'flagged for revision';

                        $notification_message = "Your business application for \"{$business_name}\" has been {$status_text}.";

                        if ($new_status === 'rejected' && !empty($rejection_reason)) {
                            $notification_message .= " Reason: " . $rejection_reason;
                        } elseif ($new_status === 'needs_revision') {
                            $notification_message .= " Please check your documents and re-upload the requested files.";
                        }

                        $link = '/lgu4/business/index.php';

                        $notificationModel->create($owner_id, $notification_message, 'info', 'business', $business_id, $link);
                    } else {
                        $message = "Failed to update status. Please check logs.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "Failed to find business data.";
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = "An error occurred: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Fetch Pending Business Applications with Filtering, Sorting, and Pagination
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$sortable_columns = ['business_name', 'owner_name', 'created_at'];
if (!in_array($sort_by, $sortable_columns)) {
    $sort_by = 'created_at';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

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

$total_query = "SELECT COUNT(DISTINCT b.id) as total " . $base_query;
$total_stmt = $db->prepare($total_query);
$total_stmt->execute($params);
$total_records = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

$query = "SELECT b.id, b.name as business_name, b.address, b.business_type, b.registration_number, b.email as contact_email, b.contact_number, b.created_at, u.name as owner_name, u.email as owner_email,
           GROUP_CONCAT(bd.file_name ORDER BY bd.id SEPARATOR '||') as document_names,
           GROUP_CONCAT(bd.file_path ORDER BY bd.id SEPARATOR '||') as document_paths,
           GROUP_CONCAT(bd.document_type ORDER BY bd.id SEPARATOR '||') as document_types,
           GROUP_CONCAT(bd.id ORDER BY bd.id SEPARATOR '||') as document_ids,
           COUNT(bd.id) as document_count
           " . $base_query . "
           GROUP BY b.id
           ORDER BY $sort_by $sort_order
           LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', (int) $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
if (!empty($search)) $stmt->bindValue(':search', '%' . $search . '%');
if (!empty($filter_type)) $stmt->bindValue(':filter_type', $filter_type);

$stmt->execute();
$pending_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getSortLink($column, $current_sort, $current_order, $current_search, $current_filter_type) {
    $order = ($current_sort === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
    $queryParams = ['sort_by' => $column, 'sort_order' => $order, 'search' => $current_search, 'filter_type' => $current_filter_type];
    return '?' . http_build_query(array_filter($queryParams));
}

function getPageLink($page, $current_search, $current_filter_type, $current_sort, $current_order) {
    $queryParams = ['page' => $page, 'search' => $current_search, 'filter_type' => $current_filter_type, 'sort_by' => $current_sort, 'sort_order' => $current_order];
    return '?' . http_build_query(array_filter($queryParams));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Applications Review</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': {
                            '50': '#F2F9F9',
                            '100': '#E0F2F1',
                            '200': '#B2DFDB',
                            '300': '#80CBC4',
                            '400': '#4DB6AC',
                            '500': '#009688',
                            '600': '#00897B',
                            '700': '#00796B',
                            '800': '#00695C',
                            '900': '#004D40',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">

    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:ml-64 md:pt-24">
        
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Business Applications Review</h1>
                <p class="mt-1 text-sm text-gray-500">Review pending business registrations and documents.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-brand-100 text-brand-800">
                    <?php echo $total_records; ?> Pending
                </span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 rounded-lg p-4 <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> shadow-sm flex items-center animate-fade-in">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-lg"></i>
                <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label for="search" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" placeholder="Business, applicant, or email..." value="<?php echo htmlspecialchars($search); ?>" 
                            class="pl-10 block w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500 sm:text-sm py-2 transition-shadow">
                    </div>
                </div>
                <div class="md:col-span-4">
                    <label for="filter_type" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Business Type</label>
                    <select name="filter_type" id="filter_type" class="block w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500 sm:text-sm py-2 transition-shadow">
                        <option value="">All Types</option>
                        <?php 
                        $types = ['Restaurant', 'Food Establishment', 'Hotel', 'Hospital', 'School', 'Factory', 'Other'];
                        foreach($types as $type) {
                            $selected = ($filter_type == $type) ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all">
                        Filter
                    </button>
                    <a href="business_applications.php" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo getSortLink('business_name', $sort_by, $sort_order, $search, $filter_type); ?>" class="group flex items-center hover:text-brand-600 transition-colors">
                                    Business
                                    <span class="ml-2 flex-none rounded text-gray-400 group-hover:text-brand-500">
                                        <?php if ($sort_by == 'business_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; else echo '<i class="fas fa-sort text-gray-300"></i>'; ?>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo getSortLink('owner_name', $sort_by, $sort_order, $search, $filter_type); ?>" class="group flex items-center hover:text-brand-600 transition-colors">
                                    Applicant
                                    <span class="ml-2 flex-none rounded text-gray-400 group-hover:text-brand-500">
                                        <?php if ($sort_by == 'owner_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; else echo '<i class="fas fa-sort text-gray-300"></i>'; ?>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo getSortLink('created_at', $sort_by, $sort_order, $search, $filter_type); ?>" class="group flex items-center hover:text-brand-600 transition-colors">
                                    Submitted
                                    <span class="ml-2 flex-none rounded text-gray-400 group-hover:text-brand-500">
                                        <?php if ($sort_by == 'created_at') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; else echo '<i class="fas fa-sort text-gray-300"></i>'; ?>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($pending_businesses)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="fas fa-clipboard-check text-gray-400 text-3xl"></i>
                                        </div>
                                        <p class="text-lg font-medium text-gray-900">No pending applications</p>
                                        <p class="text-sm mt-1">Great job! You're all caught up.</p>
                                    </div>
                                </td>
                            </tr>
            <?php else: ?>
                <?php foreach ($pending_businesses as $business): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150" data-details='<?php echo htmlspecialchars(json_encode($business), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-brand-100 rounded-lg flex items-center justify-center text-brand-600 font-bold text-lg border border-brand-200">
                                            <?php echo strtoupper(substr($business['business_name'], 0, 1)); ?>
                                        </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($business['business_name']); ?></div>
                                                <div class="text-xs text-gray-500">Reg: <?php echo htmlspecialchars($business['registration_number']); ?></div>
                                        </div>
                                    </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($business['owner_name'] ?? 'N/A'); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($business['owner_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 inline-flex text-xs font-medium rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                            <?php echo htmlspecialchars($business['business_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex flex-col">
                                            <span><?php echo date('M j, Y', strtotime($business['created_at'])); ?></span>
                                            <span class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($business['created_at'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <button type="button" onclick="openDetailsModal(this)" class="text-brand-600 hover:text-brand-900 bg-brand-50 hover:bg-brand-100 border border-brand-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="View Details">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>
                                            <div class="h-4 w-px bg-gray-300 mx-1"></div>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="business_id" value="<?php echo $business['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="button" onclick="openApproveModal(this.form, '<?php echo htmlspecialchars(addslashes($business['business_name'])); ?>')" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 border border-green-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <button type="button" onclick="openRejectModal(<?php echo $business['id']; ?>, '<?php echo htmlspecialchars(addslashes($business['business_name'])); ?>')" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button type="button" onclick="openRevisionModal(this)" class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 border border-yellow-200 px-3 py-1.5 rounded-md transition-all shadow-sm" title="Request Revision">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                                    </td>
                                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="bg-white px-4 py-3 border-t border-gray-200 flex items-center justify-between sm:px-6">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <a href="<?php echo getPageLink(max(1, $page - 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left h-4 w-4"></i>
                            </a>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if($start > 1) echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';

                            for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?php echo getPageLink($i, $search, $filter_type, $sort_by, $sort_order); ?>" aria-current="page" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo ($i == $page) ? 'z-10 bg-brand-50 border-brand-500 text-brand-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; 
                            
                            if($end < $total_pages) echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                            ?>

                            <a href="<?php echo getPageLink(min($total_pages, $page + 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right h-4 w-4"></i>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal('rejectModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-red-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white" id="modal-title">Reject Application</h3>
                    <button type="button" onclick="closeModal('rejectModal')" class="text-white hover:text-red-100 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="text-center mb-4">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-times text-red-600 text-xl"></i>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">
                            Are you sure you want to reject the application for <span id="rejectBusinessName" class="font-bold text-gray-900"></span>?
                        </p>
                    </div>
                    <form id="rejectForm" method="POST">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="business_id" id="reject_business_id">
                        
                        <div class="mb-4 text-left">
                            <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                            <textarea name="rejection_reason" id="rejection_reason" rows="4" required 
                                class="shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border-gray-300 rounded-lg" 
                                placeholder="Please provide a clear reason for the applicant..."></textarea>
                        </div>
                        
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm transition-colors">
                                Confirm Rejection
                            </button>
                            <button type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:col-start-1 sm:text-sm transition-colors" onclick="closeModal('rejectModal')">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div id="revisionModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal('revisionModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-yellow-500 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white" id="modal-title">Request Revision</h3>
                    <button type="button" onclick="closeModal('revisionModal')" class="text-white hover:text-yellow-100 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Select documents that need revision for <span id="revisionBusinessName" class="font-bold text-gray-900"></span>:</p>
                    </div>
                    
                    <form id="revisionForm" method="POST">
                        <input type="hidden" name="action" value="request_revision">
                        <input type="hidden" name="business_id" id="revision_business_id">
                        
                        <div id="documentList" class="space-y-3 max-h-60 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50 mb-4 custom-scrollbar">
                            <!-- Documents will be populated here by JS -->
                        </div>
                        
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-yellow-500 text-base font-medium text-white hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:col-start-2 sm:text-sm transition-colors">
                                Send Request
                            </button>
                            <button type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:col-start-1 sm:text-sm transition-colors" onclick="closeModal('revisionModal')">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div id="documentViewerModal" class="hidden fixed inset-0 z-[60] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 60;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity backdrop-blur-md" aria-hidden="true" onclick="closeModal('documentViewerModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full h-[90vh] flex flex-col">
                <div class="bg-gray-800 px-6 py-4 flex justify-between items-center flex-shrink-0 border-b border-gray-700">
                    <h3 class="text-lg font-bold text-white flex items-center" id="documentViewerTitle">
                        <i class="fas fa-eye mr-2 text-brand-400"></i> Document Viewer
                    </h3>
                    <div class="flex items-center space-x-4">
                        <!-- Zoom Controls -->
                        <div id="zoomControls" class="hidden flex items-center space-x-2 mr-4 border-r border-gray-600 pr-4">
                            <button type="button" onclick="zoomImage(-0.25)" class="text-gray-400 hover:text-white transition-colors p-1.5 rounded hover:bg-gray-700" title="Zoom Out">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span id="zoomLevelDisplay" class="text-gray-300 text-xs w-12 text-center font-mono bg-gray-900 py-1 rounded">100%</span>
                            <button type="button" onclick="zoomImage(0.25)" class="text-gray-400 hover:text-white transition-colors p-1.5 rounded hover:bg-gray-700" title="Zoom In">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button type="button" onclick="resetZoom()" class="text-gray-400 hover:text-white transition-colors p-1.5 rounded hover:bg-gray-700 ml-1" title="Reset Zoom">
                                <i class="fas fa-compress"></i>
                            </button>
                        </div>
                        <a id="documentDownloadBtn" href="#" download target="_blank" class="text-gray-400 hover:text-brand-400 transition-colors p-1.5 rounded hover:bg-gray-700" title="Download Document">
                            <i class="fas fa-download text-lg"></i>
                        </a>
                        <button type="button" onclick="closeModal('documentViewerModal')" class="text-gray-400 hover:text-white focus:outline-none transition-colors p-1.5 rounded hover:bg-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <div class="flex-grow bg-gray-900 p-4 overflow-hidden relative flex items-center justify-center" id="documentViewerContent">
                    <!-- Content injected by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal('approveModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-green-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white" id="modal-title">Approve Application</h3>
                    <button type="button" onclick="closeModal('approveModal')" class="text-white hover:text-green-100 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="text-center mb-4">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                            <i class="fas fa-check text-green-600 text-xl"></i>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">
                            Are you sure you want to approve the application for <span id="approveBusinessName" class="font-bold text-gray-900"></span>?
                        </p>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" onclick="confirmApprove()" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm transition-colors">
                            Confirm Approval
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:col-start-1 sm:text-sm transition-colors" onclick="closeModal('approveModal')">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal('detailsModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-brand-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-white" id="modal-title">Business Application Details</h3>
                    <button type="button" onclick="closeModal('detailsModal')" class="text-white hover:text-brand-100 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="bg-white px-6 pt-6 pb-6">
                    <div id="detailsModalContent">
                        <!-- Content will be populated by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openDocumentViewer(filePath, fileTitle, event) {
            if(event) event.preventDefault();
            const viewerContent = document.getElementById('documentViewerContent');
            const viewerTitle = document.getElementById('documentViewerTitle');
            const downloadBtn = document.getElementById('documentDownloadBtn');
            const zoomControls = document.getElementById('zoomControls');
            
            // Reset zoom state
            currentZoom = 1;
            const zoomDisplay = document.getElementById('zoomLevelDisplay');
            if(zoomDisplay) zoomDisplay.textContent = '100%';
            
            viewerTitle.innerHTML = `<i class="fas fa-eye mr-2 text-brand-400"></i> ${fileTitle}`;
            if (downloadBtn) {
                downloadBtn.href = filePath;
            }

            const cleanPath = filePath.split('?')[0].split('#')[0];
            const fileExtension = cleanPath.split('.').pop().toLowerCase();

            if (fileExtension === 'pdf') {
                if(zoomControls) zoomControls.classList.add('hidden');
                viewerContent.classList.add('overflow-hidden');
                viewerContent.classList.remove('overflow-auto');
                viewerContent.innerHTML = `<iframe src="${filePath}" class="w-full h-full border-0 rounded-lg shadow-lg" frameborder="0"></iframe>`;
            } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExtension)) {
                if(zoomControls) zoomControls.classList.remove('hidden');
                viewerContent.classList.remove('overflow-hidden');
                viewerContent.classList.add('overflow-auto');
                
                // Show loading state
                viewerContent.innerHTML = '<div class="flex justify-center items-center h-full"><i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i></div>';

                const img = new Image();
                img.id = 'viewerImage';
                img.src = filePath;
                img.className = 'max-w-full max-h-full object-contain rounded-lg shadow-2xl transition-transform duration-200';
                
                img.onload = function() {
                    viewerContent.innerHTML = `<div class="flex justify-center items-center min-h-full p-4"></div>`;
                    viewerContent.querySelector('div').appendChild(img);
                };
                
                img.onerror = function() {
                    viewerContent.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-gray-500"><i class="fas fa-exclamation-triangle text-4xl mb-4 text-red-500"></i><p class="text-lg font-medium">Failed to load image</p><p class="text-sm mt-2">Path: ${filePath}</p><a href="${filePath}" target="_blank" class="text-brand-600 hover:underline mt-4 font-medium">Try direct link</a></div>`;
                    if(zoomControls) zoomControls.classList.add('hidden');
                };
            } else {
                if(zoomControls) zoomControls.classList.add('hidden');
                viewerContent.classList.add('overflow-hidden');
                viewerContent.classList.remove('overflow-auto');
                viewerContent.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <div class="bg-gray-800 p-6 rounded-full mb-4">
                        <i class="fas fa-file-download text-5xl text-brand-400"></i>
                    </div>
                    <p class="text-lg font-medium text-white mb-2">Preview not available</p>
                    <p class="text-sm mb-6">This file type cannot be previewed directly.</p>
                    <a href="${filePath}" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 transition-colors">
                        <i class="fas fa-download mr-2"></i> Download File
                    </a>
                </div>`;
            }

            openModal('documentViewerModal');
        }

    function openDetailsModal(button) {
        const row = button.closest('tr');
        const details = JSON.parse(row.dataset.details);
        const modalContent = document.getElementById('detailsModalContent');

        const doc_names = details.document_names ? details.document_names.split('||') : [];
        const doc_paths = details.document_paths ? details.document_paths.split('||') : [];
        const doc_types = details.document_types ? details.document_types.split('||') : [];
        
        let documentsHtml = '<div class="bg-gray-50 rounded-lg p-8 text-center text-gray-500 text-sm border border-dashed border-gray-300">No documents uploaded.</div>';
        if (doc_names.length > 0 && doc_names[0]) {
            documentsHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">';
            doc_names.forEach((name, index) => {
                let docTypeFormatted = 'Document';
                if (doc_types[index]) {
                    if (doc_types[index] === 'mayors_permit') {
                        docTypeFormatted = "Mayor's Permit";
                    } else {
                        docTypeFormatted = doc_types[index].replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                }
                let rawPath = doc_paths[index] ? doc_paths[index].replace(/\\/g, '/') : '';
                // Clean path to ensure it's relative to root (remove leading ../ or /)
                rawPath = rawPath.replace(/^(\.\.\/|\.\/|\/)+/, '');
                let filePath = '../' + rawPath;

                const safeFilePath = filePath.replace(/'/g, "\\'");
                const safeDocType = docTypeFormatted.replace(/'/g, "\\'");
                documentsHtml += `
                    <a href="#" onclick="openDocumentViewer('${safeFilePath}', '${safeDocType}', event)" class="group flex items-center p-4 border border-gray-200 rounded-xl hover:border-brand-400 hover:bg-brand-50 hover:shadow-md transition-all duration-200 bg-white">
                        <div class="flex-shrink-0 h-12 w-12 bg-brand-100 rounded-lg flex items-center justify-center text-brand-600 group-hover:bg-brand-200 transition-colors">
                            <i class="fas fa-file-alt text-xl"></i>
                        </div>
                        <div class="ml-4 overflow-hidden flex-1">
                            <p class="text-sm font-bold text-gray-900 group-hover:text-brand-700 truncate" title="${docTypeFormatted}">${docTypeFormatted}</p>
                            <p class="text-xs text-gray-500 truncate mt-0.5" title="${name}">${name}</p>
                        </div>
                        <div class="ml-2">
                            <i class="fas fa-external-link-alt text-gray-300 group-hover:text-brand-500 transition-colors"></i>
                        </div>
                    </a>`;
            });
            documentsHtml += '</div>';
        }

        modalContent.innerHTML = `
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center text-lg">
                        <div class="bg-brand-100 p-2 rounded-lg mr-3 text-brand-600"><i class="fas fa-store"></i></div> Business Info
                    </h4>
                    <dl class="space-y-4 text-sm">
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Business Name</dt><dd class="font-semibold text-gray-900 text-right">${details.business_name}</dd></div>
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Business Type</dt><dd class="font-medium text-gray-900 text-right bg-gray-100 px-2 py-1 rounded">${details.business_type}</dd></div>
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Registration No.</dt><dd class="font-medium text-gray-900 text-right font-mono">${details.registration_number}</dd></div>
                        <div class="flex justify-between items-start"><dt class="text-gray-500 whitespace-nowrap mr-4">Address</dt><dd class="font-medium text-gray-900 text-right">${details.address}</dd></div>
                    </dl>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                    <h4 class="font-bold text-gray-800 border-b border-gray-100 pb-3 mb-4 flex items-center text-lg">
                        <div class="bg-green-100 p-2 rounded-lg mr-3 text-green-600"><i class="fas fa-user"></i></div> Applicant Details
                    </h4>
                    <dl class="space-y-4 text-sm">
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Applicant Name</dt><dd class="font-semibold text-gray-900 text-right">${details.owner_name}</dd></div>
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Applicant Email</dt><dd class="font-medium text-gray-900 text-right">${details.owner_email}</dd></div>
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Contact Email</dt><dd class="font-medium text-gray-900 text-right">${details.contact_email}</dd></div>
                        <div class="flex justify-between items-center"><dt class="text-gray-500">Contact Phone</dt><dd class="font-medium text-gray-900 text-right">${details.contact_number}</dd></div>
                    </dl>
                </div>
                <div class="lg:col-span-2 mt-2">
                     <h4 class="font-bold text-gray-800 mb-4 flex items-center text-lg">
                        <div class="bg-yellow-100 p-2 rounded-lg mr-3 text-yellow-600"><i class="fas fa-folder-open"></i></div> Uploaded Documents
                     </h4>
                     ${documentsHtml}
                </div>
            </div>
        `;

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

    function openModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
        }
        if (modalId === 'documentViewerModal') {
            const viewerContent = document.getElementById('documentViewerContent');
            if (viewerContent) viewerContent.innerHTML = '';
        }
    }

    function openRejectModal(businessId, businessName) {
        document.getElementById('reject_business_id').value = businessId;
        document.getElementById('rejectBusinessName').textContent = businessName;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    let formToApprove = null;
    function openApproveModal(form, businessName) {
        formToApprove = form;
        document.getElementById('approveBusinessName').textContent = businessName;
        document.getElementById('approveModal').classList.remove('hidden');
    }

    function confirmApprove() {
        if (formToApprove) {
            formToApprove.submit();
        }
    }

    function populateRevisionModal(businessId, businessName, documents) {
        document.getElementById('revision_business_id').value = businessId;
        document.getElementById('revisionBusinessName').textContent = businessName;
        
        const docList = document.getElementById('documentList');
        docList.innerHTML = '';
        
        if (documents && documents.length > 0) {
            const documents = [];
            documents.forEach(doc => {
                const html = `
                    <div class="bg-white border border-gray-200 rounded-lg p-3 mb-3 shadow-sm hover:border-yellow-300 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded bg-gray-100 flex items-center justify-center text-gray-500 mr-3">
                                    <i class="fas fa-file"></i>
                                </div>
                                <label class="font-medium text-sm text-gray-800">${doc.type}</label>
                            </div>
                            <select name="doc_status[${doc.id}]" class="text-xs border-gray-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500 py-1 pl-2 pr-8" onchange="this.closest('.bg-white').querySelector('textarea').classList.toggle('hidden', this.value !== 'rejected')">
                                <option value="pending">Approved</option>
                                <option value="rejected">Needs Revision</option>
                            </select>
                        </div>
                        <textarea name="doc_feedback[${doc.id}]" placeholder="Please explain what needs to be corrected..." class="w-full text-sm border-gray-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500 hidden p-2 bg-yellow-50" rows="2"></textarea>
                    </div>
                `;
                docList.insertAdjacentHTML('beforeend', html);
            });
        } else {
            docList.innerHTML = '<div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">No documents found for this application.</div>';
        }
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('fixed') && event.target.hasAttribute('aria-hidden')) {
            const modal = event.target.closest('[role="dialog"]');
            if (modal) {
                closeModal(modal.id);
            }
        }
    }

    let currentZoom = 1;

    function zoomImage(delta) {
        const img = document.getElementById('viewerImage');
        if (!img) return;
        
        // Initialize base dimensions if not set (first zoom action)
        if (!img.dataset.baseWidth) {
            img.dataset.baseWidth = img.clientWidth;
            // Remove max constraints to allow growing beyond container
            img.style.maxWidth = 'none';
            img.style.maxHeight = 'none';
            img.style.width = img.dataset.baseWidth + 'px';
        }
        
        currentZoom += delta;
        // Clamp zoom level between 25% and 500%
        if (currentZoom < 0.25) currentZoom = 0.25;
        if (currentZoom > 5) currentZoom = 5;
        
        img.style.width = (parseFloat(img.dataset.baseWidth) * currentZoom) + 'px';
        document.getElementById('zoomLevelDisplay').textContent = Math.round(currentZoom * 100) + '%';
    }

    function resetZoom() {
        const img = document.getElementById('viewerImage');
        if (!img) return;
        
        currentZoom = 1;
        // Reset styles to let CSS handle "fit to screen"
        img.style.maxWidth = '';
        img.style.maxHeight = '';
        img.style.width = '';
        delete img.dataset.baseWidth;
        
        document.getElementById('zoomLevelDisplay').textContent = '100%';
    }
    </script>
</body>
</html>
