<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/BusinessDocument.php';
require_once '../models/Notification.php';

$is_edit_mode = false;
$existing_data = null;
$existing_documents = [];

if (isset($_GET['edit_id']) && is_numeric($_GET['edit_id'])) {
    // Edit mode requires a logged-in user.
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../main_login.php?error=login_required');
        exit;
    }
    $is_edit_mode = true;
    $business_id_to_edit = $_GET['edit_id'];

} else {
    // Create mode: if user is logged in, they shouldn't be on this page.
    if (isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

$database = new Database();
$conn = $database->getConnection();
$user = new User($database);
$business = new Business($database);
$businessDocument = new BusinessDocument($database);

if ($is_edit_mode) {
    $business->id = $business_id_to_edit;
    $existing_data = $business->readOne();
    $existing_documents = $businessDocument->readByBusinessId($business_id_to_edit);

    // Security check: ensure the business belongs to the logged-in user and is actually rejected
    if (!$existing_data || $existing_data['owner_id'] != $_SESSION['user_id'] || $existing_data['status'] !== 'rejected') {
        // Redirect to dashboard with an error if they are not authorized to edit
        $_SESSION['error_message'] = "You are not authorized to edit this application.";
        header('Location: index.php');
        exit;
    }
}

// Determine base path for assets
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

// Create uploads directory if it doesn't exist
$upload_dir_for_move = '../uploads/business_documents/';
$upload_dir_for_db = 'uploads/business_documents/';

if (!is_dir($upload_dir_for_move)) {
    mkdir($upload_dir_for_move, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // --- UPDATE LOGIC ---
        if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
            $conn->beginTransaction();

            $business->id = $_POST['edit_id'];
            $business->owner_id = $_SESSION['user_id']; // For security check in model

            // Populate business object from POST data
            $business->name = $_POST['business_name'];
            $business->address = $_POST['address'];
            $business->contact_number = $_POST['contact_phone'];
            $business->email = $_POST['business_email'];
            $business->business_type = $_POST['business_type'];
            $business->registration_number = $_POST['registration_number'];
            $business->establishment_date = $_POST['establishment_date'];
            $business->representative_name = $_POST['representative_name'];
            $business->representative_position = $_POST['representative_position'];
            $business->representative_contact = $_POST['representative_contact'];

            if ($business->update()) {
                // Re-handle document uploads: delete old, upload new
                $businessDocument->deleteByBusinessId($business->id);

                $document_types = [
                    'building_permit', 'business_permit', 'waste_disposal_certificate', 'owner_id', 'tax_registration'
                ];
                $upload_success = true;
                foreach ($document_types as $doc_type) {
                    if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] == 0) {
                        $file = $_FILES[$doc_type];
                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $new_filename = uniqid() . '_' . $doc_type . '.' . $file_extension;
                        $file_path_for_move = $upload_dir_for_move . $new_filename;
                        $file_path_for_db = $upload_dir_for_db . $new_filename;

                        if (move_uploaded_file($file['tmp_name'], $file_path_for_move)) {
                            $businessDocument->business_id = $business->id;
                            $businessDocument->document_type = $doc_type;
                            $businessDocument->file_path = $file_path_for_db;
                            $businessDocument->file_name = $file['name'];
                            $businessDocument->mime_type = $file['type'];
                            $businessDocument->file_size = $file['size'];
                            if (!$businessDocument->create()) {
                                $error_message = "Failed to save document record for $doc_type.";
                                $upload_success = false; break;
                            }
                        } else {
                            $error_message = "Failed to upload $doc_type.";
                            $upload_success = false;
                            break;
                        }
                    } else {
                        $error_message = "Missing required document: $doc_type.";
                        $upload_success = false;
                        break;
                    }
                }

                if ($upload_success) {
                    $conn->commit();
                    $_SESSION['success_message'] = "Application updated and resubmitted successfully for review.";

                    // Notify admins of the resubmission
                    $notificationModel = new Notification($database);
                    $adminUsers = $user->readByRole('admin');
                    $superAdminUsers = $user->readByRole('super_admin');
                    $admins = array_merge($adminUsers, $superAdminUsers);
                    $business_name = $_POST['business_name'];
                    $message = "A rejected application for \"{$business_name}\" has been updated and resubmitted for review.";
                    $link = '/lgu4/admin/business_applications.php';
                    foreach ($admins as $admin) {
                        $notificationModel->create($admin['id'], $message, 'info', 'business', $business->id, $link);
                    }

                    header('Location: index.php');
                    exit();
                } else {
                    $conn->rollBack();
                }
            } else {
                $conn->rollBack();
                $error_message = "Failed to update business record.";
            }
        // --- CREATE LOGIC ---
        } else {
            if ($_POST['password'] !== $_POST['confirm_password']) {
                $error_message = "Passwords do not match.";
            } else {
                $conn->beginTransaction();

                // Create user account
                $user->name = $_POST['name'];
                $user->email = $_POST['email'];
                $user->password = $_POST['password'];
                $user->role = 'business_owner';
                $user->status = 'pending_approval';
                $user->department = null;
                $user->certification = 'Business Owner';

                $creation_result = $user->create();
                if ($creation_result['success']) {
                    $user_id = $user->id;

                    // Create business record
                    $business->name = $_POST['business_name'];
                    $business->address = $_POST['address'];
                    $business->owner_id = $user_id;
                    $business->contact_number = $_POST['contact_phone'];
                    $business->email = $_POST['business_email'];
                    $business->business_type = $_POST['business_type'];
                    $business->registration_number = $_POST['registration_number'];
                    $business->establishment_date = $_POST['establishment_date'];
                    $business->representative_name = $_POST['representative_name'];
                    $business->representative_position = $_POST['representative_position'];
                    $business->representative_contact = $_POST['representative_contact'];
                    $business->status = 'pending';

                    if ($business->create()) {
                        $business_id = $conn->lastInsertId();

                        // Handle document uploads
                        $document_types = [
                            'building_permit', 'business_permit', 'waste_disposal_certificate', 'owner_id', 'tax_registration'
                        ];
                        $upload_success = true;
                        foreach ($document_types as $doc_type) {
                            if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] == 0) {
                                $file = $_FILES[$doc_type];
                                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                                $new_filename = uniqid() . '_' . $doc_type . '.' . $file_extension;
                                $file_path_for_move = $upload_dir_for_move . $new_filename;
                                $file_path_for_db = $upload_dir_for_db . $new_filename;

                                if (move_uploaded_file($file['tmp_name'], $file_path_for_move)) {
                                    $businessDocument->business_id = $business_id;
                                    $businessDocument->document_type = $doc_type;
                                    $businessDocument->file_path = $file_path_for_db;
                                    $businessDocument->file_name = $file['name'];
                                    $businessDocument->mime_type = $file['type'];
                                    $businessDocument->file_size = $file['size'];
                                    if (!$businessDocument->create()) {
                                        $error_message = "Failed to save document record for $doc_type.";
                                        $upload_success = false; break;
                                    }
                                } else {
                                    $error_message = "Failed to upload $doc_type.";
                                    $upload_success = false; break;
                                }
                            } else {
                                $error_message = "Missing required document: $doc_type.";
                                $upload_success = false; break;
                            }
                        }

                        if ($upload_success) {
                            $conn->commit();
                            $success_message = "Business registration submitted successfully! Your application is pending approval. You will be notified once reviewed.";
                        } else {
                            $conn->rollBack();
                        }
                    } else {
                        $conn->rollBack();
                        $error_message = "Failed to create business record. Please try again.";
                    }
                } else {
                    $conn->rollBack();
                    $error_message = $creation_result['error'] ?: "Failed to create account. Please try again.";
                }
            }
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) { $conn->rollBack(); }
        $error_message = "An error occurred during registration. Please try again.";
        error_log("Business registration error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit_mode ? 'Edit Business Application' : 'Business Owner Registration'; ?> - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/auth.css">
    <style>
        body {
            background: #ffffff !important;
        }
        .login-container, .register-container {
            background: #ffffff !important;
            backdrop-filter: none !important;
        }
        .login-header h2 {
            color: #1a202c;
            font-weight: 700;
        }
        .btn-primary {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937; /* Dark gray text for better readability on yellow */
            background: linear-gradient(135deg, #fef08a 0%, #facc15 100%); /* Brighter yellow gradient */
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.4);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem; /* 20px */
        }

        @media (min-width: 768px) { /* md breakpoint */
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .col-span-2 {
            grid-column: span 2 / span 2;
        }
        /* Update blob colors for white theme */
        .bg-decoration-1 {
            background: radial-gradient(circle, #FFF59D 0%, transparent 70%);
        }
        .bg-decoration-2 {
            background: radial-gradient(circle, #e5e7eb 0%, transparent 70%);
        }
        .bg-decoration-3 {
            background: radial-gradient(circle, #FFF176 0%, transparent 70%);
        }
        .bg-decoration-4 {
            background: radial-gradient(circle, rgba(255, 249, 196, 0.4) 0%, transparent 70%);
        }
    </style>
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/register_options.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Options
    </a>

    <div class="main-content-wrapper" style="max-width: 1100px; margin: 2rem auto;">
        <div class="login-container" style="max-width: none;">

            <div class="login-header">
                <h2><?php echo $is_edit_mode ? 'Edit and Resubmit Application' : 'Join as Business Owner'; ?></h2>
                <p><?php echo $is_edit_mode ? 'Please review your details, make corrections, and re-upload all required documents.' : 'Create your account and register your business for inspection.'; ?></p>
            </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (isset($success_message)) : ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php echo $is_edit_mode ? '?edit_id=' . $business_id_to_edit : ''; ?>" enctype="multipart/form-data">
            <?php if ($is_edit_mode): ?>
                <input type="hidden" name="edit_id" value="<?php echo $business_id_to_edit; ?>">
            <?php endif; ?>
            
            <?php if (!$is_edit_mode): ?>
            <div class="form-section" style="border-top: none; padding-top: 0;">
                <h3 class="form-section-title">Step 1: Account Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Owner's Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($existing_data['owner_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Enter your email for login" required value="<?php echo htmlspecialchars($existing_data['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Create a strong password" required minlength="6">
                            <button type="button" class="password-toggle" id="passwordToggle"><i class="fas fa-eye"></i></button>
                        </div>
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">Minimum 6 characters</p>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-section">
                <h3 class="form-section-title">Step 2: Business Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="business_name">Business Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-store"></i>
                            <input type="text" id="business_name" name="business_name" placeholder="Enter the official business name" required value="<?php echo htmlspecialchars($existing_data['name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registration_number">Business Registration Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="registration_number" name="registration_number" placeholder="Enter your business registration number" required value="<?php echo htmlspecialchars($existing_data['registration_number'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group col-span-2">
                        <label for="address">Business Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" id="address" name="address" placeholder="Enter the full business address" required value="<?php echo htmlspecialchars($existing_data['address'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Business Contact Phone</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="contact_phone" name="contact_phone" placeholder="Enter business contact number" required value="<?php echo htmlspecialchars($existing_data['contact_number'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="business_email">Business Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="business_email" name="business_email" placeholder="Enter business email address" required value="<?php echo htmlspecialchars($existing_data['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                    <label for="business_type">Business Type</label>
                    <div class="input-wrapper">
                        <i class="fas fa-briefcase"></i>
                        <select id="business_type" name="business_type" required>
                            <option value="">Select Business Type</option>
                            <option value="Restaurant" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Restaurant') ? 'selected' : ''; ?>>Restaurant</option>
                            <option value="Food Establishment" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Food Establishment') ? 'selected' : ''; ?>>Food Establishment</option>
                            <option value="Hotel" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Hotel') ? 'selected' : ''; ?>>Hotel</option>
                            <option value="Hospital" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Hospital') ? 'selected' : ''; ?>>Hospital</option>
                            <option value="School" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'School') ? 'selected' : ''; ?>>School</option>
                            <option value="Factory" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Factory') ? 'selected' : ''; ?>>Factory</option>
                            <option value="Other" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    </div>
                    <div class="form-group">
                        <label for="establishment_date">Establishment Date</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar"></i>
                            <input type="date" id="establishment_date" name="establishment_date" required value="<?php echo htmlspecialchars($existing_data['establishment_date'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Step 3: Representative Details</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="representative_name">Representative Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user-tie"></i>
                            <input type="text" id="representative_name" name="representative_name" placeholder="Enter representative's full name" required value="<?php echo htmlspecialchars($existing_data['representative_name'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="representative_position">Representative Position</label>
                        <div class="input-wrapper">
                            <i class="fas fa-briefcase"></i>
                            <input type="text" id="representative_position" name="representative_position" placeholder="e.g., Owner, Manager, Director" required value="<?php echo htmlspecialchars($existing_data['representative_position'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group col-span-2">
                        <label for="representative_contact">Representative Contact</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="representative_contact" name="representative_contact" placeholder="Enter representative's contact number" required value="<?php echo htmlspecialchars($existing_data['representative_contact'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="form-section-title">Step 4: Upload Compliance Documents</h3>
                <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                    <?php echo $is_edit_mode ? 'Please re-upload all required documents to ensure they are up-to-date.' : 'Please upload the following required documents for your business registration:'; ?>
                </p>

                <?php if ($is_edit_mode && !empty($existing_documents)): ?>
                <div class="mb-6 p-4 bg-gray-100 border border-gray-200 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">Currently Uploaded Documents:</h4>
                    <ul class="list-disc list-inside text-sm space-y-1">
                        <?php foreach ($existing_documents as $doc): ?>
                            <li>
                                <a href="<?php echo $base_path . '/' . htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                    <?php echo htmlspecialchars($doc['file_name']); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))); ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="text-xs text-gray-500 mt-3">Note: Resubmitting the form will replace all previously uploaded documents.</p>
                </div>
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="building_permit">Building Permit</label>
                        <input type="file" id="building_permit" name="building_permit" accept=".pdf,.jpg,.jpeg,.png" required class="form-control-file" style="padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 100%;">
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">PDF, JPG, PNG. Required.</p>
                    </div>

                    <div class="form-group">
                        <label for="business_permit">Business Permit</label>
                        <input type="file" id="business_permit" name="business_permit" accept=".pdf,.jpg,.jpeg,.png" required class="form-control-file" style="padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 100%;">
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">PDF, JPG, PNG. Required.</p>
                    </div>

                    <div class="form-group">
                        <label for="waste_disposal_certificate">Waste Disposal Certificate</label>
                        <input type="file" id="waste_disposal_certificate" name="waste_disposal_certificate" accept=".pdf,.jpg,.jpeg,.png" required class="form-control-file" style="padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 100%;">
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">PDF, JPG, PNG. Required.</p>
                    </div>

                    <div class="form-group">
                        <label for="owner_id">ID of Business Owner</label>
                        <input type="file" id="owner_id" name="owner_id" accept=".pdf,.jpg,.jpeg,.png" required class="form-control-file" style="padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 100%;">
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">Valid government ID. Required.</p>
                    </div>

                    <div class="form-group col-span-2">
                        <label for="tax_registration">Tax Registration Certificate</label>
                        <input type="file" id="tax_registration" name="tax_registration" accept=".pdf,.jpg,.jpeg,.png" required class="form-control-file" style="padding: 10px; border: 1px solid #ccc; border-radius: 8px; width: 100%;">
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">PDF, JPG, PNG. Required.</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="registerButton"><?php echo $is_edit_mode ? 'Update and Resubmit Application' : 'Submit Application'; ?></button>
        </form>

        <p class="register-link">
            Already have an account? <a href="../main_login.php">Sign In</a>
        </p>

        <p class="footer">
            &copy; <?php echo date('Y'); ?> Health & Safety Inspection. All Rights Reserved.
        </p>
        </div>
    </div>

    <script>
        // (JavaScript remains the same)
        // Password confirmation validation
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });

        // Password toggle functionality
        document.getElementById('passwordToggle')?.addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>