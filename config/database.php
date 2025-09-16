<?php
class Database {
    private $host = 'localhost';
    private $password = 'Admin123';
    private $connections = [];

    // Define all database names as constants for easy reference and consistency
    const DB_CORE = 'hsi_lgu_core';
    const DB_CHECKLIST = 'hsi_lgu_checklist_assessment';
    const DB_SCHEDULING = 'lgu_inspection_scheduling';
    const DB_MEDIA = 'hsi_lgu_media_uploads';
    const DB_REPORTS = 'hsi_lgu_reports_notifications';
    const DB_VIOLATIONS = 'hsi_lgu_violations_ticketing';

    // Map database names to their specific usernames
    private $db_credentials = [
        self::DB_CORE => ['username' => 'hsi_lgu_core'],
        self::DB_CHECKLIST => ['username' => 'hsi_lgu_checklist_assessment'],
        self::DB_SCHEDULING => ['username' => 'hsi_lgu_inspection_scheduling'],
        self::DB_MEDIA => ['username' => 'hsi_lgu_media_uploads'],
        self::DB_REPORTS => ['username' => 'hsi_lgu_reports_notifications'],
        self::DB_VIOLATIONS => ['username' => 'hsi_lgu_violations_ticketing'],
    ];


    /**
     * Gets a PDO database connection for a specific submodule database.
     * Manages a pool of connections to avoid reconnecting.
     *
     * @param string $db_name The name of the database to connect to.
     * @return PDO The database connection object.
     */
    public function getConnection($db_name) {
        // If a connection for this database already exists, return it.
        if (isset($this->connections[$db_name])) {
            return $this->connections[$db_name];
        }

        // Check if credentials for the requested database exist.
        if (!isset($this->db_credentials[$db_name])) {
            error_log("Database configuration for '$db_name' not found.");
            die("<h1>Configuration Error</h1><p>Database configuration for '<strong>" . htmlspecialchars($db_name) . "</strong>' not found.</p>");
        }

        $username = $this->db_credentials[$db_name]['username'];

        // Otherwise, create a new connection.
        try {
            $conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $db_name . ";charset=utf8",
                $username,
                $this->password
            );
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connections[$db_name] = $conn;
        } catch(PDOException $exception) {
            error_log("Database Connection Error for '$db_name': " . $exception->getMessage());
            die("<h1>Database Connection Error</h1><p>Could not connect to the database '<strong>" . htmlspecialchars($db_name) . "</strong>'. Please check the configuration and ensure the database server is running.</p>");
        }

        return $this->connections[$db_name];
    }
}
?>
