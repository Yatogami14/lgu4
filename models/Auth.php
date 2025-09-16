<?php
class Auth {
    private $database;
    private $table_name = "users";

    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Authenticates a user.
     * @param string $email
     * @param string $password
     * @return array|false User data array on success, false on failure.
     */
    public function login($email, $password) {
        $query = "SELECT id, name, email, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        
        $user_row = $this->database->fetch(Database::DB_CORE, $query, [$email]);

        if ($user_row && password_verify($password, $user_row['password'])) {
            return $user_row; // Return user data on success
        }
        return false;
    }

    /**
     * Generates a password reset token for a user by email.
     * @param string $email
     * @return string|false The token on success, false on failure.
     */
    public function generatePasswordResetToken($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $user_row = $this->database->fetch(Database::DB_CORE, $query, [$email]);

        if ($user_row) {
            $user_id = $user_row['id'];
            try {
                $token = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                error_log('Failed to generate random bytes for password reset token: ' . $e->getMessage());
                return false;
            }
            
            $expires_at = gmdate('Y-m-d H:i:s', strtotime('+1 hour'));

            $update_query = "UPDATE " . $this->table_name . " 
                             SET reset_token = :reset_token, reset_token_expires_at = :reset_token_expires_at 
                             WHERE id = :id";
            
            $params = [
                ':reset_token' => $token,
                ':reset_token_expires_at' => $expires_at,
                ':id' => $user_id
            ];

            try {
                $this->database->query(Database::DB_CORE, $update_query, $params);
                return $token;
            } catch (PDOException $e) {
                error_log("Failed to generate password reset token: " . $e->getMessage());
            }
        }
        return false;
    }

    /**
     * Validates a password reset token.
     * @param string $token
     * @return int|false The user ID on success, false on failure.
     */
    public function validatePasswordResetToken($token) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 0,1";
        $user_row = $this->database->fetch(Database::DB_CORE, $query, [$token]);

        if ($user_row) {
            return $user_row['id'];
        }
        return false;
    }

    /**
     * Updates a user's password and clears any reset/remember-me tokens.
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function updatePassword($userId, $password) {
        $query = "UPDATE " . $this->table_name . " SET password = :password, reset_token = NULL, reset_token_expires_at = NULL, remember_token_selector = NULL, remember_token_validator_hash = NULL, remember_token_expires_at = NULL WHERE id = :id";
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $params = [':password' => $hashed_password, ':id' => $userId];

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to update password for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a "Remember Me" token.
     * @param int $userId
     * @return array|false Token data on success, false on failure.
     */
    public function generateRememberMeToken($userId) {
        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            error_log('Failed to generate random bytes for remember me token: ' . $e->getMessage());
            return false;
        }

        $validator_hash = hash('sha256', $validator);
        $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30); // 30 days

        $query = "UPDATE " . $this->table_name . " SET remember_token_selector = :selector, remember_token_validator_hash = :validator_hash, remember_token_expires_at = :expires_at WHERE id = :id";
        $params = [':selector' => $selector, ':validator_hash' => $validator_hash, ':expires_at' => $expires_at, ':id' => $userId];

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return ['selector' => $selector, 'validator' => $validator];
        } catch (PDOException $e) {
            error_log("Failed to generate remember me token for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validates a "Remember Me" token.
     * @param string $selector
     * @param string $validator
     * @return array|false User data on success, false on failure.
     */
    public function validateRememberMeToken($selector, $validator) {
        $query = "SELECT id, name, role, remember_token_validator_hash FROM " . $this->table_name . " WHERE remember_token_selector = ? AND remember_token_expires_at > NOW() LIMIT 0,1";
        $token_row = $this->database->fetch(Database::DB_CORE, $query, [$selector]);

        if ($token_row) {
            if (hash_equals($token_row['remember_token_validator_hash'], hash('sha256', $validator))) {
                return $token_row;
            }
            $this->clearRememberMeTokenBySelector($selector);
        }
        return false;
    }

    /**
     * Clears "Remember Me" token for a user.
     * @param int $userId
     * @return bool
     */
    public function clearRememberMeToken($userId) {
        $query = "UPDATE " . $this->table_name . " SET remember_token_selector = NULL, remember_token_validator_hash = NULL, remember_token_expires_at = NULL WHERE id = :id";
        try {
            $this->database->query(Database::DB_CORE, $query, [':id' => $userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to clear remember me token for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    private function clearRememberMeTokenBySelector($selector) {
        $query = "UPDATE " . $this->table_name . " SET remember_token_selector = NULL, remember_token_validator_hash = NULL, remember_token_expires_at = NULL WHERE remember_token_selector = :selector";
        $this->database->query(Database::DB_CORE, $query, [':selector' => $selector]);
    }
}
?>