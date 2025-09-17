<?php
class database {
    const DB_CHECKLIST = 'hsi_lgu_checklist_assessment';
    const DB_CORE = 'hsi_lgu_core';
    const DB_SCHEDULING = 'hsi_lgu_inspection_scheduling';
    const DB_MEDIA = 'hsi_lgu_media_uploads';
    const DB_VIOLATIONS = 'hsi_lgu_violations_ticketing';
    const DB_REPORTS = 'hsi_lgu_reports_notifications';

    private $connections = [];
    private $config = [];

    public function __construct() {
        // Load configuration from environment variables with fallbacks for development.
        $this->config = [
            self::DB_CHECKLIST => [
                'host' => getenv('DB_CHECKLIST_HOST') ?: 'localhost',
                'dbname' => getenv('DB_CHECKLIST_NAME') ?: 'hsi_lgu_checklist_assessment',
                'username' => getenv('DB_CHECKLIST_USER') ?: 'hsi_lgu_checklist_assessment',
                'password' => getenv('DB_CHECKLIST_PASS') ?: 'Admin123'
            ],
            self::DB_CORE => [
                'host' => getenv('DB_CORE_HOST') ?: 'localhost',
                'dbname' => getenv('DB_CORE_NAME') ?: 'hsi_lgu_core',
                'username' => getenv('DB_CORE_USER') ?: 'hsi_lgu_core',
                'password' => getenv('DB_CORE_PASS') ?: 'Admin123'
            ],
            self::DB_SCHEDULING => [
                'host' => getenv('DB_SCHEDULING_HOST') ?: 'localhost',
                'dbname' => getenv('DB_SCHEDULING_NAME') ?: 'hsi_lgu_inspection_scheduling',
                'username' => getenv('DB_SCHEDULING_USER') ?: 'hsi_lgu_inspection_scheduling',
                'password' => getenv('DB_SCHEDULING_PASS') ?: 'Admin123'
            ],
            self::DB_MEDIA => [
                'host' => getenv('DB_MEDIA_HOST') ?: 'localhost',
                'dbname' => getenv('DB_MEDIA_NAME') ?: 'hsi_lgu_media_uploads',
                'username' => getenv('DB_MEDIA_USER') ?: 'hsi_lgu_media_uploads',
                'password' => getenv('DB_MEDIA_PASS') ?: 'Admin123'
            ],
            self::DB_REPORTS => [
                'host' => getenv('DB_REPORTS_HOST') ?: 'localhost',
                'dbname' => getenv('DB_REPORTS_NAME') ?: 'hsi_lgu_reports_notifications',
                'username' => getenv('DB_REPORTS_USER') ?: 'hsi_lgu_reports_notifications',
                'password' => getenv('DB_REPORTS_PASS') ?: 'Admin123'
            ],
            self::DB_VIOLATIONS => [
                'host' => getenv('DB_VIOLATIONS_HOST') ?: 'localhost',
                'dbname' => getenv('DB_VIOLATIONS_NAME') ?: 'hsi_lgu_violations_ticketing',
                'username' => getenv('DB_VIOLATIONS_USER') ?: 'hsi_lgu_violations_ticketing',
                'password' => getenv('DB_VIOLATIONS_PASS') ?: 'Admin123'
            ]
        ];
    }

    public function getConnection($database) {
        if (!isset($this->connections[$database])) {
            if (!isset($this->config[$database])) {
                throw new Exception("Database configuration for '$database' not found.");
            }

            $config = $this->config[$database];
            try {
                $this->connections[$database] = new PDO(
                    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
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
        // Auto-correct database connection for cross-database queries
        if (strpos($query, 'hsi_lgu_core.') !== false) {
            $database = self::DB_CORE;
        } elseif (strpos($query, 'hsi_lgu_inspection_scheduling.') !== false) {
            $database = self::DB_SCHEDULING;
        } elseif (strpos($query, 'hsi_lgu_checklist_assessment.') !== false) {
            $database = self::DB_CHECKLIST;
        }

        $pdo = $this->getConnection($database);
        
        try {
            $stmt = $pdo->prepare($query);
            
            // Validate parameter count before execution
            $this->validateParameters($query, $params);
            
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log the error with detailed information
            error_log("Database query error: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Database: " . $database);
            error_log("Params: " . print_r($params, true));
            error_log("Params count: " . count($params));
            throw $e;
        }
    }

    /**
     * Validate that the number of parameters matches the query placeholders
     */
    private function validateParameters($query, $params) {
        if (empty($params)) {
            return; // No parameters to validate
        }

        // Count named parameters
        $namedParams = [];
        preg_match_all('/:(\w+)/', $query, $matches);
        if (!empty($matches[1])) {
            $namedParams = $matches[1];
        }

        // Count positional parameters
        $positionalParams = substr_count($query, '?');
        
        $totalExpectedParams = count($namedParams) + $positionalParams;
        
        if ($totalExpectedParams !== count($params)) {
            $errorMsg = "Parameter mismatch in query. Expected: $totalExpectedParams, Provided: " . count($params);
            error_log($errorMsg);
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            
            throw new PDOException($errorMsg);
        }

        // Additional validation for named parameters
        if (!empty($namedParams) && !empty($params)) {
            $firstKey = key($params);
            $usingNamedParams = is_string($firstKey) && $firstKey[0] === ':';
            
            if ($usingNamedParams) {
                foreach ($namedParams as $paramName) {
                    $fullParamName = ':' . $paramName;
                    if (!array_key_exists($fullParamName, $params) && !array_key_exists($paramName, $params)) {
                        $errorMsg = "Missing named parameter: $paramName";
                        error_log($errorMsg);
                        throw new PDOException($errorMsg);
                    }
                }
            }
        }
    }

    public function fetchAll($database, $query, $params = []) {
        $stmt = $this->query($database, $query, $params);
        return $stmt->fetchAll();
    }

    public function fetch($database, $query, $params = []) {
        $stmt = $this->query($database, $query, $params);
        return $stmt->fetch();
    }

    /**
     * Helper method to easily query the core database
     */
    public function queryCore($query, $params = []) {
        return $this->query(self::DB_CORE, $query, $params);
    }

    /**
     * Helper method to easily query the scheduling database
     */
    public function queryScheduling($query, $params = []) {
        return $this->query(self::DB_SCHEDULING, $query, $params);
    }

    /**
     * Additional helper methods for other databases
     */
    public function fetchAllCore($query, $params = []) {
        return $this->fetchAll(self::DB_CORE, $query, $params);
    }

    public function fetchCore($query, $params = []) {
        return $this->fetch(self::DB_CORE, $query, $params);
    }

    public function fetchAllScheduling($query, $params = []) {
        return $this->fetchAll(self::DB_SCHEDULING, $query, $params);
    }

    public function fetchScheduling($query, $params = []) {
        return $this->fetch(self::DB_SCHEDULING, $query, $params);
    }

    /**
     * Insert method with error handling
     */
    public function insert($database, $table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        return $this->query($database, $query, $data);
    }

    /**
     * Update method with error handling
     */
    public function update($database, $table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);
        $query = "UPDATE $table SET $setClause WHERE $where";
        
        $params = array_merge($data, $whereParams);
        return $this->query($database, $query, $params);
    }
}
?>