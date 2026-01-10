<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';
require_once '../utils/logger.php'; // Include logger

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Check user management permission
requirePermission('user_management', 'index.php');

$database = new Database();
$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $newUser = new User($database);
    $newUser->name = $_POST['name'];
    $newUser->email = $_POST['email'];
    $newUser->password = $_POST['password'];
    $newUser->role = $_POST['role'];
    $newUser->department = $_POST['department'];
    $newUser->certification = $_POST['certification'];
    
    if ($newUser->create()) {
        $_SESSION['success_message'] = 'User created successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to create user. Email may already exist.';
        logError("Failed to create user: " . $newUser->email); // Log error
    }
    
    header('Location: user_management.php');
    exit;
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $updateUser = new User($database);
    $updateUser->id = $_POST['user_id'];
    $updateUser->name = $_POST['name'];
    $updateUser->email = $_POST['email'];
    $updateUser->role = $_POST['role'];
    $updateUser->department = $_POST['department'];
    $updateUser->certification = $_POST['certification'];
    
    if ($updateUser->update()) {
        $_SESSION['success_message'] = 'User updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update user.';
        logError("Failed to update user ID: " . $updateUser->id); // Log error
    }
    
    header('Location: user_management.php');
    exit;
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $deleteUser = new User($database);
    $deleteUser->id = $_POST['user_id'];
    
    if ($deleteUser->delete()) {
        $_SESSION['success_message'] = 'User deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete user.';
        logError("Failed to delete user ID: " . $deleteUser->id); // Log error
    }
    
    header('Location: user_management.php');
    exit;
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_new_password']) {
        $userForPasswordUpdate = new User($database);
        
        if ($userForPasswordUpdate->updatePassword($_POST['user_id'], $_POST['new_password'])) {
            $_SESSION['success_message'] = 'Password updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update password.';
            logError("Failed to update password for user ID: " . $_POST['user_id']);
        }
    } else {
        $_SESSION['error_message'] = 'Passwords do not match or are empty.';
    }
    
    header('Location: user_management.php');
    exit;
}

// Get filters from URL
$filter_role = $_GET['role'] ?? 'all';
$search_keywords = $_GET['search'] ?? '';

// Get users based on filters
if (!empty($search_keywords)) {
    $users = $user->search($search_keywords);
} elseif ($filter_role !== 'all') {
    $users = $user->readAll($filter_role);
} else {
    $users = $user->readAll();
}
$userRoleCounts = $user->getUserCountByRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <p><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r shadow-sm animate-fade-in">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                <p class="text-sm text-gray-600 mt-1">Manage system users and roles</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterUsers()" placeholder="Search users..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
                <button onclick="openUserModal('create')" class="bg-brand-600 text-white px-4 py-2 rounded-lg hover:bg-brand-700 transition-colors shadow-sm flex items-center justify-center text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Add User
                </button>
            </div>
        </div>

        <!-- User Roles Chart -->
        <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold mb-4">User Distribution by Role</h3>
            <div class="max-w-sm mx-auto">
                <canvas id="userRolesChart"></canvas>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $userRow): ?>
                    <tr class="hover:bg-gray-50 transition-colors"
                        data-id="<?php echo htmlspecialchars($userRow['id']); ?>"
                        data-name="<?php echo htmlspecialchars($userRow['name']); ?>"
                        data-email="<?php echo htmlspecialchars($userRow['email']); ?>"
                        data-role="<?php echo htmlspecialchars($userRow['role']); ?>"
                        data-status="<?php echo htmlspecialchars($userRow['status']); ?>"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                    <?php echo substr($userRow['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userRow['name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <?php echo htmlspecialchars($userRow['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <?php echo ucwords(str_replace('_', ' ', $userRow['role'])) ?: 'N/A'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $userRow['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                       ($userRow['status'] === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo ucwords(str_replace('_', ' ', $userRow['status'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="editUser(<?php echo $userRow['id']; ?>)" class="text-brand-600 hover:text-brand-900 mr-3 transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openResetPasswordModal(<?php echo $userRow['id']; ?>, '<?php echo addslashes($userRow['name']); ?>')" class="text-yellow-600 hover:text-yellow-800 mr-3 transition-colors" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <button onclick="deleteUser(<?php echo $userRow['id']; ?>, '<?php echo addslashes($userRow['name']); ?>')" 
                                    class="text-red-500 hover:text-red-700 transition-colors" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($users)): ?>
                <div class="p-12 text-center text-gray-500">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-users text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No users found</h3>
                    <p class="mt-1 text-sm">Try adjusting your search or add a new user.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Modal (Create/Edit) -->
    <div id="userModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-md shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold text-white">Add New User</h3>
                <button onclick="closeModal('userModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="create_user" id="create_user" value="1">
                    <input type="hidden" name="update_user" id="update_user" value="0">
                    <input type="hidden" name="user_id" id="user_id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter full name" required
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" placeholder="Enter email address" required
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div id="passwordField">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter password" required
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="inspector">Inspector</option>
                            <option value="business_owner">Business Owner</option>
                            <option value="community_user">Community User</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('userModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" id="submitButton" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">
                            Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 overflow-y-auto h-full w-full z-50 backdrop-blur-sm transition-opacity">
        <div class="relative top-10 mx-auto p-0 border w-full max-w-md shadow-xl rounded-xl bg-white transform transition-all">
            <div class="bg-brand-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Reset Password</h3>
                <button onclick="closeModal('resetPasswordModal')" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Reset password for <span id="resetUserName" class="font-bold text-gray-900"></span></p>
                <form method="POST" class="space-y-4" id="resetPasswordForm">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="user_id" id="reset_user_id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required minlength="6"
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" id="confirm_new_password" placeholder="Confirm new password" required
                               class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal('resetPasswordModal')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition-colors font-medium shadow-sm">
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
function filterUsers() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const name = row.querySelector('td:nth-child(1)').innerText.toLowerCase();
        const email = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
        const role = row.querySelector('td:nth-child(3)').innerText.toLowerCase();
        
        if (name.includes(search) || email.includes(search) || role.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function openUserModal(mode) {
    // Reset form and set mode
    document.getElementById('modalTitle').textContent = mode === 'create' ? 'Add New User' : 'Edit User';
    document.getElementById('create_user').value = mode === 'create' ? '1' : '0';
    document.getElementById('update_user').value = mode === 'create' ? '0' : '1';
    document.getElementById('user_id').value = '';
    document.getElementById('name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('password').value = '';
    document.getElementById('role').value = '';
    
    // Show/hide password field based on mode
    document.getElementById('passwordField').style.display = mode === 'create' ? 'block' : 'none';
    document.getElementById('password').required = mode === 'create';
    
    // Update button text
    document.getElementById('submitButton').textContent = mode === 'create' ? 'Add User' : 'Update User';
    
    // Show modal
    openModal('userModal');
}

function openModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function editUser(id) {
    // Fetch user data and populate the modal
    const userRow = document.querySelector(`tr[data-id='${id}']`);
    const userData = userRow.dataset;

    // Populate form fields
    document.getElementById('name').value = userData.name;
    document.getElementById('email').value = userData.email;
    document.getElementById('role').value = userData.role;
    document.getElementById('user_id').value = userData.id;

    // Set form to update mode
    document.getElementById('create_user').value = '0';
    document.getElementById('update_user').value = '1';
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('passwordField').style.display = 'none';
    document.getElementById('submitButton').textContent = 'Update User';

    // Show modal
    openModal('userModal');
}

function openResetPasswordModal(id, name) {
    document.getElementById('reset_user_id').value = id;
    document.getElementById('resetUserName').innerText = name;
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_new_password').value = '';
    openModal('resetPasswordModal');
}

document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_new_password');
    
    if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match!');
        confirmPassword.focus();
    }
});

function deleteUser(id, name) {
    if (confirm(`Are you sure you want to delete user ${name}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'user_management.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_user';
        input.value = '1';
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = id;

        form.appendChild(input);
        form.appendChild(userIdInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.id === 'userModal') {
        closeModal('userModal');
    }
    if (event.target.id === 'resetPasswordModal') {
        closeModal('resetPasswordModal');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Data from PHP
    const userRoleData = <?php echo json_encode($userRoleCounts); ?>;

    // Chart: User Roles (Doughnut Chart)
    const userRolesCtx = document.getElementById('userRolesChart').getContext('2d');
    new Chart(userRolesCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(userRoleData),
            datasets: [{
                label: 'Users',
                data: Object.values(userRoleData),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
});
    </script>
</body>
</html>
