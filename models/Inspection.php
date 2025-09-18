<?php
require_once __DIR__ . '/Business.php';
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
        
        $params = [
            ":business_id" => $this->business_id,
            ":inspector_id" => !empty($this->inspector_id) ? $this->inspector_id : null,
            ":inspection_type_id" => $this->inspection_type_id,
            ":scheduled_date" => $this->scheduled_date,
            ":status" => $this->status,
            ":priority" => $this->priority,
            ":notes" => $this->notes,
        ];

        try {
            $pdo = $this->database->getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $this->id = $pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("Inspection creation failed: " . $e->getMessage());
            return false;
        }
    }

    // Read single inspection
    public function readOne() {
        // Step 1: Fetch the base inspection record from the scheduling database.
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $row = $this->database->fetch($query, [$this->id]);

        if ($row) {
            // Step 2: Populate the base properties of the object.
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

            // Step 3: Fetch related data from the core database and hydrate the row.
            if ($this->business_id) {
                $business_query = "SELECT name, address, owner_id FROM businesses WHERE id = ?";
                $business_row = $this->database->fetch($business_query, [$this->business_id]);
                $row['business_name'] = $business_row['name'] ?? 'N/A';
                $row['business_address'] = $business_row['address'] ?? 'N/A';
                $row['business_owner_id'] = $business_row['owner_id'] ?? null;
            } else {
                $row['business_name'] = 'N/A';
                $row['business_address'] = 'N/A';
                $row['business_owner_id'] = null;
            }

            if ($this->inspection_type_id) {
                $type_query = "SELECT name FROM inspection_types WHERE id = ?";
                $type_row = $this->database->fetch($type_query, [$this->inspection_type_id]);
                $row['inspection_type'] = $type_row['name'] ?? 'N/A';
            } else {
                $row['inspection_type'] = 'N/A';
            }

            if ($this->inspector_id) {
                $inspector_query = "SELECT name FROM users WHERE id = ?";
                $inspector_row = $this->database->fetch($inspector_query, [$this->inspector_id]);
                $row['inspector_name'] = $inspector_row['name'] ?? 'Unassigned';
            } else {
                $row['inspector_name'] = 'Unassigned';
            }

            return $row;
        }
        return false;
    }

    // Read all inspections
    public function readAll() {
        // Step 1: Fetch all inspections from the scheduling database without joins.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  ORDER BY scheduled_date DESC";
        $inspections = $this->database->fetchAll($query);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_filter(array_column($inspections, 'inspector_id')));

        // Step 3: Fetch related data from the core database in separate, efficient queries.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspectors = [];
        if (!empty($inspector_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspector_ids), '?'));
            $inspectors_data = $this->database->fetchAll("SELECT id, name FROM users WHERE id IN ($in_clause)", $inspector_ids);
            foreach ($inspectors_data as $inspector) {
                $inspectors[$inspector['id']] = $inspector;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspectors[$inspection['inspector_id']]['name'] ?? 'Unassigned';
        }

        return $inspections;
    }

    // Read recent inspections
    public function readRecent($limit = 5) {
        // Step 1: Fetch recent inspections from the scheduling database without joins.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  ORDER BY updated_at DESC LIMIT " . (int)$limit;
        $inspections = $this->database->fetchAll($query);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all the foreign keys needed from the core database.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_filter(array_column($inspections, 'inspector_id')));

        // Step 3: Fetch the related data from the core database in separate, efficient queries.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspectors = [];
        if (!empty($inspector_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspector_ids), '?'));
            $inspectors_data = $this->database->fetchAll("SELECT id, name FROM users WHERE id IN ($in_clause)", $inspector_ids);
            foreach ($inspectors_data as $inspector) {
                $inspectors[$inspector['id']] = $inspector;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspectors[$inspection['inspector_id']]['name'] ?? 'Unassigned';
        }

        return $inspections;
    }

    // Read all assigned inspections
    public function readAllAssigned() {
        // Step 1: Fetch all assigned inspections from the scheduling database.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE inspector_id IS NOT NULL
                  ORDER BY scheduled_date DESC";
        $inspections = $this->database->fetchAll($query);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_column($inspections, 'inspector_id'));

        // Step 3: Fetch related data from the core database in separate, efficient queries.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspectors = [];
        if (!empty($inspector_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspector_ids), '?'));
            $inspectors_data = $this->database->fetchAll("SELECT id, name FROM users WHERE id IN ($in_clause)", $inspector_ids);
            foreach ($inspectors_data as $inspector) {
                $inspectors[$inspector['id']] = $inspector;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspectors[$inspection['inspector_id']]['name'] ?? 'Unassigned';
        }

        return $inspections;
    }

    // Get upcoming inspections
    public function readUpcoming($limit = 5) {
        // Step 1: Fetch upcoming inspections from the scheduling database.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE status = 'scheduled' AND scheduled_date >= CURDATE()
                  ORDER BY scheduled_date ASC LIMIT " . (int)$limit;

        $inspections = $this->database->fetchAll($query);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));

        // Step 3: Fetch related data from the core database.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
        }

        return $inspections;
    }

    // Update inspection
    public function complete() {
        $query = "UPDATE " . $this->table_name . "
                SET completed_date=:completed_date, status=:status, compliance_score=:compliance_score, total_violations=:total_violations, 
                    notes=:notes, notes_ai_analysis=:notes_ai_analysis, draft_data = NULL
                WHERE id=:id";

        // If status is 'completed' and completed_date is not set, set it to now.
        if ($this->status === 'completed' && empty($this->completed_date)) {
            $this->completed_date = date('Y-m-d H:i:s');
        }
        
        $params = [
            ":completed_date" => !empty($this->completed_date) ? $this->completed_date : null,
            ":status" => $this->status,
            ":compliance_score" => $this->compliance_score,
            ":total_violations" => $this->total_violations,
            ":notes" => $this->notes,
            ":notes_ai_analysis" => !empty($this->notes_ai_analysis) ? $this->notes_ai_analysis : null,
            ":id" => $this->id
        ];

        try {
            $this->database->query($query, $params);
            // If the inspection is marked as completed, trigger an update on the parent business's compliance status.
            if ($this->status === 'completed' && $this->business_id) {
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

        $params = [
            ':draft_data' => $draftData,
            ':notes' => $notes,
            ':id' => $this->id
        ];

        try {
            $this->database->query($query, $params);
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
            $this->database->query($query, [$this->id]);
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

        $params = [
            ":inspector_id" => $this->inspector_id,
            ":id" => $this->id
        ];

        try {
            $this->database->query($query, $params);
            error_log("Inspection->assignInspector success: Assigned inspector_id {$this->inspector_id} to inspection_id {$this->id}");
            return true;
        } catch (PDOException $e) {
            error_log("Inspection->assignInspector failed: " . $e->getMessage() . " for inspection_id {$this->id}, inspector_id {$this->inspector_id}");
            return false;
        }
    }

    // Count all inspections
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $row = $this->database->fetch($query);
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
        $row = $this->database->fetch($query, $business_ids);
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
        $row = $this->database->fetch($query, $business_ids);
        return $row['average'] ? round($row['average']) : 0;
    }

    // Count active violations
    public function countActiveViolations() {
        $query = "SELECT COUNT(*) as count FROM violations WHERE status IN ('open', 'in_progress')";
        // This query targets a different DB, so we need a connection to it.
        // The database manager handles this. We just need to specify the DB.
        $row = $this->database->fetch($query);
        return $row['count'] ?? 0;
    }

    // Get average compliance score
    public function getAverageCompliance() {
        $query = "SELECT AVG(compliance_score) as average FROM " . $this->table_name . " WHERE compliance_score IS NOT NULL";
        $row = $this->database->fetch($query);
        return $row['average'] ? round($row['average']) : 0;
    }

    // Get inspections by status
    public function readByStatus($status) {
        // Step 1: Fetch inspections by status from the scheduling database.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE status = ?
                  ORDER BY scheduled_date DESC";
        $inspections = $this->database->fetchAll($query, [$status]);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_filter(array_column($inspections, 'inspector_id')));

        // Step 3: Fetch related data from the core database.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspectors = [];
        if (!empty($inspector_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspector_ids), '?'));
            $inspectors_data = $this->database->fetchAll("SELECT id, name FROM users WHERE id IN ($in_clause)", $inspector_ids);
            foreach ($inspectors_data as $inspector) {
                $inspectors[$inspector['id']] = $inspector;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspectors[$inspection['inspector_id']]['name'] ?? 'Unassigned';
        }

        return $inspections;
    }
    // Get inspections by inspector
    public function readByInspector($inspector_id) {
        // Step 1: Fetch inspections for the inspector from the scheduling database.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE inspector_id = ?
                  ORDER BY scheduled_date DESC";
        $inspections = $this->database->fetchAll($query, [$inspector_id]);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));

        // Step 3: Fetch related data from the core database.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspector_row = $this->database->fetch("SELECT name FROM users WHERE id = ?", [$inspector_id]);
        $inspector_name = $inspector_row['name'] ?? 'Unassigned';

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspector_name;
        }

        return $inspections;
    }

    // Get available inspections (not assigned to any inspector)
    public function getAvailableInspections() {
        // Step 1: Fetch available inspections from the scheduling database.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE inspector_id IS NULL OR inspector_id = '' OR inspector_id = 0
                  ORDER BY scheduled_date ASC";
        $inspections = $this->database->fetchAll($query);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));

        // Step 3: Fetch related data from the core database.
        $businesses = [];
        if (!empty($business_ids)) {
            $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($in_clause)", $business_ids);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
        }

        return $inspections;
    }

    // Get inspection counts by status
    public function getInspectionStatsByStatus() {
        $query = "SELECT
                    status,
                    COUNT(id) as count
                  FROM " . $this->table_name . "
                  GROUP BY status";

        $stmt = $this->database->query($query);
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
        // Step 1: Fetch inspections for the business from the scheduling database.
        $query = "SELECT id, scheduled_date, inspection_type_id
                  FROM " . $this->table_name . "
                  WHERE business_id = ?
                  ORDER BY scheduled_date DESC";
        
        $inspections = $this->database->fetchAll($query, [$business_id]);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique inspection_type_ids.
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));

        // Step 3: Fetch the inspection type names from the core database.
        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type['name'];
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']] ?? 'N/A';
            unset($inspection['inspection_type_id']); // Clean up the ID field
        }

        return $inspections;
    }

    // Get inspections by a list of business IDs
    public function readByBusinessIds(array $business_ids) {
        if (empty($business_ids)) {
            return [];
        }
        
        // Step 1: Fetch inspections for the businesses from the scheduling database.
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE business_id IN (" . $in_clause . ")
                  ORDER BY scheduled_date DESC";
        
        $inspections = $this->database->fetchAll($query, $business_ids);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids_from_result = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_filter(array_column($inspections, 'inspector_id')));

        // Step 3: Fetch related data from the core database.
        $businesses = [];
        if (!empty($business_ids_from_result)) {
            $bus_in_clause = implode(',', array_fill(0, count($business_ids_from_result), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($bus_in_clause)", $business_ids_from_result);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $type_in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($type_in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspectors = [];
        if (!empty($inspector_ids)) {
            $insp_in_clause = implode(',', array_fill(0, count($inspector_ids), '?'));
            $inspectors_data = $this->database->fetchAll("SELECT id, name FROM users WHERE id IN ($insp_in_clause)", $inspector_ids);
            foreach ($inspectors_data as $inspector) {
                $inspectors[$inspector['id']] = $inspector;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspectors[$inspection['inspector_id']]['name'] ?? 'Unassigned';
        }

        return $inspections;
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
        return $this->database->fetch($query, [$business_id]);
    }

    /**
     * Finds all inspections for a business that have not been assigned an inspector.
     * @param int $business_id
     * @return array
     */
    public function findAllUnassignedForBusiness($business_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE business_id = ? AND (inspector_id IS NULL OR inspector_id = 0 OR inspector_id = '')
                  ORDER BY scheduled_date ASC";
        return $this->database->fetchAll($query, [$business_id]);
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
        return $this->database->fetchAll($query, $params);
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

        // Step 1: Fetch recent inspections for the given businesses.
        $in_clause = implode(',', array_fill(0, count($business_ids), '?'));
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  WHERE business_id IN (" . $in_clause . ")
                  ORDER BY updated_at DESC LIMIT " . (int)$limit;

        $inspections = $this->database->fetchAll($query, $business_ids);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $business_ids_from_result = array_unique(array_column($inspections, 'business_id'));
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_filter(array_column($inspections, 'inspector_id')));

        // Step 3: Fetch related data from the core database.
        $businesses = [];
        if (!empty($business_ids_from_result)) {
            $bus_in_clause = implode(',', array_fill(0, count($business_ids_from_result), '?'));
            $businesses_data = $this->database->fetchAll("SELECT id, name, address FROM businesses WHERE id IN ($bus_in_clause)", $business_ids_from_result);
            foreach ($businesses_data as $business) {
                $businesses[$business['id']] = $business;
            }
        }

        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $type_in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll("SELECT id, name FROM inspection_types WHERE id IN ($type_in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type['name'];
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['business_name'] = $businesses[$inspection['business_id']]['name'] ?? 'N/A';
            $inspection['business_address'] = $businesses[$inspection['business_id']]['address'] ?? 'N/A';
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']] ?? 'N/A';
            // Note: inspector_name is not used in the calling page, but added for consistency.
            $inspection['inspector_name'] = 'N/A'; // Placeholder, can be hydrated if needed.
        }

        return $inspections;
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

        return $this->database->fetchAll($query, [':days' => $days]);
    }
}
?>
