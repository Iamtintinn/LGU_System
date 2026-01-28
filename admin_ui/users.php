<?php
// Start session and include authentication
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require admin role to access this page
$auth->requireRole('admin');

// Log page access
$auth->logActivity('page_access', 'Accessed registered users management');

// Handle view action for modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'view') {
  $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

  if ($userId) {
    try {
      // Create new database instance
      $database = new Database();
      $conn = $database->getConnection();
      
      if (!$conn) {
        throw new Exception("Database connection failed");
      }
      
      // Get user data - select known columns
      $stmt = $conn->prepare("
                SELECT id, first_name, middle_name, last_name, email, role, status, 
                       email_verified, phone, address, created_at, last_login, birthday
                FROM users 
                WHERE id = ?
            ");
      
      if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
      }
      
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();

      if (!$user) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => 'User not found']);
        exit;
      }

      // Clear any previous output to prevent JSON corruption
      ob_clean();

      // Set header to ensure browser expects JSON
      header('Content-Type: application/json');

      $jsonData = json_encode($user, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
      
      if ($jsonData === false) {
        // If json_encode fails, log the error and send an error JSON
        error_log('JSON encoding error: ' . json_last_error_msg());
        echo json_encode(['error' => 'Failed to encode user data: ' . json_last_error_msg()]);
      } else {
        echo $jsonData;
      }
      exit;
    } catch (Exception $e) {
      ob_clean(); // Clear any previous output
      header('Content-Type: application/json');
      error_log("Error fetching user details: " . $e->getMessage());
      echo json_encode(['error' => 'Failed to fetch user data: ' . $e->getMessage()]);
      exit;
    }
  }
}

// Get filter parameters
$filterRole = isset($_GET['role']) ? $_GET['role'] : '';

// Fetch stats for dashboard
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stats = [];
    
    $res = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total'] = $res->fetch_assoc()['total'] ?? 0;
    
    $res = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $stats['active'] = $res->fetch_assoc()['total'] ?? 0;
    
    $res = $conn->query("SELECT COUNT(*) as total FROM users WHERE email_verified = TRUE");
    $stats['verified'] = $res->fetch_assoc()['total'] ?? 0;
    
    $res = $conn->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new'] = $res->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    $stats = ['total' => '!', 'active' => '!', 'verified' => '!', 'new' => '!'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LGU Registered Users | Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

    :root {
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --glass-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(255, 255, 255, 0.4);
      --text-main: #1e293b;
      --text-muted: #64748b;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Inter", sans-serif;
    }

    body {
      height: 100vh;
      background: url('../user_and_access_management_module/assets/img/cityhall.jpeg') center/cover no-repeat fixed;
      position: relative;
      overflow: hidden;
      color: var(--text-main);
    }

    body::before {
      content: "";
      position: absolute;
      inset: 0;
      backdrop-filter: blur(8px);
      background: rgba(15, 23, 42, 0.45);
      z-index: 0;
    }

    .main-content {
      position: relative;
      margin-left: 250px;
      height: 100vh;
      padding: 30px 40px;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      z-index: 1;
    }

    /* Module Header */
    .module-header {
      color: white;
      margin-bottom: 30px;
    }

    .module-header h1 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .module-header p {
      font-size: 1rem;
      opacity: 0.9;
      letter-spacing: 0.5px;
    }

    .header-divider {
      border: none;
      height: 1px;
      background: rgba(255, 255, 255, 0.3);
      margin: 15px 0;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      border: 1px solid var(--glass-border);
      padding: 24px;
      text-align: center;
      display: flex;
      flex-direction: column;
      gap: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-number {
      font-size: 2.4rem;
      font-weight: 700;
      color: var(--primary);
    }

    .stat-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted);
    }

    /* Filters Card */
    .filters-card {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      border: 1px solid var(--glass-border);
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .filters-title {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 25px;
      color: #1e293b;
    }

    .filter-row {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .filter-label {
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-muted);
    }

    .filter-buttons {
      display: flex;
      gap: 10px;
    }

    .btn-filter {
      padding: 10px 24px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
      background: white;
      color: #64748b;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .btn-filter:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    .btn-filter.active {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    /* Registry Card and Table Scroll */
    .registry-card {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      padding: 0;
      margin-bottom: 30px;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      display: flex;
      flex-direction: column;
      max-height: 1000px; /* Increased height to allow more rows to be visible */
      min-height: 500px; /* Ensure a minimum height even with few users */
    }

    .registry-header {
      padding: 25px 30px;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }

    .registry-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #0f172a;
    }

    /* Search Section */
    .search-container {
      position: relative;
      width: 300px;
    }

    .search-input {
      width: 100%;
      padding: 10px 15px 10px 40px;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      font-size: 0.9rem;
      outline: none;
      transition: all 0.2s;
    }

    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
    }

    .table-scroll-container {
      overflow-y: auto;
      flex-grow: 1;
    }

    /* Table Styling */
    .user-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .user-table thead {
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .user-table th {
      text-align: left;
      padding: 16px 25px;
      background: #ffffff !important; /* Force opaque background */
      font-size: 0.875rem;
      font-weight: 600;
      color: #475569;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: top;
      box-shadow: 0 1px 0 rgba(0,0,0,0.05); /* Subtle bottom shadow */
    }

    /* Custom scrollbar for table container */
    .table-scroll-container::-webkit-scrollbar {
      width: 8px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
      background: #f1f5f9;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    .user-table td {
      padding: 18px 25px;
      font-size: 0.9375rem;
      color: #1e293b;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
    }

    .user-table tr:hover {
      background: #fdfdfd;
    }

    /* Role Badges */
    .badge {
      display: inline-block;
      padding: 5px 14px;
      border-radius: 30px;
      font-size: 0.75rem;
      font-weight: 600;
      text-align: center;
      min-width: 85px;
    }

    .badge-lgu_officer { background: #10b981; color: white; }
    .badge-admin { background: #ef4444; color: white; }
    .badge-engineer { background: #3b82f6; color: white; }
    .badge-citizen { background: #64748b; color: white; }

    /* Status & Verified Pills */
    .status-active, .badge-verified {
      background: #dcfce7;
      color: #10b981;
      padding: 4px 12px;
      border-radius: 30px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-block;
      min-width: 75px;
      text-align: center;
    }
    
    .badge-verified {
      color: #166534;
    }

    .status-pending { background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 600; display: inline-block; min-width: 75px; text-align: center; }
    .status-inactive { background: #fee2e2; color: #ef4444; padding: 4px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 600; display: inline-block; min-width: 75px; text-align: center; }

    .badge-not-verified {
      background: #fee2e2;
      color: #ef4444;
      padding: 4px 12px;
      border-radius: 30px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-block;
      min-width: 75px;
      text-align: center;
    }

    /* Action Button */
    .btn-view {
      padding: 7px 18px;
      background: #4f46e5;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-view:hover {
      background: #4338ca;
      box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.4);
    }

    /* Scrollbar Styling */
    .main-content::-webkit-scrollbar { width: 10px; }
    .main-content::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.1); }
    .main-content::-webkit-scrollbar-thumb {
      background: #888;
      border-radius: 10px;
      border: 2px solid transparent;
      background-clip: content-box;
    }
    .main-content::-webkit-scrollbar-thumb:hover { background: #555; background-clip: content-box; }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(5px);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 20px;
      width: 650px;
      max-width: 90%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #f1f5f9;
    }

    .modal-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e293b;
    }

    .close-btn {
      background: none;
      border: none;
      font-size: 1.8rem;
      cursor: pointer;
      color: #94a3b8;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid #f8fafc;
    }

    .detail-row:last-child { border-bottom: none; }

    .detail-label { 
      font-weight: 600; 
      color: #64748b; 
      width: 140px;
      flex-shrink: 0;
    }
    .detail-value { 
      color: #1e293b; 
      flex: 1;
      text-align: right;
    }

    .user-photo-container {
      margin-top: 20px;
      text-align: center;
      padding-top: 20px;
      border-top: 1px solid #f1f5f9;
    }

    .user-photo-container img {
      max-width: 200px;
      max-height: 200px;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .no-photo {
      padding: 40px;
      background: #f8fafc;
      border-radius: 12px;
      border: 1px dashed #cbd5e1;
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .photo-label {
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 10px;
      display: block;
    }
  </style>
</head>

<body>
  <?php include '../sidebar/admin_sidebar.php'; ?>

  <div class="main-content">
    <header class="module-header">
      <h1><i class="fas fa-users-cog"></i> Registered Users</h1>
      <div style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; color: #fff; font-size: 0.8rem; display: inline-block; margin-top: 5px;">
        <i class="fas fa-sync"></i> Real-time Database Sync Active
      </div>
      <p style="margin-top: 10px;">View and manage all registered users in the system</p>
      <hr class="header-divider">
    </header>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['active']; ?></div>
        <div class="stat-label">Active Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['verified']; ?></div>
        <div class="stat-label">Verified Users</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['new']; ?></div>
        <div class="stat-label">New Users (30 days)</div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="filters-card">
      <div class="filter-row" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 20px;">
        <div class="filter-buttons">
          <a href="?role=" class="btn-filter <?php echo $filterRole === '' ? 'active' : ''; ?>">All</a>
          <a href="?role=admin" class="btn-filter <?php echo $filterRole === 'admin' ? 'active' : ''; ?>">Admin</a>
          <a href="?role=engineer" class="btn-filter <?php echo $filterRole === 'engineer' ? 'active' : ''; ?>">Engineer</a>
          <a href="?role=lgu_officer" class="btn-filter <?php echo $filterRole === 'lgu_officer' ? 'active' : ''; ?>">LGU Officer</a>
          <a href="?role=citizen" class="btn-filter <?php echo $filterRole === 'citizen' ? 'active' : ''; ?>">Citizen</a>
        </div>
      </div>

      <!-- Sort and Filter Controls -->
      <div class="filter-row" style="justify-content: space-between;">
        <div class="filter-row">
          <span class="filter-label">Sort by Date:</span>
          <select class="form-control" style="width: 150px; padding: 8px 12px;">
            <option>Newest First</option>
            <option>Oldest First</option>
          </select>
        </div>
        <div class="filter-row">
          <span class="filter-label">Filter by Status:</span>
          <select class="form-control" style="width: 150px; padding: 8px 12px;">
            <option>All Status</option>
            <option>Pending</option>
            <option>Accepted</option>
          </select>
        </div>
      </div>
    </div>

    <!-- User Registry -->
    <div class="registry-card">
      <div class="registry-header">
        <h2 class="registry-title">User Registry</h2>
        <div class="search-container">
          <i class="fas fa-search search-icon"></i>
          <input type="text" id="userSearch" class="search-input" placeholder="Search by name, email or role..." onkeyup="filterTable()">
        </div>
      </div>
      <div class="table-scroll-container">
        <table class="user-table" id="registryTable">
          <thead>
            <tr>
              <th width="60">ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Verified</th>
              <th>Registered</th>
              <th width="100">Last<br>Login</th>
              <th width="100">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php
            try {
              $database = new Database();
              $conn = $database->getConnection();

              $sql = "SELECT id, first_name, last_name, email, role, status, email_verified, created_at, last_login 
                      FROM users";
              $params = [];
              $types = '';

              if (!empty($filterRole)) {
                $sql .= " WHERE role = ?";
                $params[] = $filterRole;
                $types .= 's';
              }

              $sql .= " ORDER BY created_at DESC";

              $stmt = $conn->prepare($sql);
              if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
              }
              $stmt->execute();
              $result = $stmt->get_result();

              // Fallback mock data if database is empty
              if ($result->num_rows === 0 && empty($filterRole)) {
                $mockUsers = [
                    ['id' => 8, 'first_name' => 'LGU', 'last_name' => 'Officer', 'email' => 'lgu.officer@lgu.gov.ph', 'role' => 'lgu_officer', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => null],
                    ['id' => 1, 'first_name' => 'System', 'last_name' => 'Administrator', 'email' => 'admin@lgu.gov.ph', 'role' => 'admin', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => '2026-01-14'],
                    ['id' => 2, 'first_name' => 'LGU', 'last_name' => 'Officer', 'email' => 'officer@lgu.gov.ph', 'role' => 'lgu_officer', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => null],
                    ['id' => 3, 'first_name' => 'Engineer', 'last_name' => 'User', 'email' => 'engineer@lgu.gov.ph', 'role' => 'engineer', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => null],
                    ['id' => 4, 'first_name' => 'Citizen', 'last_name' => 'User', 'email' => 'citizen@example.com', 'role' => 'citizen', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => null],
                    ['id' => 6, 'first_name' => 'Maria', 'last_name' => 'Reyes', 'email' => 'maria.reyes@lgu.gov.ph', 'role' => 'citizen', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => null],
                    ['id' => 7, 'first_name' => 'Carlos', 'last_name' => 'Garcia', 'email' => 'carlos.garcia@lgu.gov.ph', 'role' => 'lgu_officer', 'status' => 'active', 'email_verified' => 1, 'created_at' => '2026-01-14', 'last_login' => null]
                ];
                
                foreach ($mockUsers as $user):
                    if (!empty($filterRole) && $user['role'] !== $filterRole) continue;
                    $roleLabel = ucfirst($user['role']);
                    $fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                    $regDate = date('M j, Y', strtotime($user['created_at']));
                    $lastLog = $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never';
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td style="font-weight: 500;"><?php echo $fullName; ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo $roleLabel; ?></span></td>
                        <td><span class="status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                        <td><span class="badge-verified">Verified</span></td>
                        <td><?php echo $regDate; ?></td>
                        <td><?php echo $lastLog; ?></td>
                        <td><button class="btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">View</button></td>
                    </tr>
                    <?php
                endforeach;
            } else {
                while ($user = $result->fetch_assoc()):
                  $roleLabel = ucfirst($user['role']);
                  $fullName = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                  $regDate = date('M j, Y', strtotime($user['created_at']));
                  $lastLog = $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never';
                  ?>
                  <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td style="font-weight: 500;"><?php echo $fullName; ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="badge badge-<?php echo $user['role']; ?>"><?php echo $roleLabel; ?></span></td>
                    <td><span class="status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                    <td>
                      <?php if ($user['email_verified']): ?>
                        <span class="badge-verified">Verified</span>
                      <?php else: ?>
                        <span class="badge-not-verified">Not Verified</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $regDate; ?></td>
                    <td><?php echo $lastLog; ?></td>
                    <td><button class="btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">View</button></td>
                  </tr>
                <?php endwhile;
            }
            $stmt->close();
          } catch (Exception $e) {
            echo '<tr><td colspan="9">Error loading users.</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

  <!-- Modal -->
  <div id="userModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>User Details</h2>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <div id="modalBody">
        <!-- Details will be injected here -->
      </div>
    </div>
  </div>

  <script>
    function viewUser(userId) {
      console.log('Viewing user:', userId);
      
      const formData = new FormData();
      formData.append('action', 'view');
      formData.append('user_id', userId);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => {
        console.log('Response status:', res.status);
        console.log('Content-Type:', res.headers.get('content-type'));
        return res.json();
      })
      .then(data => {
        console.log('Response data:', data);
        if (data.error) {
          alert(data.error);
          return;
        }
        populateModal(data);
      })
      .catch(err => {
        console.error('Fetch error:', err);
        alert('An error occurred: ' + err.message);
      });
    }

    function populateModal(user) {
      const modal = document.getElementById('userModal');
      const body = document.getElementById('modalBody');
      
      const birthday = user.birthday || 'Not provided';
      const address = user.address || 'Not provided';
      const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
      const registered = new Date(user.created_at).toLocaleDateString();

      // Since photo column doesn't exist, always show no photo
      const photoHtml = '<div class="no-photo">No photo uploaded</div>';

      body.innerHTML = `
        <div class="detail-row"><span class="detail-label">User ID:</span><span class="detail-value">${user.id}</span></div>
        <div class="detail-row"><span class="detail-label">Role:</span><span class="detail-value">${user.role}</span></div>
        <div class="detail-row"><span class="detail-label">First Name:</span><span class="detail-value">${user.first_name || 'Not provided'}</span></div>
        <div class="detail-row"><span class="detail-label">Middle Name:</span><span class="detail-value">${user.middle_name || 'Not provided'}</span></div>
        <div class="detail-row"><span class="detail-label">Last Name:</span><span class="detail-value">${user.last_name || 'Not provided'}</span></div>
        <div class="detail-row"><span class="detail-label">Birthday:</span><span class="detail-value">${birthday}</span></div>
        <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">${user.email}</span></div>
        <div class="detail-row"><span class="detail-label">Address:</span><span class="detail-value">${address}</span></div>
        <div class="detail-row"><span class="detail-label">Registered:</span><span class="detail-value">${registered}</span></div>
        <div class="detail-row"><span class="detail-label">Last Login:</span><span class="detail-value">${lastLogin}</span></div>
        <div class="user-photo-container">
          <span class="photo-label">Uploaded Photo</span>
          ${photoHtml}
        </div>
      `;

      modal.style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('userModal').style.display = 'none';
    }

    window.onclick = function(event) {
      if (event.target == document.getElementById('userModal')) {
        closeModal();
      }
    }
    function filterTable() {
      const input = document.getElementById('userSearch');
      const filter = input.value.toLowerCase();
      const table = document.getElementById('registryTable');
      const tr = table.getElementsByTagName('tr');

      for (let i = 1; i < tr.length; i++) {
        const tdName = tr[i].getElementsByTagName('td')[1];
        const tdEmail = tr[i].getElementsByTagName('td')[2];
        const tdRole = tr[i].getElementsByTagName('td')[3];
        
        if (tdName || tdEmail || tdRole) {
          const nameText = tdName.textContent || tdName.innerText;
          const emailText = tdEmail.textContent || tdEmail.innerText;
          const roleText = tdRole.textContent || tdRole.innerText;
          
          if (nameText.toLowerCase().indexOf(filter) > -1 || 
              emailText.toLowerCase().indexOf(filter) > -1 || 
              roleText.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
          } else {
            tr[i].style.display = "none";
          }
        }
      }
    }
  </script>
</body>

</html>
