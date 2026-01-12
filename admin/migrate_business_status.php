<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../utils/access_control.php';

// Security check: Ensure only admins can run this script
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Access Denied. Administrator privileges required.');
}

$database = new Database();
$db = $database->getConnection();

echo '<div style="font-family: sans-serif; padding: 20px; max-width: 800px; margin: 0 auto;">';
echo '<h2 style="border-bottom: 2px solid #eee; padding-bottom: 10px;">Business Status Migration Tool</h2>';

try {
    // 1. Check for businesses with 'verified' status
    $check_query = "SELECT COUNT(*) as count FROM businesses WHERE status = 'verified'";
    $stmt = $db->query($check_query);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo "<p>Found <strong>{$count}</strong> businesses with 'verified' status.</p>";

    if ($count > 0) {
        // 2. Update them to 'active'
        $update_query = "UPDATE businesses SET status = 'active' WHERE status = 'verified'";
        $stmt = $db->prepare($update_query);
        $stmt->execute();
        
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'><strong>Success:</strong> Updated {$stmt->rowCount()} businesses to 'active' status.</div>";
    } else {
        echo "<div style='background-color: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 20px 0;'>No updates needed. All verified businesses are already active.</div>";
    }

} catch (PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo '<p><a href="businesses.php" style="display: inline-block; padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 5px;">Return to Business Management</a></p>';
echo '</div>';
?>