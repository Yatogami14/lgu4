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
        return $this->database->fetchAll($query);
    }

    // Read single inspection type
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        
        $row = $this->database->fetch($query, [$this->id]);

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
        
        $params = [
            ':name' => $this->name,
            ':description' => $this->description
        ];

        try {
            $stmt = $this->database->query($query, $params);
            $this->id = $this->database->getConnection()->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("InspectionType creation failed: " . $e->getMessage());
            return false;
        }
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
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("InspectionType update failed for ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    // Delete inspection type
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        try {
            $this->database->query($query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("InspectionType deletion failed: " . $e->getMessage());
            return false;
        }
    }

    // Get inspection type by name
    public function getByName($name) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE name = ? LIMIT 0,1";
        
        $row = $this->database->fetch($query, [$name]);

        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            return $row;
        }
        return null;
    }
}
?>
