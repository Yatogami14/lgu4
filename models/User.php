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
        $stmt = $pdo->prepare($query);

        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password);
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
        
        $row = $this->database->fetch(Database::DB_CORE, $query, [$this->id]);

        if ($row) {
            $this->name = $row['name'] ?? null;
            $this->email = $row['email'] ?? null;
            $this->role = $row['role'] ?? null;
            $this->avatar = $row['avatar'] ?? null;
            $this->department = $row['department'] ?? null;
            $this->certification = $row['certification'] ?? null;
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            return $row;
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
        $fields = [];
        $params = [':id' => $this->id];

        if ($this->name !== null) { $fields[] = "name=:name"; $params[':name'] = $this->name; }
        if ($this->email !== null) { $fields[] = "email=:email"; $params[':email'] = $this->email; }
        if ($this->role !== null) { $fields[] = "role=:role"; $params[':role'] = $this->role; }
        if ($this->department !== null) { $fields[] = "department=:department"; $params[':department'] = $this->department; }
        if ($this->certification !== null) { $fields[] = "certification=:certification"; $params[':certification'] = $this->certification; }
        if ($this->avatar !== null) {
            $fields[] = "avatar=:avatar"; 
            $params[':avatar'] = $this->avatar;
        }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("User update failed for ID {$this->id}: " . $e->getMessage());
            return false;
        }
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
