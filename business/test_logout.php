<?php
// Test script to verify logout redirects
echo "<h1>Logout Redirect Test</h1>";

// Test admin logout redirect
echo "<h2>Admin Logout Test</h2>";
echo "<p>Should redirect to: admin_landing.php</p>";
echo "<a href='admin_logout.php'>Test Admin Logout</a>";

echo "<br><br>";

// Test user logout redirect
echo "<h2>User Logout Test</h2>";
echo "<p>Should redirect to: user_landing.php</p>";
echo "<a href='user_logout.php'>Test User Logout</a>";

echo "<br><br>";

// Test general logout redirect
echo "<h2>General Logout Test</h2>";
echo "<p>Should redirect based on session role or to user_landing.php</p>";
echo "<a href='logout.php'>Test General Logout</a>";
?>
