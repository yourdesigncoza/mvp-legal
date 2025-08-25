<?php
// OpenAI GPT Handler
// Appeal Prospect MVP - AI Integration Layer

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

/**
 * OpenAI GPT-4o-mini Handler for Legal Analysis
 */
class GPTHandler 
{
    private const API_BASE_URL = 'https://api.openai.com/v1';
    private const MODEL = 'gpt-4o-mini';
    private const MAX_TOKENS = 4096;
    private const TEMPERATURE = 0.3;
    private const MAX_CONTEXT_LENGTH = 128000; // GPT-4o-mini context window
    
    private ?string $api_key = null;
    private string $system_prompt = '';
    
    public function __construct() {
        $this->loadAPIKey();
        $this->loadSystemPrompt();
    }
    
    /**
     * Analyze legal judgment text using GPT-4o-mini
     * 
     * @param string $judgment_text The judgment text to analyze
     * @param int $case_id Database case ID for tracking
     * @return array Analysis result with success status and data
     */
    public function analyzeJudgment(string $judgment_text, int $case_id): array
    {
        try {
            // Check if API key is available
            if (!$this->api_key) {
                return [
                    'success' => false,
                    'error' => 'OpenAI API key not configured. Please contact administrator.'
                ];
            }
            
            // Validate and prepare text
            $prepared_text = $this->prepareText($judgment_text);
            if (!$prepared_text['success']) {
                return $prepared_text;
            }
            
            // Estimate tokens and handle chunking if needed
            $token_estimate = $this->estimateTokens($prepared_text['text'] . $this->system_prompt);
            if ($token_estimate > self::MAX_CONTEXT_LENGTH - self::MAX_TOKENS) {
                return $this->handleLongDocument($prepared_text['text'], $case_id);
            }
            
            // Prepare API request
            $request_data = $this->buildRequestData($prepared_text['text']);
            
            // Make API call
            $api_response = $this->makeAPICall('/chat/completions', $request_data);
            
            if (!$api_response['success']) {
                return $api_response;
            }
            
            // Process response
            $analysis = $this->processResponse($api_response['data'], $case_id);
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log("GPT Analysis Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Analysis processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        if (!$this->api_key) {
            return [
                'success' => false,
                'error' => 'API key not configured'
            ];
        }
        
        try {
            $test_data = [
                'model' => self::MODEL,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test connection - respond with "OK"']
                ],
                'max_tokens' => 10,
                'temperature' => 0
            ];
            
            $response = $this->makeAPICall('/chat/completions', $test_data);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'API connection successful',
                    'model' => self::MODEL
                ];
            } else {
                return $response;
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Load OpenAI API key from settings
     */
    private function loadAPIKey(): void
    {
        $this->api_key = get_setting('openai_api_key');
        
        if ($this->api_key && (strlen($this->api_key) < 10 || !str_starts_with($this->api_key, 'sk-'))) {
            error_log("Invalid OpenAI API key format");
            $this->api_key = null;
        }
    }
    
    /**
     * Load system prompt from file
     */
    private function loadSystemPrompt(): void
    {
        $prompt_file = __DIR__ . '/prompts/appeal_prospect_v5_2.md';
        
        if (file_exists($prompt_file)) {
            $this->system_prompt = file_get_contents($prompt_file);
            
            if ($this->system_prompt === false) {
                error_log("Failed to read system prompt file");
                $this->system_prompt = $this->getFallbackPrompt();
            }
        } else {
            error_log("System prompt file not found: $prompt_file");
            $this->system_prompt = $this->getFallbackPrompt();
        }
        
        // Clean up the prompt
        $this->system_prompt = trim($this->system_prompt);
    }
    
    /**
     * Prepare and validate judgment text
     */
    private function prepareText(string $text): array
    {
        // Clean and normalize text
        $text = trim($text);
        
        if (empty($text)) {
            return [
                'success' => false,
                'error' => 'Judgment text is empty'
            ];
        }
        
        if (strlen($text) < 100) {
            return [
                'success' => false,
                'error' => 'Judgment text is too short for meaningful analysis'
            ];
        }
        
        // Normalize whitespace and encoding
        $text = preg_replace('/\s+/', ' ', $text);
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        
        return [
            'success' => true,
            'text' => $text
        ];
    }
    
    /**
     * Build request data for OpenAI API
     */
    private function buildRequestData(string $judgment_text): array
    {
        return [
            'model' => self::MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->system_prompt
                ],
                [
                    'role' => 'user',
                    'content' => "Please analyze the following South African legal judgment for appeal prospects:\n\n" . $judgment_text
                ]
            ],
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
            'top_p' => 0.9,
            'frequency_penalty' => 0.1,
            'presence_penalty' => 0.1
        ];
    }
    
    /**
     * Make HTTP request to OpenAI API
     */
    private function makeAPICall(string $endpoint, array $data): array
    {
        $url = self::API_BASE_URL . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: application/json',
            'User-Agent: Appeal-Prospect-MVP/1.0'
        ];
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false
        ]);
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        
        curl_close($curl);
        
        if ($curl_error) {
            return [
                'success' => false,
                'error' => 'Network error: ' . $curl_error
            ];
        }
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => 'Failed to get response from API'
            ];
        }
        
        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from API'
            ];
        }
        
        // Handle HTTP errors
        if ($http_code !== 200) {
            $error_message = 'API request failed';
            
            if (isset($decoded_response['error']['message'])) {
                $error_message = $decoded_response['error']['message'];
            }
            
            // Handle specific error codes
            switch ($http_code) {
                case 401:
                    $error_message = 'Invalid API key';
                    break;
                case 429:
                    $error_message = 'Rate limit exceeded. Please try again later.';
                    break;
                case 503:
                    $error_message = 'OpenAI service temporarily unavailable';
                    break;
            }
            
            return [
                'success' => false,
                'error' => $error_message,
                'http_code' => $http_code
            ];
        }
        
        return [
            'success' => true,
            'data' => $decoded_response
        ];
    }
    
    /**
     * Process OpenAI API response and extract structured analysis
     */
    private function processResponse(array $response_data, int $case_id): array
    {
        if (!isset($response_data['choices'][0]['message']['content'])) {
            return [
                'success' => false,
                'error' => 'Invalid response format from API'
            ];
        }
        
        $analysis_text = trim($response_data['choices'][0]['message']['content']);
        
        if (empty($analysis_text)) {
            return [
                'success' => false,
                'error' => 'Empty analysis received from API'
            ];
        }
        
        // Extract token usage information
        $token_usage = [
            'prompt_tokens' => $response_data['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $response_data['usage']['completion_tokens'] ?? 0,
            'total_tokens' => $response_data['usage']['total_tokens'] ?? 0
        ];
        
        // Parse the structured analysis (basic parsing for now)
        $structured_analysis = $this->parseStructuredAnalysis($analysis_text);
        
        // Save analysis to database
        $this->saveAnalysisToDatabase($case_id, $analysis_text, $structured_analysis, $token_usage);
        
        return [
            'success' => true,
            'analysis_text' => $analysis_text,
            'structured_analysis' => $structured_analysis,
            'token_usage' => $token_usage,
            'case_id' => $case_id
        ];
    }
    
    /**
     * Parse structured analysis from GPT response
     * TODO: Implement more sophisticated parsing based on System Prompt v5.2 format
     */
    private function parseStructuredAnalysis(string $analysis_text): array
    {
        // Basic parsing - will be enhanced based on actual System Prompt v5.2 output format
        $sections = [
            'review_summary' => '',
            'issues_identified' => '',
            'legal_grounds' => '',
            'strength_assessment' => '',
            'available_remedies' => '',
            'procedural_requirements' => '',
            'ethical_considerations' => '',
            'appeal_strategy' => '',
            'constitutional_aspects' => '',
            'success_probability' => '',
            'risks_challenges' => '',
            'layperson_summary' => '',
            'sources_references' => ''
        ];
        
        // Simple section detection (will be refined)
        $lines = explode("\n", $analysis_text);
        $current_section = 'review_summary';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Detect section headers (basic implementation)
            if (preg_match('/^#+ (.+)/', $line, $matches)) {
                $header = strtolower(str_replace(' ', '_', $matches[1]));
                if (array_key_exists($header, $sections)) {
                    $current_section = $header;
                    continue;
                }
            }
            
            // Add content to current section
            if (!empty($line)) {
                $sections[$current_section] .= $line . "\n";
            }
        }
        
        // Clean up sections
        foreach ($sections as $key => $content) {
            $sections[$key] = trim($content);
        }
        
        return $sections;
    }
    
    /**
     * Save analysis results to database
     */
    private function saveAnalysisToDatabase(int $case_id, string $analysis_text, array $structured_analysis, array $token_usage): bool
    {
        try {
            $sql = "UPDATE cases SET 
                        analysis_result = ?, 
                        structured_analysis = ?,
                        token_in = ?, 
                        token_out = ?, 
                        status = 'analyzed',
                        analyzed_at = NOW()
                    WHERE id = ?";
            
            $params = [
                $analysis_text,
                json_encode($structured_analysis),
                $token_usage['prompt_tokens'],
                $token_usage['completion_tokens'],
                $case_id
            ];
            
            return db_execute($sql, $params) > 0;
            
        } catch (Exception $e) {
            error_log("Failed to save analysis to database: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Handle long documents that exceed context window
     */
    private function handleLongDocument(string $text, int $case_id): array
    {
        // For now, truncate the document
        // TODO: Implement intelligent chunking strategy
        
        $max_text_tokens = self::MAX_CONTEXT_LENGTH - $this->estimateTokens($this->system_prompt) - self::MAX_TOKENS - 500; // Safety margin
        $truncated_text = $this->truncateToTokenLimit($text, $max_text_tokens);
        
        // Add warning to analysis
        $warning_note = "\n\n[NOTE: Document was truncated due to length. Analysis based on first portion of judgment.]";
        
        // Process with truncated text
        $request_data = $this->buildRequestData($truncated_text . $warning_note);
        $api_response = $this->makeAPICall('/chat/completions', $request_data);
        
        if (!$api_response['success']) {
            return $api_response;
        }
        
        $analysis = $this->processResponse($api_response['data'], $case_id);
        
        if ($analysis['success']) {
            $analysis['warning'] = 'Document was truncated due to length limits';
        }
        
        return $analysis;
    }
    
    /**
     * Estimate token count (rough approximation)
     */
    private function estimateTokens(string $text): int
    {
        // Rough approximation: 1 token ≈ 4 characters for English text
        return (int) ceil(strlen($text) / 4);
    }
    
    /**
     * Truncate text to approximate token limit
     */
    private function truncateToTokenLimit(string $text, int $max_tokens): string
    {
        $max_chars = $max_tokens * 4; // Rough approximation
        
        if (strlen($text) <= $max_chars) {
            return $text;
        }
        
        // Truncate at word boundary
        $truncated = substr($text, 0, $max_chars);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated;
    }
    
    /**
     * Fallback system prompt if file not found
     */
    private function getFallbackPrompt(): string
    {
        return "You are a legal AI assistant specializing in South African law and appeal analysis. 

Analyze the provided legal judgment and provide a comprehensive assessment of appeal prospects. Structure your response with the following sections:

1. Review Summary
2. Issues Identified  
3. Legal Grounds
4. Strength Assessment
5. Available Remedies
6. Procedural Requirements
7. Ethical Considerations
8. Appeal Strategy
9. Constitutional Aspects
10. Success Probability
11. Risks & Challenges
12. Layperson Summary
13. Sources/References

Provide detailed analysis for each section based on South African legal principles and precedent.";
    }
}

/**
 * Helper function to analyze judgment
 */
function analyze_judgment_with_gpt(string $judgment_text, int $case_id): array
{
    $gpt_handler = new GPTHandler();
    return $gpt_handler->analyzeJudgment($judgment_text, $case_id);
}

/**
 * Helper function to test OpenAI connection
 */
function test_openai_connection(): array
{
    $gpt_handler = new GPTHandler();
    return $gpt_handler->testConnection();
}