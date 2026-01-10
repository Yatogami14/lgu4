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
            $new_status = 'active';
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
                    $message = "Business application has been successfully " . ($new_status === 'active' ? 'approved.' : 'rejected.');
                    $messageType = 'success';

                    // Create a notification for the business owner
                    $business_name = $business_data['business_name'];
                    $status_text = ($new_status === 'active') ? 'approved' : 'rejected';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
        /* Custom scrollbar for table */
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 min-h-screen">

    <!-- Navigation (Simplified for this page) -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img class="h-8 w-auto rounded-full" src="../logo/logo.jpeg" alt="Logo">
                        <span class="ml-3 text-xl font-bold text-gray-900">Super Admin Panel</span>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="index.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <a href="#" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Verify Businesses
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="../logout.php" class="text-gray-500 hover:text-gray-700 p-2 rounded-md hover:bg-gray-100 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 animate-fade-in">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Business Verification</h1>
                <p class="mt-1 text-sm text-gray-500">Review and approve pending business registrations.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <!-- Stats or Actions could go here -->
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 rounded-md p-4 <?php echo $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-700' : 'bg-red-50 border-l-4 border-red-500 text-red-700'; ?> shadow-sm animate-fade-in">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium"><?php echo htmlspecialchars($message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6 animate-slide-up">
            <form method="GET" action="" class="flex flex-col md:flex-row gap-4 items-end md:items-center">
                <div class="w-full md:w-1/3">
                    <label for="search" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" placeholder="Name, business, email..." value="<?php echo htmlspecialchars($search); ?>" 
                            class="pl-10 block w-full rounded-lg border-gray-300 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 sm:text-sm py-2.5 transition-shadow">
                    </div>
                </div>
                <div class="w-full md:w-1/4">
                    <label for="filter_type" class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Business Type</label>
                    <select name="filter_type" id="filter_type" class="block w-full rounded-lg border-gray-300 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 sm:text-sm py-2.5 transition-shadow">
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
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                    <a href="verify_business_owners.php" class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden animate-slide-up" style="animation-delay: 0.1s;">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo getSortLink('business_name', $sort_by, $sort_order, $search, $filter_type); ?>" class="group flex items-center hover:text-gray-700">
                                    Business Name
                                    <span class="ml-2 flex-none rounded text-gray-400 group-hover:visible group-focus:visible">
                                        <?php if ($sort_by == 'business_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; else echo '<i class="fas fa-sort text-gray-300"></i>'; ?>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo getSortLink('owner_name', $sort_by, $sort_order, $search, $filter_type); ?>" class="group flex items-center hover:text-gray-700">
                                    Applicant
                                    <span class="ml-2 flex-none rounded text-gray-400 group-hover:visible group-focus:visible">
                                        <?php if ($sort_by == 'owner_name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; else echo '<i class="fas fa-sort text-gray-300"></i>'; ?>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="<?php echo getSortLink('created_at', $sort_by, $sort_order, $search, $filter_type); ?>" class="group flex items-center hover:text-gray-700">
                                    Submitted
                                    <span class="ml-2 flex-none rounded text-gray-400 group-hover:visible group-focus:visible">
                                        <?php if ($sort_by == 'created_at') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; else echo '<i class="fas fa-sort text-gray-300"></i>'; ?>
                                    </span>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($pendingUsers) > 0): ?>
                            <?php foreach ($pendingUsers as $user): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150" data-details='<?php echo htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8'); ?>'>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg">
                                                <?php echo strtoupper(substr($user['business_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['business_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['registration_number']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['owner_name'] ?? 'N/A'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['owner_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            <?php echo htmlspecialchars($user['business_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        <br>
                                        <span class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($user['created_at'])); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <button type="button" onclick="openDetailsModal(this)" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-md transition-colors">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this application?');" class="inline-block">
                                                <input type="hidden" name="business_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="text-green-600 hover:text-green-900 bg-green-50 hover:bg-green-100 px-3 py-1.5 rounded-md transition-colors">
                                                    <i class="fas fa-check mr-1"></i> Approve
                                                </button>
                                            </form>
                                            <button type="button" onclick="openRejectModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['business_name'])); ?>')" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-md transition-colors">
                                                <i class="fas fa-times mr-1"></i> Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="h-12 w-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-inbox text-gray-400 text-xl"></i>
                                        </div>
                                        <p class="text-base font-medium">No pending applications found.</p>
                                        <p class="text-sm mt-1">Try adjusting your search or filters.</p>
                                    </div>
                                </td>
                            </tr>
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
                                <i class="fas fa-chevron-left h-5 w-5"></i>
                            </a>
                            
                            <?php 
                            // Simple pagination logic for display
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            
                            if($start > 1) echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';

                            for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?php echo getPageLink($i, $search, $filter_type, $sort_by, $sort_order); ?>" aria-current="page" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; 
                            
                            if($end < $total_pages) echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                            ?>

                            <a href="<?php echo getPageLink(min($total_pages, $page + 1), $search, $filter_type, $sort_by, $sort_order); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right h-5 w-5"></i>
                            </a>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal('detailsModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-4">
                                <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">Application Details</h3>
                                <button type="button" onclick="closeModal('detailsModal')" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                                    <span class="sr-only">Close</span>
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div class="mt-2" id="detailsModalContent">
                                <!-- Content injected by JS -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal('detailsModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true" onclick="closeModal('rejectModal')"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Reject Application</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to reject the application for <span id="rejectBusinessName" class="font-bold text-gray-800"></span>?
                                </p>
                                <form id="rejectForm" method="POST" class="mt-4">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="business_id" id="reject_business_id">
                                    
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection</label>
                                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required 
                                        class="shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border-gray-300 rounded-md" 
                                        placeholder="Please provide a clear reason for the applicant..."></textarea>
                                    <p class="mt-1 text-xs text-gray-500">This reason will be sent to the applicant.</p>
                                    
                                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm">
                                            Confirm Rejection
                                        </button>
                                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:col-start-1 sm:text-sm" onclick="closeModal('rejectModal')">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
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
            
            let documentsHtml = '<div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500 text-sm">No documents uploaded.</div>';
            if (doc_names.length > 0 && doc_names[0]) {
                documentsHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">';
                doc_names.forEach((name, index) => {
                    const docTypeFormatted = doc_types[index].replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    documentsHtml += `
                        <a href="<?php echo $base_path; ?>/${doc_paths[index]}" target="_blank" class="group flex items-center p-3 border border-gray-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-all">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="ml-3 overflow-hidden">
                                <p class="text-sm font-medium text-gray-900 group-hover:text-blue-700">${docTypeFormatted}</p>
                                <p class="text-xs text-gray-500 truncate">${name}</p>
                            </div>
                            <div class="ml-auto">
                                <i class="fas fa-external-link-alt text-gray-400 group-hover:text-blue-500"></i>
                            </div>
                        </a>`;
                });
                documentsHtml += '</div>';
            }

            modalContent.innerHTML = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <h4 class="font-bold text-gray-800 border-b border-gray-200 pb-2 mb-3 flex items-center">
                            <i class="fas fa-store text-blue-500 mr-2"></i> Business Information
                        </h4>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Business Name</dt><dd class="font-medium text-gray-900 text-right">${details.business_name}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Business Type</dt><dd class="font-medium text-gray-900 text-right">${details.business_type}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Registration No.</dt><dd class="font-medium text-gray-900 text-right">${details.registration_number}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Address</dt><dd class="font-medium text-gray-900 text-right">${details.address}</dd></div>
                        </dl>
                    </div>
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <h4 class="font-bold text-gray-800 border-b border-gray-200 pb-2 mb-3 flex items-center">
                            <i class="fas fa-user text-green-500 mr-2"></i> Applicant Details
                        </h4>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Applicant Name</dt><dd class="font-medium text-gray-900 text-right">${details.owner_name}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Applicant Email</dt><dd class="font-medium text-gray-900 text-right">${details.owner_email}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Contact Email</dt><dd class="font-medium text-gray-900 text-right">${details.contact_email}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500">Contact Phone</dt><dd class="font-medium text-gray-900 text-right">${details.contact_number}</dd></div>
                        </dl>
                    </div>
                    <div class="lg:col-span-2">
                         <h4 class="font-bold text-gray-800 border-b border-gray-200 pb-2 mb-3 flex items-center">
                            <i class="fas fa-folder-open text-yellow-500 mr-2"></i> Uploaded Documents
                         </h4>
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
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>

</body>
</html>