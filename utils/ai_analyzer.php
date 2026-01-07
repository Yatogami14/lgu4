<?php

class GeminiAnalyzer {
    private $apiKey;
    private $textModel = 'gemini-1.5-flash'; // Default fallback
    private $visionModel = 'gemini-1.5-flash'; // Default fallback
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
 
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->discoverBestModel();
    }

    public function analyzeText($text) {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY') {
            return ['compliance' => 'needs_review', 'confidence' => 0.75, 'suggestions' => ['Set Gemini API key to enable AI analysis.']];
        }

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
        
        $url = $this->baseUrl . $this->textModel . ':generateContent?key=' . $this->apiKey;

        $response = $this->makeApiCall($url, $data);

        if (isset($response['error'])) {
            error_log("Gemini API Error: " . json_encode($response['error']));
            
            // Fallback analysis without AI
            return $this->fallbackTextAnalysis($text);
        }

        $json_string = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if ($json_string) {
            // Clean up JSON response
            $json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($json_string));
            $json_string = preg_replace('/^```\s*|\s*```$/', '', $json_string);
        }

        $analysis = json_decode($json_string, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($analysis)) {
            error_log("Gemini API JSON parse error. Raw: " . substr($json_string, 0, 200));
            return $this->fallbackTextAnalysis($text);
        }

        return [
            'compliance' => $analysis['compliance'] ?? 'needs_review', 
            'confidence' => floatval($analysis['confidence'] ?? 0.5), 
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

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['File not found or not readable.'],
                'positive_observations' => []
            ];
        }

        $fileData = base64_encode(file_get_contents($filePath));
        $mimeType = mime_content_type($filePath);
        
        // Check file size (Gemini has limits)
        $fileSize = filesize($filePath);
        if ($fileSize > 20 * 1024 * 1024) { // 20MB limit
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['File too large. Maximum size is 20MB.'],
                'positive_observations' => []
            ];
        }

        $prompt = "Analyze this safety inspection image. Return JSON with: positive_observations (array), hazards (array), compliance ('compliant','non_compliant','needs_review'), confidence (0-1).";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $fileData
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

        $url = $this->baseUrl . $this->visionModel . ':generateContent?key=' . $this->apiKey;
        
        error_log("Calling Gemini Vision API: " . $this->visionModel);

        $response = $this->makeApiCall($url, $data);

        if (isset($response['error'])) {
            $error = $response['error'];
            error_log("Vision API Error: " . json_encode($error));
            
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['API Error: ' . ($error['message'] ?? 'Unknown error')],
                'positive_observations' => []
            ];
        }

        $json_string = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if (!$json_string) {
            return [
                'compliance' => 'error',
                'confidence' => 0.0,
                'hazards' => ['No response from AI.'],
                'positive_observations' => []
            ];
        }

        // Clean JSON response
        $json_string = preg_replace('/^```json\s*|\s*```$/', '', trim($json_string));
        $json_string = preg_replace('/^```\s*|\s*```$/', '', $json_string);
        
        $analysis = json_decode($json_string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Parse Error: " . json_last_error_msg() . " | Response: " . substr($json_string, 0, 200));
            
            // Try to extract JSON if it's wrapped in text
            if (preg_match('/\{.*\}/s', $json_string, $matches)) {
                $analysis = json_decode($matches[0], true);
            }
        }

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($analysis)) {
            return [
                'compliance' => 'needs_review',
                'confidence' => 0.5,
                'hazards' => ['AI analysis format issue.'],
                'positive_observations' => ['Unable to parse AI response.']
            ];
        }

        return [
            'hazards' => $analysis['hazards'] ?? [],
            'positive_observations' => $analysis['positive_observations'] ?? [],
            'confidence' => floatval($analysis['confidence'] ?? 0.5),
            'compliance' => $analysis['compliance'] ?? 'needs_review'
        ];
    }

    private function makeApiCall($url, $data = null, $method = 'POST') {
        $ch = curl_init($url);
        
        if ($ch === false) {
            return ['error' => ['message' => "Failed to initialize cURL", 'code' => 'INIT_FAILED']];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);

        if ($curl_error) {
            return ['error' => ['message' => "cURL Error: " . $curl_error, 'code' => 'CURL']];
        }

        $response_data = json_decode($response, true);

        if ($httpcode !== 200) {
            $error_msg = "HTTP {$httpcode}";
            if (isset($response_data['error']['message'])) {
                $error_msg = $response_data['error']['message'];
            }
            return ['error' => ['message' => $error_msg, 'code' => $httpcode]];
        }

        return $response_data;
    }

    private function fallbackTextAnalysis($text) {
        // Simple keyword-based fallback when API fails
        $keywords_compliant = ['good', 'clean', 'safe', 'proper', 'correct', 'adequate'];
        $keywords_non_compliant = ['broken', 'missing', 'unsafe', 'hazard', 'danger', 'violation'];
        
        $text_lower = strtolower($text);
        $score = 0;
        
        foreach ($keywords_compliant as $word) {
            if (strpos($text_lower, $word) !== false) $score++;
        }
        foreach ($keywords_non_compliant as $word) {
            if (strpos($text_lower, $word) !== false) $score--;
        }
        
        if ($score > 0) $compliance = 'compliant';
        elseif ($score < 0) $compliance = 'non_compliant';
        else $compliance = 'needs_review';
        
        return [
            'compliance' => $compliance,
            'confidence' => 0.6,
            'suggestions' => ['Using fallback analysis. Check API configuration.']
        ];
    }

    private function discoverBestModel() {
        if (empty($this->apiKey) || $this->apiKey === 'YOUR_GEMINI_API_KEY') {
            return;
        }

        // URL to list models
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $this->apiKey;
        
        $response = $this->makeApiCall($url, null, 'GET');

        if (isset($response['models'])) {
            $models = [];
            foreach ($response['models'] as $m) {
                // Strip 'models/' prefix to get clean name (e.g., 'gemini-1.5-flash')
                $models[] = str_replace('models/', '', $m['name']);
            }

            // Priority list for models (Flash > Pro > 1.0)
            $priorities = [
                'gemini-1.5-flash',
                'gemini-1.5-flash-latest',
                'gemini-1.5-pro',
                'gemini-1.5-pro-latest',
                'gemini-pro',
                'gemini-pro-vision'
            ];

            // Find best available model
            foreach ($priorities as $p) {
                if (in_array($p, $models)) {
                    $this->textModel = $p;
                    // For vision, we prefer the same model if it's 1.5 (multimodal), otherwise fallback to pro-vision
                    if (strpos($p, '1.5') !== false || $p === 'gemini-pro-vision') {
                        $this->visionModel = $p;
                    }
                    break;
                }
            }
        }
    }
}