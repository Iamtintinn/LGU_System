<?php
// Redirect to the unified login page in the module folder
session_start();

$basePath = '';
$loginUrl = 'login.php';

$isRoot = (strpos($_SERVER['PHP_SELF'], 'road_and_infra_dept') === false);

if ($isRoot) {
    $loginUrl = 'index.php';
}

header('Location: ' . (basename($loginUrl) === 'index.php' ? '../index.php' : 'user_and_access_management_module/login.php'));
exit;
?>
