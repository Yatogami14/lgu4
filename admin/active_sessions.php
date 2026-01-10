<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';
require_once '../utils/DatabaseSessionHandler.php';

// Check if user is logged in and has permission to access this page
requirePermission('active_sessions');

$database = new Database();
$db = $database->getConnection();
$handler = new DatabaseSessionHandler($db);

// Handle force logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_logout'])) {
    $session_id_to_destroy = $_POST['session_id'];
    if ($handler->destroy($session_id_to_destroy)) {
        $_SESSION['success_message'] = 'User session terminated successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to terminate user session.';
    }
    header('Location: active_sessions.php');
    exit;
}

// Get all active sessions
$active_sessions = $handler->getAllActiveSessions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Sessions - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Active User Sessions</h2>
                <p class="text-sm text-gray-600 mt-1">Monitor and manage currently active users</p>
            </div>
            <div class="w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterSessions()" placeholder="Search sessions..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
            </div>
        </div>

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

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Activity</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($active_sessions as $session): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-brand-100 text-brand-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">
                                        <?php echo substr($session['name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($session['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($session['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $session['role']))); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono"><?php echo htmlspecialchars($session['ip_address']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('M j, Y g:i:s A', $session['last_activity']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if ($session['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to terminate this user\'s session?');">
                                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['session_id']); ?>">
                                    <button type="submit" name="force_logout" class="text-red-500 hover:text-red-700 transition-colors" title="Force Logout">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-green-600 text-xs font-medium px-2 py-1 bg-green-50 rounded-full">Current</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($active_sessions)): ?>
                <div class="p-12 text-center text-gray-500">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-users-slash text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">No active sessions</h3>
                    <p class="mt-1 text-sm">There are no other users currently logged in.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-4 text-sm text-gray-500"><i class="fas fa-info-circle mr-1"></i>Showing sessions active in the last 30 minutes.</div>
    </div>

    <script>
        function filterSessions() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(1)').innerText.toLowerCase();
                const role = row.querySelector('td:nth-child(2)').innerText.toLowerCase();
                const ip = row.querySelector('td:nth-child(3)').innerText.toLowerCase();
                
                if (name.includes(search) || role.includes(search) || ip.includes(search)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>