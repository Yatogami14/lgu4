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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:ml-64 pt-24">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">User Management</h2>
            <button onclick="openUserModal('create')" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add User
            </button>
        </div>

        <!-- User Roles Chart -->
        <div class="mb-6 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">User Distribution by Role</h3>
            <div class="max-w-sm mx-auto">
                <canvas id="userRolesChart"></canvas>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $userRow): ?>
                    <tr
                        data-id="<?php echo htmlspecialchars($userRow['id']); ?>"
                        data-name="<?php echo htmlspecialchars($userRow['name']); ?>"
                        data-email="<?php echo htmlspecialchars($userRow['email']); ?>"
                        data-role="<?php echo htmlspecialchars($userRow['role']); ?>"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">
                                    <?php echo substr($userRow['name'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo $userRow['name']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $userRow['email']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo ucwords(str_replace('_', ' ', $userRow['role'])) ?: 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editUser(<?php echo $userRow['id']; ?>)" class="text-green-600 hover:text-green-900 mr-3">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button onclick="openResetPasswordModal(<?php echo $userRow['id']; ?>, '<?php echo addslashes($userRow['name']); ?>')" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                            <button onclick="deleteUser(<?php echo $userRow['id']; ?>, '<?php echo addslashes($userRow['name']); ?>')" 
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Modal (Create/Edit) -->
    <div id="userModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Add New User</h3>
                <form method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="create_user" id="create_user" value="1">
                    <input type="hidden" name="update_user" id="update_user" value="0">
                    <input type="hidden" name="user_id" id="user_id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" id="name" placeholder="Enter full name" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" placeholder="Enter email address" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div id="passwordField">
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" placeholder="Enter password" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Role</label>
                        <select name="role" id="role" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="inspector">Inspector</option>
                            <option value="business_owner">Business Owner</option>
                            <option value="community_user">Community User</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('userModal')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" id="submitButton" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Reset Password for <span id="resetUserName" class="font-bold"></span></h3>
                <form method="POST" class="mt-4 space-y-4" id="resetPasswordForm">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="user_id" id="reset_user_id" value="">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required minlength="6"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" id="confirm_new_password" placeholder="Confirm new password" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal('resetPasswordModal')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
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
