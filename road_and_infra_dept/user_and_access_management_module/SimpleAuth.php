<?php
// Simple Auth class for main dashboard
class SimpleAuth {
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Require login to access this page
    public function requireLogin($redirectUrl = 'login.php') {
        if (!$this->isLoggedIn()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    // Log user activity
    public function logActivity($activityType, $description = '') {
        // Simple logging - can be enhanced later
        error_log("Activity: $activityType - $description");
    }
}
?>
