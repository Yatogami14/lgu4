<?php
class Database {
    private $host = 'localhost';
    private $username = 'hsi_lgu_reports_notifications';
    private $password = 'Admin123';
    private $connections = [];

    // Define database names as constants for easy reference and consistency
    const DB_REPORTS = 'hsi_lgu_reports_notifications';

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

        // Otherwise, create a new connection.
        try {
            $conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $db_name . ";charset=utf8",
                $this->username,
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
