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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: {
                        'blob': 'blob 7s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 h-full">

    <div class="min-h-screen flex">
        
        <!-- Left Side - Branding (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-yellow-400 to-yellow-600 relative overflow-hidden items-center justify-center">
            <!-- Decorative Background Elements -->
            <div class="absolute top-0 left-0 w-full h-full bg-yellow-500 opacity-10 pattern-grid-lg"></div>
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
            
            <div class="relative z-10 text-center px-12">
                <div class="bg-white/20 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-white/20">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Logo" class="h-32 w-32 mx-auto rounded-full shadow-lg mb-6 border-4 border-white/50">
                    <h1 class="text-4xl font-black text-white mb-2 tracking-tight drop-shadow-sm">Business Partner</h1>
                    <p class="text-yellow-50 text-lg font-medium max-w-md mx-auto leading-relaxed">
                        Register your establishment to ensure compliance and safety for the community.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Registration Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white relative">
            
            <!-- Back Button -->
            <a href="<?php echo $base_path; ?>/register_options.php" class="absolute top-6 left-6 inline-flex items-center text-sm font-medium text-gray-500 hover:text-yellow-600 transition-colors group">
                <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform"></i>
                Back to Options
            </a>

            <div class="mx-auto w-full max-w-2xl">
                <div class="text-center lg:text-left">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo" class="h-16 w-16 mx-auto lg:hidden mb-6 rounded-full shadow-md">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight"><?php echo $is_edit_mode ? 'Edit Application' : 'Business Registration'; ?></h2>
                    <p class="mt-2 text-sm text-gray-600">
                        <?php echo $is_edit_mode ? 'Update your details and re-upload documents.' : 'Complete the form below to register your business.'; ?>
                    </p>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-8 mt-4">
                    <div id="progress-bar" class="bg-yellow-500 h-2.5 rounded-full transition-all duration-500" style="width: 25%"></div>
                </div>

                <div class="mt-8">
                    <?php if (isset($error_message)): ?>
                        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md animate-pulse">
                            <div class="flex">
                                <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-500"></i></div>
                                <div class="ml-3"><p class="text-sm text-red-700 font-medium"><?php echo $error_message; ?></p></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)) : ?>
                        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md">
                            <div class="flex">
                                <div class="flex-shrink-0"><i class="fas fa-check-circle text-green-500"></i></div>
                                <div class="ml-3"><p class="text-sm text-green-700 font-medium"><?php echo $success_message; ?></p></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php echo $is_edit_mode ? '?edit_id=' . $business_id_to_edit : ''; ?>" enctype="multipart/form-data">
                        <?php if ($is_edit_mode): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $business_id_to_edit; ?>">
                        <?php endif; ?>
                        
                        <?php if (!$is_edit_mode): ?>
                        <!-- Step 1 -->
                        <div id="step-1" class="form-step space-y-8">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="bg-yellow-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">1</span>
                                    Account Details
                                </h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="name" class="block text-sm font-medium text-gray-700">Owner's Full Name</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($existing_data['owner_name'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Enter your full name">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-envelope text-gray-400"></i>
                                            </div>
                                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($existing_data['email'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Enter your email">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-gray-400"></i>
                                            </div>
                                            <input type="password" id="password" name="password" required minlength="6"
                                                class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Create a strong password">
                                            <button type="button" id="passwordToggle" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-gray-400"></i>
                                            </div>
                                            <input type="password" id="confirm_password" name="confirm_password" required
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Confirm your password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end mt-8">
                                <button type="button" class="next-btn w-full sm:w-auto flex justify-center py-3 px-8 border border-transparent rounded-full shadow-sm text-sm font-bold text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all">Next <i class="fas fa-arrow-right ml-2"></i></button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Step 2 -->
                        <div id="step-2" class="form-step <?php if (!$is_edit_mode) echo 'hidden'; ?> space-y-8">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="bg-yellow-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">2</span>
                                    Business Details
                                </h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="business_name" class="block text-sm font-medium text-gray-700">Business Name</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-store text-gray-400"></i>
                                            </div>
                                            <input type="text" id="business_name" name="business_name" required value="<?php echo htmlspecialchars($existing_data['name'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Official business name">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="registration_number" class="block text-sm font-medium text-gray-700">Registration Number</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-id-card text-gray-400"></i>
                                            </div>
                                            <input type="text" id="registration_number" name="registration_number" required value="<?php echo htmlspecialchars($existing_data['registration_number'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Registration No.">
                                        </div>
                                    </div>

                                    <div class="col-span-2">
                                        <label for="address" class="block text-sm font-medium text-gray-700">Business Address</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                                            </div>
                                            <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($existing_data['address'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Full business address">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="contact_phone" class="block text-sm font-medium text-gray-700">Contact Phone</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-phone text-gray-400"></i>
                                            </div>
                                            <input type="tel" id="contact_phone" name="contact_phone" required value="<?php echo htmlspecialchars($existing_data['contact_number'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Business phone">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="business_email" class="block text-sm font-medium text-gray-700">Business Email</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-envelope text-gray-400"></i>
                                            </div>
                                            <input type="email" id="business_email" name="business_email" required value="<?php echo htmlspecialchars($existing_data['email'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Business email">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="business_type" class="block text-sm font-medium text-gray-700">Business Type</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-briefcase text-gray-400"></i>
                                            </div>
                                            <select id="business_type" name="business_type" required
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out">
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

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="establishment_date" class="block text-sm font-medium text-gray-700">Establishment Date</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-calendar text-gray-400"></i>
                                            </div>
                                            <input type="date" id="establishment_date" name="establishment_date" required value="<?php echo htmlspecialchars($existing_data['establishment_date'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-8">
                                <?php if (!$is_edit_mode): ?>
                                <button type="button" class="prev-btn w-full sm:w-auto flex justify-center py-3 px-8 border border-gray-300 rounded-full shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all"><i class="fas fa-arrow-left mr-2"></i> Previous</button>
                                <?php endif; ?>
                                <button type="button" class="next-btn w-full sm:w-auto flex justify-center py-3 px-8 border border-transparent rounded-full shadow-sm text-sm font-bold text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all">Next <i class="fas fa-arrow-right ml-2"></i></button>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div id="step-3" class="form-step hidden space-y-8">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="bg-yellow-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">3</span>
                                    Representative Details
                                </h3>
                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="representative_name" class="block text-sm font-medium text-gray-700">Representative Name</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-user-tie text-gray-400"></i>
                                            </div>
                                            <input type="text" id="representative_name" name="representative_name" required value="<?php echo htmlspecialchars($existing_data['representative_name'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Full name">
                                        </div>
                                    </div>

                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="representative_position" class="block text-sm font-medium text-gray-700">Position</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-briefcase text-gray-400"></i>
                                            </div>
                                            <input type="text" id="representative_position" name="representative_position" required value="<?php echo htmlspecialchars($existing_data['representative_position'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="e.g., Manager">
                                        </div>
                                    </div>

                                    <div class="col-span-2">
                                        <label for="representative_contact" class="block text-sm font-medium text-gray-700">Contact Number</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-phone text-gray-400"></i>
                                            </div>
                                            <input type="tel" id="representative_contact" name="representative_contact" required value="<?php echo htmlspecialchars($existing_data['representative_contact'] ?? ''); ?>"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                                placeholder="Representative's phone">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between mt-8">
                                <button type="button" class="prev-btn w-full sm:w-auto flex justify-center py-3 px-8 border border-gray-300 rounded-full shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all"><i class="fas fa-arrow-left mr-2"></i> Previous</button>
                                <button type="button" class="next-btn w-full sm:w-auto flex justify-center py-3 px-8 border border-transparent rounded-full shadow-sm text-sm font-bold text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all">Next <i class="fas fa-arrow-right ml-2"></i></button>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div id="step-4" class="form-step hidden space-y-8">
                            <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200">
                                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                    <span class="bg-yellow-500 text-white w-8 h-8 rounded-full flex items-center justify-center text-sm mr-3">4</span>
                                    Upload Documents
                                </h3>
                                <p class="text-sm text-gray-500 mb-6">
                                    <?php echo $is_edit_mode ? 'Please re-upload all required documents to ensure they are up-to-date.' : 'Please upload the following required documents (PDF, JPG, PNG).'; ?>
                                </p>

                                <?php if ($is_edit_mode && !empty($existing_documents)): ?>
                                <div class="mb-6 p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                                    <h4 class="font-semibold text-gray-800 mb-2 text-sm">Current Documents:</h4>
                                    <ul class="list-disc list-inside text-xs space-y-1 text-gray-600">
                                        <?php foreach ($existing_documents as $doc): 
                                            $docStatus = $doc['status'] ?? 'pending';
                                            $docFeedback = $doc['feedback'] ?? '';
                                        ?>
                                            <li>
                                                <a href="<?php echo $base_path . '/' . htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                                    <?php echo htmlspecialchars($doc['file_name']); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $doc['document_type']))); ?>)
                                                </a>
                                                <?php if ($docStatus === 'rejected'): ?>
                                                    <span class="ml-2 text-red-600 font-bold"><i class="fas fa-exclamation-circle"></i> Revision Requested</span>
                                                    <?php if ($docFeedback): ?>
                                                        <div class="ml-4 text-red-500 mt-1"><strong>Reason:</strong> <?php echo htmlspecialchars($docFeedback); ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>

                                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
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
                                    <div class="col-span-2 sm:col-span-1">
                                        <label for="<?php echo $key; ?>" class="block text-sm font-medium text-gray-700"><?php echo $label; ?></label>
                                        <input type="file" id="<?php echo $key; ?>" name="<?php echo $key; ?>" accept=".pdf,.jpg,.jpeg,.png" <?php echo $is_edit_mode ? '' : 'required'; ?>
                                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 transition-colors">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="flex justify-between mt-8">
                                <button type="button" class="prev-btn w-full sm:w-auto flex justify-center py-3 px-8 border border-gray-300 rounded-full shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all"><i class="fas fa-arrow-left mr-2"></i> Previous</button>
                                <button type="submit" id="registerButton" class="w-full sm:w-auto flex justify-center py-3 px-8 border border-transparent rounded-full shadow-sm text-sm font-bold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all transform hover:scale-[1.02] shadow-lg">
                                    <?php echo $is_edit_mode ? 'Update and Resubmit' : 'Submit Application'; ?>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">
                                    Already have an account?
                                </span>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="../main_login.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-full shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all">
                                Sign In
                            </a>
                        </div>
                    </div>
                    
                    <p class="mt-8 text-center text-xs text-gray-500">
                        &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const steps = Array.from(document.querySelectorAll('.form-step'));
            const nextButtons = document.querySelectorAll('.next-btn');
            const prevButtons = document.querySelectorAll('.prev-btn');
            const progressBar = document.getElementById('progress-bar');

            let currentStepIndex = steps.findIndex(step => !step.classList.contains('hidden'));
            if (currentStepIndex === -1) currentStepIndex = 0;

            const totalSteps = steps.length;

            function updateProgressBar() {
                const progress = ((currentStepIndex + 1) / totalSteps) * 100;
                progressBar.style.width = `${progress}%`;
            }

            nextButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (currentStepIndex < totalSteps - 1) {
                        steps[currentStepIndex].classList.add('hidden');
                        currentStepIndex++;
                        steps[currentStepIndex].classList.remove('hidden');
                        updateProgressBar();
                        window.scrollTo(0, 0);
                    }
                });
            });

            prevButtons.forEach(button => {
                button.addEventListener('click', () => {
                    if (currentStepIndex > 0) {
                        steps[currentStepIndex].classList.add('hidden');
                        currentStepIndex--;
                        steps[currentStepIndex].classList.remove('hidden');
                        updateProgressBar();
                        window.scrollTo(0, 0);
                    }
                });
            });

            updateProgressBar();
        });

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