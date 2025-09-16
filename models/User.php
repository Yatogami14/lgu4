<?php
class User {
    private $database;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $avatar;
    public $department;
    public $certification;
    public $created_at;
    public $reset_token;
    public $reset_token_expires_at;
    public $remember_token_selector;
    public $remember_token_validator_hash;
    public $remember_token_expires_at;
    public $updated_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    // Create user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, email=:email, password=:password, role=:role,
                    avatar=:avatar, department=:department, certification=:certification";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->department = htmlspecialchars(strip_tags($this->department));
        $this->certification = htmlspecialchars(strip_tags($this->certification));

        // Hash password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":certification", $this->certification);

        if ($stmt->execute()) {
            $this->id = $pdo->lastInsertId();
            return true;
        }
        return false;
    }

    // Read single user
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'] ?? null;
            $this->email = $row['email'] ?? null;
            $this->role = $row['role'] ?? null;
            $this->avatar = $row['avatar'] ?? null;
            $this->department = $row['department'] ?? null;
            $this->certification = $row['certification'] ?? null;
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            return true;
        }
        return false;
    }

    // Read all users
    public function readAll($role = null) {
        $query = "SELECT * FROM " . $this->table_name;

        if ($role) {
            $query .= " WHERE role = :role";
        }

        $query .= " ORDER BY created_at DESC";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);

        if ($role) {
            $stmt->bindParam(":role", $role);
        }

        $stmt->execute();
        return $stmt;
    }

    // Search users by name or email
    public function search($keywords) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE name LIKE :keywords OR email LIKE :keywords
                  ORDER BY name ASC";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);

        // Sanitize
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // Bind
        $stmt->bindParam(":keywords", $keywords);

        $stmt->execute();

        return $stmt;
    }


    // Read users by specific role
    public function readByRole($role) {
        return $this->readAll($role);
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . " SET name=:name, email=:email, role=:role, 
                    department=:department, certification=:certification";

        // Conditionally update avatar
        if ($this->avatar !== null) {
            $query .= ", avatar=:avatar";
        }

        $query .= " WHERE id=:id";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->department = htmlspecialchars(strip_tags($this->department));
        $this->certification = htmlspecialchars(strip_tags($this->certification));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":certification", $this->certification);
        $stmt->bindParam(":id", $this->id);

        if ($this->avatar !== null) {
            $this->avatar = htmlspecialchars(strip_tags($this->avatar));
            $stmt->bindParam(":avatar", $this->avatar);
        }

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update user password
    public function updatePassword($password) {
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);

        // Sanitize and hash password
        $this->password = htmlspecialchars(strip_tags($password));
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            // Clear reset token after successful password update
            $this->clearPasswordResetToken();
            $this->clearRememberMeToken();
            return true;
        }
        return false;
    }

    // Generate a password reset token for a user by email
    public function generatePasswordResetToken() {
        // Find user by email first
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];

            // Generate a unique token
            $this->reset_token = bin2hex(random_bytes(32));
            
            // Set token expiry (1 hour from now) in UTC to avoid timezone issues
            $this->reset_token_expires_at = gmdate('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token to database
            $update_query = "UPDATE " . $this->table_name . " 
                             SET reset_token = :reset_token, reset_token_expires_at = :reset_token_expires_at 
                             WHERE id = :id";
            
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->bindParam(':reset_token', $this->reset_token);
            $update_stmt->bindParam(':reset_token_expires_at', $this->reset_token_expires_at);
            $update_stmt->bindParam(':id', $this->id);

            if ($update_stmt->execute()) {
                return $this->reset_token;
            }
        }
        return false;
    }

    // Validate a password reset token
    public function validatePasswordResetToken($token) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 0,1";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $token);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->id = $row['id'];
            return true;
        }
        return false;
    }

    // Generate a "Remember Me" token
    public function generateRememberMeToken() {
        try {
            $selector = bin2hex(random_bytes(16));
            $validator = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Log the error and return false if random byte generation fails
            error_log('Failed to generate random bytes for remember me token: ' . $e->getMessage());
            return false;
        }

        $validator_hash = hash('sha256', $validator);
        $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30); // 30 days

        $query = "UPDATE " . $this->table_name . "
                  SET remember_token_selector = :selector,
                      remember_token_validator_hash = :validator_hash,
                      remember_token_expires_at = :expires_at
                  WHERE id = :id";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':selector', $selector);
        $stmt->bindParam(':validator_hash', $validator_hash);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return ['selector' => $selector, 'validator' => $validator];
        }

        return false;
    }

    // Validate a "Remember Me" token
    public function validateRememberMeToken($selector, $validator) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE remember_token_selector = ? AND remember_token_expires_at > NOW()
                  LIMIT 0,1";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $selector);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $validator_hash_from_db = $row['remember_token_validator_hash'];
            $provided_validator_hash = hash('sha256', $validator);

            if (hash_equals($validator_hash_from_db, $provided_validator_hash)) {
                $this->id = $row['id'];
                $this->readOne(); // Populate user object
                return true;
            }
        }

        // If token is invalid or expired, clear it for the given selector to prevent reuse
        if ($row) {
            $this->clearRememberMeTokenBySelector($selector);
        }
        return false;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login() {
        $query = "SELECT id, name, email, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($this->password, $row['password'])) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            return true;
        }
        return false;
    }

    // Count active inspectors
    public function countActiveInspectors() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE role = 'inspector'";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Count active inspectors today
    public function countActiveInspectorsToday() {
        // Count distinct inspectors active in the last 24 hours (86400 seconds)
        $active_threshold = time() - 86400;

        $query = "SELECT COUNT(DISTINCT s.user_id) as count
                  FROM sessions s
                  JOIN " . $this->table_name . " u ON s.user_id = u.id
                  WHERE s.last_activity > :active_threshold
                  AND u.role = 'inspector'";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':active_threshold', $active_threshold, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] ?? 0;
    }
    // Check if email exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }

    // Get inspector specializations
    public function getSpecializations() {
        $query = "SELECT its.*, it.name as inspection_type_name, it.description
                  FROM " . Database::DB_SCHEDULING . ".inspector_specializations its
                  JOIN " . Database::DB_CORE . ".inspection_types it ON its.inspection_type_id = it.id
                  WHERE its.user_id = ?";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        return $stmt;
    }

    // Add inspector specialization
    public function addSpecialization($inspection_type_id, $proficiency_level = 'intermediate', $certification_date = null) {
        $query = "INSERT INTO " . Database::DB_SCHEDULING . ".inspector_specializations 
                  SET user_id=:user_id, inspection_type_id=:inspection_type_id, 
                      proficiency_level=:proficiency_level, certification_date=:certification_date";
        
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        
        $stmt->bindParam(":user_id", $this->id);
        $stmt->bindParam(":inspection_type_id", $inspection_type_id);
        $stmt->bindParam(":proficiency_level", $proficiency_level);
        $stmt->bindParam(":certification_date", $certification_date);
        
        return $stmt->execute();
    }

    // Remove inspector specialization
    public function removeSpecialization($specialization_id) {
        $query = "DELETE FROM " . Database::DB_SCHEDULING . ".inspector_specializations WHERE id = ? AND user_id = ?";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $specialization_id);
        $stmt->bindParam(2, $this->id);
        return $stmt->execute();
    }

    // Get inspectors by specialization
    public function getInspectorsBySpecialization($inspection_type_id) {
        $query = "SELECT u.*, its.proficiency_level, its.certification_date
                  FROM " . Database::DB_CORE . ".users u
                  JOIN " . Database::DB_SCHEDULING . ".inspector_specializations its ON u.id = its.user_id
                  WHERE its.inspection_type_id = ? AND u.role = 'inspector'
                  ORDER BY its.proficiency_level DESC, u.name ASC";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $inspection_type_id);
        $stmt->execute();
        return $stmt;
    }

    // Check if inspector has specialization
    public function hasSpecialization($inspection_type_id) {
        $query = "SELECT id FROM " . Database::DB_SCHEDULING . ".inspector_specializations 
                  WHERE user_id = ? AND inspection_type_id = ?";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->bindParam(2, $inspection_type_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Clear password reset token
    private function clearPasswordResetToken() {
        $query = "UPDATE " . $this->table_name . " SET reset_token = NULL, reset_token_expires_at = NULL WHERE id = :id";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    // Clear "Remember Me" token for the current user
    public function clearRememberMeToken() {
        $query = "UPDATE " . $this->table_name . "
                  SET remember_token_selector = NULL,
                      remember_token_validator_hash = NULL,
                      remember_token_expires_at = NULL
                  WHERE id = :id";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Clear "Remember Me" token by selector (for security)
    private function clearRememberMeTokenBySelector($selector) {
        $query = "UPDATE " . $this->table_name . " SET remember_token_selector = NULL, remember_token_validator_hash = NULL, remember_token_expires_at = NULL WHERE remember_token_selector = :selector";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':selector', $selector);
        return $stmt->execute();
    }
    // Get count of users grouped by role
    public function getUserCountByRole() {
        $query = "SELECT role, COUNT(*) as count FROM " . $this->table_name . " GROUP BY role";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['role']] = (int)$row['count'];
        }
        return $result;
    }
}
?>
