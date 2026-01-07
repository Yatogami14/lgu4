<?php
class Database {
    const DB_UNIFIED = 'lgu';

    private $connection = null;
    private $config = [];

    public function __construct() {
        $this->config = [
            self::DB_UNIFIED => [
                'host' =>  'localhost:3307', //getenv('DB_HOST') ?:
                'dbname' => 'hsi_lgu_unified', //getenv('DB_NAME') ?:
                'username' => 'root', //getenv('DB_USER') ?:
                'password' => '', //getenv('DB_PASS') ?:
            ]
        ];
    }

    public function getConnection() {
        if ($this->connection === null) {
            $config = $this->config[self::DB_UNIFIED];
            try {
                $this->connection = new PDO(
                    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                    $config['username'],
                    $config['password']
                );
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->connection->exec("SET time_zone = '+08:00'");
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw $e;
            }
        }

        return $this->connection;
    }

    public function query($query, $params = []) {
        $pdo = $this->getConnection();
        
        try {
            $stmt = $pdo->prepare($query);

            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $type = PDO::PARAM_STR;
                    if (is_int($value)) $type = PDO::PARAM_INT;
                    elseif (is_bool($value)) $type = PDO::PARAM_BOOL;
                    elseif (is_null($value)) $type = PDO::PARAM_NULL;

                    // Handle 0-indexed positional parameters (convert to 1-indexed)
                    // Named parameters (strings) are passed as-is
                    $paramKey = is_int($key) ? $key + 1 : $key;
                    $stmt->bindValue($paramKey, $value, $type);
                }
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            error_log("Query: " . $query);
            error_log("Params: " . print_r($params, true));
            throw new PDOException("Query failed: " . $e->getMessage() . " - Check error logs for details");
        }
    }

    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    public function fetch($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    /**
     * Safe method that returns empty array instead of throwing exception
     */
    public function safeFetchAll($query, $params = []) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Safe fetch all failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Safe method that returns false instead of throwing exception
     */
    public function safeFetch($query, $params = []) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Safe fetch failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert method with error handling
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        return $this->query($query, $data);
    }

    /**
     * Update method with error handling
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);
        $query = "UPDATE $table SET $setClause WHERE $where";
        
        $params = array_merge($data, $whereParams);
        return $this->query($query, $params);
    }

    /**
     * Count rows in a table
     */
    public function count($table, $where = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM $table";
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        $result = $this->fetch($query, $params);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Check if a record exists
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
}
?>
