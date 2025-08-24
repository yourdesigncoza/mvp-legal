<?php
// Perplexity API Integration
// Appeal Prospect MVP - Web Research Enhancement

declare(strict_types=1);

require_once __DIR__ . '/settings.php';

/**
 * Perplexity API Handler for Legal Research
 */
class PerplexityHandler 
{
    private const API_BASE_URL = 'https://api.perplexity.ai';
    private const MODEL = 'llama-3.1-sonar-small-128k-online';
    private const MAX_TOKENS = 1024;
    private const TEMPERATURE = 0.2;
    private const MAX_CITATIONS = 5;
    
    private ?string $api_key = null;
    
    public function __construct() {
        $this->loadAPIKey();
    }
    
    /**
     * Research legal sources related to judgment analysis
     * 
     * @param string $case_summary Brief summary of the case for research
     * @param array $legal_issues Key legal issues identified
     * @return array Research result with citations and sources
     */
    public function researchLegalSources(string $case_summary, array $legal_issues = []): array
    {
        try {
            // Check if API key is available (optional feature)
            if (!$this->api_key) {
                return [
                    'success' => true,
                    'citations' => [],
                    'sources' => [],
                    'message' => 'Perplexity API key not configured - web research unavailable',
                    'available' => false
                ];
            }
            
            // Build research query
            $research_query = $this->buildResearchQuery($case_summary, $legal_issues);
            
            // Make API request
            $api_response = $this->makeAPICall($research_query);
            
            if (!$api_response['success']) {
                // Graceful degradation - don't fail the entire analysis
                return [
                    'success' => true,
                    'citations' => [],
                    'sources' => [],
                    'message' => 'Web research temporarily unavailable: ' . $api_response['error'],
                    'available' => false
                ];
            }
            
            // Parse and format citations
            $formatted_results = $this->parseResearchResults($api_response['data']);
            
            return [
                'success' => true,
                'citations' => $formatted_results['citations'],
                'sources' => $formatted_results['sources'],
                'summary' => $formatted_results['summary'],
                'available' => true
            ];
            
        } catch (Exception $e) {
            error_log("Perplexity Research Error: " . $e->getMessage());
            
            // Graceful degradation
            return [
                'success' => true,
                'citations' => [],
                'sources' => [],
                'message' => 'Web research encountered an error',
                'available' => false
            ];
        }
    }
    
    /**
     * Test Perplexity API connection
     */
    public function testConnection(): array
    {
        if (!$this->api_key) {
            return [
                'success' => false,
                'error' => 'API key not configured',
                'available' => false
            ];
        }
        
        try {
            $test_query = "What is South African law?";
            
            $test_data = [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $test_query
                    ]
                ],
                'max_tokens' => 50,
                'temperature' => 0,
                'return_citations' => true
            ];
            
            $response = $this->makeAPICallRaw('/chat/completions', $test_data);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Perplexity API connection successful',
                    'model' => self::MODEL,
                    'available' => true
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'],
                    'available' => false
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection test failed: ' . $e->getMessage(),
                'available' => false
            ];
        }
    }
    
    /**
     * Load Perplexity API key from settings
     */
    private function loadAPIKey(): void
    {
        $this->api_key = get_setting('perplexity_api_key');
        
        if ($this->api_key && strlen($this->api_key) < 10) {
            error_log("Invalid Perplexity API key format");
            $this->api_key = null;
        }
    }
    
    /**
     * Build research query for legal sources
     */
    private function buildResearchQuery(string $case_summary, array $legal_issues): string
    {
        $query = "Find recent South African legal cases, legislation, and academic sources related to: ";
        $query .= $case_summary;
        
        if (!empty($legal_issues)) {
            $query .= " Focus on these legal issues: " . implode(', ', $legal_issues);
        }
        
        $query .= " Provide specific case citations, statutes, and authoritative legal sources from South African courts and legal databases.";
        
        return $query;
    }
    
    /**
     * Make API call to Perplexity (wrapper with error handling)
     */
    private function makeAPICall(string $query): array
    {
        $request_data = [
            'model' => self::MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a legal research assistant specializing in South African law. Provide accurate citations and sources for legal research queries. Focus on recent cases, relevant legislation, and authoritative legal commentary.'
                ],
                [
                    'role' => 'user',
                    'content' => $query
                ]
            ],
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
            'return_citations' => true,
            'return_images' => false
        ];
        
        return $this->makeAPICallRaw('/chat/completions', $request_data);
    }
    
    /**
     * Make raw HTTP request to Perplexity API
     */
    private function makeAPICallRaw(string $endpoint, array $data): array
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
            CURLOPT_TIMEOUT => 60,
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
                'error' => 'Failed to get response from Perplexity API'
            ];
        }
        
        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from Perplexity API'
            ];
        }
        
        // Handle HTTP errors
        if ($http_code !== 200) {
            $error_message = 'Perplexity API request failed';
            
            if (isset($decoded_response['error']['message'])) {
                $error_message = $decoded_response['error']['message'];
            }
            
            // Handle specific error codes
            switch ($http_code) {
                case 401:
                    $error_message = 'Invalid Perplexity API key';
                    break;
                case 429:
                    $error_message = 'Rate limit exceeded for Perplexity API';
                    break;
                case 503:
                    $error_message = 'Perplexity service temporarily unavailable';
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
     * Parse research results and extract citations
     */
    private function parseResearchResults(array $response_data): array
    {
        $citations = [];
        $sources = [];
        $summary = '';
        
        if (isset($response_data['choices'][0]['message']['content'])) {
            $summary = trim($response_data['choices'][0]['message']['content']);
        }
        
        // Extract citations from response
        if (isset($response_data['citations']) && is_array($response_data['citations'])) {
            foreach ($response_data['citations'] as $citation) {
                if (isset($citation['url']) && isset($citation['title'])) {
                    $sources[] = [
                        'title' => $citation['title'],
                        'url' => $citation['url'],
                        'snippet' => $citation['snippet'] ?? '',
                        'domain' => parse_url($citation['url'], PHP_URL_HOST) ?? ''
                    ];
                }
            }
        }
        
        // Format citations for display
        foreach ($sources as $index => $source) {
            if ($index >= self::MAX_CITATIONS) break;
            
            $citations[] = [
                'number' => $index + 1,
                'title' => $source['title'],
                'url' => $source['url'],
                'domain' => $source['domain'],
                'snippet' => $source['snippet']
            ];
        }
        
        // Extract additional citations from content if available
        if ($summary) {
            $additional_citations = $this->extractCitationsFromText($summary);
            $citations = array_merge($citations, $additional_citations);
        }
        
        return [
            'citations' => array_slice($citations, 0, self::MAX_CITATIONS),
            'sources' => array_slice($sources, 0, self::MAX_CITATIONS),
            'summary' => $summary
        ];
    }
    
    /**
     * Extract legal citations from research text
     */
    private function extractCitationsFromText(string $text): array
    {
        $citations = [];
        
        // Pattern to match South African case citations
        // e.g., "Smith v Jones 2023 (2) SA 123 (SCA)"
        $case_pattern = '/\b([A-Z][a-zA-Z\s&]+\s+v\s+[A-Z][a-zA-Z\s&]+)\s+(\d{4})\s*(\([0-9]+\)\s*[A-Z]{2,4}\s+\d+(?:\s*\([A-Z]{2,4}\))?)/';
        
        preg_match_all($case_pattern, $text, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $citations[] = [
                'type' => 'case',
                'citation' => trim($match[0]),
                'case_name' => trim($match[1]),
                'year' => $match[2],
                'reference' => trim($match[3])
            ];
        }
        
        // Pattern to match legislation
        // e.g., "Section 25 of the Constitution", "Companies Act 71 of 2008"
        $legislation_pattern = '/\b([A-Z][a-zA-Z\s]+Act\s+\d+\s+of\s+\d{4}|Section\s+\d+[a-z]*(?:\([0-9a-z]+\))?\s+of\s+the\s+[A-Z][a-zA-Z\s]+(?:Act)?)/';
        
        preg_match_all($legislation_pattern, $text, $leg_matches);
        
        foreach ($leg_matches[0] as $legislation) {
            $citations[] = [
                'type' => 'legislation',
                'citation' => trim($legislation)
            ];
        }
        
        return array_slice($citations, 0, 3); // Limit additional citations
    }
    
    /**
     * Check if Perplexity research is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->api_key);
    }
    
    /**
     * Get research status message
     */
    public function getStatusMessage(): string
    {
        if ($this->isAvailable()) {
            return 'Web research available';
        } else {
            return 'Web research unavailable - API key not configured';
        }
    }
}

/**
 * Helper function to research legal sources
 */
function research_legal_sources(string $case_summary, array $legal_issues = []): array
{
    $perplexity = new PerplexityHandler();
    return $perplexity->researchLegalSources($case_summary, $legal_issues);
}

/**
 * Helper function to test Perplexity connection
 */
function test_perplexity_connection(): array
{
    $perplexity = new PerplexityHandler();
    return $perplexity->testConnection();
}

/**
 * Helper function to check if web research is available
 */
function is_web_research_available(): bool
{
    $perplexity = new PerplexityHandler();
    return $perplexity->isAvailable();
}