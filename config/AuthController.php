<?php
// This is a hypothetical AuthController.php. Please adapt this to your actual file.

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/User.php';
// require_once __DIR__ . '/../helpers/MailerHelper.php'; // No longer needed

class AuthController {

    public function register() {
        // ... (code to handle form submission and validation)

        $database = new Database();
        $db = $database->getConnection();

        $user = new User($database);

        // Set user properties from the form
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password = $_POST['password'];
        $user->role = 'business_owner'; // Or whatever the default role is
        
        // The User class now defaults status to 'active', so no need to set it here.

        if ($user->create()) {
            // REGISTRATION SUCCESSFUL
            
            // --- EMAIL VERIFICATION LOGIC TO REMOVE ---
            /*
            $mailer = new MailerHelper();
            $token = bin2hex(random_bytes(16)); // Generate a token
            
            // Save the token to the database associated with the user
            // e.g., $user->saveVerificationToken($token);

            if ($mailer->sendVerificationEmail($user->email, $token)) {
                // Redirect with a message to check email
                header("Location: /login.php?message=registration_successful_check_email");
            } else {
                // This is the part that is likely causing your error message
                header("Location: /login.php?message=registration_successful_no_email");
            }
            */
            // --- END OF LOGIC TO REMOVE ---

            // NEW: Redirect directly to login with a simple success message
            // Or, even better, log the user in directly.
            
            // For now, let's just redirect to the login page.
            header("Location: /login.php?message=registration_successful");
            exit();

        } else {
            // Handle registration failure
            header("Location: /register.php?error=registration_failed");
            exit();
        }
    }
}
?>