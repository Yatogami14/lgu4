<?php
require_once 'config/ai_config.php';

header('Content-Type: text/plain');

$apiKey = GEMINI_API_KEY;
$model = 'gemini-1.5-flash';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;

$text = "The facility is clean and well-maintained. All safety equipment is present.";
$prompt = "You are an AI assistant for a health and safety inspection platform. Analyze the following inspector's observation notes. Based on the text, determine the compliance status. The status must be one of: 'compliant', 'non_compliant', or 'needs_review'. Also provide a confidence score for your assessment (from 0.0 to 1.0) and up to two brief suggestions for the inspector.\n\nReturn your analysis ONLY as a valid JSON object with the keys: 'compliance', 'confidence', 'suggestions' (which should be an array of strings).\n\nInspector's notes: \"{$text}\"";

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
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
?>
