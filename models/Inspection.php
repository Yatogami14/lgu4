<?php
class Inspection {
    private $table_name = "inspections";
    private $database;

    public $id;
    public $business_id;
    public $inspector_id;
    public $inspection_type_id;
    public $scheduled_date;
    public $completed_date;
    public $status;
    public $priority;
    public $compliance_score;
    public $total_violations;
    public $notes;
    public $notes_ai_analysis;
    public $draft_data;
    public $created_at;
    public $updated_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    // Create inspection
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET business_id=:business_id, inspector_id=:inspector_id, 
                    inspection_type_id=:inspection_type_id, scheduled_date=:scheduled_date,
                    status=:status, priority=:priority, notes=:notes";

        // Sanitize input
        $this->business_id = htmlspecialchars(strip_tags($this->business_id));
        $this->inspector_id = !empty($this->inspector_id) ? htmlspecialchars(strip_tags($this->inspector_id)) : null;
        $this->inspection_type_id = htmlspecialchars(strip_tags($this->inspection_type_id));
        $this->scheduled_date = htmlspecialchars(strip_tags($this->scheduled_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        $params = [
            ":business_id" => $this->business_id,
            ":inspector_id" => $this->inspector_id,
            ":inspection_type_id" => $this->inspection_type_id,
            ":scheduled_date" => $this->scheduled_date,
            ":status" => $this->status,
            ":priority" => $this->priority,
            ":notes" => $this->notes,
        ];

        try {
            $this->database->query(Database::DB_SCHEDULING, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Inspection creation failed: " . $e->getMessage());
            return false;
        }
    }

    // Read single inspection
    public function readOne() {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  WHERE i.id = ? LIMIT 0,1";

        $row = $this->database->fetch(Database::DB_SCHEDULING, $query, [$this->id]);

        if ($row) {
            $this->business_id = $row['business_id'] ?? null;
            $this->inspector_id = $row['inspector_id'] ?? null;
            $this->inspection_type_id = $row['inspection_type_id'] ?? null;
            $this->scheduled_date = $row['scheduled_date'] ?? null;
            $this->completed_date = $row['completed_date'] ?? null;
            $this->status = $row['status'] ?? null;
            $this->priority = $row['priority'] ?? null;
            $this->compliance_score = $row['compliance_score'] ?? null;
            $this->total_violations = $row['total_violations'] ?? null;
            $this->notes = $row['notes'] ?? null;
            $this->notes_ai_analysis = $row['notes_ai_analysis'] ?? null;
            $this->draft_data = $row['draft_data'] ?? null;
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            return $row;
        }
        return false;
    }

    // Read all inspections
    public function readAll() {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  ORDER BY i.scheduled_date DESC";

        return $this->database->query(Database::DB_SCHEDULING, $query);
    }

    // Read recent inspections
    public function readRecent($limit = 5) {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  ORDER BY i.updated_at DESC LIMIT " . (int)$limit;

        return $this->database->fetchAll(Database::DB_SCHEDULING, $query);
    }

    // Read all assigned inspections
    public function readAllAssigned() {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  WHERE i.inspector_id IS NOT NULL
                  ORDER BY i.scheduled_date DESC";

        return $this->database->query(Database::DB_SCHEDULING, $query);
    }

    // Get upcoming inspections
    public function readUpcoming($limit = 5) {
        $query = "SELECT i.*, b.name as business_name, it.name as inspection_type
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  WHERE i.status = 'scheduled' AND i.scheduled_date >= CURDATE()
                  ORDER BY i.scheduled_date ASC LIMIT " . (int)$limit;

        return $this->database->fetchAll(Database::DB_SCHEDULING, $query);
    }

    // Update inspection
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET completed_date=:completed_date, status=:status, compliance_score=:compliance_score, total_violations=:total_violations, 
                    notes=:notes, notes_ai_analysis=:notes_ai_analysis, draft_data = NULL
                WHERE id=:id";

        // Sanitize input
        // If status is 'completed' and completed_date is not set, set it to now.
        if ($this->status === 'completed' && empty($this->completed_date)) {
            $this->completed_date = date('Y-m-d H:i:s');
        }
        $this->completed_date = !empty($this->completed_date) ? htmlspecialchars(strip_tags($this->completed_date)) : null;
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->compliance_score = htmlspecialchars(strip_tags($this->compliance_score));
        $this->total_violations = htmlspecialchars(strip_tags($this->total_violations));
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        // notes_ai_analysis is JSON, so we don't use strip_tags
        $this->notes_ai_analysis = !empty($this->notes_ai_analysis) ? $this->notes_ai_analysis : null;
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $params = [
            ":completed_date" => $this->completed_date,
            ":status" => $this->status,
            ":compliance_score" => $this->compliance_score,
            ":total_violations" => $this->total_violations,
            ":notes" => $this->notes,
            ":notes_ai_analysis" => $this->notes_ai_analysis,
            ":id" => $this->id,
        ];

        try {
            $this->database->query(Database::DB_SCHEDULING, $query, $params);
            // If the inspection is marked as completed, trigger an update on the parent business's compliance status.
            if ($this->status === 'completed' && $this->business_id) {
                require_once 'Business.php';
                $business = new Business($this->database);
                $business->id = $this->business_id;
                $business->updateComplianceStatus();
            }
            return true;
        } catch (PDOException $e) {
            error_log("Inspection update failed: " . $e->getMessage());
            return false;
        }
    }

    // Save inspection draft
    public function saveDraft($draftData, $notes) {
        $query = "UPDATE " . $this->table_name . "
                  SET draft_data = :draft_data,
                      notes = :notes,
                      status = 'in_progress'
                  WHERE id = :id";
        
        // Sanitize
        $notes = htmlspecialchars(strip_tags($notes));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $params = [
            ':draft_data' => $draftData,
            ':notes' => $notes,
            ':id' => $this->id,
        ];

        try {
            $this->database->query(Database::DB_SCHEDULING, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Draft saving failed: " . $e->getMessage());
            return false;
        }
    }

    // Delete inspection
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        try {
            $this->database->query(Database::DB_SCHEDULING, $query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Inspection deletion failed: " . $e->getMessage());
            return false;
        }
    }

    // Assign inspector to inspection
    public function assignInspector() {
        $query = "UPDATE " . $this->table_name . "
                SET inspector_id=:inspector_id, updated_at=NOW()
                WHERE id=:id";

        // Sanitize input
        $this->inspector_id = htmlspecialchars(strip_tags($this->inspector_id));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $params = [
            ":inspector_id" => $this->inspector_id,
            ":id" => $this->id,
        ];

        try {
            $this->database->query(Database::DB_SCHEDULING, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Inspection->assignInspector failed: " . $e->getMessage());
            return false;
        }
    }

    // Count all inspections
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $row = $this->database->fetch(Database::DB_SCHEDULING, $query);
        return $row['count'] ?? 0;
    }

    /**
     * Count all inspections for a list of businesses.
     * @param array $business_ids
     * @return int
     */
    public function countAllForBusinesses(array $business_ids) {
        if (empty($business_ids)) {
            return 0;
        }
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE business_id IN (" . $in_clause . ")";
        $row = $this->database->fetch(Database::DB_SCHEDULING, $query, $business_ids);
        return $row['count'] ?? 0;
    }

    /**
     * Get average compliance score for a list of businesses.
     * @param array $business_ids
     * @return int
     */
    public function getAverageComplianceForBusinesses(array $business_ids) {
        if (empty($business_ids)) {
            return 0;
        }
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $query = "SELECT AVG(compliance_score) as average FROM " . $this->table_name . " WHERE compliance_score IS NOT NULL AND business_id IN (" . $in_clause . ")";
        $row = $this->database->fetch(Database::DB_SCHEDULING, $query, $business_ids);
        return $row['average'] ? round($row['average']) : 0;
    }

    // Count active violations
    public function countActiveViolations() {
        $query = "SELECT COUNT(*) as count FROM " . Database::DB_VIOLATIONS . ".violations WHERE status IN ('open', 'in_progress')";
        // This query targets a different DB, so we need a connection to it.
        // The database manager handles this. We just need to specify the DB.
        $row = $this->database->fetch(Database::DB_VIOLATIONS, $query);
        return $row['count'] ?? 0;
    }

    // Get average compliance score
    public function getAverageCompliance() {
        $query = "SELECT AVG(compliance_score) as average FROM " . $this->table_name . " WHERE compliance_score IS NOT NULL";
        $row = $this->database->fetch(Database::DB_SCHEDULING, $query);
        return $row['average'] ? round($row['average']) : 0;
    }

    // Get inspections by status
    public function readByStatus($status) {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  WHERE i.status = ?
                  ORDER BY i.scheduled_date DESC";

        return $this->database->query(Database::DB_SCHEDULING, $query, [$status]);
    }

    // Get inspections by inspector
    public function readByInspector($inspector_id) {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  WHERE i.inspector_id = ?
                  ORDER BY i.scheduled_date DESC";

        return $this->database->query(Database::DB_SCHEDULING, $query, [$inspector_id]);
    }

    // Get inspections by user ID
    public function readByUserId($user_id, $limit = 5) {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, it.name as inspection_type
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  WHERE i.inspector_id = ?
                  ORDER BY i.scheduled_date DESC LIMIT " . (int)$limit;

        return $this->database->fetchAll(Database::DB_SCHEDULING, $query, [$user_id]);
    }

    // Get available inspections (not assigned to any inspector)
    public function getAvailableInspections() {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, it.name as inspection_type
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  WHERE i.inspector_id IS NULL OR i.inspector_id = ''
                  ORDER BY i.scheduled_date ASC";

        return $this->database->query(Database::DB_SCHEDULING, $query);
    }

    // Get inspection counts by status
    public function getInspectionStatsByStatus() {
        $query = "SELECT
                    status,
                    COUNT(id) as count
                  FROM " . $this->table_name . "
                  GROUP BY status";

        $stmt = $this->database->query(Database::DB_SCHEDULING, $query);
        $stats = [
            'scheduled' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'cancelled' => 0,
            'requested' => 0,
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (array_key_exists($row['status'], $stats)) {
                $stats[$row['status']] = (int)$row['count'];
            }
        }
        return $stats;
    }

    // Get inspections by business ID
    public function readByBusinessId($business_id) {
        $query = "SELECT i.id, i.scheduled_date, it.name as inspection_type
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  WHERE i.business_id = ?
                  ORDER BY i.scheduled_date DESC";

        return $this->database->query(Database::DB_SCHEDULING, $query, [$business_id]);
    }

    // Get inspections by a list of business IDs
    public function readByBusinessIds(array $business_ids) {
        if (empty($business_ids)) {
            return [];
        }
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  WHERE i.business_id IN (" . $in_clause . ")
                  ORDER BY i.scheduled_date DESC";

        return $this->database->fetchAll(Database::DB_SCHEDULING, $query, $business_ids);
    }

    /**
     * Finds the next upcoming inspection for a business that has not been assigned an inspector.
     * @param int $business_id
     * @return array|false
     */
    public function findNextUnassignedForBusiness($business_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE business_id = ? AND (inspector_id IS NULL OR inspector_id = 0 OR inspector_id = '')
                  ORDER BY scheduled_date ASC
                  LIMIT 1";
        return $this->database->fetch(Database::DB_SCHEDULING, $query, [$business_id]);
    }

    /**
     * Finds all upcoming inspections for a business that have not been assigned an inspector.
     * @param int $business_id
     * @return array
     */
    public function findAllUnassignedForBusiness($business_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE business_id = ? AND (inspector_id IS NULL OR inspector_id = 0 OR inspector_id = '')
                  ORDER BY scheduled_date ASC";
        return $this->database->fetchAll(Database::DB_SCHEDULING, $query, [$business_id]);
    }

    /**
     * Get compliance score trend over a number of days for specific businesses.
     * @param array $business_ids
     * @param int $days
     * @return array
     */
    public function getComplianceTrendForBusinesses(array $business_ids, $days = 30) {
        if (empty($business_ids)) {
            return [];
        }
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));

        $query = "SELECT 
                    DATE(completed_date) as inspection_date, 
                    AVG(compliance_score) as avg_score
                  FROM " . $this->table_name . "
                  WHERE completed_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  AND status = 'completed'
                  AND compliance_score IS NOT NULL
                  AND business_id IN (" . $in_clause . ")
                  GROUP BY DATE(completed_date)
                  ORDER BY DATE(completed_date) ASC";

        $params = array_merge([$days], $business_ids);
        return $this->database->fetchAll(Database::DB_SCHEDULING, $query, $params);
    }

    /**
     * Read recent inspections for a list of businesses.
     * @param array $business_ids
     * @param int $limit
     * @return array
     */
    public function readRecentForBusinesses(array $business_ids, $limit = 5) {
        if (empty($business_ids)) {
            return [];
        }
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN " . Database::DB_CORE . ".businesses b ON i.business_id = b.id
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN " . Database::DB_CORE . ".users u ON i.inspector_id = u.id
                  WHERE i.business_id IN (" . $in_clause . ")
                  ORDER BY i.updated_at DESC LIMIT " . (int)$limit;

        return $this->database->fetchAll(Database::DB_SCHEDULING, $query, $business_ids);
    }

    /**
     * Get compliance score trend over a number of days.
     * @param int $days
     * @return array
     */
    public function getComplianceTrend($days = 30) {
        $query = "SELECT 
                    DATE(completed_date) as inspection_date, 
                    AVG(compliance_score) as avg_score
                  FROM " . $this->table_name . "
                  WHERE completed_date >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  AND status = 'completed'
                  AND compliance_score IS NOT NULL
                  GROUP BY DATE(completed_date)
                  ORDER BY DATE(completed_date) ASC";

        return $this->database->fetchAll(Database::DB_SCHEDULING, $query, [':days' => $days]);
    }
}
?>
