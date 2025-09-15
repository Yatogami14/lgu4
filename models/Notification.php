<?php
class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $message;
    public $type;
    public $is_read;
    public $related_entity_type;
    public $related_entity_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create notification
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET user_id=:user_id, message=:message, type=:type, 
                    related_entity_type=:related_entity_type, related_entity_id=:related_entity_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->message = htmlspecialchars(strip_tags($this->message));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->related_entity_type = htmlspecialchars(strip_tags($this->related_entity_type));
        $this->related_entity_id = htmlspecialchars(strip_tags($this->related_entity_id));

        // Bind parameters
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":related_entity_type", $this->related_entity_type);
        $stmt->bindParam(":related_entity_id", $this->related_entity_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read single notification
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->user_id = $row['user_id'];
            $this->message = $row['message'];
            $this->type = $row['type'];
            $this->is_read = $row['is_read'];
            $this->related_entity_type = $row['related_entity_type'];
            $this->related_entity_id = $row['related_entity_id'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Read notifications by user
    public function readByUser($user_id, $limit = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC";

        if ($limit) {
            $query .= " LIMIT ?";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        if ($limit) {
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        $notifications = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = $row;
        }
        return $notifications;
    }

    // Read unread notifications by user
    public function readUnreadByUser($user_id, $limit = null) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = ? AND is_read = 0 
                  ORDER BY created_at DESC";

        if ($limit) {
            $query .= " LIMIT ?";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        
        if ($limit) {
            $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        $notifications = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $notifications[] = $row;
        }
        return $notifications;
    }

    // Mark notification as read
    public function markAsRead() {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Mark all notifications as read for user
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Count unread notifications for user
    public function countUnread($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    // Delete notification
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete old notifications (cleanup)
    public function deleteOld($days = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $days, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Create notification for inspection scheduled
    public function createInspectionScheduled($user_id, $business_name, $inspection_date, $inspection_id = null) {
        $this->user_id = $user_id;
        $this->message = "New inspection scheduled for " . $business_name . " on " . date('M j, Y', strtotime($inspection_date));
        $this->type = "info";
        $this->related_entity_type = "inspection";
        $this->related_entity_id = $inspection_id;
        
        return $this->create();
    }

    // Create notification for violation reported
    public function createViolationReported($user_id, $business_name, $violation_desc, $violation_id = null) {
        $this->user_id = $user_id;
        $this->message = "Violation reported at " . $business_name . " - " . $violation_desc;
        $this->type = "warning";
        $this->related_entity_type = "violation";
        $this->related_entity_id = $violation_id;
        
        return $this->create();
    }

    // Create notification for certification expiry
    public function createCertificationExpiry($user_id, $days_remaining) {
        $this->user_id = $user_id;
        $this->message = "Inspector certification expires in " . $days_remaining . " days";
        $this->type = "alert";
        $this->related_entity_type = "user";
        $this->related_entity_id = $user_id;
        
        return $this->create();
    }

    // Create notification for inspection completed
    public function createInspectionCompleted($user_id, $business_name, $compliance_score, $inspection_id = null) {
        $this->user_id = $user_id;
        $this->message = "Inspection completed for " . $business_name . " with " . $compliance_score . "% compliance";
        $this->type = "success";
        $this->related_entity_type = "inspection";
        $this->related_entity_id = $inspection_id;
        
        return $this->create();
    }

    // Create notification for inspection assignment
    public function createAssignmentNotification($inspector_id, $business_name, $inspection_id) {
        $this->user_id = $inspector_id;
        $this->message = "You have been assigned a new inspection for " . $business_name;
        $this->type = "info";
        $this->related_entity_type = "inspection";
        $this->related_entity_id = $inspection_id;
        
        return $this->create();
    }
}
?>
