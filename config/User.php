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
    public $status = 'active'; // Default to active
    public $created_at;
    public $updated_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Create a new user.
     * Hashes the password before saving.
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, email=:email, password=:password, role=:role, status=:status";

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Hash the password before saving
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $params = [
            ":name" => $this->name,
            ":email" => $this->email,
            ":password" => $this->password,
            ":role" => $this->role,
            ":status" => $this->status
        ];

        try {
            $pdo = $this->database->getConnection();
            $stmt = $pdo->prepare($query);
            if ($stmt->execute($params)) {
                $this->id = $pdo->lastInsertId();
                return true;
            }
            return false;
        } catch (PDOException $e) {
            // Check for duplicate email (error code 1062)
            if ($e->errorInfo[1] == 1062) {
                error_log("User creation failed: Duplicate email address - " . $this->email);
            } else {
                error_log("User creation failed: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Read a single user's details.
     * Does not retrieve the password hash.
     * @return array|null
     */
    public function readOne() {
        $query = "SELECT id, name, email, role, status, created_at, updated_at
                  FROM " . $this->table_name . "
                  WHERE id = ? LIMIT 0,1";

        $row = $this->database->fetch($query, [$this->id]);

        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->password = null; // Password hash is not exposed
            return $row;
        }
        return null;
    }

    /**
     * Read all users with pagination.
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function readAll($limit = 10, $offset = 0) {
        $query = "SELECT id, name, email, role, status, created_at
                  FROM " . $this->table_name . "
                  ORDER BY created_at DESC
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->database->fetchAll($query);
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
        if ($this->role !== null) { $fields[] = "role=:role"; $params[':role'] = htmlspecialchars(strip_tags($this->role)); }
        if ($this->status !== null) { $fields[] = "status=:status"; $params[':status'] = htmlspecialchars(strip_tags($this->status)); }
        
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
     * Authenticate a user for login.
     * @return bool
     */
    public function login() {
        $query = "SELECT id, name, email, password, role, status
                  FROM " . $this->table_name . "
                  WHERE email = :email LIMIT 0,1";

        $row = $this->database->fetch($query, [':email' => $this->email]);

        if ($row && password_verify($this->password, $row['password']) && $row['status'] === 'active') {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->role = $row['role'];
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
        $query = "SELECT id, name, email, role, status
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
        $query = "SELECT id, name, email, role, status
                  FROM " . $this->table_name . "
                  WHERE name LIKE ? OR email LIKE ?
                  ORDER BY name ASC";

        $keywords = "%" . htmlspecialchars(strip_tags($keywords)) . "%";
        return $this->database->fetchAll($query, [$keywords, $keywords]);
    }
}
?>