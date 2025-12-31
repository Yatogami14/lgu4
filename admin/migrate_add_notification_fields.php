<?php
/**
 * Migration to add new fields to the notifications table.
 * This script is idempotent and can be run multiple times safely.
 */

header('Content-Type: text/plain');

// Go up two directories to get to the project root from /migrations/
require_once dirname(__DIR__) . '/config/database.php';

echo "Starting notification table migration...\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "Database connection successful.\n";

    $tableName = 'notifications';
    $columnsToAdd = [
        'type' => "ADD COLUMN `type` VARCHAR(50) DEFAULT 'info' AFTER `message`",
        'related_entity_type' => "ADD COLUMN `related_entity_type` VARCHAR(50) DEFAULT NULL AFTER `type`",
        'related_entity_id' => "ADD COLUMN `related_entity_id` INT(11) DEFAULT NULL AFTER `related_entity_type`"
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