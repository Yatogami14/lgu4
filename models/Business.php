<?php
class Business {
    private $database;
    private $table_name = "businesses";

    public $id;
    public $name;
    public $address;
    public $owner_id;
    public $inspector_id;
    public $contact_number;
    public $email;
    public $business_type;
    public $registration_number;
    public $establishment_date;
    public $inspection_frequency;
    public $last_inspection_date;
    public $next_inspection_date;
    public $is_compliant;
    public $compliance_score;
    public $created_at;
    public $updated_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    // Create business
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, address=:address, owner_id=:owner_id, contact_number=:contact_number, 
                      email=:email, business_type=:business_type, registration_number=:registration_number";

        $params = [
            ":name" => $this->name,
            ":address" => $this->address,
            ":owner_id" => !empty($this->owner_id) ? $this->owner_id : null,
            ":contact_number" => $this->contact_number,
            ":email" => $this->email,
            ":business_type" => $this->business_type,
            ":registration_number" => $this->registration_number
        ];

        try {
            $pdo = $this->database->getConnection(Database::DB_CORE);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $this->id = $pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("Business creation failed: " . $e->getMessage());
            return false;
        }
    }

    // Read single business
    public function readOne() {
        $query = "SELECT b.*, u.name as owner_name, u.email as owner_email, ui.name as inspector_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  LEFT JOIN users ui ON b.inspector_id = ui.id
                  WHERE b.id = ? LIMIT 0,1";

        $row = $this->database->fetch(Database::DB_CORE, $query, [$this->id]);

        if ($row) {
            $this->name = $row['name'] ?? null;
            $this->address = $row['address'] ?? null;
            $this->owner_id = $row['owner_id'] ?? null;
            $this->inspector_id = $row['inspector_id'] ?? null;
            $this->contact_number = $row['contact_number'] ?? null;
            $this->email = $row['email'] ?? null;
            $this->business_type = $row['business_type'] ?? null;
            $this->registration_number = $row['registration_number'] ?? null;
            $this->establishment_date = $row['establishment_date'] ?? null;
            $this->inspection_frequency = $row['inspection_frequency'] ?? null;
            $this->last_inspection_date = $row['last_inspection_date'] ?? null;
            $this->next_inspection_date = $row['next_inspection_date'] ?? null;
            $this->is_compliant = $row['is_compliant'] ?? null;
            $this->compliance_score = $row['compliance_score'] ?? null;
            $this->created_at = $row['created_at'] ?? null;
            $this->updated_at = $row['updated_at'] ?? null;
            return $row;
        }
        return null;
    }

    // Read all businesses
    public function readAll() {
        $query = "SELECT b.*, u.name as owner_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  ORDER BY b.created_at DESC";

        return $this->database->fetchAll(Database::DB_CORE, $query);
    }

    /**
     * Read all businesses with their latest compliance score for public display.
     * @param string $search
     * @return array
     */
    public function readAllWithCompliance($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT 
                    b.id, b.name, b.address, b.business_type, b.compliance_score
                  FROM " . $this->table_name . " b";
        $params = [];
        if (!empty($search)) {
            $query .= " WHERE b.name LIKE :search OR b.address LIKE :search OR b.business_type LIKE :search";
            $params[':search'] = "%" . htmlspecialchars(strip_tags($search)) . "%";
        }

        // PDO does not support binding for LIMIT/OFFSET, so we cast to int for safety.
        $query .= " ORDER BY b.name ASC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->database->fetchAll(Database::DB_CORE, $query, $params);
    }

    /**
     * Count all businesses with their latest compliance score for pagination.
     * @param string $search
     * @return int
     */
    public function countAllWithCompliance($search = '') {
        $query = "SELECT COUNT(b.id) as total_rows FROM " . $this->table_name . " b";
        $params = [];
        if (!empty($search)) {
            $query .= " WHERE b.name LIKE :search OR b.address LIKE :search OR b.business_type LIKE :search";
            $params[':search'] = "%" . htmlspecialchars(strip_tags($search)) . "%";
        }

        $row = $this->database->fetch(Database::DB_CORE, $query, $params);
        return $row['total_rows'] ?? 0;
    }

    // Update business
    public function update() {
        $fields = [];
        $params = [':id' => $this->id];

        // Build the SET clause dynamically based on which properties are not null
        if ($this->name !== null) { $fields[] = "name=:name"; $params[':name'] = $this->name; }
        if ($this->address !== null) { $fields[] = "address=:address"; $params[':address'] = $this->address; }
        if ($this->owner_id !== null) { $fields[] = "owner_id=:owner_id"; $params[':owner_id'] = $this->owner_id; }
        if ($this->inspector_id !== null) { $fields[] = "inspector_id=:inspector_id"; $params[':inspector_id'] = $this->inspector_id; }
        if ($this->contact_number !== null) { $fields[] = "contact_number=:contact_number"; $params[':contact_number'] = $this->contact_number; }
        if ($this->email !== null) { $fields[] = "email=:email"; $params[':email'] = $this->email; }
        if ($this->business_type !== null) { $fields[] = "business_type=:business_type"; $params[':business_type'] = $this->business_type; }
        if ($this->registration_number !== null) { $fields[] = "registration_number=:registration_number"; $params[':registration_number'] = $this->registration_number; }
        if ($this->establishment_date !== null) { $fields[] = "establishment_date=:establishment_date"; $params[':establishment_date'] = $this->establishment_date; }
        if ($this->inspection_frequency !== null) { $fields[] = "inspection_frequency=:inspection_frequency"; $params[':inspection_frequency'] = $this->inspection_frequency; }
        if ($this->last_inspection_date !== null) { $fields[] = "last_inspection_date=:last_inspection_date"; $params[':last_inspection_date'] = $this->last_inspection_date; }
        if ($this->next_inspection_date !== null) { $fields[] = "next_inspection_date=:next_inspection_date"; $params[':next_inspection_date'] = $this->next_inspection_date; }
        if ($this->is_compliant !== null) { $fields[] = "is_compliant=:is_compliant"; $params[':is_compliant'] = $this->is_compliant; }
        if ($this->compliance_score !== null) { $fields[] = "compliance_score=:compliance_score"; $params[':compliance_score'] = $this->compliance_score; }

        if (empty($fields)) {
            // Nothing to update
            return true;
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Business update failed: " . $e->getMessage());
            return false;
        }
    }

    // Delete business
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        try {
            $this->database->query(Database::DB_CORE, $query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Business deletion failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign an inspector to a business.
     * This is a specific update method to avoid nullifying other fields.
     * @return bool
     */
    public function assignInspector() {
        $query = "UPDATE " . $this->table_name . " SET inspector_id = :inspector_id WHERE id = :id";

        $params = [
            ':inspector_id' => $this->inspector_id,
            ':id' => $this->id
        ];

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Assign inspector failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count all businesses for a specific owner.
     * @param int $owner_id
     * @return int
     */
    public function countAllForOwner($owner_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE owner_id = ?";
        $row = $this->database->fetch(Database::DB_CORE, $query, [$owner_id]);
        return $row['count'] ?? 0;
    }

    // Count all businesses
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $row = $this->database->fetch(Database::DB_CORE, $query);
        return $row['count'] ?? 0;
    }

    // Get business statistics by risk
    public function getBusinessStats() {
        $query = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN compliance_score < 50 THEN 1 ELSE 0 END) as high_risk,
                    SUM(CASE WHEN compliance_score >= 50 AND compliance_score < 80 THEN 1 ELSE 0 END) as medium_risk,
                    SUM(CASE WHEN compliance_score >= 80 THEN 1 ELSE 0 END) as low_risk
                  FROM " . $this->table_name;

        $stats = $this->database->fetch(Database::DB_CORE, $query);

        // Ensure all keys exist even if SUM returns NULL
        $stats['total'] = $stats['total'] ?? 0;
        $stats['high_risk'] = $stats['high_risk'] ?? 0;
        $stats['medium_risk'] = $stats['medium_risk'] ?? 0;
        $stats['low_risk'] = $stats['low_risk'] ?? 0;

        return $stats;
    }

    // Get business count by type
    public function getBusinessCountByType() {
        $query = "SELECT business_type, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE business_type IS NOT NULL AND business_type != ''
                  GROUP BY business_type 
                  ORDER BY count DESC";

        return $this->database->fetchAll(Database::DB_CORE, $query);
    }

    // Search businesses
    public function search($keywords) {
        $query = "SELECT b.*, u.name as owner_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.name LIKE ? OR b.address LIKE ? OR b.business_type LIKE ?
                  ORDER BY b.created_at DESC";

        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        return $this->database->fetchAll(Database::DB_CORE, $query, [$keywords, $keywords, $keywords]);
    }

    // Get businesses by type
    public function readByType($business_type) {
        $query = "SELECT b.*, u.name as owner_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.business_type = ?
                  ORDER BY b.created_at DESC";

        return $this->database->fetchAll(Database::DB_CORE, $query, [$business_type]);
    }

    // Get compliance statistics for business
    public function getComplianceStats($business_id) {
        $query = "SELECT
                    COUNT(id) as total_inspections,
                    AVG(CASE WHEN status = 'completed' THEN compliance_score ELSE NULL END) as avg_compliance,
                    SUM(total_violations) as total_violations,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_inspections
                  FROM inspections
                  WHERE business_id = ?";

        $stats = $this->database->fetch(Database::DB_SCHEDULING, $query, [$business_id]);

        // Format the results
        $stats['avg_compliance'] = !empty($stats['avg_compliance']) ? round($stats['avg_compliance']) : 0;
        $stats['total_violations'] = $stats['total_violations'] ?? 0;
        $stats['completed_inspections'] = $stats['completed_inspections'] ?? 0;
        $stats['compliance_rate'] = ($stats['total_inspections'] > 0) ?
            round(($stats['completed_inspections'] / $stats['total_inspections']) * 100) : 0;

        return $stats;
    }

    // Count active violations for a specific business
    public function countActiveViolationsByBusinessId($business_id) {
        // This assumes a 'violations' table exists with 'business_id' and 'status' columns.
        // The status 'open' and 'in_progress' are considered active.
        $query = "SELECT COUNT(*) as active_violations_count
                  FROM violations
                  WHERE business_id = ? AND status IN ('open', 'in_progress')";

        $row = $this->database->fetch(Database::DB_VIOLATIONS, $query, [$business_id]);

        return $row['active_violations_count'] ?? 0;
    }

    // Get recent inspections for business
    public function getRecentInspections($business_id, $limit = 5) {
        // Step 1: Fetch recent inspections from the scheduling database.
        $query = "SELECT *
                  FROM inspections
                  WHERE business_id = ?
                  ORDER BY updated_at DESC
                  LIMIT ?";
        
        $inspections = $this->database->fetchAll(Database::DB_SCHEDULING, $query, [$business_id, (int)$limit]);

        if (empty($inspections)) {
            return [];
        }

        // Step 2: Collect all unique foreign keys.
        $inspection_type_ids = array_unique(array_column($inspections, 'inspection_type_id'));
        $inspector_ids = array_unique(array_filter(array_column($inspections, 'inspector_id')));

        // Step 3: Fetch related data from the core database.
        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll(Database::DB_CORE, "SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type;
            }
        }

        $inspectors = [];
        if (!empty($inspector_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspector_ids), '?'));
            $inspectors_data = $this->database->fetchAll(Database::DB_CORE, "SELECT id, name FROM users WHERE id IN ($in_clause)", $inspector_ids);
            foreach ($inspectors_data as $inspector) {
                $inspectors[$inspector['id']] = $inspector;
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($inspections as &$inspection) {
            $inspection['inspection_type'] = $inspection_types[$inspection['inspection_type_id']]['name'] ?? 'N/A';
            $inspection['inspector_name'] = $inspectors[$inspection['inspector_id']]['name'] ?? 'Unassigned';
        }

        return $inspections;
    }

    // Get businesses by owner ID
    public function readByOwnerId($owner_id) {
        $query = "SELECT b.*, u.name as owner_name
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.owner_id = ?
                  ORDER BY b.created_at DESC";

        return $this->database->fetchAll(Database::DB_CORE, $query, [$owner_id]);
    }

    // Calculate next inspection date based on frequency
    public function calculateNextInspectionDate($last_inspection_date = null) {
        if (!$last_inspection_date) {
            $last_inspection_date = date('Y-m-d');
        }

        $next_date = new DateTime($last_inspection_date);
        
        switch ($this->inspection_frequency) {
            case 'weekly':
                $next_date->modify('+1 week');
                break;
            case 'monthly':
                $next_date->modify('+1 month');
                break;
            case 'quarterly':
                $next_date->modify('+3 months');
                break;
            default:
                $next_date->modify('+1 month');
        }

        return $next_date->format('Y-m-d');
    }

    // Get inspection frequency based on business type
    public function getInspectionFrequencyByType($business_type) {
        $frequency_map = [
            'Restaurant' => 'monthly',
            'Food Establishment' => 'monthly',
            'Hotel' => 'quarterly',
            'Hospital' => 'monthly',
            'School' => 'quarterly',
            'Factory' => 'monthly',
            'Office Building' => 'quarterly',
            'Shopping Mall' => 'quarterly',
            'Construction Site' => 'weekly',
            'Gas Station' => 'monthly'
        ];

        return $frequency_map[$business_type] ?? 'monthly';
    }

    // Create inspection reminder notification
    public function createInspectionReminderNotification($days_before = 7) {
        require_once 'Notification.php';
        $notification = new Notification($this->database);
        
        $next_inspection = $this->next_inspection_date;
        $days_remaining = floor((strtotime($next_inspection) - time()) / (60 * 60 * 24));
        
        if ($days_remaining <= $days_before) {
            $message = "Upcoming inspection for " . $this->name . " in " . $days_remaining . " days";
            $notification->user_id = $this->owner_id;
            $notification->message = $message;
            $notification->type = 'alert';
            $notification->related_entity_type = 'business';
            $notification->related_entity_id = $this->id;
            
            return $notification->create();
        }
        
        return false;
    }

    // Update compliance status based on inspection results
    public function updateComplianceStatus() {
        // Get the average compliance score from all completed inspections for this business
        $stats = $this->getComplianceStats($this->id);
        $avg_compliance = $stats['avg_compliance'];
        // Get the most recent completed inspection date using the refactored method
        $last_inspection_date = $this->getLastCompletedInspectionDate($this->id);

        // Load current business data to get inspection frequency
        $current_data = $this->readOne();
        if (!$current_data) {
            return false; // Business not found
        }
        $next_inspection_date = $this->calculateNextInspectionDate($last_inspection_date);
        
        // Prepare the update query
        $query = "UPDATE " . $this->table_name . "
                SET compliance_score = :compliance_score,
                    is_compliant = :is_compliant,
                    last_inspection_date = :last_inspection_date,
                    next_inspection_date = :next_inspection_date
                WHERE id = :id";

        $is_compliant = ($avg_compliance >= 80) ? 1 : 0;

        $params = [
            ":compliance_score" => $avg_compliance,
            ":is_compliant" => $is_compliant,
            ":last_inspection_date" => $last_inspection_date,
            ":next_inspection_date" => $next_inspection_date,
            ":id" => $this->id
        ];

        try {
            $this->database->query(Database::DB_CORE, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Update compliance status failed: " . $e->getMessage());
            return false;
        }
    }

    // Get businesses due for inspection
    public function getBusinessesDueForInspection($limit = 10) {
        $query = "SELECT b.*, u.name as owner_name, u.email as owner_email
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                  AND b.next_inspection_date >= CURDATE()
                  ORDER BY b.next_inspection_date ASC
                  LIMIT ?";

        return $this->database->fetchAll(Database::DB_CORE, $query, [(int)$limit]);
    }

    // Get overdue inspections
    public function getOverdueInspections($limit = 10) {
        $query = "SELECT b.*, u.name as owner_name, u.email as owner_email
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.next_inspection_date < CURDATE()
                  ORDER BY b.next_inspection_date ASC
                  LIMIT ?";

        return $this->database->fetchAll(Database::DB_CORE, $query, [(int)$limit]);
    }

    // Get inspector for a business
    public function getInspector($business_id) {
        $query = "SELECT u.id, u.name, u.email
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.inspector_id = u.id
                  WHERE b.id = ? AND b.inspector_id IS NOT NULL";

        $row = $this->database->fetch(Database::DB_CORE, $query, [$business_id]);
        return $row ?: null;
    }

    /**
     * Gets the date of the most recently completed inspection for a business.
     * @param int $business_id
     * @return string|null
     */
    public function getLastCompletedInspectionDate($business_id) {
        $query = "SELECT MAX(completed_date) as last_inspection_date
                  FROM inspections
                  WHERE business_id = ? AND status = 'completed'";
        $row = $this->database->fetch(Database::DB_SCHEDULING, $query, [$business_id]);
        return $row['last_inspection_date'] ?? null;
    }
}
?>
