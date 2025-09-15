<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$user = new User($db_core);

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$is_token_valid = false;

if ($token) {
    if ($user->validatePasswordResetToken($token)) {
        $is_token_valid = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_token_valid) {
    if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_new_password']) {
        if (strlen($_POST['new_password']) >= 6) {
            if ($user->updatePassword($_POST['new_password'])) {
                $_SESSION['success_message'] = 'Your password has been reset successfully. Please log in.';
                header('Location: main_login.php');
                exit;
            } else {
                $error_message = 'Failed to update password. Please try again.';
            }
        } else {
            $error_message = 'Password must be at least 6 characters long.';
        }
    } else {
        $error_message = 'Passwords do not match or are empty.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="font-sans">
    <div class="form-card p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Reset Your Password</h1>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($is_token_valid): ?>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="new_password" required minlength="6"
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg"
                               placeholder="Enter new password">
                    </div>
                </div>
                <div>
                    <label for="confirm_new_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_new_password" id="confirm_new_password" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg"
                               placeholder="Confirm new password">
                    </div>
                </div>
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-lg font-medium">
                    Reset Password
                </button>
            </form>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg text-center">
                <p>This password reset link is invalid or has expired.</p>
                <a href="forgot_password.php" class="font-bold text-red-800 hover:underline mt-2 inline-block">Request a new link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>