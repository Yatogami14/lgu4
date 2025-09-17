<?php
// Initialize database-backed sessions
try {
    // Load environment variables from .env file if the Dotenv package is installed.
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        // Check if the Dotenv class exists to avoid fatal errors if `composer install` hasn't been run.
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->load();
        }
    }

    // Existing database and session handler setup
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/utils/DatabaseSessionHandler.php';

    $database = new Database();
    $db_connection = $database->getConnection(Database::DB_CORE);
    $handler = new DatabaseSessionHandler($db_connection);
    session_set_save_handler($handler, true);
} catch (Exception $e) {
    // If database fails, use default session handler
    error_log("Database session initialization failed: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for "Remember Me" cookie if user is not logged in via session
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me']) && isset($database)) {
    try {
        require_once dirname(__DIR__) . '/models/Auth.php';

        list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

        if ($selector && $validator) {
            $auth = new Auth($database);

            $user_data = $auth->validateRememberMeToken($selector, $validator);
            if ($user_data) {
                // Log the user in
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['user_role'] = $user_data['role'];
                $_SESSION['user_name'] = $user_data['name'];

                // Regenerate the token for security (prevents token theft and reuse)
                $token_data = $auth->generateRememberMeToken($user_data['id']);
                if ($token_data) {
                    $cookie_value = $token_data['selector'] . ':' . $token_data['validator'];
                    setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30-day cookie
                }
            } else {
                // Invalid token, clear the cookie
                if (isset($_COOKIE['remember_me'])) {
                    setcookie('remember_me', '', time() - 3600, "/");
                }
            }
        }
    } catch (Exception $e) {
        // If remember me fails, clear the cookie
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', time() - 3600, "/");
        }
    }
}
