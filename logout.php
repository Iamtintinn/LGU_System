<?php
// Logout script for Road and Infrastructure Department
session_start();

$basePath = '';
$loginUrl = 'login.php';

$isRoot = (strpos($_SERVER['PHP_SELF'], 'road_and_infra_dept') === false);
if ($isRoot) {
    $loginUrl = 'index.php';
}

// Destroy all session data
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login.php
header('Location: ' . $loginUrl);
exit;
?>
