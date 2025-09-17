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
                'username' => getenv('DB_CHECKLIST_USER') ?: 'hsi_lca',
                'password' => getenv('DB_CHECKLIST_PASS') ?: 'Admin123'
            ],
            self::DB_CORE => [
                'host' => getenv('DB_CORE_HOST') ?: 'localhost',
                'dbname' => getenv('DB_CORE_NAME') ?: 'hsi_lgu_core',
                'username' => getenv('DB_CORE_USER') ?: 'hsi_hlc',
                'password' => getenv('DB_CORE_PASS') ?: 'Admin123'
            ],
            self::DB_SCHEDULING => [
                'host' => getenv('DB_SCHEDULING_HOST') ?: 'localhost',
                'dbname' => getenv('DB_SCHEDULING_NAME') ?: 'hsi_lgu_inspection_scheduling',
                'username' => getenv('DB_SCHEDULING_USER') ?: 'hsi_hlis',
                'password' => getenv('DB_SCHEDULING_PASS') ?: 'Admin123'
            ],
            self::DB_MEDIA => [
                'host' => getenv('DB_MEDIA_HOST') ?: 'localhost',
                'dbname' => getenv('DB_MEDIA_NAME') ?: 'hsi_lgu_media_uploads',
                'username' => getenv('DB_MEDIA_USER') ?: 'hsi_hlmu',
                'password' => getenv('DB_MEDIA_PASS') ?: 'Admin123'
            ],
            self::DB_REPORTS => [
                'host' => getenv('DB_REPORTS_HOST') ?: 'localhost',
                'dbname' => getenv('DB_REPORTS_NAME') ?: 'hsi_lgu_reports_notifications',
                'username' => getenv('DB_REPORTS_USER') ?: 'hsi_hlrn',
                'password' => getenv('DB_REPORTS_PASS') ?: 'Admin123'
            ],
            self::DB_VIOLATIONS => [
                'host' => getenv('DB_VIOLATIONS_HOST') ?: 'localhost',
                'dbname' => getenv('DB_VIOLATIONS_NAME') ?: 'hsi_lgu_violations_ticketing',
                'username' => getenv('DB_VIOLATIONS_USER') ?: 'hsi_hlvt',
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
        } elseif (strpos($query, 'hsi_lgu_media_uploads.') !== false) {
            $database = self::DB_MEDIA;
        } elseif (strpos($query, 'hsi_lgu_violations_ticketing.') !== false) {
            $database = self::DB_VIOLATIONS;
        } elseif (strpos($query, 'hsi_lgu_reports_notifications.') !== false) {
            $database = self::DB_REPORTS;
        }

        $pdo = $this->getConnection($database);
        
        try {
            $stmt = $pdo->prepare($query);
            
            // DEBUG: Log the query and parameters for troubleshooting
            $this->debugQuery($query, $params);
            
            // Validate parameters before execution - THIS WILL THROW EXCEPTION IF MISMATCH
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
            
            // Re-throw the exception with more helpful message
            throw new PDOException("Query failed: " . $e->getMessage() . " - Check error logs for details");
        }
    }

    /**
     * Debug query and parameters for troubleshooting
     */
    private function debugQuery($query, $params) {
        error_log("=== QUERY DEBUG ===");
        error_log("Query: " . $query);
        error_log("Params count: " . count($params));
        error_log("Params: " . print_r($params, true));
        
        // Count expected parameters
        $namedParams = [];
        preg_match_all('/:(\w+)/', $query, $matches);
        if (!empty($matches[1])) {
            $namedParams = $matches[1];
            error_log("Named parameters found: " . implode(', ', $namedParams));
        }
        
        $positionalParams = substr_count($query, '?');
        error_log("Positional parameters found: " . $positionalParams);
        
        $totalExpectedParams = count($namedParams) + $positionalParams;
        error_log("Total expected parameters: " . $totalExpectedParams);
        error_log("=================================");
    }

    /**
     * Validate that the number of parameters matches the query placeholders
     */
    private function validateParameters($query, $params) {
        if (empty($params)) {
            // If no parameters are provided but query has placeholders, that's an error
            $positionalParams = substr_count($query, '?');
            preg_match_all('/:(\w+)/', $query, $matches);
            $namedParams = !empty($matches[1]) ? $matches[1] : [];
            
            if ($positionalParams > 0 || !empty($namedParams)) {
                $errorMsg = "PARAMETER MISMATCH ERROR: Query expects parameters but none provided";
                error_log($errorMsg);
                error_log("Query: " . $query);
                throw new PDOException($errorMsg);
            }
            return;
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
            $errorMsg = "PARAMETER MISMATCH ERROR: Expected $totalExpectedParams parameters, but provided " . count($params);
            error_log($errorMsg);
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            
            // Throw a more descriptive exception
            throw new PDOException($errorMsg);
        }

        // Additional validation for named parameters
        if (!empty($namedParams) && !empty($params)) {
            $firstKey = key($params);
            $usingNamedParams = is_string($firstKey) && (strpos($firstKey, ':') === 0);
            
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
     * Safe method that returns empty array instead of throwing exception
     */
    public function safeFetchAll($database, $query, $params = []) {
        try {
            $stmt = $this->query($database, $query, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Safe fetch all failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Safe method that returns false instead of throwing exception
     */
    public function safeFetch($database, $query, $params = []) {
        try {
            $stmt = $this->query($database, $query, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Safe fetch failed: " . $e->getMessage());
            return false;
        }
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
     * Safe helper methods that won't throw exceptions
     */
    public function safeFetchAllCore($query, $params = []) {
        return $this->safeFetchAll(self::DB_CORE, $query, $params);
    }

    public function safeFetchCore($query, $params = []) {
        return $this->safeFetch(self::DB_CORE, $query, $params);
    }

    public function safeFetchAllScheduling($query, $params = []) {
        return $this->safeFetchAll(self::DB_SCHEDULING, $query, $params);
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

    /**
     * Method to manually fix parameter mismatches (for emergency use)
     */
    public function executeFixedQuery($database, $query, $expectedParamCount) {
        $pdo = $this->getConnection($database);
        
        // Remove any parameters if they don't match
        if (substr_count($query, '?') !== $expectedParamCount) {
            // This is a hacky fix - remove extra parameters from query
            $query = preg_replace('/\?/', '', $query, substr_count($query, '?') - $expectedParamCount);
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Emergency method to execute query without parameters (use with caution)
     */
    public function executeWithoutParams($database, $query) {
        $pdo = $this->getConnection($database);
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Count rows in a table
     */
    public function count($database, $table, $where = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM $table";
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        $result = $this->fetch($database, $query, $params);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Check if a record exists
     */
    public function exists($database, $table, $where, $params = []) {
        return $this->count($database, $table, $where, $params) > 0;
    }
}
?>