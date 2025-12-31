<?php
class Notification {
    private $conn;
    private $table_name = "notifications";
    private $database;

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
     * @param string $type The type of notification (default 'info').
     * @param string|null $related_entity_type The type of related entity.
     * @param int|null $related_entity_id The ID of the related entity.
     * @param string|null $link An optional link for the notification.
     * @return bool True on success, false on failure.
     */
    public function create($user_id, $message, $type = 'info', $related_entity_type = null, $related_entity_id = null, $link = null) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, message, type, related_entity_type, related_entity_id, link) VALUES (:user_id, :message, :type, :related_entity_type, :related_entity_id, :link)";

        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([
                ':user_id' => $user_id,
                ':message' => $message,
                ':type' => $type,
                ':related_entity_type' => $related_entity_type,
                ':related_entity_id' => $related_entity_id,
                ':link' => $link
            ]);
        } catch (PDOException $e) {
            error_log("Notification Model Error (create): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches unread notifications for a specific user.
     *
     * @param int      $user_id The user's ID.
     * @param int|null $limit   Optional limit for the number of notifications.
     * @return array An array of unread notifications.
     */
    public function readByUser($user_id, $limit = 5) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?";
        $params = [$user_id, (int)$limit];
        return $this->database->safeFetchAll($query, $params);
    }

    /**
     * Counts the number of unread notifications for a user.
     *
     * @param int $user_id The user's ID.
     * @return int The count of unread notifications.
     */
    public function countUnread($user_id) {
        return $this->database->count($this->table_name, 'user_id = :user_id AND is_read = 0', [':user_id' => $user_id]);
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

    public function createAssignmentNotification($user_id, $business_name, $inspection_id, $reason = null) {
        $message = "You have been assigned a new inspection for \"{$business_name}\".";
        if ($reason) {
            $message = "An inspection for \"{$business_name}\" has been {$reason}.";
        }
        $link = "/lgu4/admin/inspection_view.php?id={$inspection_id}";
        return $this->create($user_id, $message, 'info', 'inspection', $inspection_id, $link);
    }

    public function createUnassignmentNotification($user_id, $business_name, $inspection_id, $reason) {
        $message = "An inspection for \"{$business_name}\" you were assigned to has been {$reason}.";
        $link = "/lgu4g/admin/inspections.php";
        return $this->create($user_id, $message, 'warning', 'inspection', $inspection_id, $link);
    }

    public function createRescheduleNotification($user_id, $business_name, $inspection_id, $new_date) {
        $message = "The inspection for \"{$business_name}\" has been rescheduled to {$new_date}.";
        $link = "/lgu4/admin/inspection_view.php?id={$inspection_id}";
        return $this->create($user_id, $message, 'info', 'inspection', $inspection_id, $link);
    }
}
?>