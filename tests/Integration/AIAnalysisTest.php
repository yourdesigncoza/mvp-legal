<?php
/**
 * AI Analysis Workflow Integration Tests
 * Appeal Prospect MVP - AI Integration Testing
 */

declare(strict_types=1);

require_once __DIR__ . '/../TestCase.php';

class AIAnalysisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__ . '/../../app/gpt_handler.php';
        require_once __DIR__ . '/../../app/perplexity.php';
        require_once __DIR__ . '/../../app/save_fetch.php';
        require_once __DIR__ . '/../../app/settings.php';
        
        // Set test API keys
        set_setting('openai_api_key', 'test-openai-key', true);
        set_setting('perplexity_api_key', 'test-perplexity-key', true);
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testCompleteAnalysisWorkflow(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId, [
            'judgment_text' => 'This is a comprehensive legal judgment for testing AI analysis. ' . 
                              str_repeat('The case involves multiple legal issues and complex reasoning. ', 20),
            'status' => 'uploaded'
        ]);
        
        // Mock successful analysis result
        $mockAnalysisResult = json_encode([
            'review_summary' => 'Test case review summary with comprehensive analysis',
            'issues_identified' => 'Key legal issues have been identified in this test case',
            'legal_grounds' => 'Strong legal grounds exist for potential appeal',
            'appeal_strength' => 'The appeal has moderate to strong prospects of success',
            'available_remedies' => 'Several remedies are available including monetary damages',
            'procedural_requirements' => 'Standard appeal procedures must be followed',
            'ethical_considerations' => 'No significant ethical issues identified',
            'appeal_strategy' => 'Recommended strategy focuses on procedural errors',
            'constitutional_implications' => 'No constitutional issues identified',
            'success_probability' => 'Estimated 60-70% chance of success on appeal',
            'risk_analysis' => 'Moderate risk with manageable downside exposure',
            'layperson_summary' => 'This case has good prospects for a successful appeal',
            'sources_citations' => 'Various legal precedents support this analysis'
        ]);
        
        $mockStructuredAnalysis = json_decode($mockAnalysisResult, true);
        $mockTokenUsage = [
            'prompt_tokens' => 1500,
            'completion_tokens' => 800,
            'total_tokens' => 2300
        ];
        
        // Test saving analysis result
        $result = save_analysis_result($caseId, $mockAnalysisResult, $mockStructuredAnalysis, $mockTokenUsage);
        $this->assertTrue($result, 'Analysis result should be saved successfully');
        
        // Verify case status was updated
        $stmt = $this->db->prepare('SELECT status FROM cases WHERE id = ?');
        $stmt->execute([$caseId]);
        $status = $stmt->fetchColumn();
        $this->assertEquals('analyzed', $status);
        
        // Test retrieving analysis result
        $retrievedResult = get_case_results($caseId, $userId);
        $this->assertNotNull($retrievedResult);
        $this->assertEquals($caseId, $retrievedResult['case_id']);
        $this->assertArrayHasKey('structured_analysis', $retrievedResult);
        $this->assertArrayHasKey('token_usage', $retrievedResult);
        
        // Verify structured analysis
        $structuredAnalysis = $retrievedResult['structured_analysis'];
        $this->assertArrayHasKey('review_summary', $structuredAnalysis);
        $this->assertArrayHasKey('success_probability', $structuredAnalysis);
        $this->assertEquals('Test case review summary with comprehensive analysis', $structuredAnalysis['review_summary']);
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisDataRetrieval(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        // Create multiple analysis results
        $analysisData = [
            'analysis_result' => json_encode(['test' => 'data1']),
            'structured_analysis' => ['section1' => 'content1'],
            'token_usage' => ['total_tokens' => 1000],
            'processing_time' => 15.5
        ];
        
        $this->assertTrue(save_analysis_result(
            $caseId, 
            $analysisData['analysis_result'], 
            $analysisData['structured_analysis'], 
            $analysisData['token_usage']
        ));
        
        // Test get_case_results
        $result = get_case_results($caseId, $userId);
        $this->assertNotNull($result);
        $this->assertEquals($caseId, $result['case_id']);
        $this->assertArrayHasKey('structured_analysis', $result);
        $this->assertArrayHasKey('token_usage', $result);
        
        // Test admin access
        $result = get_case_results($caseId, $userId, true);
        $this->assertNotNull($result);
        
        // Test unauthorized access
        $otherUserId = $this->createTestUser(['email' => 'other@test.com']);
        $result = get_case_results($caseId, $otherUserId);
        $this->assertNull($result, 'Should not return results for unauthorized user');
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisStatistics(): void
    {
        $userId = $this->createTestUser();
        
        // Create multiple cases with different statuses
        $uploadedCase = $this->createTestCase($userId, ['status' => 'uploaded']);
        $analyzedCase1 = $this->createTestCase($userId, ['status' => 'analyzed']);
        $analyzedCase2 = $this->createTestCase($userId, ['status' => 'analyzed']);
        
        // Add analysis results for analyzed cases
        save_analysis_result($analyzedCase1, '{}', [], ['total_tokens' => 1000]);
        save_analysis_result($analyzedCase2, '{}', [], ['total_tokens' => 1500]);
        
        // Get statistics for user
        $stats = get_case_statistics($userId);
        
        $this->assertArrayHasKey('total_cases', $stats);
        $this->assertArrayHasKey('completed_analyses', $stats);
        $this->assertArrayHasKey('pending_analyses', $stats);
        $this->assertArrayHasKey('total_tokens_used', $stats);
        
        $this->assertEquals(3, $stats['total_cases']);
        $this->assertEquals(2, $stats['completed_analyses']);
        $this->assertEquals(1, $stats['pending_analyses']);
        $this->assertEquals(2500, $stats['total_tokens_used']);
        
        // Get global statistics (admin view)
        $globalStats = get_case_statistics(0);
        $this->assertArrayHasKey('total_cases', $globalStats);
        $this->assertGreaterThanOrEqual(3, $globalStats['total_cases']);
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testCaseSearchFunctionality(): void
    {
        $userId = $this->createTestUser();
        
        // Create test cases with different content
        $case1 = $this->createTestCase($userId, [
            'case_name' => 'Contract Dispute Smith v Jones',
            'judgment_text' => 'This case involves a contract dispute between Smith and Jones regarding breach of contract terms.'
        ]);
        
        $case2 = $this->createTestCase($userId, [
            'case_name' => 'Property Rights Matter',
            'judgment_text' => 'Property dispute involving boundary issues and easement rights.'
        ]);
        
        $case3 = $this->createTestCase($userId, [
            'case_name' => 'Criminal Appeal Case',
            'judgment_text' => 'Criminal matter involving appeals against conviction for fraud charges.'
        ]);
        
        // Test search by case name
        $results = search_user_cases($userId, 'Contract');
        $this->assertCount(1, $results);
        $this->assertEquals($case1, $results[0]['id']);
        
        // Test search by content
        $results = search_user_cases($userId, 'property');
        $this->assertCount(1, $results);
        $this->assertEquals($case2, $results[0]['id']);
        
        // Test search with multiple results
        $results = search_user_cases($userId, 'case');
        $this->assertGreaterThanOrEqual(2, count($results));
        
        // Test empty search
        $results = search_user_cases($userId, '');
        $this->assertCount(3, $results); // Should return all cases
        
        // Test no results
        $results = search_user_cases($userId, 'nonexistent');
        $this->assertCount(0, $results);
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisResultStructure(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        // Test with complete structured analysis
        $structuredAnalysis = [
            'review_summary' => 'Comprehensive case review',
            'issues_identified' => 'Multiple legal issues identified',
            'legal_grounds' => 'Strong legal foundation',
            'appeal_strength' => 'High probability of success',
            'available_remedies' => 'Various remedies available',
            'procedural_requirements' => 'Standard procedures apply',
            'ethical_considerations' => 'No ethical concerns',
            'appeal_strategy' => 'Multi-pronged approach recommended',
            'constitutional_implications' => 'No constitutional issues',
            'success_probability' => '75% chance of success',
            'risk_analysis' => 'Low to moderate risk',
            'layperson_summary' => 'Good case for appeal',
            'sources_citations' => 'Multiple precedents cited'
        ];
        
        $tokenUsage = [
            'prompt_tokens' => 2000,
            'completion_tokens' => 1200,
            'total_tokens' => 3200
        ];
        
        $webResearch = [
            'queries_performed' => ['contract law', 'breach of contract'],
            'sources_found' => 5,
            'research_time' => 8.5
        ];
        
        // Save with web research data
        $result = save_analysis_result(
            $caseId, 
            json_encode($structuredAnalysis), 
            $structuredAnalysis, 
            $tokenUsage,
            $webResearch
        );
        
        $this->assertTrue($result);
        
        // Retrieve and verify structure
        $retrieved = get_case_results($caseId, $userId);
        
        $this->assertArrayHasKey('structured_analysis', $retrieved);
        $this->assertArrayHasKey('token_usage', $retrieved);
        $this->assertArrayHasKey('web_research', $retrieved);
        
        // Verify all expected sections exist
        foreach (array_keys($structuredAnalysis) as $section) {
            $this->assertArrayHasKey($section, $retrieved['structured_analysis']);
        }
        
        // Verify token usage structure
        $this->assertEquals(3200, $retrieved['token_usage']['total_tokens']);
        
        // Verify web research data
        if ($retrieved['web_research']) {
            $this->assertArrayHasKey('queries_performed', $retrieved['web_research']);
            $this->assertEquals(5, $retrieved['web_research']['sources_found']);
        }
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisResultValidation(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        // Test with invalid JSON
        $result = save_analysis_result($caseId, 'invalid json', [], []);
        $this->assertFalse($result, 'Should reject invalid JSON');
        
        // Test with empty analysis
        $result = save_analysis_result($caseId, '{}', [], []);
        $this->assertTrue($result, 'Should accept empty but valid JSON');
        
        // Test with non-existent case
        $result = save_analysis_result(99999, '{"test": "data"}', [], []);
        $this->assertFalse($result, 'Should reject non-existent case ID');
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testMultipleAnalysisResults(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        // Save first analysis
        $analysis1 = ['version' => '1.0', 'result' => 'first analysis'];
        $this->assertTrue(save_analysis_result($caseId, json_encode($analysis1), $analysis1, ['total_tokens' => 1000]));
        
        // Save second analysis (should update, not create new record)
        $analysis2 = ['version' => '2.0', 'result' => 'updated analysis'];
        $this->assertTrue(save_analysis_result($caseId, json_encode($analysis2), $analysis2, ['total_tokens' => 1200]));
        
        // Verify only one result exists (latest)
        $result = get_case_results($caseId, $userId);
        $this->assertEquals('2.0', $result['structured_analysis']['version']);
        $this->assertEquals(1200, $result['token_usage']['total_tokens']);
        
        // Verify database has only one record for this case
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM case_results WHERE case_id = ?');
        $stmt->execute([$caseId]);
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisPerformanceMetrics(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        $startTime = microtime(true);
        
        // Simulate analysis processing
        $structuredAnalysis = array_fill_keys([
            'review_summary', 'issues_identified', 'legal_grounds', 
            'appeal_strength', 'available_remedies', 'procedural_requirements'
        ], 'Test content');
        
        $tokenUsage = ['total_tokens' => 2500];
        
        $result = save_analysis_result($caseId, json_encode($structuredAnalysis), $structuredAnalysis, $tokenUsage);
        $processingTime = microtime(true) - $startTime;
        
        $this->assertTrue($result);
        $this->assertLessThan(1.0, $processingTime, 'Analysis saving should complete within 1 second');
        
        // Test retrieval performance
        $startTime = microtime(true);
        $retrieved = get_case_results($caseId, $userId);
        $retrievalTime = microtime(true) - $startTime;
        
        $this->assertNotNull($retrieved);
        $this->assertLessThan(0.5, $retrievalTime, 'Analysis retrieval should complete within 0.5 seconds');
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisWithWebResearch(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        $structuredAnalysis = [
            'review_summary' => 'Case involving contract law',
            'success_probability' => '70% chance of success'
        ];
        
        $tokenUsage = ['total_tokens' => 1800];
        
        $webResearch = [
            'queries_performed' => [
                'contract law precedents',
                'breach of contract remedies',
                'recent contract dispute cases'
            ],
            'sources_found' => 12,
            'research_time' => 15.3,
            'relevant_cases' => [
                'Smith v Jones Ltd [2023]',
                'Contract Dispute Corp v ABC [2022]'
            ],
            'legal_principles' => [
                'Breach of contract requires material failure',
                'Damages must be foreseeable'
            ]
        ];
        
        $result = save_analysis_result(
            $caseId, 
            json_encode($structuredAnalysis), 
            $structuredAnalysis, 
            $tokenUsage,
            $webResearch
        );
        
        $this->assertTrue($result);
        
        // Retrieve and verify web research data
        $retrieved = get_case_results($caseId, $userId);
        
        $this->assertArrayHasKey('web_research', $retrieved);
        $webResearchData = $retrieved['web_research'];
        
        $this->assertArrayHasKey('queries_performed', $webResearchData);
        $this->assertCount(3, $webResearchData['queries_performed']);
        $this->assertEquals(12, $webResearchData['sources_found']);
        $this->assertEquals(15.3, $webResearchData['research_time']);
        $this->assertArrayHasKey('relevant_cases', $webResearchData);
        $this->assertArrayHasKey('legal_principles', $webResearchData);
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testTokenUsageTracking(): void
    {
        $userId = $this->createTestUser();
        
        // Create multiple cases with token usage
        $cases = [];
        $totalTokens = 0;
        
        for ($i = 1; $i <= 5; $i++) {
            $caseId = $this->createTestCase($userId, ['case_name' => "Test Case $i"]);
            $tokens = $i * 500; // 500, 1000, 1500, 2000, 2500
            $totalTokens += $tokens;
            
            $this->assertTrue(save_analysis_result(
                $caseId, 
                '{}', 
                [], 
                ['total_tokens' => $tokens, 'prompt_tokens' => $tokens * 0.6, 'completion_tokens' => $tokens * 0.4]
            ));
            
            $cases[] = $caseId;
        }
        
        // Verify token usage statistics
        $stats = get_case_statistics($userId);
        $this->assertEquals($totalTokens, $stats['total_tokens_used']);
        
        // Test individual case token tracking
        for ($i = 0; $i < 5; $i++) {
            $result = get_case_results($cases[$i], $userId);
            $expectedTokens = ($i + 1) * 500;
            $this->assertEquals($expectedTokens, $result['token_usage']['total_tokens']);
        }
    }
    
    /**
     * @group integration
     * @group ai-analysis
     */
    public function testAnalysisErrorHandling(): void
    {
        $userId = $this->createTestUser();
        $caseId = $this->createTestCase($userId);
        
        // Test handling of malformed JSON
        $result = save_analysis_result($caseId, '{"malformed": json}', [], []);
        $this->assertFalse($result);
        
        // Test handling of oversized analysis
        $largeAnalysis = str_repeat('Large analysis content ', 10000); // Very large content
        $result = save_analysis_result($caseId, json_encode(['content' => $largeAnalysis]), ['content' => $largeAnalysis], []);
        $this->assertTrue($result, 'Should handle large analysis content');
        
        // Test retrieval of non-existent case
        $result = get_case_results(99999, $userId);
        $this->assertNull($result);
        
        // Test statistics for user with no cases
        $emptyUserId = $this->createTestUser(['email' => 'empty@test.com']);
        $stats = get_case_statistics($emptyUserId);
        
        $this->assertEquals(0, $stats['total_cases']);
        $this->assertEquals(0, $stats['completed_analyses']);
        $this->assertEquals(0, $stats['pending_analyses']);
        $this->assertEquals(0, $stats['total_tokens_used']);
    }
}