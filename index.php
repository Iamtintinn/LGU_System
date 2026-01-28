<?php
// Main Router and Entry Point for Road and Infrastructure Department
session_start();

$basePath = '';
$loginUrl = 'road_and_infra_dept/user_and_access_management_module/login.php';

if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
    $basePath = 'lg-road-monitoring/';
    $loginUrl = 'index.php';
    $employeeUrl = 'lg-road-monitoring/index.php';
}

// Correct path to auth.php relative to root index.php
require_once 'road_and_infra_dept/config/auth.php';

// Redirect to dashboard if logged in, otherwise to the unified login page
if ($auth->isLoggedIn()) {
    $auth->redirectToDashboard();
} else {
    // If loginUrl is 'index.php', we need to be careful not to loop
    // But since this is the root index.php, we should probably redirect to the module login
    header('Location: ' . $loginUrl);
}
exit;
?>
