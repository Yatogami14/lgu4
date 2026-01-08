<?php
class GeminiAnalyzer {
    private $apiKey;
    private $textModel = 'gemini-2.5-flash';
    private $visionModel = 'gemini-2.5-flash';
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $useFallback = false;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getTextModel() {
        return $this->textModel;
    }

    public function getVisionModel() {
        return $this->visionModel;
    }

    public function analyzeText($text) {
        $prompt = "You are an AI assistant for a health and safety inspection platform. Analyze the following inspector's observation notes. Based on the text, determine the compliance status. The status must be one of: 'compliant', 'non_compliant', or 'needs_review'. Also provide a confidence score for your assessment (from 0.0 to 1.0) and up to two brief suggestions for the inspector.\n\nReturn your analysis ONLY as a valid JSON object with the keys: 'compliance', 'confidence', 'suggestions' (which should be an array of strings).\n\nInspector's notes: \"{$text}\"";
        $parts = [['text' => $prompt]];
        return $this->_generateContent($parts, $this->textModel);
    }

    public function analyzeMedia($imagePath) {
        // Log the start of media analysis
        error_log("Starting media analysis for: " . $imagePath);

        if (!file_exists($imagePath)) {
            error_log("Image file not found: " . $imagePath);
            return $this->createResponse('error', 0.0, ['Image file not found: ' . $imagePath], []);
        }

        if ($this->useFallback) {
            error_log("Using fallback analysis for: " . $imagePath);
            return $this->fallbackMediaAnalysis($imagePath);
        }

        try {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            // Enhanced prompt for better photo analysis
            $prompt = "You are an expert Health and Safety Inspector analyzing workplace photos for compliance with safety regulations.

Analyze the provided image carefully and provide a structured JSON response with the following keys:
- 'compliance': Your overall assessment. Must be one of 'compliant', 'non_compliant', or 'needs_review'. Use 'needs_review' if the image is unclear, not workplace-related, or you cannot determine compliance.
- 'confidence': A score from 0.0 to 1.0 indicating your confidence in the compliance assessment. Be conservative - use lower scores for unclear images.
- 'hazards': An array of strings, each describing a specific safety hazard you've identified. Be specific and detailed (e.g., 'Exposed electrical wiring without proper insulation', 'Missing guard rails on elevated platform', 'Improper storage of flammable materials'). List up to 5 major hazards. If none visible, return an empty array.
- 'positive_observations': An array of strings, each describing good safety practices you've observed. Be specific (e.g., 'All personnel wearing appropriate PPE including hard hats and safety vests', 'Emergency exit signs are clearly visible and unobstructed', 'Fire extinguishers are properly mounted and accessible'). List up to 5 positive observations. If none visible, return an empty array.

Focus on:
- Personal Protective Equipment (PPE) usage
- Workplace organization and housekeeping
- Electrical safety
- Fire safety equipment
- Fall protection
- Hazardous material storage
- Emergency exits and signage
- Equipment guarding and maintenance

Return ONLY the valid JSON object. If you're unsure about any aspect, reflect that in your confidence score.";

            $parts = [
                ['text' => $prompt],
                [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $imageData
                    ]
                ]
            ];

            error_log("Calling Gemini API for media analysis with model: " . $this->visionModel);
            $result = $this->_generateContent($parts, $this->visionModel);

            if (isset($result['error'])) {
                error_log("Gemini API Error in analyzeMedia: " . $result['error'] . (isset($result['raw']) ? ' - Raw: ' . json_encode($result['raw']) : ''));
                return $this->createResponse('needs_review', 0.0, ['AI analysis failed: ' . $result['error'] . '. Please review manually.'], []);
            }

            error_log("Successfully analyzed media: " . $imagePath);
            return $this->formatMediaResponse($result);

        } catch (Exception $e) {
            error_log("Gemini Analyzer Exception in analyzeMedia: " . $e->getMessage() . " for file: " . $imagePath);
            return $this->createResponse('needs_review', 0.0, ['Analysis error: ' . $e->getMessage() . '. Please review manually.'], []);
        }
    }

    private function _generateContent(array $parts, string $model) {
        $data = [
            'contents' => [['parts' => $parts]],
            'generationConfig' => ['responseMimeType' => 'application/json']
        ];

        $url = $this->baseUrl . $model . ':generateContent?key=' . $this->apiKey;

        $maxRetries = 3;
        $attempt = 0;
        $response = null;
        $httpCode = 0;
        $error = null;

        do {
            $attempt++;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // If rate limited (429), wait and retry
            if ($httpCode == 429 && $attempt < $maxRetries) {
                sleep(2); // Wait 2 seconds before retrying
                continue;
            }
            break;
        } while ($attempt < $maxRetries);
        
        if ($error) {
            return ['error' => 'cURL Error: ' . $error];
        }
        if ($httpCode != 200) {
            return ['error' => "HTTP Error $httpCode", 'raw' => $response];
        }

        $responseData = json_decode($response, true);
        
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            if (isset($responseData['candidates'][0]['finishReason']) && $responseData['candidates'][0]['finishReason'] === 'SAFETY') {
                return ['error' => 'Content blocked due to safety settings.'];
            }
            return ['error' => 'Invalid API response structure', 'raw' => $responseData];
        }

        $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];
        $decoded = json_decode($jsonString, true);

        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $this->parseGeminiResponse($jsonString);
    }

    private function parseGeminiResponse($text) {
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return ['error' => 'Failed to parse JSON response', 'raw' => $text];
    }

    private function formatMediaResponse($data) {
        $hazards = $data['hazards'] ?? [];
        $positive = $data['positive_observations'] ?? [];
        $status = $data['compliance'] ?? 'needs_review';
        $confidence = floatval($data['confidence'] ?? 0.1);
        
        return $this->createResponse($status, $confidence, $hazards, $positive);
    }

    private function createResponse($status, $confidence, $hazards, $positive) {
        return [
            'compliance' => $status,
            'confidence' => $confidence,
            'hazards' => (array)$hazards,
            'positive_observations' => (array)$positive
        ];
    }

    private function fallbackMediaAnalysis($imagePath) {
        $hazards = ['AI analysis is currently unavailable. Please review manually.'];
        $positive = [];
        $status = 'needs_review';
        $confidence = 0.0;
        return $this->createResponse($status, $confidence, $hazards, $positive);
    }
}
?>