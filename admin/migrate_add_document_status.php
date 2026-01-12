<?php
/**
 * Migration to add status and feedback fields to business_documents table.
 * Fixes: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'status'
 */

header('Content-Type: text/plain');

require_once dirname(__DIR__) . '/config/database.php';

echo "Starting business_documents migration...\n\n";

try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "Database connection successful.\n";

    $tableName = 'business_documents';
    $columnsToAdd = [
        'status' => "ADD COLUMN `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' AFTER `file_size`",
        'feedback' => "ADD COLUMN `feedback` TEXT DEFAULT NULL AFTER `status`"
    ];

    // Check existing columns
    $stmt = $conn->query("DESCRIBE `$tableName`");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $migrationsApplied = 0;

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
    echo "Migration finished. ";
    echo $migrationsApplied > 0 ? "$migrationsApplied change(s) applied." : "Schema is already up to date.";

} catch (PDOException $e) {
    die("DATABASE ERROR: " . $e->getMessage());
}
?>