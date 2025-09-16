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
                    due_date = :due_date,
                    created_by = :created_by";

        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);

        // Bind
        $stmt->bindParam(":inspection_id", $this->inspection_id);
        $stmt->bindParam(":business_id", $this->business_id);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":severity", $this->severity);
        $stmt->bindParam(":status", $this->status);
        $due_date = !empty($this->due_date) ? $this->due_date : null;
        $stmt->bindParam(":due_date", $due_date);
        $stmt->bindParam(":created_by", $this->created_by);

        if ($stmt->execute()) {
            $this->id = $pdo->lastInsertId();
            return true;
        }

        error_log("Violation creation failed: " . implode(";", $stmt->errorInfo()));
        return false;
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
            $this->database->query(Database::DB_VIOLATIONS, $query, $params);
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

        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);

        // Bind
        $stmt->bindParam(':inspection_id', $inspection_id, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id, PDO::PARAM_INT);

        if ($stmt->execute()) { return true; }
        error_log("Violation linking failed for violation ID {$this->id}: " . implode(";", $stmt->errorInfo()));
        return false;
    }

    /**
     * Read community-reported violations that are awaiting action (open, no inspection ID).
     * @param int $limit
     * @return array
     */
    public function readCommunityReportsAwaitingAction($limit = 5) {
        $query = "SELECT v.*, b.name as business_name
                  FROM " . $this->table_name . " v
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON v.business_id = b.id
                  WHERE v.inspection_id = 0 AND v.status = 'open'
                  ORDER BY v.created_at ASC
                  LIMIT :limit";
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Read all violations, optionally filtered by an array of business IDs.
     * @param array $business_ids
     * @return PDOStatement
     */
    public function readAll($business_ids = []) {
        $query = "SELECT v.*, b.name as business_name 
                  FROM " . $this->table_name . " v
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON v.business_id = b.id";

        if (!empty($business_ids)) {
            // Create placeholders for the IN clause
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $query .= " WHERE v.business_id IN (" . $in_clause . ")";
        }

        $query .= " ORDER BY v.created_at DESC";
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);

        if (!empty($business_ids)) {
            // Bind each business ID to the placeholder
            foreach ($business_ids as $k => $id) {
                $stmt->bindValue(($k + 1), $id, PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Read all violations for a specific inspection.
     * @param int $inspection_id
     * @return PDOStatement
     */
    public function readByInspectionId($inspection_id) {
        $query = "SELECT v.* 
                  FROM " . $this->table_name . " v
                  WHERE v.inspection_id = ?
                  ORDER BY v.created_at DESC";

        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $inspection_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get violation statistics.
     * @param array $business_ids
     * @return array
     */
    public function getViolationStats($business_ids = []) {
        $query = "SELECT
                    COUNT(v.id) as total,
                    SUM(CASE WHEN v.status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN v.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN v.status = 'resolved' THEN 1 ELSE 0 END) as resolved
                  FROM " . $this->table_name . " v";
    
        if (!empty($business_ids)) {
            $query .= " LEFT JOIN " . Database::DB_SCHEDULING . ".inspections i ON v.inspection_id = i.id";
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $query .= " WHERE i.business_id IN (" . $in_clause . ")";
        }

        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        if (!empty($business_ids)) {
            foreach ($business_ids as $k => $id) {
                $stmt->bindValue(($k + 1), $id, PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return $stats;
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

        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $stats = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
     * @return PDOStatement
     */
    public function readByInspectorId($inspector_id) {
        $query = "SELECT v.*, b.name as business_name
                  FROM " . $this->table_name . " v
                  LEFT JOIN " . Database::DB_SCHEDULING . ".inspections i ON v.inspection_id = i.id
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  WHERE i.inspector_id = ?
                  ORDER BY v.created_at DESC";
        
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $inspector_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get violation statistics for a specific inspector.
     * @param int $inspector_id
     * @return array
     */
    public function getViolationStatsByInspectorId($inspector_id) {
        $query = "SELECT
                    COUNT(v.id) as total,
                    SUM(CASE WHEN v.status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN v.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN v.status = 'resolved' THEN 1 ELSE 0 END) as resolved
                  FROM " . $this->table_name . " v
                  LEFT JOIN " . Database::DB_SCHEDULING . ".inspections i ON v.inspection_id = i.id
                  WHERE i.inspector_id = ?";
        
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $inspector_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Read all violations for a specific creator.
     * @param int $creator_id
     * @return PDOStatement
     */
    public function readByCreatorId($creator_id) {
        $query = "SELECT v.*, b.name as business_name
                  FROM " . $this->table_name . " v
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON v.business_id = b.id
                  WHERE v.created_by = ?
                  ORDER BY v.created_at DESC";
        
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $creator_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get violation statistics for a specific creator.
     * @param int $creator_id
     * @return array
     */
    public function getViolationStatsByCreatorId($creator_id) {
        $query = "SELECT
                    COUNT(id) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
                  FROM " . $this->table_name . "
                  WHERE created_by = ?";
        
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $creator_id, PDO::PARAM_INT);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure all keys exist even if SUM returns NULL
        return array_map(fn($v) => $v ?? 0, $stats);
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
        
        $pdo = $this->database->getConnection(Database::DB_VIOLATIONS);
        $stmt = $pdo->prepare($query);
        foreach ($business_ids as $k => $id) {
            $stmt->bindValue(($k + 1), $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] ?? 0;
    }
}
?>