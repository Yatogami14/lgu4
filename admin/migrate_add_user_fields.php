<?php
/**
 * Migration to add department and certification fields to the users table.
 * This script is idempotent and can be run multiple times safely.
 */

header('Content-Type: text/plain');

// Go up one directory to get to the project root from /admin/
require_once dirname(__DIR__) . '/config/database.php';

echo "Starting user fields migration...\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "Database connection successful.\n";

    $tableName = 'users';
    $columnsToAdd = [
        'department' => "ADD COLUMN `department` VARCHAR(255) DEFAULT NULL AFTER `code_expiry`",
        'certification' => "ADD COLUMN `certification` VARCHAR(255) DEFAULT NULL AFTER `department`"
    ];

    // Get existing columns
    $stmt = $conn->query("DESCRIBE `$tableName`");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $migrationsApplied = 0;

    // Loop through columns and add if they don't exist
    foreach ($columnsToAdd as $columnName => $alterStatement) {
        if (!in_array($columnName, $existingColumns)) {
            echo "Applying migration: Adding column '$columnName'...\n";
            $conn->exec("ALTER TABLE `$tableName` " . $alterStatement);
            echo "SUCCESS: Column '$columnName' added.\n\n";
            $migrationsApplied++;
        } else {
            echo "INFO: Column '$columnName' already exists. Skipping.\n\n";
        }
    }

    echo "----------------------------------------\n";
    echo "Migration script finished. ";
    echo $migrationsApplied > 0 ? "$migrationsApplied change(s) applied." : "Schema is already up to date.";

} catch (PDOException $e) {
    die("DATABASE ERROR: " . $e->getMessage());
}

?>
