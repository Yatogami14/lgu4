<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/BusinessDocument.php';
require_once '../models/Notification.php';
require_once '../ValidationService.php';

// Determine base path for assets
$base_path = '..';

$database = new Database();
$db = $database->getConnection();
$user = new User($database);
$business = new Business($database);
$businessDocument = new BusinessDocument($database);
$notificationModel = new Notification($database);
$validator = new ValidationService($database);

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate Basic Inputs
    $data = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'account_type' => 'business_owner',
        'terms' => isset($_POST['terms'])
    ];

    if (!$validator->validateRegistration($data)) {
        $errors = $validator->getErrors();
    }
    
    // 2. Validate Business Name
    $business_name = trim($_POST['business_name'] ?? '');
    if (empty($business_name)) {
        $errors['business_name'] = "Business Name is required.";
    }

    // 3. Validate File Upload
    if (empty($_FILES['business_permit']['name'])) {
        $errors['business_permit'] = "Business Permit file is required.";
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['business_permit']['type'], $allowed_types)) {
            $errors['business_permit'] = "Only JPG, PNG, and PDF files are allowed.";
        } elseif ($_FILES['business_permit']['size'] > $max_size) {
            $errors['business_permit'] = "File size must be less than 5MB.";
        }
    }

    // 4. Process Registration if No Errors
    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // --- Create User Account ---
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = $data['password'];
            $user->role = 'business_owner';
            $user->status = 'pending_approval'; // User cannot log in until business is verified
            $user_result = $user->create();
            if (!$user_result['success']) {
                throw new Exception($user_result['error'] ?: 'Failed to create user account.');
            }
            $user_id = $user->id;

            // --- Handle File Upload ---
            $upload_dir_for_move = '../uploads/business_documents/';
            $upload_dir_for_db = 'uploads/business_documents/';
            if (!is_dir($upload_dir_for_move)) {
                mkdir($upload_dir_for_move, 0755, true);
            }
            $file_extension = pathinfo($_FILES['business_permit']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('permit_', true) . '.' . $file_extension;
            $target_file = $upload_dir_for_move . $file_name;
            $db_path = $upload_dir_for_db . $file_name;

            if (!move_uploaded_file($_FILES['business_permit']['tmp_name'], $target_file)) {
                throw new Exception("Failed to upload file. Please try again.");
            }

            // --- Create Business Record ---
            $business->name = $business_name;
            $business->owner_id = $user_id;
            $business->address = $_POST['address'] ?? 'Not Provided';
            $business->email = $data['email'];
            $business->status = 'pending';
            // Set other required fields to placeholders since the form is simple
            $business->contact_number = 'N/A';
            $business->business_type = 'N/A';
            $business->registration_number = 'N/A';
            $business->establishment_date = date('Y-m-d');
            $business->representative_name = 'N/A';
            $business->representative_position = 'N/A';
            $business->representative_contact = 'N/A';

            if (!$business->create()) {
                throw new Exception('Failed to create business record.');
            }
            $business_id = $db->lastInsertId();

            // --- Create Business Document Record ---
            $businessDocument->business_id = $business_id;
            $businessDocument->document_type = 'business_permit';
            $businessDocument->file_path = $db_path;
            $businessDocument->file_name = $_FILES['business_permit']['name'];
            $businessDocument->mime_type = $_FILES['business_permit']['type'];
            $businessDocument->file_size = $_FILES['business_permit']['size'];
            if (!$businessDocument->create()) {
                throw new Exception('Failed to save business document record.');
            }

            // --- Notify Admins ---
            $adminUsers = $user->readByRole('admin');
            $superAdminUsers = $user->readByRole('super_admin');
            $admins = array_merge($adminUsers, $superAdminUsers);
            $message = "A new business application for \"{$business_name}\" has been submitted and is awaiting review.";
            $link = '/lgu4/admin/verify_business_owners.php';
            foreach ($admins as $admin) {
                $notificationModel->create($admin['id'], $message, 'info', 'business', $business_id, $link);
            }

            // --- Commit and Redirect ---
            $db->commit();
                $_SESSION['success_message'] = "Registration successful! Your account is pending approval. Please wait for admin verification.";
            header("Location: ../main_login.php");
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $errors['general'] = $e->getMessage();
            // Delete uploaded file if it exists
            if (isset($target_file) && file_exists($target_file)) {
                unlink($target_file);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Owner Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/login.css">
</head>
<body>
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>

    <a href="<?php echo $base_path; ?>/register_options.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back
    </a>

    <div class="main-content-wrapper">
        <div class="login-container" style="max-width: 500px;">
            <div class="login-header">
                <h2>Business Registration</h2>
                <p>Create your business owner account</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="error-message"><?php echo $errors['general']; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    <?php if (isset($errors['name'])) echo "<div class='error-message'>{$errors['name']}</div>"; ?>
                </div>

                <div class="form-group">
                    <label>Business Name</label>
                    <input type="text" name="business_name" value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>" required>
                    <?php if (isset($errors['business_name'])) echo "<div class='error-message'>{$errors['business_name']}</div>"; ?>
                </div>

                <div class="form-group">
                    <label>Business Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                    <?php if (isset($errors['address'])) echo "<div class='error-message'>{$errors['address']}</div>"; ?>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <?php if (isset($errors['email'])) echo "<div class='error-message'>{$errors['email']}</div>"; ?>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <?php if (isset($errors['password'])) echo "<div class='error-message'>{$errors['password']}</div>"; ?>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                    <?php if (isset($errors['confirm_password'])) echo "<div class='error-message'>{$errors['confirm_password']}</div>"; ?>
                </div>

                <div class="form-group">
                    <label>Upload Business Permit (PDF/Image)</label>
                    <input type="file" name="business_permit" accept=".pdf,.jpg,.jpeg,.png" required style="padding: 10px; border: 1px solid #ddd; width: 100%;">
                    <?php if (isset($errors['business_permit'])) echo "<div class='error-message'>{$errors['business_permit']}</div>"; ?>
                </div>

                <label><input type="checkbox" name="terms" required> I agree to the Terms & Conditions</label>
                
                <button type="submit" class="btn-primary">Register Business</button>
            </form>
        </div>
    </div>
</body>
</html>