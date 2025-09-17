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
     * @return array
     */
    public function readByUserId($user_id) {
        // Step 1: Get all specializations for the user from the scheduling DB
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ?";
        $specializations = $this->database->fetchAll(Database::DB_SCHEDULING, $query, [$user_id]);

        if (empty($specializations)) {
            return [];
        }

        // Step 2: Collect all inspection_type_ids
        $inspection_type_ids = array_unique(array_column($specializations, 'inspection_type_id'));

        // Step 3: Fetch inspection type details from the core DB
        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_query = "SELECT id, name, description FROM inspection_types WHERE id IN ($in_clause)";
            $types_data = $this->database->fetchAll(Database::DB_CORE, $types_query, $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        // Step 4: Hydrate the specializations with the type names and descriptions
        foreach ($specializations as &$spec) {
            if (isset($inspection_types[$spec['inspection_type_id']])) {
                $spec['inspection_type_name'] = $inspection_types[$spec['inspection_type_id']]['name'];
                $spec['description'] = $inspection_types[$spec['inspection_type_id']]['description'];
            } else {
                $spec['inspection_type_name'] = 'N/A';
                $spec['description'] = 'N/A';
            }
        }

        return $specializations;
    }

    /**
     * Create a new specialization record.
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
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
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
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
     * @return array
     */
    public function getInspectorsBySpecialization($inspection_type_id) {
        // Step 1: Get all specializations for the given type from the scheduling DB
        $query = "SELECT * FROM " . $this->table_name . " WHERE inspection_type_id = ?";
        $specializations = $this->database->fetchAll(Database::DB_SCHEDULING, $query, [$inspection_type_id]);

        if (empty($specializations)) {
            return [];
        }

        // Step 2: Collect all user_ids
        $user_ids = array_unique(array_column($specializations, 'user_id'));

        // Step 3: Fetch inspector details from the core DB
        $inspectors = [];
        if (!empty($user_ids)) {
            $in_clause = implode(',', array_fill(0, count($user_ids), '?'));
            $users_query = "SELECT * FROM users WHERE id IN ($in_clause) AND role = 'inspector'";
            $users_data = $this->database->fetchAll(Database::DB_CORE, $users_query, $user_ids);
            foreach ($users_data as $user) {
                $inspectors[$user['id']] = $user;
            }
        }

        // Step 4: Combine the data
        $result = [];
        foreach ($specializations as $spec) {
            if (isset($inspectors[$spec['user_id']])) {
                $result[] = array_merge($inspectors[$spec['user_id']], [
                    'proficiency_level' => $spec['proficiency_level'],
                    'certification_date' => $spec['certification_date']
                ]);
            }
        }

        // Step 5: Sort the results as the original query did
        usort($result, function($a, $b) {
            $proficiencyOrder = ['expert' => 3, 'intermediate' => 2, 'beginner' => 1];
            $a_prof = $proficiencyOrder[$a['proficiency_level']] ?? 0;
            $b_prof = $proficiencyOrder[$b['proficiency_level']] ?? 0;

            if ($a_prof !== $b_prof) {
                return $b_prof <=> $a_prof; // DESC
            }
            return $a['name'] <=> $b['name']; // ASC
        });
        
        return $result;
    }

    /**
     * Check if a user has a specific specialization.
     * @param int $user_id
     * @param int $inspection_type_id
     * @return bool
     */
    public function userHasSpecialization($user_id, $inspection_type_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE user_id = ? AND inspection_type_id = ?";
        $result = $this->database->fetch(Database::DB_SCHEDULING, $query, [$user_id, $inspection_type_id]);
        return !empty($result);
    }
}
?>