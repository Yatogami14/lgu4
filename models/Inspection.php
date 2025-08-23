<?php
class Inspection {
    private $conn;
    private $table_name = "inspections";

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
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create inspection
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET business_id=:business_id, inspector_id=:inspector_id, 
                    inspection_type_id=:inspection_type_id, scheduled_date=:scheduled_date,
                    status=:status, priority=:priority, notes=:notes";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->business_id = htmlspecialchars(strip_tags($this->business_id));
        $this->inspector_id = htmlspecialchars(strip_tags($this->inspector_id));
        $this->inspection_type_id = htmlspecialchars(strip_tags($this->inspection_type_id));
        $this->scheduled_date = htmlspecialchars(strip_tags($this->scheduled_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind parameters
        $stmt->bindParam(":business_id", $this->business_id);
        $stmt->bindParam(":inspector_id", $this->inspector_id);
        $stmt->bindParam(":inspection_type_id", $this->inspection_type_id);
        $stmt->bindParam(":scheduled_date", $this->scheduled_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":notes", $this->notes);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read single inspection
    public function readOne() {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN businesses b ON i.business_id = b.id
                  LEFT JOIN inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  WHERE i.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->business_id = $row['business_id'];
            $this->inspector_id = $row['inspector_id'];
            $this->inspection_type_id = $row['inspection_type_id'];
            $this->scheduled_date = $row['scheduled_date'];
            $this->completed_date = $row['completed_date'];
            $this->status = $row['status'];
            $this->priority = $row['priority'];
            $this->compliance_score = $row['compliance_score'];
            $this->total_violations = $row['total_violations'];
            $this->notes = $row['notes'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return $row;
        }
        return false;
    }

    // Read all inspections
    public function readAll() {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN businesses b ON i.business_id = b.id
                  LEFT JOIN inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  ORDER BY i.scheduled_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Read recent inspections
    public function readRecent($limit = 5) {
        $query = "SELECT i.*, b.name as business_name, b.address as business_address, 
                         it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN businesses b ON i.business_id = b.id
                  LEFT JOIN inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  ORDER BY i.created_at DESC LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $inspections = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inspections[] = $row;
        }
        return $inspections;
    }

    // Update inspection
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET business_id=:business_id, inspector_id=:inspector_id, 
                    inspection_type_id=:inspection_type_id, scheduled_date=:scheduled_date,
                    completed_date=:completed_date, status=:status, priority=:priority,
                    compliance_score=:compliance_score, total_violations=:total_violations,
                    notes=:notes
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->business_id = htmlspecialchars(strip_tags($this->business_id));
        $this->inspector_id = htmlspecialchars(strip_tags($this->inspector_id));
        $this->inspection_type_id = htmlspecialchars(strip_tags($this->inspection_type_id));
        $this->scheduled_date = htmlspecialchars(strip_tags($this->scheduled_date));
        $this->completed_date = htmlspecialchars(strip_tags($this->completed_date));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->priority = htmlspecialchars(strip_tags($this->priority));
        $this->compliance_score = htmlspecialchars(strip_tags($this->compliance_score));
        $this->total_violations = htmlspecialchars(strip_tags($this->total_violations));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind parameters
        $stmt->bindParam(":business_id", $this->business_id);
        $stmt->bindParam(":inspector_id", $this->inspector_id);
        $stmt->bindParam(":inspection_type_id", $this->inspection_type_id);
        $stmt->bindParam(":scheduled_date", $this->scheduled_date);
        $stmt->bindParam(":completed_date", $this->completed_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":compliance_score", $this->compliance_score);
        $stmt->bindParam(":total_violations", $this->total_violations);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete inspection
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Count all inspections
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Count active violations
    public function countActiveViolations() {
        $query = "SELECT COUNT(*) as count FROM violations WHERE status IN ('open', 'in_progress')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Get average compliance score
    public function getAverageCompliance() {
        $query = "SELECT AVG(compliance_score) as average FROM " . $this->table_name . " WHERE compliance_score IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['average'] ? round($row['average']) : 0;
    }

    // Get inspections by status
    public function readByStatus($status) {
        $query = "SELECT i.*, b.name as business_name, it.name as inspection_type, u.name as inspector_name
                  FROM " . $this->table_name . " i
                  LEFT JOIN businesses b ON i.business_id = b.id
                  LEFT JOIN inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  WHERE i.status = ?
                  ORDER BY i.scheduled_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $status);
        $stmt->execute();
        return $stmt;
    }

    // Get inspections by inspector
    public function readByInspector($inspector_id) {
        $query = "SELECT i.*, b.name as business_name, it.name as inspection_type
                  FROM " . $this->table_name . " i
                  LEFT JOIN businesses b ON i.business_id = b.id
                  LEFT JOIN inspection_types it ON i.inspection_type_id = it.id
                  WHERE i.inspector_id = ?
                  ORDER BY i.scheduled_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $inspector_id);
        $stmt->execute();
        return $stmt;
    }
}
?>
