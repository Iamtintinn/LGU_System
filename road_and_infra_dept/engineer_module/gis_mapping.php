<?php
// Start session and include authentication
session_start();
require_once '../config/auth.php';

// Require login to access this page
$auth->requireLogin();

// Log page access
$auth->logActivity('page_access', 'Accessed GIS mapping and visualization');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LGU GIS Mapping & Visualization</title>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Poppins", sans-serif;
      }

      body {
        height: 100vh;
        background: url("../sidebar/assets/img/cityhall.jpeg") center/cover no-repeat fixed;
        position: relative;
        overflow: hidden;
      }

      body::before {
        content: "";
        position: absolute;
        inset: 0;
        backdrop-filter: blur(6px);
        background: rgba(0, 0, 0, 0.35);
        z-index: 0;
      }

      .main-content {
        position: relative;
        margin-left: 250px;
        height: 100vh;
        z-index: 1;
        padding: 2rem;
        overflow-y: auto;
      }

      .header {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
      }

      .header h1 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
      }

      .header p {
        color: #64748b;
      }

      .user-info {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
      }

      .logout-btn {
        background: #dc2626;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        cursor: pointer;
        margin-left: 0.5rem;
        text-decoration: none;
        display: inline-block;
      }

      .logout-btn:hover {
        background: #b91c1c;
      }

      .map-container {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 1.5rem;
        margin-top: 2rem;
        height: 500px;
        position: relative;
      }

      .map-placeholder {
        width: 100%;
        height: 100%;
        background: #f0f0f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 1.2rem;
      }

      .controls {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        flex-wrap: wrap;
      }

      .control-btn {
        background: #2563eb;
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        transition: background 0.3s ease;
      }

      .control-btn:hover {
        background: #1d4ed8;
      }
    </style>
  </head>

  <body>
    <!-- Include Sidebar -->
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="main-content">
      <div class="user-info">
        Welcome, <?php echo htmlspecialchars($auth->getUserFullName(), ENT_QUOTES, 'UTF-8'); ?> 
        (<?php echo htmlspecialchars($auth->getUserRole(), ENT_QUOTES, 'UTF-8'); ?>)
        <a href="../logout.php" class="logout-btn">Logout</a>
      </div>

      <div class="header">
        <h1>GIS Mapping & Visualization</h1>
        <p>Interactive mapping of road infrastructure and damage reports</p>
      </div>

      <div class="map-container">
        <div class="map-placeholder">
          🗺️ Interactive Map Will Be Displayed Here
        </div>
      </div>

      <div class="controls">
        <button class="control-btn">📍 Show Damage Reports</button>
        <button class="control-btn">🔍 Filter by Severity</button>
        <button class="control-btn">📊 View Analytics</button>
        <button class="control-btn">📅 Filter by Date</button>
        <button class="control-btn">🏗️ Show Construction Zones</button>
      </div>
    </div>

    <script>
      console.log('GIS Mapping Dashboard loaded');
      // Map integration will be added here
    </script>
  </body>
</html>
