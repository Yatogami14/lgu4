<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Violation.php';
require_once '../models/Business.php';
require_once '../utils/access_control.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once '../vendor/autoload.php';

// Check if user is logged in and has permission
requirePermission('violations');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_violations = $database->getConnection(Database::DB_VIOLATIONS);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

$violationModel = new Violation($db_violations);

// Handle form submission for creating a violation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_violation') {
    $violationModel->business_id = $_POST['business_id'];
    $violationModel->description = $_POST['description'];
    $violationModel->severity = 'low'; // Community reports are initially low severity
    $violationModel->status = 'open';
    $violationModel->created_by = $_SESSION['user_id'];
    $violationModel->inspection_id = 0; // No inspection associated with community report initially
    $violationModel->due_date = null;

    if ($violationModel->create()) {
        $_SESSION['success_message'] = 'Concern reported successfully! It will be reviewed by an administrator.';

        // --- START: Send Email Notification to Admins ---
        try {
            // 1. Get Violation Details for Email
            $businessModel = new Business($database);
            $businessModel->id = $violationModel->business_id;
            $businessData = $businessModel->readOne();
            $businessName = $businessData['name'] ?? 'Unknown Business';

            // 2. Get Admin Emails
            $adminUserModel = new User($db_core);
            $admins = $adminUserModel->readByRole('admin')->fetchAll(PDO::FETCH_ASSOC);
            $super_admins = $adminUserModel->readByRole('super_admin')->fetchAll(PDO::FETCH_ASSOC);
            $all_admin_users = array_merge($admins, $super_admins);
            $admin_emails = array_unique(array_column($all_admin_users, 'email'));

            if (!empty($admin_emails)) {
                // 3. Configure and Send Email
                $mailerConfig = require '../config/mailer.php';
                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host       = $mailerConfig['host'];
                $mail->SMTPAuth   = $mailerConfig['smtp_auth'];
                $mail->Username   = $mailerConfig['username'];
                $mail->Password   = $mailerConfig['password'];
                if ($mailerConfig['smtp_secure'] === 'tls') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($mailerConfig['smtp_secure'] === 'ssl') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                }
                $mail->Port       = $mailerConfig['port'];

                $mail->setFrom($mailerConfig['from_email'], $mailerConfig['from_name']);
                foreach ($admin_emails as $email) {
                    $mail->addAddress($email);
                }

                $mail->isHTML(true);
                $mail->Subject = 'New Community Concern Reported: ' . $businessName;
                $admin_link = "http://" . $_SERVER['HTTP_HOST'] . "/lgu4/admin/violations.php";
                $mail->Body    = "<p>A new community concern has been reported for <strong>" . htmlspecialchars($businessName) . "</strong>.</p><p><strong>Concern:</strong> " . htmlspecialchars($violationModel->description) . "</p><p>Please review this concern in the admin portal by clicking the link below:</p><p><a href='{$admin_link}'>View Violations</a></p>";

                $mail->send();
            }
        } catch (Exception $e) {
            // Log the error, but don't block the user's success message.
            error_log("Mailer Error (Community Violation): {$mail->ErrorInfo}");
        }
        // --- END: Send Email Notification ---
    } else {
        $_SESSION['error_message'] = 'Failed to report concern.';
    }
    header('Location: violations.php');
    exit;
}

// Get violations reported by this user
$violationsStmt = $violationModel->readByCreatorId($_SESSION['user_id']);
$violations = $violationsStmt->fetchAll(PDO::FETCH_ASSOC);
$violationStats = $violationModel->getViolationStatsByCreatorId($_SESSION['user_id']);

// Get all businesses for the reporting form
$businessModel = new Business($database);
$allBusinesses = $businessModel->readAll()->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report & View Concerns - Community Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Community Concerns</h2>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600">
                <i class="fas fa-bullhorn mr-2"></i>Report a Concern
            </button>
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6"><p class="text-sm text-gray-600">Total Reports</p><p class="text-2xl font-bold"><?php echo $violationStats['total'] ?? 0; ?></p></div>
            <div class="bg-white rounded-lg shadow p-6"><p class="text-sm text-gray-600">Open</p><p class="text-2xl font-bold"><?php echo $violationStats['open'] ?? 0; ?></p></div>
            <div class="bg-white rounded-lg shadow p-6"><p class="text-sm text-gray-600">In Progress</p><p class="text-2xl font-bold"><?php echo $violationStats['in_progress'] ?? 0; ?></p></div>
            <div class="bg-white rounded-lg shadow p-6"><p class="text-sm text-gray-600">Resolved</p><p class="text-2xl font-bold"><?php echo $violationStats['resolved'] ?? 0; ?></p></div>
        </div>

        <!-- My Reported Concerns Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <h3 class="text-lg font-bold p-4 border-b">My Reported Concerns</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concern</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Reported</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($violations)): ?>
                        <tr><td colspan="4" class="text-center py-10 text-gray-500">You have not reported any concerns.</td></tr>
                    <?php else: ?>
                        <?php foreach ($violations as $violation): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($violation['business_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4"><div class="text-sm text-gray-900"><?php echo htmlspecialchars($violation['description']); ?></div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('M j, Y', strtotime($violation['created_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $violation['status'] == 'open' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo str_replace('_', ' ', htmlspecialchars($violation['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Violation Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Report a Public Concern</h3>
                <form method="POST" class="mt-4 space-y-4">
                    <input type="hidden" name="action" value="create_violation">
                    <div>
                        <label for="create_business_id" class="block text-sm font-medium text-gray-700">Business Name (if known)</label>
                        <select id="create_business_id" name="business_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select a Business</option>
                            <?php foreach ($allBusinesses as $business): ?>
                                <option value="<?php echo $business['id']; ?>"><?php echo htmlspecialchars($business['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="create_description" class="block text-sm font-medium text-gray-700">Describe the Concern</label>
                        <textarea id="create_description" name="description" rows="4" placeholder="Please provide as much detail as possible, including location, date, and time." required
                                  class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">
                            Submit Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>