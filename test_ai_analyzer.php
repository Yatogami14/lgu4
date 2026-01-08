<?php
require_once 'config/ai_config.php';
require_once 'utils/ai_analyzer.php';

// Test the AI analyzer with a sample image
$analyzer = new GeminiAnalyzer(GEMINI_API_KEY);

// Use a sample image from uploads
$sampleImage = 'uploads/analysis_1767868586_695f88aaba5a1.jpg';

if (file_exists($sampleImage)) {
    echo "Testing AI analyzer with sample image: $sampleImage\n\n";

    $result = $analyzer->analyzeMedia($sampleImage);

    echo "Analysis Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

    // Check logs
    echo "\nCheck logs/ai_analyzer.log for detailed logging.\n";
} else {
    echo "Sample image not found: $sampleImage\n";
    echo "Available images in uploads:\n";
    $files = glob('uploads/*.jpg');
    foreach ($files as $file) {
        echo "- " . basename($file) . "\n";
    }
}
?>
