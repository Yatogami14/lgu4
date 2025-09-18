<?php
class Notification {
    private $database;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $message;
    public $type;
    public $is_read;
    public $related_entity_type;
    public $related_entity_id;
    public $created_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    // Create notification
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET user_id=:user_id, message=:message, type=:type,
                    related_entity_type=:related_entity_type, related_entity_id=:related_entity_id";

        $params = [
            ":user_id" => $this->user_id,
            ":message" => $this->message,
            ":type" => $this->type,
            ":related_entity_type" => $this->related_entity_type,
            ":related_entity_id" => $this->related_entity_id
        ];

        try {
            $pdo = $this->database->getConnection();
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $this->id = $pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }

    // Read single notification
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $row = $this->database->fetch($query, [$this->id]);

        if ($row) {
            $this->user_id = $row['user_id'] ?? null;
            $this->message = $row['message'] ?? null;
            $this->type = $row['type'] ?? null;
            $this->is_read = $row['is_read'] ?? null;
            $this->related_entity_type = $row['related_entity_type'] ?? null;
            $this->related_entity_id = $row['related_entity_id'] ?? null;
            $this->created_at = $row['created_at'] ?? null;
            return $row;
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

        $params = [$user_id];
        if ($limit) {
            $params[] = $limit;
        }
        try {
            $notifications = $this->database->fetchAll($query, $params);
        } catch (PDOException $e) {
            error_log("Error reading unread notifications by user: " . $e->getMessage());
            return [];
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

        $params = [$user_id];
        if ($limit) {
            $params[] = $limit;
        }
        try {
            $notifications = $this->database->fetchAll($query, $params);
        } catch (PDOException $e) {
            error_log("Error reading notifications by user: " . $e->getMessage());
            return [];
        }
        return $notifications;
    }

    // Mark notification as read
    public function markAsRead() {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE id = ?";
        try {
            $this->database->query($query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error marking notification as read (ID: {$this->id}): " . $e->getMessage());
            return false;
        }
    }

    // Mark all notifications as read for user
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        try {
            $this->database->query($query, [$user_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read for user (ID: {$user_id}): " . $e->getMessage());
            return false;
        }
    }

    // Count unread notifications for user
    public function countUnread($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                  WHERE user_id = ? AND is_read = 0";
        $row = [];
        try {
            $row = $this->database->fetch($query, [$user_id]);
        } catch (PDOException $e) {
            error_log("Error counting unread notifications for user (ID: {$user_id}): " . $e->getMessage());
            return 0;
        }

        return $row['count'];
    }

    // Delete notification
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        try {
            $this->database->query($query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting notification (ID: {$this->id}): " . $e->getMessage());
            return false;
        }
    }

    // Delete old notifications (cleanup)
    public function deleteOld($days = 30) {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        try {
            $this->database->query($query, [$days]);
            return true;
        } catch (PDOException $e) {
            error_log("Error deleting old notifications (days: {$days}): " . $e->getMessage());
            return false;
        }
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
