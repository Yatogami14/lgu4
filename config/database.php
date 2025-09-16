<?php
// Check if class is already defined to prevent redeclaration
if (!class_exists('Database')) {
    class Database {
        // Constants for database keys, mapping to the config array below
        const DB_CORE = 'frsm';
        const DB_SCHEDULING = 'ficr';
        const DB_MEDIA = 'fsiet';
        const DB_VIOLATIONS = 'piar';
        const DB_CHECKLIST = 'ird';
        const DB_REPORTS = 'hwrm';

        private $connections = [];
        private $config = [
            'ird' => [ // DB_CHECKLIST
                'host' => 'localhost',
                'dbname' => 'hsi_lgu_checklist_assessment',
                'username' => 'hsi_lgu_checklist_assessment',
                'password' => 'Admin123'
            ],
            'frsm' => [ // DB_CORE
                'host' => 'localhost',
                'dbname' => 'hsi_lgu_core',
                'username' => 'hsi_lgu_core',
                'password' => 'Admin123'
            ],
            'ficr' => [ // DB_SCHEDULING
                'host' => 'localhost',
                'dbname' => 'hsi_lgu_inspection_scheduling',
                'username' => 'hsi_lgu_inspection_scheduling',
                'password' => 'Admin123'
            ],
            'fsiet' => [ // DB_MEDIA
                'host' => 'localhost',
                'dbname' => 'hsi_lgu_media_uploads',
                'username' => 'hsi_lgu_media_uploads',
                'password' => 'Admin123'
            ],
            'hwrm' => [ // DB_REPORTS
                'host' => 'localhost',
                'dbname' => 'hsi_lgu_reports_notifications',
                'username' => 'hsi_lgu_reports_notifications',
                'password' => 'Admin123'
            ],
            'piar' => [ // DB_VIOLATIONS
                'host' => 'localhost',
                'dbname' => 'hsi_lgu_violations_ticketing',
                'username' => 'hsi_lgu_violations_ticketing',
                'password' => 'Admin123'
            ]
        ];

        public function getConnection($database) {
            if (!isset($this->connections[$database])) {
                if (!isset($this->config[$database])) {
                    throw new Exception("Database configuration for '$database' not found.");
                }

                $config = $this->config[$database];
                try {
                    $this->connections[$database] = new PDO(
                        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
                        $config['username'],
                        $config['password']
                    );
                    $this->connections[$database]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->connections[$database]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $this->connections[$database]->exec("SET time_zone = '+08:00'");
                } catch (PDOException $e) {
                    error_log("Database connection failed for $database: " . $e->getMessage());
                    throw $e;
                }
            }

            return $this->connections[$database];
        }

        public function query($database, $query, $params = []) {
            $pdo = $this->getConnection($database);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        }

        public function fetchAll($database, $query, $params = []) {
            $stmt = $this->query($database, $query, $params);
            return $stmt->fetchAll();
        }

        public function fetch($database, $query, $params = []) {
            $stmt = $this->query($database, $query, $params);
            return $stmt->fetch();
        }
    }
}
?>