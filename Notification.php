<?php
class Notification {
    private $conn;
    private $table_name = "notifications";
    private $database;

    // Object properties
    public $id;
    public $user_id;
    public $message;
    public $link;
    public $is_read;
    public $created_at;
    public $type;

    public function __construct(Database $database) {
        $this->database = $database;
        $this->conn = $database->getConnection();
    }

    /**
     * Creates a new notification for a user.
     *
     * @param int $user_id The ID of the user to notify.
     * @param string $message The notification message.
     * @param string|null $link An optional link for the notification.
     * @param string $type The type of notification (e.g., 'info', 'success', 'warning', 'alert').
     * @return bool True on success, false on failure.
     */
    public function create($user_id, $message, $link = null, $type = 'info', $related_entity_type = null, $related_entity_id = null) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, message, link, type, related_entity_type, related_entity_id) VALUES (:user_id, :message, :link, :type, :related_entity_type, :related_entity_id)";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':message' => $message,
                ':link' => $link,
                ':type' => $type,
                ':related_entity_type' => $related_entity_type,
                ':related_entity_id' => $related_entity_id
            ]);
        } catch (PDOException $e) {
            error_log("Notification Model Error (create): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches unread notifications for a specific user.
     *
     * @param int $user_id The user's ID.
     * @return array An array of unread notifications.
     */
    public function getUnreadByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC";
        return $this->database->safeFetchAll($query, [':user_id' => $user_id]);
    }

    /**
     * Marks a specific notification as read.
     *
     * @param int $notification_id The ID of the notification.
     * @return bool True on success, false on failure.
     */
    public function markAsRead($notification_id) {
        $query = "UPDATE " . $this->table_name . " SET is_read = 1 WHERE id = :id";
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([':id' => $notification_id]);
        } catch (PDOException $e) {
            error_log("Notification Model Error (markAsRead): " . $e->getMessage());
            return false;
        }
    }

    public function readByUser($user_id, $limit = 5) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countUnread($user_id) {
        $query = "SELECT COUNT(*) as unread_count FROM " . $this->table_name . " WHERE user_id = :user_id AND is_read = 0";
        $row = $this->database->fetch($query, [':user_id' => $user_id]);
        return $row ? (int)$row['unread_count'] : 0;
    }

    public function createAssignmentNotification($user_id, $business_name, $inspection_id, $reason = null) {
        $message = "You have been assigned a new inspection for \"{$business_name}\".";
        if ($reason) {
            $message = "An inspection for \"{$business_name}\" has been {$reason}.";
        }
        $link = "/lgu4/admin/inspection_view.php?id={$inspection_id}";
        return $this->create($user_id, $message, $link, 'info', 'inspection', $inspection_id);
    }

    public function createUnassignmentNotification($user_id, $business_name, $inspection_id, $reason) {
        $message = "An inspection for \"{$business_name}\" you were assigned to has been {$reason}.";
        $link = "/lgu4/admin/inspections.php";
        return $this->create($user_id, $message, $link, 'warning', 'inspection', $inspection_id);
    }

    public function createRescheduleNotification($user_id, $business_name, $inspection_id, $new_date) {
        $message = "The inspection for \"{$business_name}\" has been rescheduled to {$new_date}.";
        $link = "/lgu4/admin/inspection_view.php?id={$inspection_id}";
        return $this->create($user_id, $message, $link, 'info', 'inspection', $inspection_id);
    }
}
?>