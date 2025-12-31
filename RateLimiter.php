<?php

class RateLimiter
{
    private PDO $conn;
    private array $configs;

    /**
     * Constructor.
     *
     * @param PDO $conn The database connection.
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;

        // Define rate limiting rules for different actions.
        // This makes the limiter more flexible and configurable.
        $this->configs = [
            'registration' => [
                'limit' => 5, // 5 attempts
                'period' => 3600, // per hour (3600 seconds)
            ],
            'login_failure' => [
                'limit' => 10, // 10 failed attempts
                'period' => 900, // per 15 minutes (900 seconds)
            ],
            'password_reset' => [
                'limit' => 3, // 3 requests
                'period' => 1800, // per 30 minutes (1800 seconds)
            ],
            // Add other actions here as needed
        ];
    }

    /**
     * Checks if an action is allowed under the rate limit rules.
     *
     * @param string $action The name of the action (e.g., 'login_failure').
     * @param string $scope The scope to apply the limit to (e.g., an IP address or email).
     * @return bool True if the action is allowed, false otherwise.
     */
    public function isAllowed(string $action, string $scope): bool
    {
        if (!isset($this->configs[$action])) {
            // Default to allowed if no configuration is set for the action.
            // Consider logging this as a warning.
            error_log("RateLimiter: No configuration found for action '{$action}'.");
            return true;
        }

        $config = $this->configs[$action];
        $limit = $config['limit'];
        $period = $config['period'];

        // Clean up old attempts to keep the table size manageable.
        $this->cleanup($action, $period);

        $sql = "SELECT COUNT(*) FROM action_attempts WHERE action_name = :action AND scope = :scope AND attempt_time > :time_limit";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':action' => $action,
            ':scope' => $scope,
            ':time_limit' => date('Y-m-d H:i:s', time() - $period)
        ]);

        $attempts = (int) $stmt->fetchColumn();

        return $attempts < $limit;
    }

    /**
     * Records an action attempt.
     *
     * @param string $action The name of the action.
     * @param string $scope The scope of the action.
     */
    public function recordAttempt(string $action, string $scope): void
    {
        // The ip_address column is now part of the scope, but we can keep it for logging/analysis.
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $sql = "INSERT INTO action_attempts (action_name, scope, ip_address) VALUES (:action, :scope, :ip)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':action' => $action,
            ':scope' => $scope,
            ':ip' => $ip_address
        ]);
    }

    /**
     * Deletes old records from the action_attempts table for a specific action.
     * This is a simple garbage collection mechanism.
     */
    private function cleanup(string $action, int $period): void
    {
        $cleanup_sql = "DELETE FROM action_attempts WHERE action_name = :action AND attempt_time <= :time_limit";
        $stmt = $this->conn->prepare($cleanup_sql);
        $stmt->execute([
            ':action' => $action,
            ':time_limit' => date('Y-m-d H:i:s', time() - ($period * 2)) // Clean up records older than twice the period
        ]);
    }
}