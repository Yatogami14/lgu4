<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

echo '<div style="font-family: sans-serif; padding: 20px;">';
echo "<h2>🔧 Business Status Fix Tool</h2>";

try {
    // 1. Check for businesses with 'verified' status
    $checkSql = "SELECT id, name, status FROM businesses WHERE status = 'verified'";
    $stmt = $conn->prepare($checkSql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 0) {
        echo "<p>Found <strong>" . count($results) . "</strong> businesses with 'verified' status that need updating:</p>";
        echo "<ul>";
        foreach ($results as $row) {
            echo "<li>ID: " . htmlspecialchars($row['id']) . " - <strong>" . htmlspecialchars($row['name']) . "</strong></li>";
        }
        echo "</ul>";

        // 2. Perform Update
        $updateSql = "UPDATE businesses SET status = 'active' WHERE status = 'verified'";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute();
        
        echo "<hr>";
        echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>✅ Successfully updated " . $updateStmt->rowCount() . " records to 'active'.</p>";
        echo "<p>These businesses should now be visible in the public search.</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ No businesses found with 'verified' status. Your data is already correct.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo '</div>';
?>