<?php
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function open($savePath, $sessionName): bool {
        return $this->conn !== null;
    }

    public function close(): bool {
        return true;
    }

    public function read($sessionId): string {
        $query = "SELECT data FROM sessions WHERE id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $row['data'];
        }
        return "";
    }

    public function write($sessionId, $sessionData): bool {
        $query = "REPLACE INTO sessions (id, user_id, last_activity, data)
                  VALUES (:session_id, :user_id, :last_activity, :session_data)";

        $stmt = $this->conn->prepare($query);

        $userId = $_SESSION['user_id'] ?? null;
        $lastActivity = time();

        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':last_activity', $lastActivity);
        $stmt->bindParam(':session_data', $sessionData);

        return $stmt->execute();
    }

    public function destroy($sessionId): bool {
        $query = "DELETE FROM sessions WHERE id = :session_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        return $stmt->execute();
    }

    public function gc($maxLifetime): int {
        $old = time() - $maxLifetime;
        $query = "DELETE FROM sessions WHERE last_activity < :old";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':old', $old, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    // Custom method to get all active sessions
    public function getAllActiveSessions() {
        // Consider sessions active within the last 30 minutes (1800 seconds)
        $active_threshold = time() - 1800;

        $query = "SELECT s.id as session_id, s.last_activity, u.id as user_id, u.name, u.email, u.role
                  FROM sessions s
                  LEFT JOIN users u ON s.user_id = u.id
                  WHERE s.last_activity > :active_threshold
                  AND s.user_id IS NOT NULL
                  ORDER BY s.last_activity DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':active_threshold', $active_threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}