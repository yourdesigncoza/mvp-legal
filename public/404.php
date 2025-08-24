<?php
/**
 * 404 Error Handler
 * Appeal Prospect MVP - Not Found Page
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/error_handler.php';

// Initialize error handling
ErrorHandler::initialize();

// Handle 404 error
ErrorHandler::handle404();