<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('profile');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$notification = new Notification($database);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/avatars/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $avatar_filename = uniqid() . '-' . basename($_FILES['avatar']['name']);
            $target_file = $upload_dir . $avatar_filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $user->avatar = 'uploads/avatars/' . $avatar_filename;
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        }

        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        if ($user->role !== 'inspector') {
            $user->department = $_POST['department'];
            $user->certification = $_POST['certification'];
        }
        if (!isset($error_message) && $user->update()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile.";
        }
    }
}

// Get notifications
$notifications = $notification->readByUser($user->id, 5);
$unread_count = $notification->countUnread($user->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1" title="Go to Homepage">
                    <i class="fas fa-shield-alt text-yellow-600 text-xl sm:text-2xl"></i>
                    <div class="min-w-0 flex-1">
                        <h1 class="text-sm sm:text-xl font-bold text-gray-900 truncate">LGU Health & Safety</h1>
                        <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Digital Inspection Platform</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-1 sm:space-x-4 flex-shrink-0">
                    <a href="index.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <i class="fas fa-home"></i>
                    </a>
                    <a href="../logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
        <h2 class="text-2xl font-bold mb-6">User Profile</h2>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Card -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold mb-4">Profile Information</h3>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="bg-green-100 text-green-700 p-3 rounded-md mb-4">
                            <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 text-red-700 p-3 rounded-md mb-4">
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4" enctype="multipart/form-data">
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo !empty($user->avatar) ? '../' . htmlspecialchars($user->avatar) : 'https://via.placeholder.com/100'; ?>" 
                                 alt="Avatar" class="w-24 h-24 rounded-full object-cover">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Update Profile Photo</label>
                                <input type="file" name="avatar" accept="image/*"
                                       class="mt-1 block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-md file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-yellow-50 file:text-yellow-700
                                              hover:file:bg-yellow-100">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($user->name); ?>" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user->email); ?>" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <input type="text" name="department" value="<?php echo htmlspecialchars($user->department); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm <?php echo $user->role === 'inspector' ? 'bg-gray-100' : ''; ?>"
                                       <?php echo $user->role === 'inspector' ? 'readonly' : ''; ?>>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Certification</label>
                                <input type="text" name="certification" value="<?php echo htmlspecialchars($user->certification); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm <?php echo $user->role === 'inspector' ? 'bg-gray-100' : ''; ?>"
                                       <?php echo $user->role === 'inspector' ? 'readonly' : ''; ?>>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <input type="text" value="<?php echo ucfirst($user->role); ?>" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_profile" 
                                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-md hover:bg-yellow-500">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                <?php if ($user->role === 'inspector'): ?>
                <div class="mt-4 bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg text-sm">
                    <i class="fas fa-info-circle mr-2"></i>Your department and certification can only be updated by an administrator.
                </div>
                <?php endif; ?>
            </div>

            <!-- Notifications Card -->
            <div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Notifications</h3>
                        <?php if ($unread_count > 0): ?>
                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                <?php echo $unread_count; ?> unread
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if (empty($notifications)): ?>
                            <p class="text-gray-500 text-sm">No notifications</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $note): ?>
                                <div class="border-l-4 <?php echo $note['type'] == 'alert' ? 'border-red-500' : 
                                                         ($note['type'] == 'warning' ? 'border-yellow-500' : 
                                                         ($note['type'] == 'success' ? 'border-green-500' : 'border-gray-500')); ?> pl-4 py-2">
                                    <p class="text-sm <?php echo $note['is_read'] == 0 ? 'font-medium text-gray-900' : 'text-gray-600'; ?>">
                                        <?php echo $note['message']; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="#" class="text-yellow-600 text-sm hover:text-yellow-800">
                            View all notifications →
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6 mt-6">
                    <h3 class="text-lg font-bold mb-4">Quick Stats</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Inspections Completed</span>
                            <span class="text-sm font-medium">12</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Violations Reported</span>
                            <span class="text-sm font-medium">8</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Average Compliance</span>
                            <span class="text-sm font-medium text-green-600">85%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Member Since</span>
                            <span class="text-sm font-medium"><?php echo date('M Y', strtotime($user->created_at)); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
