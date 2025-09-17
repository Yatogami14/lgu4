<?php
class InspectionType {
    private $database;
    private $table_name = "inspection_types";

    public $id;
    public $name;
    public $description;
    public $created_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    // Read all inspection types
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        return $this->database->fetchAll(Database::DB_CORE, $query);
    }

    // Read single inspection type
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $row = $this->database->fetch(Database::DB_CORE, $query, [$this->id]);

        if ($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            return $row;
        }
        return false;
    }

    // Create inspection type
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, description=:description";

        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":description", $this->description);

        if ($stmt->execute()) {
            $this->id = $pdo->lastInsertId();
            return true;
        }
        return false;
    }

    // Update inspection type
    public function update() {
        $fields = [];
        $params = [':id' => $this->id];

        if ($this->name !== null) { $fields[] = "name=:name"; $params[':name'] = $this->name; }
        if ($this->description !== null) { $fields[] = "description=:description"; $params[':description'] = $this->description; }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("InspectionType update failed for ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    // Delete inspection type
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

    // Get inspection type by name
    public function getByName($name) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE name = ? LIMIT 0,1";
        $pdo = $this->database->getConnection(Database::DB_CORE);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $name);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
}
?>
