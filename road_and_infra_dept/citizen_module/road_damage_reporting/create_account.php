<?php
require_once '../config/database.php';

$error = '';
$success = '';

// Password generator function
function generateTempPassword($length = 10) {
    // Mix of upper, lower, digit, and special
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $charsLength = strlen($chars);
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, $charsLength - 1)];
    }
    return $pass;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_account'])) {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Always generate a random temp password
    $tempPassword = generateTempPassword();

    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already exists in the system.';
            } else {
                // Hash the password
                $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
                
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, status, email_verified) VALUES (?, ?, ?, ?, ?, 'pending', 0)");
                $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $role);
                
                if ($stmt->execute()) {
                    $success = 'Account created successfully!<br>Temporary password: <strong>' . htmlspecialchars($tempPassword) . '</strong><br><br><small>You will be redirected to the permission management page in 3 seconds...</small>';
                    // Clear form data
                    $firstName = $lastName = $email = $role = '';
                    
                    // Redirect to permission management page after 3 seconds
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "../user_and_access_management_module/permission.php";
                        }, 3000);
                    </script>';
                } else {
                    $error = 'Error creating account: ' . $conn->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account | Road Damage Reporting</title>
<style>
body {
    background: url("assets/img/cityhall.jpeg") center/cover no-repeat fixed;
    height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

body::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    backdrop-filter: blur(6px);
    background: rgba(0, 0, 0, 0.35);
    z-index: 0;
}

.nav {
    position: relative;
    z-index: 1;
    height: 80px;
    display: flex;
    align-items: center;
    background: transparent;
}

.wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    box-sizing: border-box;
    min-height: 0;
    padding: 30px 20px 20px 20px;
    position: relative;
    z-index: 1;
    margin-top: 0;
    min-height: calc(100vh - 80px);
}

@media (max-width: 600px) {
    .card {
        padding: 22px 8px;
    }
    .wrapper {
        margin-top: 0;
    }
}

/* Card styling */
.card {
    width: 100%;
    max-width: 500px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(8px);
    border-radius: 20px;
    padding: 30px 25px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    box-sizing: border-box;
    animation: fadeIn 0.5s ease-in-out;
    margin-top: 5px;
}

/* To allow main section to scroll, but keep header visible */
html, body {
    height: 100%;
}

body {
    min-height: 100vh;
}

.wrapper {
    overflow-y: auto;
    max-height: calc(100vh - 80px);
}

/* Custom scrollbar for card */
.card::-webkit-scrollbar {
    width: 8px;
}

.card::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}

.card::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.card::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

/* LOGO AREA */
.site-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    font-weight: 600;
    font-size: 18px;
}

/* LOGO IMAGE */
.site-logo img {
    width: 40px;
    height: auto;
    border-radius: 8px;
}

/* NAV LINKS */
.nav-links {
    display: flex;
    align-items: center;
}

.nav-links a {
    margin-left: 25px;
    text-decoration: none;
    color: #fff;
    opacity: 0.85;
    font-weight: 500;
    padding: 8px 14px;
    border-radius: 10px;
    transition: 0.25s ease;
}

.nav-links a:hover,
.nav-links a.active {
    opacity: 1;
    font-weight: 600;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    word-break: break-word;
    display: block;
}

.alert-error {
    background-color: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background-color: #efe;
    color: #3c3;
    border: 1px solid #cfc;
}

/* Role Select Styling */
.role-select {
    width: 100%;
    padding: 10px 38px 10px 12px;
    border-radius: 10px;
    border: none;
    background: rgba(255,255,255,0.7);
    outline: none;
    font-size: 14px;
    appearance: none;
    background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path fill="%23333" d="M6 9L1 4h10z"/></svg>');
    background-repeat: no-repeat;
    background-position: right 12px center;
    cursor: pointer;
    font-family: 'Poppins', sans-serif;
}

/* Name fields side by side */
.name-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 14px;
}

.name-row .input-box {
    margin-bottom: 0;
}

/* Basic input styling */
.input-box {
    margin-bottom: 20px;
    position: relative;
}

.input-box label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.input-box input,
.input-box select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: rgba(255,255,255,0.8);
}

.input-box input:focus,
.input-box select:focus {
    outline: none;
    border-color: #2563eb;
    background: white;
}

.input-box .icon {
    position: absolute;
    right: 16px;
    top: 42px;
    font-size: 16px;
    color: #666;
}

.btn-primary {
    width: 100%;
    padding: 14px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.link {
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
}

.link:hover {
    text-decoration: underline;
}

.small-text {
    font-size: 12px;
    color: #666;
    margin-top: 8px;
    text-align: center;
}

.icon-top {
    width: 60px;
    height: 60px;
    margin: 0 auto 20px auto;
    display: block;
    border-radius: 50%;
}

.title {
    text-align: center;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #1e293b;
}

.subtitle {
    text-align: center;
    font-size: 14px;
    color: #64748b;
    margin-bottom: 30px;
}
</style>
</head>

<body>

<header class="nav">
    <div class="site-logo">
        <img src="assets/img/logocityhall.png" alt="LGU Logo">
        <span>Road Damage Reporting System</span>
    </div>

    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
    </div>
</header>

<div class="wrapper">
    <div class="card">

        <img src="assets/img/logocityhall.png" class="icon-top">

        <h2 class="title">Create User Account</h2>
        <p class="subtitle">Register a new user for the road damage reporting system.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="name-row">
                <div class="input-box">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="Juan" value="<?= htmlspecialchars($firstName ?? '') ?>" required>
                    <span class="icon">👤</span>
                </div>

                <div class="input-box">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Dela Cruz" value="<?= htmlspecialchars($lastName ?? '') ?>" required>
                    <span class="icon">👤</span>
                </div>
            </div>

            <div class="input-box">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="name@lgu.gov.ph" value="<?= htmlspecialchars($email ?? '') ?>" required>
                <span class="icon">📧</span>
            </div>

            <div class="input-box">
                <label>Role</label>
                <select name="role" required class="role-select">
                    <option value="">Select Role</option>
                    <option value="citizen" <?= (isset($role) && $role === 'citizen') ? 'selected' : '' ?>>Citizen</option>
                    <option value="engineer" <?= (isset($role) && $role === 'engineer') ? 'selected' : '' ?>>Engineer</option>
                    <option value="lgu_officer" <?= (isset($role) && $role === 'lgu_officer') ? 'selected' : '' ?>>LGU Officer</option>
                    <option value="admin" <?= (isset($role) && $role === 'admin') ? 'selected' : '' ?>>Administrator</option>
                </select>
                <span class="icon">👔</span>
            </div>

            <div class="input-box">
                <label>Temporary Password</label>
                <input type="text" name="temp_password" placeholder="Will be generated automatically" value="<?= isset($tempPassword) ? htmlspecialchars($tempPassword) : '' ?>" readonly style="background-color:#f2f2f2; color:#666;">
                <span class="icon">🔒</span>
                <span class="small-text">Temporary password will be generated and shown after creation.</span>
            </div>

            <button type="submit" name="create_account" class="btn-primary">Create Account</button>

            <p class="small-text">
                Already have an account?
                <a href="../user_and_access_management_module/login.php" class="link">Sign In</a>
            </p>

        </form>
    </div>
</div>

</body>
</html>
