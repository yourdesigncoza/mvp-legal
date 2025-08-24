<?php
/**
 * Enhanced Form Validation System
 * Appeal Prospect MVP - Client and Server-side Validation
 */

declare(strict_types=1);

require_once __DIR__ . '/security.php';

/**
 * Form Validator Class
 */
class FormValidator
{
    private array $errors = [];
    private array $data = [];
    private array $rules = [];
    private array $messages = [];
    
    /**
     * Create new validator instance
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }
    
    /**
     * Validate all fields
     */
    public function validate(): bool
    {
        $this->errors = [];
        
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $this->validateField($field, $value, $fieldRules);
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get validated data (sanitized)
     */
    public function getValidatedData(): array
    {
        return $this->data;
    }
    
    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Validate individual field
     */
    private function validateField(string $field, $value, array|string $rules): void
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        foreach ($rules as $rule) {
            $this->applyRule($field, $value, $rule);
        }
    }
    
    /**
     * Apply validation rule
     */
    private function applyRule(string $field, $value, string $rule): void
    {
        $params = [];
        
        // Parse rule parameters
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }
        
        $method = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $method)) {
            $result = $this->$method($value, $params);
            if ($result !== true) {
                $this->addError($field, $rule, $result, $params);
            }
        }
    }
    
    /**
     * Add validation error
     */
    private function addError(string $field, string $rule, string $message, array $params = []): void
    {
        $customMessage = $this->messages["{$field}.{$rule}"] ?? $this->messages[$rule] ?? $message;
        
        // Replace placeholders
        $customMessage = str_replace(':field', $field, $customMessage);
        $customMessage = str_replace(':value', (string)($this->data[$field] ?? ''), $customMessage);
        
        foreach ($params as $i => $param) {
            $customMessage = str_replace(":{$i}", $param, $customMessage);
        }
        
        $this->errors[$field][] = $customMessage;
    }
    
    // Validation Rules
    
    public function validateRequired($value): bool|string
    {
        if (is_null($value) || (is_string($value) && trim($value) === '')) {
            return 'This field is required.';
        }
        return true;
    }
    
    public function validateEmail($value): bool|string
    {
        if (!$value) return true; // Skip if empty (use required rule)
        
        $validation = validate_email((string)$value);
        if (!$validation['valid']) {
            return $validation['error'];
        }
        
        // Update data with sanitized value
        return true;
    }
    
    public function validateMin($value, array $params): bool|string
    {
        if (!$value) return true;
        
        $min = (int)($params[0] ?? 0);
        if (is_string($value) && strlen($value) < $min) {
            return "Must be at least {$min} characters long.";
        }
        if (is_numeric($value) && $value < $min) {
            return "Must be at least {$min}.";
        }
        return true;
    }
    
    public function validateMax($value, array $params): bool|string
    {
        if (!$value) return true;
        
        $max = (int)($params[0] ?? 0);
        if (is_string($value) && strlen($value) > $max) {
            return "Cannot be longer than {$max} characters.";
        }
        if (is_numeric($value) && $value > $max) {
            return "Cannot be greater than {$max}.";
        }
        return true;
    }
    
    public function validateBetween($value, array $params): bool|string
    {
        if (!$value) return true;
        
        $min = (int)($params[0] ?? 0);
        $max = (int)($params[1] ?? 0);
        $length = is_string($value) ? strlen($value) : (float)$value;
        
        if ($length < $min || $length > $max) {
            return "Must be between {$min} and {$max}" . (is_string($value) ? ' characters' : '') . '.';
        }
        return true;
    }
    
    public function validateNumeric($value): bool|string
    {
        if (!$value) return true;
        
        if (!is_numeric($value)) {
            return 'Must be a number.';
        }
        return true;
    }
    
    public function validateInteger($value): bool|string
    {
        if (!$value) return true;
        
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return 'Must be a whole number.';
        }
        return true;
    }
    
    public function validateUrl($value): bool|string
    {
        if (!$value) return true;
        
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return 'Must be a valid URL.';
        }
        return true;
    }
    
    public function validateIn($value, array $params): bool|string
    {
        if (!$value) return true;
        
        if (!in_array($value, $params)) {
            $options = implode(', ', $params);
            return "Must be one of: {$options}.";
        }
        return true;
    }
    
    public function validateConfirmed($value, array $params = []): bool|string
    {
        $confirmField = $params[0] ?? str_replace('_confirmation', '', array_search($value, $this->data));
        $confirmValue = $this->data[$confirmField] ?? null;
        
        if ($value !== $confirmValue) {
            return 'The confirmation does not match.';
        }
        return true;
    }
    
    public function validateFile($value): bool|string
    {
        if (!$value || !is_array($value)) return true;
        
        if ($value['error'] !== UPLOAD_ERR_OK) {
            return 'File upload failed.';
        }
        return true;
    }
    
    public function validateMimes($value, array $params): bool|string
    {
        if (!$value || !is_array($value)) return true;
        
        $allowedMimes = $params;
        $fileMime = mime_content_type($value['tmp_name']);
        
        if (!in_array($fileMime, $allowedMimes)) {
            $allowed = implode(', ', $allowedMimes);
            return "File must be one of: {$allowed}.";
        }
        return true;
    }
    
    public function validateSize($value, array $params): bool|string
    {
        if (!$value || !is_array($value)) return true;
        
        $maxSize = (int)($params[0] ?? 0) * 1024; // Convert KB to bytes
        
        if ($value['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            return "File size cannot exceed {$maxSizeMB}MB.";
        }
        return true;
    }
    
    public function validatePassword($value): bool|string
    {
        if (!$value) return true;
        
        $validation = validate_password((string)$value, true);
        if (!$validation['valid']) {
            return $validation['error'];
        }
        return true;
    }
    
    public function validateCaseName($value): bool|string
    {
        if (!$value) return true;
        
        $validation = validate_case_name((string)$value);
        if (!$validation['valid']) {
            return $validation['error'];
        }
        return true;
    }
    
    public function validateName($value): bool|string
    {
        if (!$value) return true;
        
        $validation = validate_name((string)$value);
        if (!$validation['valid']) {
            return $validation['error'];
        }
        return true;
    }
}

/**
 * Helper function to create validator
 */
function validator(array $data, array $rules, array $messages = []): FormValidator
{
    return new FormValidator($data, $rules, $messages);
}

/**
 * Generate client-side validation attributes
 */
function validation_attributes(array $rules): string
{
    $attributes = [];
    
    foreach ($rules as $rule) {
        if (str_contains($rule, ':')) {
            [$ruleName, $params] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $params = '';
        }
        
        switch ($ruleName) {
            case 'required':
                $attributes[] = 'required';
                break;
            case 'email':
                $attributes[] = 'type="email"';
                break;
            case 'numeric':
            case 'integer':
                $attributes[] = 'type="number"';
                break;
            case 'url':
                $attributes[] = 'type="url"';
                break;
            case 'min':
                if ($params) {
                    $attributes[] = "minlength=\"{$params}\"";
                }
                break;
            case 'max':
                if ($params) {
                    $attributes[] = "maxlength=\"{$params}\"";
                }
                break;
            case 'between':
                $paramArray = explode(',', $params);
                if (count($paramArray) >= 2) {
                    $attributes[] = "minlength=\"{$paramArray[0]}\"";
                    $attributes[] = "maxlength=\"{$paramArray[1]}\"";
                }
                break;
        }
    }
    
    return implode(' ', $attributes);
}

/**
 * Generate validation error JSON for client-side
 */
function validation_errors_json(array $errors): string
{
    return json_encode($errors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
}

/**
 * Check if field has error
 */
function has_error(array $errors, string $field): bool
{
    return isset($errors[$field]);
}

/**
 * Get first error for field
 */
function get_error(array $errors, string $field): string
{
    return $errors[$field][0] ?? '';
}

/**
 * Get error CSS class
 */
function error_class(array $errors, string $field, string $errorClass = 'is-invalid', string $successClass = ''): string
{
    if (has_error($errors, $field)) {
        return $errorClass;
    }
    
    // Show success class if form was submitted and field has no errors
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $successClass) {
        return $successClass;
    }
    
    return '';
}