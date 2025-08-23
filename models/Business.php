<?php
class Business {
    private $conn;
    private $table_name = "businesses";

    public $id;
    public $name;
    public $address;
    public $owner_id;
    public $contact_number;
    public $email;
    public $business_type;
    public $registration_number;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create business
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET name=:name, address=:address, owner_id=:owner_id, 
                    contact_number=:contact_number, email=:email, 
                    business_type=:business_type, registration_number=:registration_number";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->owner_id = htmlspecialchars(strip_tags($this->owner_id));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->business_type = htmlspecialchars(strip_tags($this->business_type));
        $this->registration_number = htmlspecialchars(strip_tags($this->registration_number));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":owner_id", $this->owner_id);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":business_type", $this->business_type);
        $stmt->bindParam(":registration_number", $this->registration_number);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read single business
    public function readOne() {
        $query = "SELECT b.*, u.name as owner_name, u.email as owner_email
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.id = ? LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->name = $row['name'];
            $this->address = $row['address'];
            $this->owner_id = $row['owner_id'];
            $this->contact_number = $row['contact_number'];
            $this->email = $row['email'];
            $this->business_type = $row['business_type'];
            $this->registration_number = $row['registration_number'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return $row;
        }
        return false;
    }

    // Read all businesses
    public function readAll() {
        $query = "SELECT b.*, u.name as owner_name 
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  ORDER BY b.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Update business
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET name=:name, address=:address, owner_id=:owner_id, 
                    contact_number=:contact_number, email=:email, 
                    business_type=:business_type, registration_number=:registration_number
                WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->owner_id = htmlspecialchars(strip_tags($this->owner_id));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->business_type = htmlspecialchars(strip_tags($this->business_type));
        $this->registration_number = htmlspecialchars(strip_tags($this->registration_number));

        // Bind parameters
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":owner_id", $this->owner_id);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":business_type", $this->business_type);
        $stmt->bindParam(":registration_number", $this->registration_number);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete business
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Count all businesses
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Search businesses
    public function search($keywords) {
        $query = "SELECT b.*, u.name as owner_name 
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.name LIKE ? OR b.address LIKE ? OR b.business_type LIKE ?
                  ORDER BY b.created_at DESC";

        $stmt = $this->conn->prepare($query);

        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // Bind parameters
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);

        $stmt->execute();
        return $stmt;
    }

    // Get businesses by type
    public function readByType($business_type) {
        $query = "SELECT b.*, u.name as owner_name 
                  FROM " . $this->table_name . " b
                  LEFT JOIN users u ON b.owner_id = u.id
                  WHERE b.business_type = ?
                  ORDER BY b.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $business_type);
        $stmt->execute();
        return $stmt;
    }

    // Get compliance statistics for business
    public function getComplianceStats($business_id) {
        $query = "SELECT 
                    COUNT(*) as total_inspections,
                    AVG(compliance_score) as avg_compliance,
                    SUM(total_violations) as total_violations,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_inspections
                  FROM inspections 
                  WHERE business_id = ? AND compliance_score IS NOT NULL";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $business_id);
        $stmt->execute();

        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format the results
        $stats['avg_compliance'] = $stats['avg_compliance'] ? round($stats['avg_compliance']) : 0;
        $stats['compliance_rate'] = $stats['total_inspections'] > 0 ? 
            round(($stats['completed_inspections'] / $stats['total_inspections']) * 100) : 0;
        
        return $stats;
    }

    // Get recent inspections for business
    public function getRecentInspections($business_id, $limit = 5) {
        $query = "SELECT i.*, it.name as inspection_type, u.name as inspector_name
                  FROM inspections i
                  LEFT JOIN inspection_types it ON i.inspection_type_id = it.id
                  LEFT JOIN users u ON i.inspector_id = u.id
                  WHERE i.business_id = ?
                  ORDER BY i.scheduled_date DESC
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $business_id);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $inspections = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inspections[] = $row;
        }
        return $inspections;
    }
}
?>
