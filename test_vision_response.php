<?php
require_once 'config/ai_config.php';

header('Content-Type: text/plain');

// Create a test image file (small 1x1 pixel PNG)
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
file_put_contents('test_image.png', $testImageData);

$apiKey = GEMINI_API_KEY;
$model = 'gemini-2.5-flash';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;

$prompt = "Analyze this safety inspection image. Return JSON with: positive_observations (array), hazards (array), compliance ('compliant','non_compliant','needs_review'), confidence (0-1).";

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt],
                [
                    'inline_data' => [
                        'mime_type' => 'image/png',
                        'data' => base64_encode($testImageData)
                    ]
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.1,
        'maxOutputTokens' => 500,
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

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

if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
    $json_string = $response_data['candidates'][0]['content']['parts'][0]['text'];
    echo "Extracted JSON String:\n";
    echo $json_string . "\n\n";

    // Clean up JSON response
    $json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($json_string));
    $json_string = preg_replace('/^```\s*|\s*```$/', '', $json_string);

    echo "Cleaned JSON String:\n";
    echo $json_string . "\n\n";

    $analysis = json_decode($json_string, true);
    echo "JSON Decode Result:\n";
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON Error: " . json_last_error_msg() . "\n";
    } else {
        print_r($analysis);
    }
}

// Clean up test file
unlink('test_image.png');
?>
