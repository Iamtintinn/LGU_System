<?php
// Helper Functions for Road and Infrastructure Department

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Check if user is LGU officer
 */
function isLguOfficer() {
    return hasRole('lgu_officer');
}

/**
 * Check if user is engineer
 */
function isEngineer() {
    return hasRole('engineer');
}

/**
 * Check if user is citizen
 */
function isCitizen() {
    return hasRole('citizen');
}

/**
 * Get current user display name
 */
function getUserDisplayName() {
    return trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Format currency for display
 */
function formatCurrency($amount, $currency = '₱') {
    return $currency . number_format($amount, 2);
}

/**
 * Generate safe HTML output
 */
function safeHtml($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage() {
    $message = $_SESSION['flash_message'] ?? '';
    $type = $_SESSION['flash_type'] ?? 'info';
    
    // Clear flash message
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    
    return ['message' => $message, 'type' => $type];
}

/**
 * Generate pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="btn btn-secondary">Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="btn btn-primary">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="btn btn-secondary">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="btn btn-secondary">Next</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Truncate text to specified length
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Generate status badge HTML
 */
function getStatusBadge($status, $type = 'default') {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'completed' => '<span class="badge bg-info">Completed</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'default' => '<span class="badge bg-light">' . ucfirst($status) . '</span>'
    ];
    
    return $badges[$status] ?? $badges['default'];
}

/**
 * Generate role badge HTML
 */
function getRoleBadge($role) {
    $badges = [
        'admin' => '<span class="badge bg-danger">Administrator</span>',
        'lgu_officer' => '<span class="badge bg-warning">LGU Officer</span>',
        'engineer' => '<span class="badge bg-info">Engineer</span>',
        'citizen' => '<span class="badge bg-secondary">Citizen</span>'
    ];
    
    return $badges[$role] ?? '<span class="badge bg-light">' . ucfirst($role) . '</span>';
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Log debug information
 */
function debugLog($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}";
    error_log($logMessage);
    
    // Also log to file if in development
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        file_put_contents(__DIR__ . '/../logs/debug.log', $logMessage . PHP_EOL, FILE_APPEND);
    }
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get client IP address
 */
function getClientIp() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            return $_SERVER[$key];
        }
    }
    
    return '0.0.0.0';
}

/**
 * Clean input data
 */
function cleanInput($data, $type = 'string') {
    switch ($type) {
        case 'string':
            return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
        case 'int':
            return (int)($data ?? 0);
        case 'float':
            return (float)($data ?? 0);
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Create slug from string
 */
function createSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>
