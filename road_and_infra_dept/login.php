<?php
// Redirect to the unified login page in the module folder
session_start();

$basePath = '';
$loginUrl = 'login.php';

if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
    $basePath = 'lgu-portal/public/';
    $loginUrl = 'index.php';
    $employeeUrl = 'lgu-portal/public/employee.php';
}

header('Location: ' . (basename($loginUrl) === 'index.php' ? '../index.php' : 'user_and_access_management_module/login.php'));
exit;
?>
