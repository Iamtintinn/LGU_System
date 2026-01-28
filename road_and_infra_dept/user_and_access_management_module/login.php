<?php
// Start session
session_start();

// Include authentication and database
require_once '../config/database.php';
require_once '../config/auth.php';

// Redirect if already logged in (unless bypass parameter is set)
if ($auth->isLoggedIn() && !isset($_GET['bypass'])) {
    $auth->redirectToDashboard();
    exit;
}

// Handle registration form submission
$registerMessage = '';
$registerMessageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_register'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $registerMessage = 'Please fill in all fields';
        $registerMessageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registerMessage = 'Invalid email format';
        $registerMessageType = 'error';
    } elseif (strlen($password) < 6) {
        $registerMessage = 'Password must be at least 6 characters';
        $registerMessageType = 'error';
    } else {
        try {
            // Create database connection
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $existingUser = $checkStmt->get_result()->num_rows > 0;
            $checkStmt->close();
            
            if ($existingUser) {
                $registerMessage = 'Email address already registered';
                $registerMessageType = 'error';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user with default 'pending' status
                $stmt = $conn->prepare("
                    INSERT INTO users (email, password, role, status, email_verified, created_at) 
                    VALUES (?, ?, 'citizen', 'pending', 0, CURRENT_TIMESTAMP)
                ");
                
                if ($stmt) {
                    $stmt->bind_param("ss", $email, $hashedPassword);
                    
                    if ($stmt->execute()) {
                        $registerMessage = 'Account created successfully! Please proceed to provide additional information.';
                        $registerMessageType = 'success';
                        
                        // Store registration data in session for additional info step
                        $_SESSION['registration_email'] = $email;
                        
                        // Auto-switch to additional info panel after successful registration
                        echo '<script>setTimeout(() => showPanel("additional"), 1000);</script>';
                    } else {
                        $registerMessage = 'Failed to create account';
                        $registerMessageType = 'error';
                    }
                    $stmt->close();
                } else {
                    $registerMessage = 'Database error occurred';
                    $registerMessageType = 'error';
                }
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $registerMessage = 'An error occurred during registration';
            $registerMessageType = 'error';
        }
    }
}

// Handle Additional Information form submission
$additionalMessage = '';
$additionalMessageType = '';
$submittedData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_additional'])) {
    // Collect form data
    $submittedData = [
        'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
        'middle_name' => filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_STRING),
        'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
        'birthday' => filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_STRING),
        'address' => filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING),
        'civil_status' => filter_input(INPUT_POST, 'civil_status', FILTER_SANITIZE_STRING),
        'role' => filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING)
    ];
    
    try {
        // Create database connection
        $database = new Database();
        $conn = $database->getConnection();
        
        // Update user information in database
        $stmt = $conn->prepare("
            UPDATE users SET 
                first_name = ?, 
                middle_name = ?, 
                last_name = ?, 
                birthday = ?, 
                address = ?, 
                civil_status = ?, 
                role = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE email = ?
        ");
        
        $email = $_SESSION['registration_email'] ?? '';
        $stmt->bind_param("ssssssss", 
            $submittedData['first_name'],
            $submittedData['middle_name'],
            $submittedData['last_name'],
            $submittedData['birthday'],
            $submittedData['address'],
            $submittedData['civil_status'],
            $submittedData['role'],
            $email
        );
        
        if ($stmt->execute()) {
            // Handle file upload
            if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['valid_id'];
                $submittedData['valid_id'] = $uploadedFile['name'];
                $additionalMessage = "Additional information submitted successfully! File uploaded: " . $uploadedFile['name'];
                $additionalMessageType = "success";
            } else {
                $additionalMessage = "Additional information submitted successfully!";
                $additionalMessageType = "success";
            }
        } else {
            $additionalMessage = "Failed to update additional information";
            $additionalMessageType = "error";
        }
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Additional info update error: " . $e->getMessage());
        $additionalMessage = "An error occurred while updating your information";
        $additionalMessageType = "error";
    }
}

// Handle login form submission
$loginMessage = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['submit_additional'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $loginMessage = 'Please fill in all fields';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loginMessage = 'Invalid email format';
        $messageType = 'error';
    } else {
        try {
            // Create database connection
            $database = new Database();
            $conn = $database->getConnection();
            
            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("
                SELECT id, email, password, first_name, last_name, role, status, email_verified 
                FROM users 
                WHERE email = ?
            ");
            
            if (!$stmt) {
                throw new Exception("Database preparation failed");
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    
                    // Check if user is active
                    if ($user['status'] !== 'active') {
                        $loginMessage = 'Account is not active. Please contact administrator.';
                        $messageType = 'error';
                    }
                    // Check if email is verified
                    elseif (!$user['email_verified']) {
                        $loginMessage = 'Please verify your email address before logging in.';
                        $messageType = 'error';
                    }
                    else {
                        // Login successful - set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        
                        // Log successful login attempt
                        logLoginAttempt($conn, $email, true, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
                        
                        // Update last login timestamp
                        updateLastLogin($conn, $user['id']);
                        
                        // Create user session
                        createUserSession($conn, $user['id']);
                        
                        // Determine redirect based on user role
                        // Both Admin and LGU Officer can login from this unified page
                        switch ($user['role']) {
                            case 'admin':
                                // Redirect to Admin UI
                                $redirectUrl = '../admin_ui/index.php';
                                break;
                            case 'lgu_officer':
                                // Redirect to LGU Officer Dashboard
                                $redirectUrl = '../lgu_officer_module/index.php';
                                break;
                            case 'engineer':
                                $redirectUrl = '../engineer_module/index.php';
                                break;
                            case 'citizen':
                                $redirectUrl = '../citizen_module/index.php';
                                break;
                            default:
                                $redirectUrl = '../dashboard.php';
                        }
                        
                        // Redirect to appropriate dashboard
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                } else {
                    // Invalid password
                    logLoginAttempt($conn, $email, false, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
                    $loginMessage = 'Invalid email or password';
                    $messageType = 'error';
                }
            } else {
                // User not found
                logLoginAttempt($conn, $email, false, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '');
                $loginMessage = 'Invalid email or password';
                $messageType = 'error';
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $loginMessage = 'An error occurred. Please try again later.';
            $messageType = 'error';
        }
    }
}

// Helper functions
function logLoginAttempt($conn, $email, $success, $ip_address, $user_agent) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO login_attempts (email, ip_address, success, user_agent) 
            VALUES (?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("ssis", $email, $ip_address, $success, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

function updateLastLogin($conn, $user_id) {
    try {
        $stmt = $conn->prepare("
            UPDATE users SET last_login = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

function createUserSession($conn, $user_id) {
    try {
        $session_id = session_id();
        $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $conn->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $session_id, $ip_address, $user_agent, $expires_at);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Failed to create user session: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LGU | Login</title>
    <link rel="stylesheet" href="styles/style.css" />
    <style>
      body {
        height: 100vh;
        display: flex;
        flex-direction: column;

        /* NEW — background image + blur */
        background: url("assets/img/cityhall.jpeg") center/cover no-repeat fixed;
        position: relative;
        overflow: hidden;
      }

      /* NEW — Blur overlay */
      body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;

        backdrop-filter: blur(6px); /* actual blur */
        background: rgba(0, 0, 0, 0.35); /* dark overlay */
        z-index: 0; /* keeps blur behind content */
      }

      /* Make content appear ABOVE blur */
      .nav,
      .wrapper {
        position: relative;
        z-index: 1;
      }

      /* Make content appear ABOVE blur */
      .footer,
      .wrapper {
        position: relative;
        z-index: 1;
      }

      .message {
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
      }

      .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }

      .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }
    </style>
  </head>

  <body>
    <header class="nav">
      <div class="nav-logo">🏛️ Local Government Unit Portal</div>
      <div class="nav-links">
        <a href="">Home</a>
      </div>
    </header>
    <div class="wrapper">
      <div class="slider" id="slider">
        <!-- LOGIN -->
        <div class="panel login">
          <div class="card">
            <img src="assets/img/logocityhall.png" class="icon-top" />
            <h2 class="title">LGU Login</h2>
            <p class="subtitle">
              Secure access to community maintenance services.
            </p>

            <form id="loginForm" method="POST" action="">
              <div class="input-box">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="name@lgu.gov.ph" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                <span class="icon">📧</span>
              </div>

              <div class="input-box">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required />
                <span class="icon">🔒</span>
              </div>

              <button type="submit" class="btn-primary">Sign In</button>

              <?php if (!empty($loginMessage)): ?>
                <div class="message <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars($loginMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
              <?php endif; ?>

              <p class="small-text">
                Don't have an account?
                <a href="#" class="link" onclick="showPanel('register')"
                  >Create one</a
                >
              </p>
            </form>
          </div>
        </div>

        <!-- REGISTER -->
        <div class="panel register">
          <div class="card">
            <h2 class="title">Create Account</h2>
            <p class="subtitle">Register for LGU services.</p>
            
            <?php if ($registerMessage): ?>
              <div class="message <?php echo $registerMessageType; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px;">
                <?php echo htmlspecialchars($registerMessage); ?>
              </div>
            <?php endif; ?>

            <form method="POST" action="">
              <div class="input-box">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Password</label>
                <input type="password" name="password" />
              </div>

              <button
                class="btn-primary"
                type="submit"
                name="submit_register"
              >
                Next
              </button>

              <p class="small-text">
                Already have an account?
                <a href="#" class="link" onclick="showPanel('login')"
                  >Back to Login</a
                >
              </p>
            </form>
          </div>
        </div>

        <!-- ADDITIONAL INFO PANEL -->
        <div class="panel additional">
          <div class="card wide">
            <h2 class="title">Additional Information</h2>
            
            <?php if ($additionalMessage): ?>
              <div class="message <?php echo $additionalMessageType; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 8px;">
                <?php echo htmlspecialchars($additionalMessage); ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($submittedData)): ?>
              <div class="submitted-data" style="margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h4 style="margin-bottom: 10px; color: #1e293b;">Submitted Information:</h4>
                <p><strong>First Name:</strong> <?php echo htmlspecialchars($submittedData['first_name'] ?? ''); ?></p>
                <p><strong>Middle Name:</strong> <?php echo htmlspecialchars($submittedData['middle_name'] ?? ''); ?></p>
                <p><strong>Last Name:</strong> <?php echo htmlspecialchars($submittedData['last_name'] ?? ''); ?></p>
                <p><strong>Birthday:</strong> <?php echo htmlspecialchars($submittedData['birthday'] ?? ''); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($submittedData['address'] ?? ''); ?></p>
                <p><strong>Civil Status:</strong> <?php echo htmlspecialchars($submittedData['civil_status'] ?? ''); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($submittedData['role'] ?? ''); ?></p>
                <?php if (isset($submittedData['valid_id'])): ?>
                  <p><strong>Valid ID:</strong> <?php echo htmlspecialchars($submittedData['valid_id']); ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <form class="two-column-form" method="POST" action="">
              <div class="input-box">
                <label>First Name</label>
                <input type="text" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Last Name</label>
                <input type="text" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Birthday</label>
                <input type="date" name="birthday" value="<?php echo isset($_POST['birthday']) ? htmlspecialchars($_POST['birthday']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Address</label>
                <input type="text" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Civil Status</label>
                <input type="text" name="civil_status" value="<?php echo isset($_POST['civil_status']) ? htmlspecialchars($_POST['civil_status']) : ''; ?>" />
              </div>

              <div class="input-box">
                <label>Role</label>
                <select name="role">
                  <option value="">Select role</option>
                  <option value="engineer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'engineer') ? 'selected' : ''; ?>>Engineer</option>
                  <option value="lgu_officer" <?php echo (isset($_POST['role']) && $_POST['role'] === 'lgu_officer') ? 'selected' : ''; ?>>LGU Officer</option>
                  <option value="citizen" <?php echo (isset($_POST['role']) && $_POST['role'] === 'citizen') ? 'selected' : ''; ?>>Citizen</option>
                </select>
              </div>

              <!-- UPLOAD ID -->
              <div class="input-box">
                <label>Upload Valid ID</label>
                <input type="file" name="valid_id" accept="image/*" />
              </div>

              <!-- FULL WIDTH BUTTON -->
              <div class="form-actions">
                <button class="btn-primary" type="submit" name="submit_additional">Submit</button>
                <p class="small-text">
                  <a href="#" class="link" onclick="showPanel('register')"
                    >Back</a
                  >
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <footer class="footer">
      <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">About</a>
        <a href="#">Help</a>
      </div>

      <div class="footer-logo">
        © 2025 LGU Citizen Portal · All Rights Reserved
      </div>
    </footer>
    <script>
      function showPanel(panel) {
        const wrapper = document.querySelector(".wrapper");

        wrapper.classList.remove("show-register", "show-additional");

        if (panel === "register") wrapper.classList.add("show-register");
        if (panel === "additional") wrapper.classList.add("show-additional");
      }
    </script>
  </body>
</html>
