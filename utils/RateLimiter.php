<?php

class RateLimiter {
    private $conn;

    // Define limits for different actions (scope => [attempts, period_in_seconds])
    private $limits = [
        'login_failure' => [10, 900],    // 10 failed logins per 15 minutes
        'registration' => [5, 3600],     // 5 registration attempts per hour
        'password_reset' => [3, 1800], // 3 password reset requests per 30 minutes
        'email_resend' => [3, 1800]      // 3 verification resends per 30 minutes
    ];

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    /**
     * Checks if the current request is allowed for a given scope and identifier.
     *
     * @param string $scope The action being performed (e.g., 'login_failure').
     * @param string $identifier The unique identifier, typically the IP address.
     * @return bool True if the action is allowed, false if it is rate-limited.
     */
    public function isAllowed(string $scope, string $identifier): bool {
        if (!isset($this->limits[$scope])) {
            return true; // No limit defined for this scope.
        }

        list($max_attempts, $period) = $this->limits[$scope];

        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM action_attempts WHERE scope = ? AND identifier = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)"
        );
        $stmt->execute([$scope, $identifier, $period]);
        $attempts = $stmt->fetchColumn();

        return $attempts < $max_attempts;
    }

    /**
     * Records an attempt for a given scope and identifier.
     *
     * @param string $scope The action being performed.
     * @param string $identifier The unique identifier, typically the IP address.
     */
    public function recordAttempt(string $scope, string $identifier): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO action_attempts (scope, identifier, attempt_time) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$scope, $identifier]);
    }

    /**
     * Cleans up old records from the action_attempts table.
     */
    public function cleanup(): void {
        $this->conn->query("DELETE FROM action_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }
}