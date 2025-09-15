<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';
require_once '../utils/DatabaseSessionHandler.php';

// Check if user is logged in and has permission to access this page
requirePermission('active_sessions');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$handler = new DatabaseSessionHandler($db_core);

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Active User Sessions</h2>
        </div>

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

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($active_sessions)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-10 text-gray-500">No active user sessions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($active_sessions as $session): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($session['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($session['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $session['role']))); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($session['ip_address']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M j, Y g:i:s A', $session['last_activity']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($session['user_id'] != $_SESSION['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to terminate this user\'s session?');">
                                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['session_id']); ?>">
                                    <button type="submit" name="force_logout" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-sign-out-alt"></i> Force Logout
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">Current Session</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-sm text-gray-500"><i class="fas fa-info-circle mr-1"></i>Showing sessions active in the last 30 minutes.</div>
    </div>
</body>
</html>