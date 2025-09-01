<?php
/**
 * Script to fix require_once paths in business and community directories
 */

// Files to fix in business directory
$businessFiles = [
    'business/inspectors.php',
    'business/inspection_form.php',
    'business/profile.php',
    'business/inspections.php',
    'business/index.php',
    'business/error.php',
    'business/business_view.php',
    'business/businesses.php',
    'business/analytics.php',
    'business/violations.php',
    'business/business_landing.php',
    'business/schedule.php',
    'business/public_register.php'
];

// Files to fix in community directory
$communityFiles = [
    'community/violations.php',
    'community/community_landing.php',
    'community/schedule.php',
    'community/public_register.php',
    'community/public_login.php',
    'community/profile.php',
    'community/inspectors.php',
    'community/inspection_form.php',
    'community/inspections.php',
    'community/index.php',
    'community/error.php',
    'community/business_view.php',
    'community/businesses.php',
    'community/analytics.php'
];

function fixFilePaths($filePath) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    
    // Replace config/database.php with ../config/database.php
    $content = str_replace("require_once 'config/database.php';", "require_once '../config/database.php';", $content);
    
    // Replace models/User.php with ../models/User.php
    $content = str_replace("require_once 'models/User.php';", "require_once '../models/User.php';", $content);
    
    if (file_put_contents($filePath, $content)) {
        echo "Fixed paths in: $filePath\n";
        return true;
    } else {
        echo "Failed to fix paths in: $filePath\n";
        return false;
    }
}

echo "Fixing paths in business directory...\n";
foreach ($businessFiles as $file) {
    fixFilePaths($file);
}

echo "\nFixing paths in community directory...\n";
foreach ($communityFiles as $file) {
    fixFilePaths($file);
}

echo "\nPath fixing completed!\n";
?>
