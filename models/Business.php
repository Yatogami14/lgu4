<?php
class Business {
    private $database;
    private $table_name = "businesses";
    private $conn;

    // Object properties based on your list and application usage
    public $id;
    public $name;
    public $address;
    public $owner_id;
    public $user_id; // Often used for the owner's account
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
    public $representative_name;
    public $representative_position;
    public $representative_contact;
    public $status;
    public $rejection_reason;
    public $created_at;
    public $updated_at;
    public $owner_name;
    public $contact_email;
    public $contact_phone;
    public $license_number;
    public $building_activity_type;

    public function __construct(Database $database) {
        $this->database = $database;
        $this->conn = $database->getConnection();
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET name=:name, address=:address, owner_id=:owner_id, contact_number=:contact_number,
                      email=:email, business_type=:business_type, registration_number=:registration_number,
                      establishment_date=:establishment_date, representative_name=:representative_name,
                      representative_position=:representative_position, representative_contact=:representative_contact,
                      status=:status";

        $params = [
            ':name' => htmlspecialchars(strip_tags($this->name)),
            ':address' => htmlspecialchars(strip_tags($this->address)),
            ':owner_id' => htmlspecialchars(strip_tags($this->owner_id)),
            ':contact_number' => htmlspecialchars(strip_tags($this->contact_number)),
            ':email' => htmlspecialchars(strip_tags($this->email)),
            ':business_type' => htmlspecialchars(strip_tags($this->business_type)),
            ':registration_number' => htmlspecialchars(strip_tags($this->registration_number)),
            ':establishment_date' => htmlspecialchars(strip_tags($this->establishment_date)),
            ':representative_name' => htmlspecialchars(strip_tags($this->representative_name)),
            ':representative_position' => htmlspecialchars(strip_tags($this->representative_position)),
            ':representative_contact' => htmlspecialchars(strip_tags($this->representative_contact)),
            ':status' => htmlspecialchars(strip_tags($this->status))
        ];

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Business creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET name=:name, address=:address, contact_number=:contact_number,
                      email=:email, business_type=:business_type, registration_number=:registration_number,
                      establishment_date=:establishment_date, representative_name=:representative_name,
                      representative_position=:representative_position, representative_contact=:representative_contact,
                      status=:status, rejection_reason=:rejection_reason, updated_at=NOW()
                  WHERE id=:id AND owner_id=:owner_id";

        $params = [
            ':name' => htmlspecialchars(strip_tags($this->name)),
            ':address' => htmlspecialchars(strip_tags($this->address)),
            ':contact_number' => htmlspecialchars(strip_tags($this->contact_number)),
            ':email' => htmlspecialchars(strip_tags($this->email)),
            ':business_type' => htmlspecialchars(strip_tags($this->business_type)),
            ':registration_number' => htmlspecialchars(strip_tags($this->registration_number)),
            ':establishment_date' => htmlspecialchars(strip_tags($this->establishment_date)),
            ':representative_name' => htmlspecialchars(strip_tags($this->representative_name)),
            ':representative_position' => htmlspecialchars(strip_tags($this->representative_position)),
            ':representative_contact' => htmlspecialchars(strip_tags($this->representative_contact)),
            ':status' => 'pending', // Reset status to pending for re-review
            ':rejection_reason' => null, // Clear rejection reason
            ':id' => $this->id,
            ':owner_id' => $this->owner_id // Security check
        ];

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Business update failed: " . $e->getMessage());
            return false;
        }
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name ASC";
        return $this->database->fetchAll($query);
    }

    public function readByOwnerId($owner_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE owner_id = ? ORDER BY name ASC";
        return $this->database->fetchAll($query, [$owner_id]);
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $row = $this->database->fetch($query, [$this->id]);
        if ($row) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            return $row;
        }
        return false;
    }

    public function findById($id) {
        $query = "SELECT b.id, b.name as business_name, b.owner_id as user_id FROM " . $this->table_name . " b WHERE b.id = ? LIMIT 0,1";
        return $this->database->fetch($query, [$id]);
    }

    public function updateStatus($id, $status, $reason = null) {
        $query = "UPDATE " . $this->table_name . " SET status = :status, updated_at = NOW()";
        $params = [':status' => $status, ':id' => $id];

        // Only add rejection_reason to the query if a reason is provided
        if ($reason !== null) {
            $query .= ", rejection_reason = :reason";
            $params[':reason'] = $reason;
        }

        $query .= " WHERE id = :id";

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Business status update failed: " . $e->getMessage());
            return false;
        }
    }

    public function countAll() {
        return $this->database->count($this->table_name);
    }

    public function countAllWithCompliance($search = '') {
        $query = "SELECT COUNT(b.id) as total_records
                  FROM " . $this->table_name . " b
                  WHERE b.status = 'verified' AND (b.name LIKE :search OR b.address LIKE :search OR b.business_type LIKE :search)";
        $params = [':search' => "%{$search}%"];
        $row = $this->database->fetch($query, $params);
        return $row['total_records'] ?? 0;
    }

    public function readAllWithCompliance($search = '', $limit = 10, $offset = 0) {
        $query = "SELECT b.id, b.name, b.business_type, b.address, b.compliance_score
                  FROM " . $this->table_name . " b
                  WHERE b.status = 'verified' AND (b.name LIKE :search OR b.address LIKE :search OR b.business_type LIKE :search)
                  ORDER BY b.compliance_score DESC, b.name ASC
                  LIMIT :limit OFFSET :offset";
        
        $params = [
            ':search' => "%{$search}%",
            ':limit' => $limit,
            ':offset' => $offset
        ];

        return $this->database->fetchAll($query, $params);
    }

    public function updateComplianceStatus() {
        // This logic recalculates and updates the compliance score for the business.
        // It might involve getting all recent inspections and averaging their scores.
        $query = "SELECT AVG(compliance_score) as avg_score FROM inspections WHERE business_id = ? AND status = 'completed'";
        $result = $this->database->fetch($query, [$this->id]);
        $new_score = $result && $result['avg_score'] !== null ? round($result['avg_score']) : null;

        $update_query = "UPDATE " . $this->table_name . " SET compliance_score = ? WHERE id = ?";
        $this->database->query($update_query, [$new_score, $this->id]);
    }

    public function getBusinessStats() {
        $query = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN compliance_score < 50 THEN 1 ELSE 0 END) as high_risk,
                    SUM(CASE WHEN compliance_score >= 50 AND compliance_score < 80 THEN 1 ELSE 0 END) as medium_risk,
                    SUM(CASE WHEN compliance_score >= 80 THEN 1 ELSE 0 END) as low_risk
                  FROM " . $this->table_name . "
                  WHERE status = 'verified'";
        
        $stats = $this->database->fetch($query);

        // Ensure all keys exist even if SUM returns NULL (e.g., on an empty table)
        return [
            'total' => (int)($stats['total'] ?? 0),
            'high_risk' => (int)($stats['high_risk'] ?? 0),
            'medium_risk' => (int)($stats['medium_risk'] ?? 0),
            'low_risk' => (int)($stats['low_risk'] ?? 0),
        ];
    }

    public function getBusinessCountByType() {
        $query = "SELECT business_type, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  WHERE status = 'verified'
                  GROUP BY business_type 
                  ORDER BY count DESC";
        
        return $this->database->fetchAll($query);
    }

    /**
     * Count all pending business applications.
     * @return int
     */
    public function countPending() {
        return $this->database->count($this->table_name, "status = 'pending'");
    }

    public function getInspector($business_id) {
        $query = "SELECT u.id, u.name 
                  FROM users u
                  JOIN " . $this->table_name . " b ON u.id = b.inspector_id
                  WHERE b.id = ?";
        return $this->database->fetch($query, [$business_id]);
    }

    public function getLastCompletedInspectionDate($business_id) {
        $query = "SELECT MAX(completed_date) as last_date 
                  FROM inspections 
                  WHERE business_id = ? AND status = 'completed'";
        $result = $this->database->fetch($query, [$business_id]);
        return $result['last_date'] ?? null;
    }

    public function assignInspector() {
        $query = "UPDATE " . $this->table_name . " SET inspector_id = :inspector_id, updated_at = NOW() WHERE id = :id";
        $params = [
            ':inspector_id' => $this->inspector_id,
            ':id' => $this->id
        ];
        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Business->assignInspector failed: " . $e->getMessage());
            return false;
        }
    }

    public function getComplianceStats($business_id) {
        // Dummy implementation. You would build a real query here.
        $stats = [
            'total_inspections' => 0,
            'avg_compliance' => 0,
            'total_violations' => 0,
            'compliance_rate' => 0,
        ];
        $query = "SELECT COUNT(*) as total, AVG(compliance_score) as avg_score FROM inspections WHERE business_id = ?";
        $inspection_stats = $this->database->fetch($query, [$business_id]);

        $stats['total_inspections'] = $inspection_stats['total'] ?? 0;
        $stats['avg_compliance'] = $inspection_stats['avg_score'] ? round($inspection_stats['avg_score']) : 0;

        return $stats;
    }

    public function getRecentInspections($business_id, $limit = 5) {
        $query = "SELECT i.scheduled_date, it.name as inspection_type, u.name as inspector_name, i.status, i.compliance_score 
                  FROM inspections i
                  JOIN inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  WHERE i.business_id = ?
                  ORDER BY i.scheduled_date DESC
                  LIMIT ?";
        return $this->database->fetchAll($query, [$business_id, $limit]);
    }

    // Other methods like create, update, delete, etc. would go here.
}
?>