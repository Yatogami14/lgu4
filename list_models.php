<?php
require_once 'config/ai_config.php';

header('Content-Type: text/plain');

$apiKey = GEMINI_API_KEY;
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: " . $httpcode . "\n\n";

if ($curl_error) {
    echo "cURL Error: " . $curl_error . "\n";
    exit;
}

$response_data = json_decode($response, true);

echo "Raw API Response:\n";
echo $response . "\n\n";

if (isset($response_data['models'])) {
    echo "Available Models:\n";
    foreach ($response_data['models'] as $model) {
        echo "- " . $model['name'] . "\n";
        if (isset($model['supportedGenerationMethods'])) {
            echo "  Supported Methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
        }
        echo "\n";
    }
} else {
    echo "No models found in response.\n";
}
?>
