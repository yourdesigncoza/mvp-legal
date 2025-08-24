<?php
/**
 * Phoenix UI Helper Functions
 * Appeal Prospect MVP - Template Helpers
 */

declare(strict_types=1);

/**
 * Render a template with variables
 */
function render_template(string $template, array $variables = []): void {
    // Extract variables to current scope
    extract($variables);
    
    // Include the template
    $template_path = __DIR__ . '/templates/' . $template . '.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        throw new RuntimeException("Template not found: {$template}");
    }
}

/**
 * Render page with layout
 */
function render_page(string $content, array $options = []): void {
    $variables = array_merge([
        'content' => $content,
        'page_title' => $options['title'] ?? 'Appeal Prospect MVP',
        'page_header' => $options['header'] ?? null,
        'main_content' => $options['main_content'] ?? null,
        'additional_scripts' => $options['scripts'] ?? ''
    ], $options);
    
    render_template('layout', $variables);
}

/**
 * Set flash message
 */
function set_flash_message(string $message, string $type = 'info'): void {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Create Phoenix alert HTML
 */
function phoenix_alert(string $message, string $type = 'info', bool $dismissible = true): string {
    $alert_classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
        'primary' => 'alert-primary'
    ];
    
    $class = $alert_classes[$type] ?? 'alert-info';
    $dismissible_class = $dismissible ? ' alert-dismissible fade show' : '';
    $dismiss_button = $dismissible ? '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' : '';
    
    $icons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'danger' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle',
        'primary' => 'fas fa-info-circle'
    ];
    
    $icon = $icons[$type] ?? 'fas fa-info-circle';
    
    return "
    <div class=\"alert {$class}{$dismissible_class}\" role=\"alert\">
        <i class=\"{$icon} me-2\"></i>
        {$message}
        {$dismiss_button}
    </div>";
}

/**
 * Create Phoenix badge
 */
function phoenix_badge(string $text, string $type = 'primary', bool $with_icon = false): string {
    $icons = [
        'success' => 'fas fa-check',
        'danger' => 'fas fa-times',
        'warning' => 'fas fa-exclamation',
        'info' => 'fas fa-info',
        'primary' => 'fas fa-star',
        'secondary' => 'fas fa-minus'
    ];
    
    $icon_html = $with_icon && isset($icons[$type]) ? "<i class=\"{$icons[$type]} me-1\"></i>" : '';
    
    return "<span class=\"badge badge-phoenix badge-phoenix-{$type}\">{$icon_html}{$text}</span>";
}

/**
 * Create Phoenix card
 */
function phoenix_card(string $content, array $options = []): string {
    $header = $options['header'] ?? null;
    $footer = $options['footer'] ?? null;
    $class = $options['class'] ?? '';
    
    $header_html = $header ? "<div class=\"card-header\">{$header}</div>" : '';
    $footer_html = $footer ? "<div class=\"card-footer\">{$footer}</div>" : '';
    
    return "
    <div class=\"card {$class}\">
        {$header_html}
        <div class=\"card-body\">
            {$content}
        </div>
        {$footer_html}
    </div>";
}

/**
 * Create Phoenix button
 */
function phoenix_button(string $text, array $options = []): string {
    $type = $options['type'] ?? 'primary';
    $size = $options['size'] ?? '';
    $icon = $options['icon'] ?? null;
    $href = $options['href'] ?? null;
    $onclick = $options['onclick'] ?? null;
    $class = $options['class'] ?? '';
    $id = $options['id'] ?? '';
    
    $size_class = $size ? " btn-{$size}" : '';
    $icon_html = $icon ? "<i class=\"{$icon} me-2\"></i>" : '';
    $onclick_attr = $onclick ? " onclick=\"{$onclick}\"" : '';
    $id_attr = $id ? " id=\"{$id}\"" : '';
    
    if ($href) {
        return "<a href=\"{$href}\" class=\"btn btn-phoenix-{$type}{$size_class} {$class}\"{$id_attr}{$onclick_attr}>{$icon_html}{$text}</a>";
    } else {
        return "<button type=\"button\" class=\"btn btn-phoenix-{$type}{$size_class} {$class}\"{$id_attr}{$onclick_attr}>{$icon_html}{$text}</button>";
    }
}

/**
 * Create Phoenix form input
 */
function phoenix_input(string $name, array $options = []): string {
    $type = $options['type'] ?? 'text';
    $label = $options['label'] ?? null;
    $placeholder = $options['placeholder'] ?? '';
    $value = $options['value'] ?? '';
    $required = $options['required'] ?? false;
    $icon = $options['icon'] ?? null;
    $help = $options['help'] ?? null;
    $class = $options['class'] ?? '';
    
    $required_attr = $required ? ' required' : '';
    $label_html = $label ? "<label class=\"form-label\" for=\"{$name}\">{$label}</label>" : '';
    $help_html = $help ? "<div class=\"form-text\">{$help}</div>" : '';
    
    if ($icon) {
        $input_html = "
        <div class=\"form-icon-container\">
            <input class=\"form-control form-icon-input {$class}\" 
                   id=\"{$name}\" 
                   name=\"{$name}\" 
                   type=\"{$type}\" 
                   placeholder=\"{$placeholder}\" 
                   value=\"{$value}\"{$required_attr}>
            <span class=\"{$icon} text-body fs-9 form-icon\"></span>
        </div>";
    } else {
        $input_html = "
        <input class=\"form-control {$class}\" 
               id=\"{$name}\" 
               name=\"{$name}\" 
               type=\"{$type}\" 
               placeholder=\"{$placeholder}\" 
               value=\"{$value}\"{$required_attr}>";
    }
    
    return "
    <div class=\"mb-3\">
        {$label_html}
        {$input_html}
        {$help_html}
    </div>";
}

/**
 * Create Phoenix textarea
 */
function phoenix_textarea(string $name, array $options = []): string {
    $label = $options['label'] ?? null;
    $placeholder = $options['placeholder'] ?? '';
    $value = $options['value'] ?? '';
    $required = $options['required'] ?? false;
    $rows = $options['rows'] ?? 3;
    $help = $options['help'] ?? null;
    $class = $options['class'] ?? '';
    
    $required_attr = $required ? ' required' : '';
    $label_html = $label ? "<label class=\"form-label\" for=\"{$name}\">{$label}</label>" : '';
    $help_html = $help ? "<div class=\"form-text\">{$help}</div>" : '';
    
    return "
    <div class=\"mb-3\">
        {$label_html}
        <textarea class=\"form-control {$class}\" 
                  id=\"{$name}\" 
                  name=\"{$name}\" 
                  rows=\"{$rows}\" 
                  placeholder=\"{$placeholder}\"{$required_attr}>{$value}</textarea>
        {$help_html}
    </div>";
}

/**
 * Create Phoenix table
 */
function phoenix_table(array $headers, array $rows, array $options = []): string {
    $class = $options['class'] ?? '';
    $responsive = $options['responsive'] ?? true;
    $striped = $options['striped'] ?? false;
    $hover = $options['hover'] ?? false;
    
    $table_classes = ['table', 'table-sm', 'fs-9', 'mb-0'];
    if ($striped) $table_classes[] = 'table-striped';
    if ($hover) $table_classes[] = 'table-hover';
    if ($class) $table_classes[] = $class;
    
    $header_html = '<thead class="text-body"><tr>';
    foreach ($headers as $header) {
        $header_html .= "<th class=\"sort pe-1 align-middle white-space-nowrap\">{$header}</th>";
    }
    $header_html .= '</tr></thead>';
    
    $body_html = '<tbody>';
    foreach ($rows as $row) {
        $body_html .= '<tr class="btn-reveal-trigger">';
        foreach ($row as $cell) {
            $body_html .= "<td class=\"py-2 align-middle\">{$cell}</td>";
        }
        $body_html .= '</tr>';
    }
    $body_html .= '</tbody>';
    
    $table_html = "
    <table class=\"" . implode(' ', $table_classes) . "\">
        {$header_html}
        {$body_html}
    </table>";
    
    if ($responsive) {
        return "<div class=\"table-responsive scrollbar\">{$table_html}</div>";
    }
    
    return $table_html;
}

/**
 * Format file size
 */
function format_file_size(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Sanitize output for HTML
 */
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF field HTML
 */
function csrf_field(): string {
    if (function_exists('csrf_field')) {
        return \csrf_field();
    }
    
    // Fallback if auth functions not loaded yet
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

?>