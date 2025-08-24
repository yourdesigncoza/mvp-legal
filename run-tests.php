<?php
/**
 * Test Runner Script
 * Appeal Prospect MVP - Automated Testing Suite
 */

declare(strict_types=1);

// Ensure we're running from command line
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Define constants
define('TEST_ROOT', __DIR__);
define('APP_ROOT', __DIR__);
define('APP_ENV', 'testing');

// Colors for CLI output
class Colors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const RESET = "\033[0m";
}

/**
 * Test Suite Runner
 */
class TestRunner
{
    private array $config;
    private string $phpunitPath;
    private string $testDirectory;
    
    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->phpunitPath = $this->findPHPUnit();
        $this->testDirectory = __DIR__ . '/tests';
    }
    
    /**
     * Load configuration
     */
    private function loadConfig(): array
    {
        return [
            'coverage' => true,
            'verbose' => true,
            'stop_on_failure' => false,
            'test_suites' => [
                'unit' => 'tests/Unit',
                'integration' => 'tests/Integration', 
                'functional' => 'tests/Functional',
                'security' => 'tests/Security'
            ]
        ];
    }
    
    /**
     * Find PHPUnit executable
     */
    private function findPHPUnit(): string
    {
        $paths = [
            __DIR__ . '/vendor/bin/phpunit',
            '/usr/local/bin/phpunit',
            '/usr/bin/phpunit'
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }
        
        // Try global phpunit
        $output = shell_exec('which phpunit 2>/dev/null');
        if ($output && trim($output)) {
            return trim($output);
        }
        
        echo Colors::RED . "PHPUnit not found. Please install PHPUnit.\n" . Colors::RESET;
        echo "You can install it via Composer: composer require --dev phpunit/phpunit\n";
        exit(1);
    }
    
    /**
     * Run all tests
     */
    public function runAll(): void
    {
        $this->printHeader();
        $this->checkEnvironment();
        
        $startTime = microtime(true);
        $allPassed = true;
        $results = [];
        
        foreach ($this->config['test_suites'] as $suite => $path) {
            echo Colors::CYAN . "\n=== Running $suite tests ===\n" . Colors::RESET;
            
            $result = $this->runTestSuite($suite, $path);
            $results[$suite] = $result;
            
            if (!$result['success']) {
                $allPassed = false;
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        
        $this->printSummary($results, $totalTime, $allPassed);
        
        if (!$allPassed) {
            exit(1);
        }
    }
    
    /**
     * Run specific test suite
     */
    public function runSuite(string $suite): void
    {
        if (!isset($this->config['test_suites'][$suite])) {
            echo Colors::RED . "Unknown test suite: $suite\n" . Colors::RESET;
            echo "Available suites: " . implode(', ', array_keys($this->config['test_suites'])) . "\n";
            exit(1);
        }
        
        $this->printHeader();
        $this->checkEnvironment();
        
        $path = $this->config['test_suites'][$suite];
        $result = $this->runTestSuite($suite, $path);
        
        if (!$result['success']) {
            exit(1);
        }
    }
    
    /**
     * Run specific test file
     */
    public function runFile(string $file): void
    {
        $this->printHeader();
        $this->checkEnvironment();
        
        $testFile = $this->findTestFile($file);
        if (!$testFile) {
            echo Colors::RED . "Test file not found: $file\n" . Colors::RESET;
            exit(1);
        }
        
        $result = $this->runTestFile($testFile);
        
        if (!$result['success']) {
            exit(1);
        }
    }
    
    /**
     * Run test suite
     */
    private function runTestSuite(string $suite, string $path): array
    {
        $fullPath = __DIR__ . '/' . $path;
        
        if (!is_dir($fullPath)) {
            echo Colors::YELLOW . "Test directory not found: $fullPath\n" . Colors::RESET;
            return ['success' => true, 'tests' => 0, 'assertions' => 0, 'failures' => 0, 'time' => 0];
        }
        
        $command = $this->buildCommand($fullPath);
        $startTime = microtime(true);
        
        echo "Running: $command\n";
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        $time = microtime(true) - $startTime;
        $outputText = implode("\n", $output);
        
        // Parse PHPUnit output
        $stats = $this->parseOutput($outputText);
        
        if ($returnCode === 0) {
            echo Colors::GREEN . "✓ $suite tests passed\n" . Colors::RESET;
        } else {
            echo Colors::RED . "✗ $suite tests failed\n" . Colors::RESET;
            if ($this->config['verbose']) {
                echo $outputText . "\n";
            }
        }
        
        return [
            'success' => $returnCode === 0,
            'tests' => $stats['tests'],
            'assertions' => $stats['assertions'], 
            'failures' => $stats['failures'],
            'time' => $time,
            'output' => $outputText
        ];
    }
    
    /**
     * Run specific test file
     */
    private function runTestFile(string $file): array
    {
        $command = $this->buildCommand($file);
        $startTime = microtime(true);
        
        echo "Running: $command\n";
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        $time = microtime(true) - $startTime;
        $outputText = implode("\n", $output);
        
        echo $outputText . "\n";
        
        if ($returnCode === 0) {
            echo Colors::GREEN . "✓ Test file passed\n" . Colors::RESET;
        } else {
            echo Colors::RED . "✗ Test file failed\n" . Colors::RESET;
        }
        
        return [
            'success' => $returnCode === 0,
            'time' => $time,
            'output' => $outputText
        ];
    }
    
    /**
     * Build PHPUnit command
     */
    private function buildCommand(string $path): string
    {
        $command = escapeshellcmd($this->phpunitPath);
        
        // Add configuration file
        $configFile = __DIR__ . '/phpunit.xml';
        if (file_exists($configFile)) {
            $command .= ' --configuration ' . escapeshellarg($configFile);
        }
        
        // Add coverage if enabled
        if ($this->config['coverage']) {
            $command .= ' --coverage-text';
        }
        
        // Add verbose output
        if ($this->config['verbose']) {
            $command .= ' --verbose';
        }
        
        // Add stop on failure
        if ($this->config['stop_on_failure']) {
            $command .= ' --stop-on-failure';
        }
        
        // Add test path
        $command .= ' ' . escapeshellarg($path);
        
        return $command;
    }
    
    /**
     * Find test file
     */
    private function findTestFile(string $file): ?string
    {
        // Try direct path first
        if (file_exists($file)) {
            return $file;
        }
        
        // Try in test directory
        $testFile = $this->testDirectory . '/' . $file;
        if (file_exists($testFile)) {
            return $testFile;
        }
        
        // Try with Test suffix
        if (!str_ends_with($file, 'Test.php')) {
            $testFile = $this->testDirectory . '/' . $file . 'Test.php';
            if (file_exists($testFile)) {
                return $testFile;
            }
        }
        
        // Search recursively
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testDirectory)
        );
        
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && str_contains($fileInfo->getFilename(), $file)) {
                return $fileInfo->getPathname();
            }
        }
        
        return null;
    }
    
    /**
     * Parse PHPUnit output
     */
    private function parseOutput(string $output): array
    {
        $stats = ['tests' => 0, 'assertions' => 0, 'failures' => 0];
        
        // Parse test results
        if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output, $matches)) {
            $stats['tests'] = (int)$matches[1];
            $stats['assertions'] = (int)$matches[2];
        } elseif (preg_match('/Tests: (\d+), Assertions: (\d+), Failures: (\d+)/', $output, $matches)) {
            $stats['tests'] = (int)$matches[1];
            $stats['assertions'] = (int)$matches[2];
            $stats['failures'] = (int)$matches[3];
        }
        
        return $stats;
    }
    
    /**
     * Print header
     */
    private function printHeader(): void
    {
        echo Colors::BLUE . str_repeat('=', 60) . "\n";
        echo "Appeal Prospect MVP - Test Suite Runner\n";
        echo str_repeat('=', 60) . "\n" . Colors::RESET;
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "PHPUnit: " . $this->phpunitPath . "\n";
        echo "Environment: " . (APP_ENV ?? 'testing') . "\n\n";
    }
    
    /**
     * Check environment
     */
    private function checkEnvironment(): void
    {
        $errors = [];
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_sqlite', 'json', 'mbstring'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $errors[] = "Missing extension: $ext";
            }
        }
        
        // Check test directory
        if (!is_dir($this->testDirectory)) {
            $errors[] = "Test directory not found: " . $this->testDirectory;
        }
        
        // Check write permissions for coverage
        if ($this->config['coverage'] && !is_writable(__DIR__)) {
            $errors[] = "Directory not writable for coverage reports: " . __DIR__;
        }
        
        if (!empty($errors)) {
            echo Colors::RED . "Environment check failed:\n" . Colors::RESET;
            foreach ($errors as $error) {
                echo "  - $error\n";
            }
            exit(1);
        }
        
        echo Colors::GREEN . "✓ Environment check passed\n" . Colors::RESET;
    }
    
    /**
     * Print test summary
     */
    private function printSummary(array $results, float $totalTime, bool $allPassed): void
    {
        echo Colors::BLUE . "\n" . str_repeat('=', 60) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat('=', 60) . "\n" . Colors::RESET;
        
        $totalTests = 0;
        $totalAssertions = 0;
        $totalFailures = 0;
        
        foreach ($results as $suite => $result) {
            $status = $result['success'] ? Colors::GREEN . '✓' : Colors::RED . '✗';
            $time = number_format($result['time'], 2);
            
            echo "$status $suite: {$result['tests']} tests, {$result['assertions']} assertions, {$result['failures']} failures ({$time}s)" . Colors::RESET . "\n";
            
            $totalTests += $result['tests'];
            $totalAssertions += $result['assertions'];
            $totalFailures += $result['failures'];
        }
        
        echo str_repeat('-', 60) . "\n";
        echo "Total: $totalTests tests, $totalAssertions assertions, $totalFailures failures (" . number_format($totalTime, 2) . "s)\n";
        
        if ($allPassed) {
            echo Colors::GREEN . "\n🎉 ALL TESTS PASSED!\n" . Colors::RESET;
        } else {
            echo Colors::RED . "\n❌ SOME TESTS FAILED!\n" . Colors::RESET;
        }
        
        echo Colors::BLUE . str_repeat('=', 60) . "\n" . Colors::RESET;
    }
}

/**
 * Main execution
 */
function main(): void
{
    $runner = new TestRunner();
    
    $args = array_slice($_SERVER['argv'], 1);
    
    if (empty($args)) {
        // Run all tests
        $runner->runAll();
    } elseif (count($args) === 1) {
        $arg = $args[0];
        
        if (in_array($arg, ['unit', 'integration', 'functional', 'security'])) {
            // Run specific suite
            $runner->runSuite($arg);
        } elseif ($arg === 'help' || $arg === '--help' || $arg === '-h') {
            // Show help
            showHelp();
        } else {
            // Run specific file
            $runner->runFile($arg);
        }
    } else {
        echo Colors::RED . "Too many arguments\n" . Colors::RESET;
        showHelp();
        exit(1);
    }
}

/**
 * Show help text
 */
function showHelp(): void
{
    echo Colors::BLUE . "Appeal Prospect MVP Test Runner\n\n" . Colors::RESET;
    echo "Usage:\n";
    echo "  php run-tests.php                 Run all test suites\n";
    echo "  php run-tests.php unit            Run unit tests only\n";
    echo "  php run-tests.php integration     Run integration tests only\n";
    echo "  php run-tests.php functional      Run functional tests only\n";
    echo "  php run-tests.php security        Run security tests only\n";
    echo "  php run-tests.php <filename>      Run specific test file\n";
    echo "  php run-tests.php help            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php run-tests.php SecurityTest.php\n";
    echo "  php run-tests.php Unit/SecurityTest.php\n";
    echo "  php run-tests.php integration\n\n";
    echo "Test Coverage:\n";
    echo "  Coverage reports are generated in tests/coverage/\n";
    echo "  Open tests/coverage/index.html in browser to view detailed coverage\n\n";
}

// Run the script
main();