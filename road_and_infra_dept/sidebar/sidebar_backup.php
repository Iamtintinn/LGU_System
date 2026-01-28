<?php
// Start session and include authentication
session_start();
require_once '../config/auth.php';

// Require login to access this page
$auth->requireLogin();
?>
<nav class="sidebar-nav">
  <div class="sidebar-content">
    <div class="sidebar-header">
      <img src="assets/img/logocityhall.png" alt="LGU Logo" class="sidebar-logo" />
      <h3>LGU Portal</h3>
      <p class="user-role"><?php echo htmlspecialchars($auth->getUserRole(), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <ul class="sidebar-menu">
      <li class="menu-item">
        <a href="../road_damage_reporting_module/dashboard.php" class="menu-link">
          <span class="menu-icon">📊</span>
          <span class="menu-text">Damage Reporting</span>
        </a>
      </li>

      <?php if ($auth->isEngineer() || $auth->isAdmin()): ?>
      <li class="menu-item">
        <a href="../damage_assessment_and_cost_estimation_module/damage_and_cost_dashboard.php" class="menu-link">
          <span class="menu-icon">💰</span>
          <span class="menu-text">Cost Assessment</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if ($auth->isEngineer() || $auth->isAdmin()): ?>
      <li class="menu-item">
        <a href="../inspection_and_workflow_module/inspection_and_workflow.php" class="menu-link">
          <span class="menu-icon">🔍</span>
          <span class="menu-text">Inspection</span>
        </a>
      </li>
      <?php endif; ?>

      <li class="menu-item">
        <a href="../gis_mapping_and_visualization_module/mapping.php" class="menu-link">
          <span class="menu-icon">🗺️</span>
          <span class="menu-text">GIS Mapping</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="../document_and_report_management_module/damage_and_report_management.php" class="menu-link">
          <span class="menu-icon">📄</span>
          <span class="menu-text">Documents</span>
        </a>
      </li>

      <?php if ($auth->isLguOfficer() || $auth->isAdmin()): ?>
      <li class="menu-item">
        <a href="../public_transparency_module/public_transparency_module.php" class="menu-link">
          <span class="menu-icon">👥</span>
          <span class="menu-text">Public Transparency</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if ($auth->isAdmin()): ?>
      <li class="menu-item">
        <a href="../user_and_access_management_module/permission.php" class="menu-link">
          <span class="menu-icon">👤</span>
          <span class="menu-text">User Management</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="../user_and_access_management_module/registered.php" class="menu-link">
          <span class="menu-icon">📋</span>
          <span class="menu-text">Registered Users</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
      <div class="user-info">
        <p class="user-name"><?php echo htmlspecialchars($auth->getUserFullName(), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="user-email"><?php echo htmlspecialchars($auth->getUserEmail(), ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <a href="../logout.php" class="logout-btn">Logout</a>
    </div>
  </div>
</nav>

<style>
  @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
  }
  
  body {
    min-height: 100vh;
  }

  .sidebar-nav {
    display: flex;
    flex-direction: column;
  }

  .sidebar-content {
    flex: 1;
    overflow-y: auto;
  }

  body {
    background: url("assets/img/cityhall.jpeg") center/cover no-repeat fixed;
    position: relative;
  }

  body::before {
    content: "";
    position: absolute;
    inset: 0;
    backdrop-filter: blur(6px);
    background: rgba(0, 0, 0, 0.35);
    z-index: 0;
  }

  /* SIDEBAR */
  .sidebar-nav {
    position: fixed;
    width: 250px;
    height: 100vh;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border-right: 1px solid rgba(255, 255, 255, 0.2);
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  }

  .sidebar-header {
    padding: 1.5rem;
    text-align: center;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  }

  .sidebar-logo {
    width: 60px;
    height: 60px;
    margin-bottom: 1rem;
    border-radius: 50%;
  }

  .sidebar-header h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1e293b;
  }

  .user-role {
    font-size: 0.875rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 500;
  }

  .sidebar-menu {
    list-style: none;
    padding: 1rem 0;
  }

  .menu-item {
    margin-bottom: 0.25rem;
  }

  .menu-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    color: #1e293b;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
  }

  .menu-link:hover {
    background: rgba(37, 99, 235, 0.1);
    border-left-color: #2563eb;
  }

  .menu-icon {
    margin-right: 0.75rem;
    font-size: 1.1rem;
  }

  .menu-text {
    font-weight: 500;
  }

  .sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    margin-top: auto;
  }

  .user-info {
    margin-bottom: 1rem;
  }

  .user-name {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
  }

  .user-email {
    font-size: 0.75rem;
    color: #64748b;
  }

  .logout-btn {
    display: block;
    width: 100%;
    padding: 0.5rem;
    background: #dc2626;
    color: white;
    text-decoration: none;
    text-align: center;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: background 0.3s ease;
  }

  .logout-btn:hover {
    background: #b91c1c;
  }
</style>
