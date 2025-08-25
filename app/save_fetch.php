<?php
// Data Processing Functions
// Appeal Prospect MVP - Result Storage and Retrieval

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Save analysis results to database
 * 
 * @param int $case_id Case ID
 * @param string $analysis_result Raw analysis text
 * @param array $structured_analysis Parsed 13-section analysis
 * @param array $token_usage Token usage statistics
 * @return bool Success status
 */
function save_analysis_result(int $case_id, string $analysis_result, array $structured_analysis, array $token_usage): bool
{
    try {
        $sql = "UPDATE cases SET 
                    analysis_result = ?, 
                    structured_analysis = ?,
                    token_in = ?, 
                    token_out = ?, 
                    status = 'analyzed',
                    analyzed_at = NOW(),
                    completed_analysis_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $analysis_result,
            json_encode($structured_analysis, JSON_UNESCAPED_UNICODE),
            $token_usage['prompt_tokens'] ?? 0,
            $token_usage['completion_tokens'] ?? 0,
            $case_id
        ];
        
        return db_execute($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to save analysis result: " . $e->getMessage());
        return false;
    }
}

/**
 * Save research citations to database
 * 
 * @param int $case_id Case ID
 * @param array $citations Citation array from Perplexity
 * @param string $research_summary Optional research summary
 * @return bool Success status
 */
function save_research_citations(int $case_id, array $citations, string $research_summary = ''): bool
{
    try {
        $sql = "UPDATE cases SET 
                    citations = ?, 
                    research_summary = ?,
                    updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            json_encode($citations, JSON_UNESCAPED_UNICODE),
            $research_summary,
            $case_id
        ];
        
        return db_execute($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to save research citations: " . $e->getMessage());
        return false;
    }
}

/**
 * Get case results with full analysis data
 * 
 * @param int $case_id Case ID
 * @param int $user_id User ID for access control
 * @param bool $admin_access Whether user has admin access
 * @return array|null Case data or null if not found
 */
function get_case_results(int $case_id, int $user_id, bool $admin_access = false): ?array
{
    try {
        $sql = "SELECT * FROM cases WHERE id = ?";
        $params = [$case_id];
        
        // Add access control unless admin
        if (!$admin_access) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }
        
        $case_data = db_query_single($sql, $params);
        
        if (!$case_data) {
            return null;
        }
        
        // Parse JSON fields
        if (!empty($case_data['structured_analysis'])) {
            $case_data['structured_analysis_parsed'] = json_decode($case_data['structured_analysis'], true);
        }
        
        if (!empty($case_data['citations'])) {
            $case_data['citations_parsed'] = json_decode($case_data['citations'], true);
        }
        
        return $case_data;
        
    } catch (Exception $e) {
        error_log("Failed to get case results: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user's cases with basic information
 * 
 * @param int $user_id User ID
 * @param int $limit Number of cases to return
 * @param int $offset Offset for pagination
 * @param string $status Filter by status (optional)
 * @return array Array of case data
 */
function get_user_cases(int $user_id, int $limit = 20, int $offset = 0, string $status = ''): array
{
    try {
        $sql = "SELECT 
                    id, case_name, original_filename, status, 
                    created_at, analyzed_at, completed_analysis_at,
                    token_in, token_out,
                    (CASE WHEN analysis_result IS NOT NULL THEN 1 ELSE 0 END) as has_results
                FROM cases 
                WHERE user_id = ?";
        
        $params = [$user_id];
        
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return db_query($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to get user cases: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all cases (admin function)
 * 
 * @param int $limit Number of cases to return
 * @param int $offset Offset for pagination
 * @param string $status Filter by status (optional)
 * @return array Array of case data with user info
 */
function get_all_cases(int $limit = 50, int $offset = 0, string $status = ''): array
{
    try {
        $sql = "SELECT 
                    c.id, c.case_name, c.original_filename, c.status,
                    c.created_at, c.analyzed_at, c.completed_analysis_at,
                    c.token_in, c.token_out,
                    u.name as user_name, u.email as user_email,
                    (CASE WHEN c.analysis_result IS NOT NULL THEN 1 ELSE 0 END) as has_results
                FROM cases c
                JOIN users u ON c.user_id = u.id";
        
        $params = [];
        
        if (!empty($status)) {
            $sql .= " WHERE c.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return db_query($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to get all cases: " . $e->getMessage());
        return [];
    }
}

/**
 * Update case status
 * 
 * @param int $case_id Case ID
 * @param string $status New status
 * @param string $error_message Optional error message for failed status
 * @return bool Success status
 */
function update_case_status(int $case_id, string $status, string $error_message = ''): bool
{
    try {
        $timestamp_field = '';
        switch ($status) {
            case 'analyzing':
                $timestamp_field = ', started_analysis_at = NOW()';
                break;
            case 'analyzed':
                $timestamp_field = ', completed_analysis_at = NOW(), analyzed_at = NOW()';
                break;
        }
        
        $sql = "UPDATE cases SET 
                    status = ?, 
                    error_message = ?,
                    updated_at = NOW()
                    {$timestamp_field}
                WHERE id = ?";
        
        $params = [$status, $error_message, $case_id];
        
        return db_execute($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to update case status: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete case and associated files
 * 
 * @param int $case_id Case ID
 * @param int $user_id User ID for access control
 * @param bool $admin_access Whether user has admin access
 * @return bool Success status
 */
function delete_case(int $case_id, int $user_id, bool $admin_access = false): bool
{
    try {
        // Get case data first to check permissions and find files
        $case_data = get_case_results($case_id, $user_id, $admin_access);
        
        if (!$case_data) {
            return false;
        }
        
        // Delete associated file if exists
        if (!empty($case_data['stored_filename'])) {
            $file_path = __DIR__ . '/../uploads/' . $case_data['user_id'] . '/' . $case_data['stored_filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete case from database
        $sql = "DELETE FROM cases WHERE id = ?";
        $params = [$case_id];
        
        // Add access control unless admin
        if (!$admin_access) {
            $sql = "DELETE FROM cases WHERE id = ? AND user_id = ?";
            $params[] = $user_id;
        }
        
        return db_execute($sql, $params) > 0;
        
    } catch (Exception $e) {
        error_log("Failed to delete case: " . $e->getMessage());
        return false;
    }
}

/**
 * Get case statistics
 * 
 * @param int $user_id User ID (0 for all users - admin only)
 * @return array Statistics array
 */
function get_case_statistics(int $user_id = 0): array
{
    try {
        $sql = "SELECT 
                    COUNT(*) as total_cases,
                    SUM(CASE WHEN status = 'uploaded' THEN 1 ELSE 0 END) as pending_cases,
                    SUM(CASE WHEN status = 'analyzing' THEN 1 ELSE 0 END) as processing_cases,
                    SUM(CASE WHEN status = 'analyzed' THEN 1 ELSE 0 END) as completed_cases,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_cases,
                    SUM(COALESCE(token_in, 0) + COALESCE(token_out, 0)) as total_tokens,
                    AVG(COALESCE(token_in, 0) + COALESCE(token_out, 0)) as avg_tokens_per_case
                FROM cases";
        
        $params = [];
        
        if ($user_id > 0) {
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
        }
        
        $stats = db_query_single($sql, $params);
        
        // Convert to integers and handle nulls
        return [
            'total_cases' => (int)($stats['total_cases'] ?? 0),
            'pending_cases' => (int)($stats['pending_cases'] ?? 0),
            'processing_cases' => (int)($stats['processing_cases'] ?? 0),
            'completed_cases' => (int)($stats['completed_cases'] ?? 0),
            'failed_cases' => (int)($stats['failed_cases'] ?? 0),
            'total_tokens' => (int)($stats['total_tokens'] ?? 0),
            'avg_tokens_per_case' => round((float)($stats['avg_tokens_per_case'] ?? 0), 0)
        ];
        
    } catch (Exception $e) {
        error_log("Failed to get case statistics: " . $e->getMessage());
        return [
            'total_cases' => 0,
            'pending_cases' => 0,
            'processing_cases' => 0,
            'completed_cases' => 0,
            'failed_cases' => 0,
            'total_tokens' => 0,
            'avg_tokens_per_case' => 0
        ];
    }
}

/**
 * Search cases by name or content
 * 
 * @param string $search_term Search term
 * @param int $user_id User ID (0 for all users - admin only)
 * @param int $limit Number of results to return
 * @return array Search results
 */
function search_cases(string $search_term, int $user_id = 0, int $limit = 20): array
{
    try {
        if (empty(trim($search_term))) {
            return [];
        }
        
        $sql = "SELECT 
                    c.id, c.case_name, c.original_filename, c.status,
                    c.created_at, c.analyzed_at,
                    u.name as user_name, u.email as user_email,
                    (CASE WHEN c.analysis_result IS NOT NULL THEN 1 ELSE 0 END) as has_results
                FROM cases c
                JOIN users u ON c.user_id = u.id
                WHERE (c.case_name LIKE ? OR c.analysis_result LIKE ?)";
        
        $search_param = '%' . $search_term . '%';
        $params = [$search_param, $search_param];
        
        if ($user_id > 0) {
            $sql .= " AND c.user_id = ?";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return db_query($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to search cases: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent analysis activity
 * 
 * @param int $user_id User ID (0 for all users - admin only)
 * @param int $days Number of days to look back
 * @return array Activity data
 */
function get_recent_activity(int $user_id = 0, int $days = 7): array
{
    try {
        $sql = "SELECT 
                    c.id, c.case_name, c.status,
                    c.created_at, c.analyzed_at, c.completed_analysis_at,
                    u.name as user_name
                FROM cases c
                JOIN users u ON c.user_id = u.id
                WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $params = [$days];
        
        if ($user_id > 0) {
            $sql .= " AND c.user_id = ?";
            $params[] = $user_id;
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT 50";
        
        return db_query($sql, $params);
        
    } catch (Exception $e) {
        error_log("Failed to get recent activity: " . $e->getMessage());
        return [];
    }
}

/**
 * Format case data for display
 * 
 * @param array $case_data Raw case data from database
 * @return array Formatted case data
 */
function format_case_for_display(array $case_data): array
{
    // Parse JSON fields if not already done
    if (isset($case_data['structured_analysis']) && !isset($case_data['structured_analysis_parsed'])) {
        $case_data['structured_analysis_parsed'] = json_decode($case_data['structured_analysis'], true) ?? [];
    }
    
    if (isset($case_data['citations']) && !isset($case_data['citations_parsed'])) {
        $case_data['citations_parsed'] = json_decode($case_data['citations'], true) ?? [];
    }
    
    // Add computed fields
    $case_data['total_tokens'] = ($case_data['token_in'] ?? 0) + ($case_data['token_out'] ?? 0);
    $case_data['has_analysis'] = !empty($case_data['analysis_result']) || !empty($case_data['has_results']);
    $case_data['has_citations'] = !empty($case_data['citations_parsed']);
    $case_data['document_length'] = strlen($case_data['judgment_text'] ?? '');
    
    // Status badges
    $status_config = [
        'uploaded' => ['class' => 'secondary', 'text' => 'Pending'],
        'analyzing' => ['class' => 'warning', 'text' => 'Analyzing'],
        'analyzed' => ['class' => 'success', 'text' => 'Complete'],
        'failed' => ['class' => 'danger', 'text' => 'Failed']
    ];
    
    $case_data['status_badge'] = $status_config[$case_data['status']] ?? ['class' => 'secondary', 'text' => 'Unknown'];
    
    return $case_data;
}