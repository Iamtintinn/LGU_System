<?php
// Security functions and configurations for Road and Infrastructure Department

class Security {
    
    // Rate limiting for login attempts
    private static $maxLoginAttempts = 5;
    private static $loginLockoutTime = 900; // 15 minutes in seconds
    
    // CSRF token functions
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        // Check if token is still valid (1 hour expiry)
        $tokenAge = time() - $_SESSION['csrf_token_time'];
        if ($tokenAge > 3600) {
            self::regenerateCSRFToken();
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Input sanitization
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Email validation
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && 
               strlen($email) <= 255 &&
               preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
    }
    
    // Password strength validation
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    // Rate limiting for login attempts
    public static function isLoginLocked($email) {
        require_once 'database.php';
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM login_attempts 
                WHERE email = ? AND success = FALSE 
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            
            if ($stmt) {
                $stmt->bind_param("si", $email, self::$loginLockoutTime);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                return $row['attempt_count'] >= self::$maxLoginAttempts;
            }
        } catch (Exception $e) {
            error_log("Error checking login lock: " . $e->getMessage());
        }
        
        return false;
    }
    
    public static function getRemainingLockoutTime($email) {
        require_once 'database.php';
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            $stmt = $conn->prepare("
                SELECT attempted_at 
                FROM login_attempts 
                WHERE email = ? AND success = FALSE 
                ORDER BY attempted_at DESC 
                LIMIT 1
            ");
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $lastAttempt = strtotime($row['attempted_at']);
                    $unlockTime = $lastAttempt + self::$loginLockoutTime;
                    $remainingTime = $unlockTime - time();
                    
                    $stmt->close();
                    return max(0, $remainingTime);
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error getting lockout time: " . $e->getMessage());
        }
        
        return 0;
    }
    
    // XSS protection
    public static function escapeOutput($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    // SQL injection protection (using prepared statements is preferred)
    public static function escapeSql($conn, $string) {
        return $conn->real_escape_string($string);
    }
    
    // File upload security
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error occurred";
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = "File size exceeds maximum allowed size";
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = "File type not allowed";
            }
        }
        
        // Check for malicious file extensions
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'exe', 'bat', 'cmd', 'sh'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($extension, $dangerousExtensions)) {
            $errors[] = "Dangerous file extension detected";
        }
        
        return $errors;
    }
    
    // Generate secure random string
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    // Hash password securely
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Check if password needs rehashing
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    // Set secure session parameters
    public static function setSecureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }
    
    // Validate IP address
    public static function validateIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }
    
    // Check for suspicious activity
    public static function isSuspiciousActivity($email, $ip) {
        require_once 'database.php';
        
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check for multiple IPs from same email in short time
            $stmt = $conn->prepare("
                SELECT COUNT(DISTINCT ip_address) as ip_count 
                FROM login_attempts 
                WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                // If more than 3 different IPs in 1 hour, flag as suspicious
                return $row['ip_count'] > 3;
            }
        } catch (Exception $e) {
            error_log("Error checking suspicious activity: " . $e->getMessage());
        }
        
        return false;
    }
}

// Set secure session parameters
Security::setSecureSession();
?>
