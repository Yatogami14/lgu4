<?php
// Check if class is already defined to prevent redeclaration
if (!class_exists('Database')) {
    class Database {
        // Constants for database keys, mapping to the config array below
        const DB_CORE = 'hsi_lgu_core';
        const DB_SCHEDULING = 'hsi_lgu_inspection_scheduling';
        const DB_MEDIA = 'hsi_lgu_media_uploads';
        const DB_VIOLATIONS = 'hsi_lgu_violations_ticketing';
        const DB_CHECKLIST = 'hsi_lgu_checklist_assessment';
        const DB_REPORTS = 'hsi_lgu_reports_notifications';

        private $connections = [];
        private $config = [
            'hsi_lgu_checklist_assessment' => [ // DB_CHECKLIST
                'host' => getenv('DB_CHECKLIST_HOST') ?: 'localhost',
                'dbname' => getenv('DB_CHECKLIST_NAME') ?: 'hsi_lgu_checklist_assessment',
                'username' => getenv('DB_CHECKLIST_USER') ?: 'hsi_lgu_checklist_assessment',
                'password' => getenv('DB_CHECKLIST_PASS') ?: 'Admin123'
            ],
            'hsi_lgu_core' => [ // DB_CORE
                'host' => getenv('DB_CORE_HOST') ?: 'localhost',
                'dbname' => getenv('DB_CORE_NAME') ?: 'hsi_lgu_core',
                'username' => getenv('DB_CORE_USER') ?: 'hsi_lgu_core',
                'password' => getenv('DB_CORE_PASS') ?: 'Admin123'
            ],
            'hsi_lgu_inspection_scheduling' => [ // DB_SCHEDULING
                'host' => getenv('DB_SCHEDULING_HOST') ?: 'localhost',
                'dbname' => getenv('DB_SCHEDULING_NAME') ?: 'hsi_lgu_inspection_scheduling',
                'username' => getenv('DB_SCHEDULING_USER') ?: 'hsi_lgu_inspection_scheduling',
                'password' => getenv('DB_SCHEDULING_PASS') ?: 'Admin123'
            ],
            'hsi_lgu_media_uploads' => [ // DB_MEDIA
                'host' => getenv('DB_MEDIA_HOST') ?: 'localhost',
                'dbname' => getenv('DB_MEDIA_NAME') ?: 'hsi_lgu_media_uploads',
                'username' => getenv('DB_MEDIA_USER') ?: 'hsi_lgu_media_uploads',
                'password' => getenv('DB_MEDIA_PASS') ?: 'Admin123'
            ],
            'hsi_lgu_reports_notifications' => [ // DB_REPORTS
                'host' => getenv('DB_REPORTS_HOST') ?: 'localhost',
                'dbname' => getenv('DB_REPORTS_NAME') ?: 'hsi_lgu_reports_notifications',
                'username' => getenv('DB_REPORTS_USER') ?: 'hsi_lgu_reports_notifications',
                'password' => getenv('DB_REPORTS_PASS') ?: 'Admin123'
            ],
            'hsi_lgu_violations_ticketing' => [ // DB_VIOLATIONS
                'host' => getenv('DB_VIOLATIONS_HOST') ?: 'localhost',
                'dbname' => getenv('DB_VIOLATIONS_NAME') ?: 'hsi_lgu_violations_ticketing',
                'username' => getenv('DB_VIOLATIONS_USER') ?: 'hsi_lgu_violations_ticketing',
                'password' => getenv('DB_VIOLATIONS_PASS') ?: 'Admin123'
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