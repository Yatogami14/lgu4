<?php
class User {
    private $database;
    private $table_name = "users";

    // Object properties
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $status = 'active'; // Default status
    public $department;
    public $certification;
    public $avatar;
    public $created_at;
    public $updated_at;
    public $reset_token;
    public $reset_token_expires_at;
    public $remember_token_selector;
    public $remember_token_validator_hash;
    public $remember_token_expires_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Create a new user.
     * Hashes the password before saving.
     * @return array ['success' => bool, 'error' => string]
     */
    public function create() {
        $fields = ["name", "email", "password", "role", "status"];
        $placeholders = [":name", ":email", ":password", ":role", ":status"];

        $params = [
            ":name" => htmlspecialchars(strip_tags($this->name)),
            ":email" => htmlspecialchars(strip_tags($this->email)),
            ":password" => password_hash($this->password, PASSWORD_BCRYPT),
            ":role" => htmlspecialchars(strip_tags($this->role)),
            ":status" => htmlspecialchars(strip_tags($this->status))
        ];

        if ($this->department !== null) {
            $fields[] = "department";
            $placeholders[] = ":department";
            $params[':department'] = htmlspecialchars(strip_tags($this->department));
        }
        if ($this->certification !== null) {
            $fields[] = "certification";
            $placeholders[] = ":certification";
            $params[':certification'] = htmlspecialchars(strip_tags($this->certification));
        }

        $query = "INSERT INTO " . $this->table_name . " (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";

        try {
            $pdo = $this->database->getConnection();
            $stmt = $pdo->prepare($query);
            if ($stmt->execute($params)) {
                $this->id = $pdo->lastInsertId();
                return ['success' => true, 'error' => ''];
            }
            return ['success' => false, 'error' => 'Unknown database error during user creation.'];
        } catch (PDOException $e) {
            // Check for duplicate email (error code 1062)
            if ($e->errorInfo[1] == 1062) {
                error_log("User creation failed: Duplicate email address - " . $this->email);
                return ['success' => false, 'error' => 'An account with this email already exists.'];
            } else {
                error_log("User creation failed: " . $e->getMessage());
                return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
            }
        }
    }

    /**
     * Read a single user's details.
     * Does not retrieve the password hash.
     * @return array|null
     */
    public function readOne() {
        $query = "SELECT id, name, email, role, status, department, certification, avatar, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = ? LIMIT 0,1";

        $row = $this->database->fetch($query, [$this->id]);

        if ($row) {
            $this->id = $row['id'] ?? null;
            $this->name = $row['name'] ?? null;
            $this->email = $row['email'] ?? null;
            $this->role = $row['role'] ?? null;
            $this->status = $row['status'] ?? null;
            $this->department = $row['department'] ?? null;
            $this->certification = $row['certification'] ?? null;
            $this->avatar = $row['avatar'] ?? null;
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            return $row;
        }
        return null;
    }
    /**
     * Read all users with pagination and optional role filter.
     * @param string|null $role
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function readAll($role = null, $limit = 1000, $offset = 0) {
        $query = "SELECT id, name, email, role, status, department, certification, avatar, created_at, updated_at
                  FROM " . $this->table_name;
        $params = [];
        if ($role) {
            $query .= " WHERE role = :role";
            $params[':role'] = $role;
        }
        $query .= " ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->database->fetchAll($query, $params);
    }

    /**
     * Update an existing user.
     * Hashes the password if a new one is provided.
     * @return bool
     */
    public function update() {
        $fields = [];
        $params = [':id' => $this->id];

        if ($this->name !== null) { $fields[] = "name=:name"; $params[':name'] = htmlspecialchars(strip_tags($this->name)); }
        if ($this->email !== null) { $fields[] = "email=:email"; $params[':email'] = htmlspecialchars(strip_tags($this->email)); }

        // Prevent changing the role of a super_admin
        if ($this->role !== null) {
            $current_user_query = "SELECT role FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
            $user_to_update = $this->database->fetch($current_user_query, [$this->id]);

            // Only perform security checks if the role is actually being changed
            if ($user_to_update && $user_to_update['role'] !== $this->role) {
                $is_self_update = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $this->id);
                $is_acting_user_super_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin');

                // Prevent a user from changing their own role unless they are a super_admin.
                if ($is_self_update && !$is_acting_user_super_admin) {
                    error_log("Security: User ID {$_SESSION['user_id']} (role: {$_SESSION['user_role']}) blocked from changing their own role.");
                    return false;
                }

                // Also, keep the existing check to prevent anyone from demoting a super_admin.
                if ($user_to_update['role'] === 'super_admin' && $this->role !== 'super_admin') {
                    $acting_user_id = $_SESSION['user_id'] ?? 'N/A';
                    error_log("Security: User ID {$acting_user_id} blocked from demoting super_admin ID {$this->id}.");
                    return false;
                }
            }
            $fields[] = "role=:role";
            $params[':role'] = htmlspecialchars(strip_tags($this->role));
        }
        
        // Add other updatable fields from profile page
        if ($this->department !== null) { $fields[] = "department=:department"; $params[':department'] = htmlspecialchars(strip_tags($this->department)); }
        if ($this->certification !== null) { $fields[] = "certification=:certification"; $params[':certification'] = htmlspecialchars(strip_tags($this->certification)); }
        if ($this->avatar !== null) { $fields[] = "avatar=:avatar"; $params[':avatar'] = htmlspecialchars(strip_tags($this->avatar)); }

        // Only update password if a new one is provided
        if (!empty($this->password)) {
            $fields[] = "password=:password";
            $params[':password'] = password_hash($this->password, PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("User update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a user.
     * @return bool
     */
    public function delete() {
        // Prevent deletion of super_admin accounts
        $check_query = "SELECT role FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $row = $this->database->fetch($check_query, [$this->id]);
        if ($row && $row['role'] === 'super_admin') {
            return false;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        try {
            $this->database->query($query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates a user's status.
     * @param int $userId
     * @param string $status
     * @return bool
     */
    public function updateStatus($userId, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        try {
            $this->database->query($query, [':status' => $status, ':id' => $userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to update user status for ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticate a user for login.
     * @return bool
     */
    public function login() {
        // The property could be an email or a username.
        // The calling code should set $this->email with the identifier.
        $login_identifier = $this->email;

        $query = "SELECT id, name, email, password, role, status
                  FROM " . $this->table_name . "
                  WHERE email = :identifier OR name = :identifier LIMIT 0,1";

        $row = $this->database->fetch($query, [':identifier' => $login_identifier]);

        if ($row && password_verify($this->password, $row['password'])) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->email = $row['email']; // Ensure the object has the correct email
            $this->password = null; // Clear password from memory
            return true;
        }
        return false;
    }

    /**
     * Find a user by their email address.
     * @return array|null
     */
    public function findByEmail() {
        $query = "SELECT id, name, email, role, status
                  FROM " . $this->table_name . "
                  WHERE email = ? LIMIT 0,1";

        $row = $this->database->fetch($query, [$this->email]);

        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            return $row;
        }
        return null;
    }

    /**
     * Read all users with a specific role.
     * @param string $role
     * @return array
     */
    public function readByRole($role) {
        $query = "SELECT id, name, email, role
                  FROM " . $this->table_name . "
                  WHERE role = ?
                  ORDER BY name ASC";

        return $this->database->fetchAll($query, [$role]);
    }

    /**
     * Count all users in the database.
     * @return int
     */
    public function countAll() {
        return $this->database->count($this->table_name);
    }

    /**
     * Search for users by name or email.
     * @param string $keywords
     * @return array
     */
    public function search($keywords) {
        $query = "SELECT id, name, email, role, status, department, certification, avatar, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE name LIKE ? OR email LIKE ?
                  ORDER BY name ASC";

        $keywords = "%" . htmlspecialchars(strip_tags($keywords)) . "%";
        return $this->database->fetchAll($query, [$keywords, $keywords]);
    }

    /**
     * Check if an email address already exists.
     * @return bool
     */
    public function emailExists() {
        return $this->database->exists($this->table_name, 'email = ?', [$this->email]);
    }

    /**
     * Get count of users grouped by role.
     * @return array
     */
    public function getUserCountByRole() {
        $query = "SELECT role, COUNT(*) as count FROM " . $this->table_name . " GROUP BY role";
        $results = $this->database->fetchAll($query);
        $result = [];
        foreach ($results as $row) {
            $result[$row['role']] = (int)$row['count'];
        }
        return $result;
    }

    /**
     * Count all users with the 'inspector' role.
     * @return int
     */
    public function countActiveInspectors() {
        return $this->database->count($this->table_name, "role = 'inspector'");
    }

    /**
     * Count inspectors who were active today.
     * This assumes a 'sessions' table is being used for session management.
     * @return int
     */
    public function countActiveInspectorsToday() {
        // This requires a 'sessions' table which is not in the unified schema yet.
        // A proper implementation would join users with a sessions table.
        try {
            $query = "SELECT COUNT(DISTINCT u.id) as count
                      FROM " . $this->table_name . " u
                      JOIN sessions s ON u.id = s.user_id
                      WHERE u.role = 'inspector' AND s.last_activity >= UNIX_TIMESTAMP(CURDATE())";
            $row = $this->database->fetch($query);
            return $row['count'] ?? 0;
        } catch (PDOException $e) {
            // This will catch if the 'sessions' table doesn't exist
            error_log("Could not count active inspectors today, likely 'sessions' table is missing: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generates a password reset token for a user by email.
     * @param string $email
     * @return string|false The token on success, false on failure.
     */
    public function generatePasswordResetToken($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $user_row = $this->database->fetch($query, [$email]);

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
                $this->database->query($update_query, $params);
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
        $user_row = $this->database->fetch($query, [$token]);

        if ($user_row) {
            return $user_row['id'];
        }
        return false;
    }

    /**
     * Updates a user's password and clears any reset/remember-me tokens for security.
     * @param int $userId
     * @param string $password
     * @return bool
     */
    public function updatePassword($userId, $password) {
        $query = "UPDATE " . $this->table_name . " SET password = :password, reset_token = NULL, reset_token_expires_at = NULL, remember_token_selector = NULL, remember_token_validator_hash = NULL, remember_token_expires_at = NULL WHERE id = :id";
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $params = [':password' => $hashed_password, ':id' => $userId];

        try {
            $this->database->query($query, $params);
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
            $this->database->query($query, $params);
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
        $query = "SELECT id, name, role, status, remember_token_validator_hash FROM " . $this->table_name . " WHERE remember_token_selector = ? AND remember_token_expires_at > NOW() LIMIT 0,1";
        $token_row = $this->database->fetch($query, [$selector]);

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
            $this->database->query($query, [':id' => $userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to clear remember me token for user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    private function clearRememberMeTokenBySelector($selector) {
        $query = "UPDATE " . $this->table_name . " SET remember_token_selector = NULL, remember_token_validator_hash = NULL, remember_token_expires_at = NULL WHERE remember_token_selector = :selector";
        $this->database->query($query, [':selector' => $selector]);
    }

    /**
     * Gets the last login/activity time for the current user from the sessions table.
     * @return string|null The last activity timestamp (Y-m-d H:i:s) or null if not found.
     */
    public function getLastLoginTime() {
        if (empty($this->id)) {
            return null;
        }

        try {
            $query = "SELECT MAX(last_activity) as last_login FROM sessions WHERE user_id = :user_id";
            $row = $this->database->fetch($query, [':user_id' => $this->id]);

            if ($row && $row['last_login']) {
                // last_activity is a UNIX timestamp
                return date('Y-m-d H:i:s', $row['last_login']);
            }
            return null;
        } catch (PDOException $e) {
            error_log("Could not get last login time for user {$this->id}: " . $e->getMessage());
            return null;
        }
    }
}
?>
