<?php
class Violation {
    private $database;
    private $table_name = "violations";

    public $id;
    public $inspection_id;
    public $business_id;
    public $checklist_response_id;
    public $media_id;
    public $description;
    public $severity;
    public $status;
    public $due_date;
    public $resolved_date;
    public $created_by;
    public $hash;
    public $created_at;
    public $updated_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    inspection_id = :inspection_id,
                    business_id = :business_id,
                    description = :description,
                    severity = :severity,
                    status = :status,
                    due_date = :due_date";

        $params = [
            ':inspection_id' => $this->inspection_id,
            ':business_id' => $this->business_id,
            ':description' => $this->description,
            ':severity' => $this->severity,
            ':status' => $this->status,
            ':due_date' => !empty($this->due_date) ? $this->due_date : null
        ];

        try {
            $stmt = $this->database->query($query, $params);
            $this->id = $this->database->getConnection()->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("Violation creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        $fields = [];
        $params = [':id' => $this->id];

        if ($this->description !== null) { $fields[] = "description=:description"; $params[':description'] = $this->description; }
        if ($this->severity !== null) { $fields[] = "severity=:severity"; $params[':severity'] = $this->severity; }
        if ($this->status !== null) { $fields[] = "status=:status"; $params[':status'] = $this->status; }
        if ($this->due_date !== null) { $fields[] = "due_date=:due_date"; $params[':due_date'] = $this->due_date; }
        if ($this->resolved_date !== null) { $fields[] = "resolved_date=:resolved_date"; $params[':resolved_date'] = $this->resolved_date; }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Violation update failed for ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Links a violation to a specific inspection and updates its status.
     * @param int $inspection_id
     * @return bool
     */
    public function linkToInspection($inspection_id) {
        $query = "UPDATE " . $this->table_name . "
                  SET inspection_id = :inspection_id,
                      status = 'in_progress'
                  WHERE id = :id";

        $params = [
            ':inspection_id' => $inspection_id,
            ':id' => $this->id
        ];

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Violation linking failed for violation ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Read community-reported violations that are awaiting action (open, no inspection ID).
     * @param int $limit
     * @return array
     */
    public function readCommunityReportsAwaitingAction($limit = 5) {
        $query = "SELECT v.*
                  FROM " . $this->table_name . " v
                  WHERE v.inspection_id = 0 AND v.status = 'open'
                  ORDER BY v.created_at ASC
                  LIMIT :limit";

        $violations = $this->database->fetchAll($query, [':limit' => (int)$limit]);
        return $this->hydrateViolationsWithBusinessData($violations);
    }

    /**
     * Read all violations, optionally filtered by an array of business IDs.
     * @param array $business_ids
     * @return array
     */
    public function readAll($business_ids = []) {
        // Step 1: Fetch violations from the violations database.
        $query = "SELECT v.*
                  FROM " . $this->table_name . " v
                  ";
        $params = [];
        if (!empty($business_ids)) {
            // Create placeholders for the IN clause
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $query .= " WHERE v.business_id IN (" . $in_clause . ")";
            $params = $business_ids;
        }

        $query .= " ORDER BY v.created_at DESC";
        $violations = $this->database->fetchAll($query, $params);
        // Step 2: Hydrate with business data from the core database.
        return $this->hydrateViolationsWithBusinessData($violations);
    }

    /**
     * Read all violations for a specific inspection.
     * @param int $inspection_id
     * @return array
     */
    public function readByInspectionId($inspection_id) {
        $query = "SELECT v.* 
                  FROM " . $this->table_name . " v
                  WHERE v.inspection_id = ?
                  ORDER BY v.created_at DESC";

        return $this->database->fetchAll($query, [$inspection_id]);
    }

    /**
     * Get violation statistics.
     * @param array $business_ids
     * @return array
     */
    public function getViolationStats($business_ids = []) {
        $query = "SELECT COUNT(v.id) as total,
                         SUM(CASE WHEN v.status = 'open' THEN 1 ELSE 0 END) as open,
                         SUM(CASE WHEN v.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                         SUM(CASE WHEN v.status = 'resolved' THEN 1 ELSE 0 END) as resolved
                  FROM " . $this->table_name . " v";
        
        $params = [];
        if (!empty($business_ids)) {
            // Filter violations by business IDs
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $query .= " WHERE v.business_id IN ($in_clause)";
            $params = $business_ids;
        }

        $stats = $this->database->fetch($query, $params);

        // Ensure all keys exist even if SUM returns NULL
        return array_map(fn($v) => $v ?? 0, $stats);
    }

    /**
     * Get violation statistics by severity.
     * @return array
     */
    public function getViolationStatsBySeverity() {
        $query = "SELECT
                    severity,
                    COUNT(id) as count
                  FROM " . $this->table_name . "
                  GROUP BY severity";

        $results = $this->database->fetchAll($query);
        $stats = [];
        foreach ($results as $row) {
            $stats[$row['severity']] = (int)$row['count'];
        }
        
        $severities = ['low', 'medium', 'high', 'critical'];
        foreach ($severities as $severity) {
            if (!isset($stats[$severity])) {
                $stats[$severity] = 0;
            }
        }
        
        return $stats;
    }

    /**
     * Read all violations for a specific inspector.
     * @param int $inspector_id
     * @return array
     */
    public function readByInspectorId($inspector_id) {
        // Step 1: Get all inspections for the inspector from the scheduling DB
        $inspection_query = "SELECT id, business_id FROM inspections WHERE inspector_id = ?";
        $inspections = $this->database->fetchAll($inspection_query, [$inspector_id]);

        if (empty($inspections)) {
            return [];
        }

        $inspection_ids = array_column($inspections, 'id');

        // Step 2: Get all violations for those inspection IDs from the violations DB
        $vio_in_clause = implode(',', array_fill(0, count($inspection_ids), '?'));
        $violation_query = "SELECT * FROM " . $this->table_name . " WHERE inspection_id IN ($vio_in_clause) ORDER BY created_at DESC";
        $violations = $this->database->fetchAll($violation_query, $inspection_ids);

        // Step 3: Hydrate with business data
        return $this->hydrateViolationsWithBusinessData($violations);
    }

    /**
     * Get violation statistics for a specific inspector.
     * @param int $inspector_id
     * @return array
     */
    public function getViolationStatsByInspectorId($inspector_id) {
        // Step 1: Get inspection IDs for the inspector
        $inspection_query = "SELECT id FROM inspections WHERE inspector_id = ?";
        $inspection_rows = $this->database->fetchAll($inspection_query, [$inspector_id]);

        if (empty($inspection_rows)) {
            return ['total' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0];
        }
        $inspection_ids = array_column($inspection_rows, 'id');

        // Step 2: Get stats for those inspection IDs
        $in_clause = implode(',', array_fill(0, count($inspection_ids), '?'));
        $query = "SELECT COUNT(id) as total,
                         SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                         SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                         SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                  FROM " . $this->table_name . "
                  WHERE inspection_id IN ($in_clause)";
        
        $stats = $this->database->fetch($query, $inspection_ids);

        // Ensure all keys exist even if SUM returns NULL
        return array_map(fn($v) => $v ?? 0, $stats);
    }

    /**
     * Read all violations for a specific creator.
     * @param int $creator_id
     * @return array
     */
    public function readByCreatorId($creator_id) {
        // The 'created_by' column does not exist in the database schema.
        // Returning an empty array to prevent fatal errors.
        // To restore this functionality, the 'created_by' column must be added to the 'violations' table.
        return [];
    }

    /**
     * Get violation statistics for a specific creator.
     * @param int $creator_id
     * @return array
     */
    public function getViolationStatsByCreatorId($creator_id) {
        // The 'created_by' column does not exist in the database schema.
        // Returning zero stats to prevent fatal errors.
        return ['total' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0];
    }

    /**
     * Count active violations for a list of businesses.
     * @param array $business_ids
     * @return int
     */
    public function countActiveForBusinesses(array $business_ids) {
        if (empty($business_ids)) {
            return 0;
        }
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE status IN ('open', 'in_progress') AND business_id IN (" . $in_clause . ")";

        $row = $this->database->fetch($query, $business_ids);
        return $row['count'] ?? 0;
    }

    /**
     * Hydrates an array of violations with business names from the core database.
     * @param array $violations
     * @return array
     */
    private function hydrateViolationsWithBusinessData(array $violations) {
        if (empty($violations)) {
            return [];
        }

        $business_ids = array_unique(array_column($violations, 'business_id'));
        if (empty($business_ids)) {
            return $violations; // No businesses to hydrate
        }

        $businesses = [];
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $businesses_data = $this->database->fetchAll("SELECT id, name FROM businesses WHERE id IN ($in_clause)", $business_ids);
        foreach ($businesses_data as $business) {
            $businesses[$business['id']] = $business;
        }

        foreach ($violations as &$violation) {
            $violation['business_name'] = $businesses[$violation['business_id']]['name'] ?? 'N/A';
        }

        return $violations;
    }
}
?>