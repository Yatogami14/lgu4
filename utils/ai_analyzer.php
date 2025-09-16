<?php

class GeminiAnalyzer {
    private $apiKey;
    private $modelUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash-latest:generateContent?key=';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function analyzeText($text) {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY') {
            return ['compliance' => 'needs_review', 'confidence' => 0.75, 'suggestions' => ['Set Gemini API key to enable AI analysis.']];
        }

        $prompt = "You are an AI assistant for a health and safety inspection platform. Analyze the following inspector's observation notes. Based on the text, determine the compliance status. The status must be one of: 'compliant', 'non_compliant', or 'needs_review'. Also provide a confidence score for your assessment (from 0.0 to 1.0) and up to two brief suggestions for the inspector.\n\nReturn your analysis ONLY as a valid JSON object with the keys: 'compliance', 'confidence', 'suggestions' (which should be an array of strings).\n\nInspector's notes: \"{$text}\"";

        $data = ['contents' => [['parts' => [['text' => $prompt]]]]];
        $url = $this->modelUrl . $this->apiKey;

        $response = $this->makeApiCall($url, $data);

        if (isset($response['error'])) {
            $apiError = $response['error']['message'];
            $statusCode = $response['error']['code'];
            error_log("Gemini API call failed (Code: {$statusCode}): " . $apiError);
            
            $suggestion = "API Error (Code: {$statusCode}).";
            if (strpos($apiError, 'API key not valid') !== false) {
                $suggestion = 'The configured API key is not valid.';
            } else if ($statusCode === 400) {
                $suggestion = 'API request is malformed. (Code: 400)';
            }
            return ['compliance' => 'error', 'confidence' => 0.0, 'suggestions' => [$suggestion]];
        }

        $json_string = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if ($json_string) {
            $json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($json_string));
        }

        $analysis = json_decode($json_string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Gemini API returned invalid JSON: " . json_last_error_msg() . " | Raw response: " . $json_string);
            return ['compliance' => 'error', 'confidence' => 0.0, 'suggestions' => ['AI analysis returned an invalid format.']];
        }

        return [
            'compliance' => $analysis['compliance'] ?? 'needs_review', 
            'confidence' => $analysis['confidence'] ?? 0.5, 
            'suggestions' => $analysis['suggestions'] ?? ['No suggestions provided.']
        ];
    }

    public function analyzeMedia($filePath) {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY') {
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['AI Vision analysis is not configured. Please set a valid Gemini API key.'],
                'positive_observations' => []
            ];
        }

        $fileData = base64_encode(file_get_contents($filePath));
        $mimeType = mime_content_type($filePath);

        $prompt = "You are an AI assistant for a health and safety inspection platform. Analyze the following image for potential safety hazards and positive compliance observations.
Identify up to three specific hazards you see. Examples of hazards include 'blocked fire exit', 'spill on floor', 'improperly stored chemicals', 'missing safety gear', 'exposed wiring'.
Also, identify up to two positive compliance observations. Examples of positive observations include 'clear and unobstructed walkways', 'fire extinguisher is properly mounted and accessible', 'employees wearing appropriate PPE'.
Based on the findings, determine a general compliance status: 'compliant', 'non_compliant', or 'needs_review'.

Return your analysis ONLY as a valid JSON object with the following keys:
- 'hazards': an array of strings for detected hazards. Should be empty if none are found.
- 'positive_observations': an array of strings for detected positive compliance signs. Should be empty if none are found.
- 'confidence': a score from 0.0 to 1.0 for the overall assessment.
- 'compliance': the status string ('compliant', 'non_compliant', or 'needs_review').

Image for analysis is provided.";
        $data = ['contents' => [['parts' => [['text' => $prompt], ['inline_data' => ['mime_type' => $mimeType, 'data' => $fileData]]]]]];
        $url = $this->modelUrl . $this->apiKey;

        $response = $this->makeApiCall($url, $data);

        if (isset($response['error'])) {
            $apiError = $response['error']['message'];
            $statusCode = $response['error']['code'];
            error_log("Gemini Vision API call failed (Code: {$statusCode}): " . $apiError);

            $errorMessage = "API Error (Code: {$statusCode}).";
            if (strpos($apiError, 'API key not valid') !== false) {
                $errorMessage = 'The configured API key is not valid.';
            } else if ($statusCode === 400) {
                $errorMessage = 'API request is malformed. (Code: 400)';
            }
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['AI Vision API Error: ' . $errorMessage],
                'positive_observations' => []
            ];
        }

        $json_string = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($json_string) {
            $json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($json_string));
        }
        $analysis = json_decode($json_string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Gemini Vision API returned invalid JSON: " . json_last_error_msg() . " | Raw response: " . $json_string);
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['AI analysis returned an invalid format.'],
                'positive_observations' => []
            ];
        }

        return [
            'hazards' => $analysis['hazards'] ?? [],
            'positive_observations' => $analysis['positive_observations'] ?? [],
            'confidence' => $analysis['confidence'] ?? 0.5,
            'compliance' => $analysis['compliance'] ?? 'needs_review'
        ];
    }

    private function makeApiCall($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local dev only. Set to true in production.
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Set a 60-second timeout

        $response_body = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return ['error' => ['message' => "cURL Error: " . $curl_error, 'code' => 'cURL']];
        }

        $response_data = json_decode($response_body, true);

        if ($httpcode !== 200) {
            // Try to get a more specific error message from Google's response
            $error_message = "API call failed with status code: " . $httpcode;
            if (isset($response_data['error']['message'])) {
                $error_message = $response_data['error']['message'];
            }
            return ['error' => ['message' => $error_message, 'code' => $httpcode]];
        }

        return $response_data;
    }
}