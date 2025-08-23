<?php
class User {
    private $conn;
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
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, email=:email, password=:password, role=:role, 
                    avatar=:avatar, department=:department, certification=:certification";

        $stmt = $this->conn->prepare($query);

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
            return true;
        }
        return false;
    }

    // Read single user
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->avatar = $row['avatar'];
            $this->department = $row['department'];
            $this->certification = $row['certification'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Read all users
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Update user
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET name=:name, email=:email, role=:role, 
                    avatar=:avatar, department=:department, certification=:certification
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->department = htmlspecialchars(strip_tags($this->department));
        $this->certification = htmlspecialchars(strip_tags($this->certification));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":certification", $this->certification);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete user
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login user
    public function login() {
        $query = "SELECT id, name, email, password, role FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
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
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
?>
