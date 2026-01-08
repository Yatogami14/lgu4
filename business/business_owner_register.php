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
    if (!$existing_data || $existing_data['owner_id'] != $_SESSION['user_id'] || !in_array($existing_data['status'], ['rejected', 'needs_revision'])) {
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
                $document_types = [
                    'building_permit', 'business_permit', 'waste_disposal_certificate', 'owner_id', 'tax_registration', 'mayors_permit'
                ];
                $upload_success = true;
                foreach ($document_types as $doc_type) {
                    if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] == 0) {
                        // New file uploaded - replace existing
                        $businessDocument->deleteByBusinessIdAndType($business->id, $doc_type);
                        
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
                        // No new file uploaded. Check if we have an existing one.
                        $has_existing = false;
                        foreach ($existing_documents as $ex_doc) {
                            if ($ex_doc['document_type'] === $doc_type) {
                                $has_existing = true;
                                break;
                            }
                        }
                        if (!$has_existing) {
                            $error_message = "Missing required document: $doc_type.";
                            $upload_success = false;
                            break;
                        }
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
                            'building_permit', 'business_permit', 'waste_disposal_certificate', 'owner_id', 'tax_registration', 'mayors_permit'
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
    <title><?php echo $is_edit_mode ? 'Edit Application' : 'Business Registration'; ?> - HSI-QC Protektado</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/business_register.css">
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/register_options.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Options
    </a>

    <div class="main-content-wrapper">
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo">
            <h1>HSI-QC Protektado</h1>
            <p class="tagline">Register your establishment to ensure compliance and safety for the community.</p>
        </div>

        <div class="register-container">

            <div class="register-header">
                <h2><?php echo $is_edit_mode ? 'Edit Application' : 'Business Registration'; ?></h2>
                <p><?php echo $is_edit_mode ? 'Update your details and re-upload documents.' : 'Complete the form below to register your business.'; ?></p>
            </div>

            <!-- Progress Bar -->
            <div class="progress-wrapper">
                <div class="progress-container">
                    <div id="progress-bar" class="progress-bar" style="width: 25%"></div>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success_message)) : ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php echo $is_edit_mode ? '?edit_id=' . $business_id_to_edit : ''; ?>" enctype="multipart/form-data">
                <?php if ($is_edit_mode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo $business_id_to_edit; ?>">
                <?php endif; ?>
                
                <?php if (!$is_edit_mode): ?>
                <!-- Step 1 -->
                <div id="step-1" class="form-step">
                    <h3 class="step-title"><span class="step-number">1</span> Account Details</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-user"></i>
                                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($existing_data['owner_name'] ?? ''); ?>" placeholder=" ">
                                <label for="name">Owner's Full Name</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($existing_data['email'] ?? ''); ?>" placeholder=" ">
                                <label for="email">Email Address</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" required minlength="6" placeholder=" ">
                                <button type="button" class="password-toggle" id="passwordToggle"></i></button>
                                <label for="password">Password</label>
                            </div>
                            <p class="input-hint">Minimum 6 characters</p>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" required placeholder=" ">
                                <label for="confirm_password">Confirm Password</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions right">
                        <button type="button" class="btn-primary next-btn">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step 2 -->
                <div id="step-2" class="form-step" style="<?php if (!$is_edit_mode) echo 'display: none;'; ?>">
                    <h3 class="step-title"><span class="step-number">2</span> Business Details</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-store"></i>
                                <input type="text" id="business_name" name="business_name" required value="<?php echo htmlspecialchars($existing_data['name'] ?? ''); ?>" placeholder=" ">
                                <label for="business_name">Business Name</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-id-card"></i>
                                <input type="text" id="registration_number" name="registration_number" required value="<?php echo htmlspecialchars($existing_data['registration_number'] ?? ''); ?>" placeholder=" ">
                                <label for="registration_number">Registration Number</label>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <div class="input-wrapper">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($existing_data['address'] ?? ''); ?>" placeholder=" ">
                                <label for="address">Business Address</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="contact_phone" name="contact_phone" required value="<?php echo htmlspecialchars($existing_data['contact_number'] ?? ''); ?>" placeholder=" ">
                                <label for="contact_phone">Contact Phone</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-envelope"></i>
                                <input type="email" id="business_email" name="business_email" required value="<?php echo htmlspecialchars($existing_data['email'] ?? ''); ?>" placeholder=" ">
                                <label for="business_email">Business Email</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-briefcase"></i>
                                <select id="business_type" name="business_type" required>
                                    <option value="" disabled selected hidden></option>
                                    <option value="Restaurant" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Restaurant') ? 'selected' : ''; ?>>Restaurant</option>
                                    <option value="Food Establishment" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Food Establishment') ? 'selected' : ''; ?>>Food Establishment</option>
                                    <option value="Hotel" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Hotel') ? 'selected' : ''; ?>>Hotel</option>
                                    <option value="Hospital" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Hospital') ? 'selected' : ''; ?>>Hospital</option>
                                    <option value="School" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'School') ? 'selected' : ''; ?>>School</option>
                                    <option value="Factory" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Factory') ? 'selected' : ''; ?>>Factory</option>
                                    <option value="Other" <?php echo (isset($existing_data['business_type']) && $existing_data['business_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <label for="business_type">Business Type</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-calendar"></i>
                                <input type="text" onfocus="(this.type='date')" onblur="(this.type='text')" id="establishment_date" name="establishment_date" required value="<?php echo htmlspecialchars($existing_data['establishment_date'] ?? ''); ?>" placeholder=" ">
                                <label for="establishment_date">Establishment Date</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <?php if (!$is_edit_mode): ?>
                        <button type="button" class="btn-secondary prev-btn"><i class="fas fa-arrow-left"></i> Previous</button>
                        <?php endif; ?>
                        <button type="button" class="btn-primary next-btn">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 3 -->
                <div id="step-3" class="form-step" style="display: none;">
                    <h3 class="step-title"><span class="step-number">3</span> Representative Details</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-user-tie"></i>
                                <input type="text" id="representative_name" name="representative_name" required value="<?php echo htmlspecialchars($existing_data['representative_name'] ?? ''); ?>" placeholder=" ">
                                <label for="representative_name">Representative Name</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <i class="fas fa-briefcase"></i>
                                <input type="text" id="representative_position" name="representative_position" required value="<?php echo htmlspecialchars($existing_data['representative_position'] ?? ''); ?>" placeholder=" ">
                                <label for="representative_position">Position</label>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <div class="input-wrapper">
                                <i class="fas fa-phone"></i>
                                <input type="tel" id="representative_contact" name="representative_contact" required value="<?php echo htmlspecialchars($existing_data['representative_contact'] ?? ''); ?>" placeholder=" ">
                                <label for="representative_contact">Contact Number</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary prev-btn"><i class="fas fa-arrow-left"></i> Previous</button>
                        <button type="button" class="btn-primary next-btn">Next <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- Step 4 -->
                <div id="step-4" class="form-step" style="display: none;">
                    <h3 class="step-title"><span class="step-number">4</span> Upload Documents</h3>
                    <p class="step-desc">
                        <?php echo $is_edit_mode ? 'Please re-upload all required documents to ensure they are up-to-date.' : 'Please upload the following required documents (PDF, JPG, PNG).'; ?>
                    </p>

                    <?php if ($is_edit_mode && !empty($existing_documents)): ?>
                    <div class="existing-documents">
                        <h4>Current Documents:</h4>
                        <ul>
                            <?php foreach ($existing_documents as $doc): 
                                $docStatus = $doc['status'] ?? 'pending';
                                $docFeedback = $doc['feedback'] ?? '';
                            ?>
                                <li>
                                    <a href="<?php echo $base_path . '/' . htmlspecialchars($doc['file_path']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($doc['file_name']); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))); ?>)
                                    </a>
                                    <?php if ($docStatus === 'rejected'): ?>
                                        <span class="status-rejected"><i class="fas fa-exclamation-circle"></i> Revision Requested</span>
                                        <?php if ($docFeedback): ?>
                                            <div class="feedback"><strong>Reason:</strong> <?php echo htmlspecialchars($docFeedback); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <div class="form-grid">
                        <?php
                        $docs = [
                            'building_permit' => 'Building Permit',
                            'business_permit' => 'Business Permit',
                            'waste_disposal_certificate' => 'Waste Disposal Permit',
                            'owner_id' => 'ID of Business Owner',
                            'tax_registration' => 'Tax Registration',
                            'mayors_permit' => 'Mayor\'s Permit'
                        ];
                        foreach ($docs as $key => $label):
                        ?>
                        <div class="form-group">
                            <label for="<?php echo $key; ?>"><?php echo $label; ?></label>
                            <div class="input-wrapper file-input">
                                <i class="fas fa-file-upload"></i>
                                <input type="file" id="<?php echo $key; ?>" name="<?php echo $key; ?>" accept=".pdf,.jpg,.jpeg,.png" <?php echo $is_edit_mode ? '' : 'required'; ?>>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary prev-btn"><i class="fas fa-arrow-left"></i> Previous</button>
                        <button type="submit" id="registerButton" class="btn-primary">
                            <?php echo $is_edit_mode ? 'Update and Resubmit' : 'Submit Application'; ?>
                        </button>
                    </div>
                </div>
            </form>

            <div class="login-link">
                Already have an account? <a href="../main_login.php">Sign In</a>
            </div>
            
            <p class="footer">
                &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const steps = Array.from(document.querySelectorAll('.form-step'));
            const nextButtons = document.querySelectorAll('.next-btn');
            const prevButtons = document.querySelectorAll('.prev-btn');
            const progressBar = document.getElementById('progress-bar');
            const registerContainer = document.querySelector('.register-container');

            let currentStepIndex = steps.findIndex(step => {
                return window.getComputedStyle(step).display !== 'none';
            });
            if (currentStepIndex === -1) currentStepIndex = 0;

            const totalSteps = steps.length;

            function updateProgressBar() {
                if (progressBar && totalSteps > 0) {
                    const progress = ((currentStepIndex + 1) / totalSteps) * 100;
                    progressBar.style.width = `${progress}%`;
                }
            }

            nextButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Find the step containing this button
                    const currentStep = button.closest('.form-step');
                    const index = steps.indexOf(currentStep);

                    if (index !== -1 && index < totalSteps - 1) {
                        steps[index].style.display = 'none';
                        currentStepIndex = index + 1;
                        steps[currentStepIndex].style.display = 'block';
                        updateProgressBar();
                        if (registerContainer) registerContainer.scrollTo({ top: 0, behavior: 'smooth' }); else window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });

            prevButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const currentStep = button.closest('.form-step');
                    const index = steps.indexOf(currentStep);

                    if (index > 0) {
                        steps[index].style.display = 'none';
                        currentStepIndex = index - 1;
                        steps[currentStepIndex].style.display = 'block';
                        updateProgressBar();
                        if (registerContainer) registerContainer.scrollTo({ top: 0, behavior: 'smooth' }); else window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
            });

            updateProgressBar();

            // Password toggle functionality
            const passwordToggle = document.getElementById('passwordToggle');
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const passwordInput = document.getElementById('password');
                    const icon = this.querySelector('i');

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }

            // Password confirmation validation
            document.getElementById('registerForm')?.addEventListener('submit', function(e) {
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');

                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    confirmPassword.focus();
                }
            });
            
            // Ripple Effect
            function createRipple(event) {
                const button = event.currentTarget;
                const circle = document.createElement("span");
                const diameter = Math.max(button.clientWidth, button.clientHeight);
                const radius = diameter / 2;

                circle.style.width = circle.style.height = `${diameter}px`;
                circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
                circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
                circle.classList.add("ripple");

                const ripple = button.getElementsByClassName("ripple")[0];
                if (ripple) ripple.remove();
                button.appendChild(circle);
            }
            document.querySelectorAll(".btn-primary, .btn-secondary").forEach(btn => btn.addEventListener("click", createRipple));
        });
    </script>
</body>
</html>