<?php
// Check if class is already defined to prevent redeclaration
if (!class_exists('Database')) {
    class Database {
        private $connections = [];

        // --- Constants for Database Names ---
        const DB_CORE = 'hsi_lgu_core';
        const DB_CHECKLIST = 'hsi_lgu_checklist_assessment';
        const DB_SCHEDULING = 'lgu_inspection_scheduling';
        const DB_MEDIA = 'hsi_lgu_media_uploads';
        const DB_REPORTS = 'hsi_lgu_reports_notifications';
        const DB_VIOLATIONS = 'hsi_lgu_violations_ticketing';

        // --- Centralized Configuration ---
        private $config = [
            self::DB_CORE => [
                'host' => 'hsi.qcprotektado.com',
                'dbname' => self::DB_CORE,
                'username' => 'hsi_lgu_core',
                'password' => 'Admin123'
            ],
            self::DB_CHECKLIST => [
                'host' => 'hsi.qcprotektado.com',
                'dbname' => self::DB_CHECKLIST,
                'username' => 'hsi_lgu_checklist_assessment',
                'password' => 'Admin123'
            ],
            self::DB_SCHEDULING => [
                'host' => 'hsi.qcprotektado.com',
                'dbname' => self::DB_SCHEDULING,
                'username' => 'lgu_inspection_scheduling',
                'password' => 'Admin123'
            ],
            self::DB_MEDIA => [
                'host' => 'hsi.qcprotektado.com',
                'dbname' => self::DB_MEDIA,
                'username' => 'hsi_lgu_media_uploads',
                'password' => 'Admin123'
            ],
            self::DB_REPORTS => [
                'host' => 'hsi.qcprotektado.com',
                'dbname' => self::DB_REPORTS,
                'username' => 'hsi_lgu_reports_notifications',
                'password' => 'Admin123'
            ],
            self::DB_VIOLATIONS => [
                'host' => 'hsi.qcprotektado.com',
                'dbname' => self::DB_VIOLATIONS,
                'username' => 'hsi_lgu_violations_ticketing',
                'password' => 'Admin123'
            ],
        ];

        /**
         * Gets a PDO database connection for a specific submodule database.
         * Manages a pool of connections to avoid reconnecting.
         *
         * @param string $db_name The name of the database to connect to (use class constants).
         * @return PDO The database connection object.
         * @throws PDOException if the connection fails.
         * @throws Exception if the configuration for the database is not found.
         */
        public function getConnection($db_name) {
            if (isset($this->connections[$db_name])) {
                return $this->connections[$db_name];
            }

            if (!isset($this->config[$db_name])) {
                throw new Exception("Database configuration for '$db_name' not found.");
            }

            $db_config = $this->config[$db_name];
            try {
                $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8";
                $this->connections[$db_name] = new PDO(
                    $dsn,
                    $db_config['username'],
                    $db_config['password']
                );
                $this->connections[$db_name]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connections[$db_name]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->connections[$db_name]->exec("SET time_zone = '+08:00'"); // Set timezone to Asia/Manila
            } catch (PDOException $e) {
                error_log("Database connection failed for $db_name: " . $e->getMessage());
                // Re-throw the exception to be handled by the calling code
                throw $e;
            }

            return $this->connections[$db_name];
        }

        /**
         * Prepares and executes a query.
         *
         * @param string $database The database to query (use class constants).
         * @param string $query The SQL query string.
         * @param array $params The parameters to bind to the query.
         * @return PDOStatement The executed statement object.
         */
        public function query($database, $query, $params = []) {
            $pdo = $this->getConnection($database);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        }

        /**
         * Executes a query and returns all results.
         *
         * @param string $database The database to query.
         * @param string $query The SQL query string.
         * @param array $params The parameters to bind.
         * @return array An array of all result set rows.
         */
        public function fetchAll($database, $query, $params = []) {
            $stmt = $this->query($database, $query, $params);
            return $stmt->fetchAll();
        }

        /**
         * Executes a query and returns a single result.
         *
         * @param string $database The database to query.
         * @param string $query The SQL query string.
         * @param array $params The parameters to bind.
         * @return mixed A single result row.
         */
        public function fetch($database, $query, $params = []) {
            $stmt = $this->query($database, $query, $params);
            return $stmt->fetch();
        }
    }
}
?>
