<?php
class Specialization {
    private $database;
    private $table_name = "inspector_specializations";

    // Object properties
    public $id;
    public $user_id;
    public $inspection_type_id;
    public $proficiency_level;
    public $certification_date;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Get all specializations for a given user.
     * @param int $user_id
     * @return PDOStatement
     */
    public function readByUserId($user_id) {
        $query = "SELECT its.*, it.name as inspection_type_name, it.description
                  FROM " . Database::DB_SCHEDULING . "." . $this->table_name . " its
                  JOIN " . Database::DB_CORE . ".inspection_types it ON its.inspection_type_id = it.id
                  WHERE its.user_id = ?";
        
        return $this->database->query(Database::DB_SCHEDULING, $query, [$user_id]);
    }

    /**
     * Create a new specialization record.
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . Database::DB_SCHEDULING . "." . $this->table_name . " 
                  SET user_id=:user_id, inspection_type_id=:inspection_type_id, 
                      proficiency_level=:proficiency_level, certification_date=:certification_date";
        
        $params = [
            ":user_id" => $this->user_id,
            ":inspection_type_id" => $this->inspection_type_id,
            ":proficiency_level" => $this->proficiency_level,
            ":certification_date" => $this->certification_date
        ];

        try {
            $pdo = $this->database->getConnection(Database::DB_SCHEDULING);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $this->id = $pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("Specialization creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a specialization by its ID.
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . Database::DB_SCHEDULING . "." . $this->table_name . " WHERE id = ?";
        
        try {
            $this->database->query(Database::DB_SCHEDULING, $query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Specialization deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all inspectors who have a specific specialization.
     * @param int $inspection_type_id
     * @return PDOStatement
     */
    public function getInspectorsBySpecialization($inspection_type_id) {
        $query = "SELECT u.*, its.proficiency_level, its.certification_date
                  FROM " . Database::DB_CORE . ".users u
                  JOIN " . Database::DB_SCHEDULING . "." . $this->table_name . " its ON u.id = its.user_id
                  WHERE its.inspection_type_id = ? AND u.role = 'inspector'
                  ORDER BY its.proficiency_level DESC, u.name ASC";
        
        return $this->database->query(Database::DB_SCHEDULING, $query, [$inspection_type_id]);
    }

    /**
     * Check if a user has a specific specialization.
     * @param int $user_id
     * @param int $inspection_type_id
     * @return bool
     */
    public function userHasSpecialization($user_id, $inspection_type_id) {
        $query = "SELECT id FROM " . Database::DB_SCHEDULING . "." . $this->table_name . " 
                  WHERE user_id = ? AND inspection_type_id = ?";
        $result = $this->database->fetch(Database::DB_SCHEDULING, $query, [$user_id, $inspection_type_id]);
        return !empty($result);
    }
}
?>