<?php
require_once 'config/ai_config.php';

header('Content-Type: text/plain');

$apiKey = GEMINI_API_KEY;
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;

$ch = curl_init($url);
if ($ch === false) {
    die("Failed to initialize cURL");
}

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local dev only

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: " . $error . "\n";
    exit;
}

echo "HTTP Status Code: " . $httpCode . "\n\n";

$data = json_decode($response, true);

if (isset($data['models'])) {
    echo "Available Models:\n";
    echo "-----------------\n";
    foreach ($data['models'] as $model) {
        echo "Name: " . $model['name'] . "\n";
        echo "Display Name: " . ($model['displayName'] ?? 'N/A') . "\n";
        echo "Version: " . ($model['version'] ?? 'N/A') . "\n";
        echo "Supported Methods: " . implode(', ', $model['supportedGenerationMethods'] ?? []) . "\n";
        echo "-----------------\n";
    }
} else {
    echo "Error or No Models Found:\n";
    print_r($data);
}
?>