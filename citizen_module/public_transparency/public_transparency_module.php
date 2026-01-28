<?php
// Start session and include authentication
session_start();
require_once '../config/auth.php';

// Public access - no authentication required for transparency data
// Log page access if user is logged in
if ($auth->isLoggedIn()) {
    $auth->logActivity('page_access', 'Accessed public transparency module');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LGU Public Transparency Module</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap");

    :root {
      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --glass-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(255, 255, 255, 0.4);
      --text-main: #1e293b;
      --text-muted: #64748b;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
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

    /* Main Content */
    .main-content {
      position: relative;
      margin-left: 250px;
      height: 100vh;
      padding: 40px 60px;
      display: flex;
      flex-direction: column;
      gap: 24px;
      overflow-y: auto;
      z-index: 1;
    }

    .header {
      color: white;
      margin-bottom: 30px;
    }

    .header h1 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .header p {
      font-size: 1rem;
      opacity: 0.9;
    }

    .divider {
      border: none;
      height: 1px;
      background: rgba(255, 255, 255, 0.3);
      margin: 15px 0;
    }

    .card {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      border: 1px solid var(--glass-border);
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      margin-bottom: 30px;
    }

    .card-title {
      color: var(--primary);
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 20px;
    }

    .card-description {
      color: var(--text-muted);
      margin-bottom: 20px;
    }

    /* Statistics Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .stat-card {
      background: var(--glass-bg);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      border: 1px solid var(--glass-border);
      padding: 24px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      transition: all 0.3s ease;
      text-align: center;
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      font-size: 1.25rem;
      color: white;
    }

    .stat-icon.total { background: var(--primary); }
    .stat-icon.pending { background: var(--warning); }
    .stat-icon.repair { background: var(--danger); }
    .stat-icon.completed { background: var(--success); }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 0.25rem;
    }

    .stat-label {
      color: var(--text-muted);
      font-size: 0.875rem;
    }

    /* Table Styling */
    .table-container {
      width: 100%;
      overflow-x: auto;
      margin-bottom: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      border-radius: 8px;
      overflow: hidden;
    }

    th {
      background: #f1f5f9;
      color: #1e293b;
      font-weight: 700;
      text-align: left;
      padding: 15px;
      font-size: 0.9rem;
    }

    td {
      padding: 15px;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      font-size: 0.9rem;
      color: #334155;
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover {
      background: #f8fafc;
    }

    /* Status Badge */
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #dcfce7; color: #166534; }
    .status-under_repair { background: #fef3c7; color: #92400e; }

    /* Map Placeholder */
    .map-container {
      height: 400px;
      border-radius: 14px;
      background: #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #555;
      position: relative;
    }

    .map-placeholder {
      text-align: center;
    }

    .map-placeholder i {
      font-size: 3rem;
      margin-bottom: 15px;
      color: var(--text-muted);
    }

    /* Loading State */
    .loading {
      text-align: center;
      padding: 40px;
      color: var(--text-muted);
    }

    .loading-spinner {
      border: 3px solid #f3f3f3;
      border-top: 3px solid var(--primary);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 0 auto 20px;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        padding: 20px;
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .card {
        padding: 20px;
      }

      th, td {
        padding: 10px;
        font-size: 0.8rem;
      }
    }

    @media (max-width: 480px) {
      .main-content {
        padding: 15px;
      }

      .stats-grid {
        grid-template-columns: 1fr;
      }

      .header h1 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>

<body>
  <!-- Include Sidebar -->
  <?php include '../sidebar/sidebar_citizen.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Header -->
    <header class="header">
      <h1><i class="fas fa-chart-line"></i> Public Transparency</h1>
      <p>View verified road maintenance data, project costs, and repair progress in your community.</p>
      <hr class="divider">
    </header>

    <!-- Loading State -->
    <div id="loadingState" class="loading">
      <div class="loading-spinner"></div>
      <p>Loading transparency data...</p>
    </div>

    <!-- Content Container -->
    <div id="contentContainer" style="display: none;">
      <!-- Statistics Overview -->
      <div class="card">
        <h3 class="card-title">Road Maintenance Overview</h3>
        <p class="card-description">Summary of verified road conditions and repair progress.</p>

        <div class="stats-grid" id="statsGrid">
          <!-- Stats will be populated by JavaScript -->
        </div>
      </div>

      <!-- Cost Transparency -->
      <div class="card">
        <h3 class="card-title">Cost Transparency (Approved Projects)</h3>
        <p class="card-description">
          Displays approved and verified cost information for road maintenance projects.
        </p>

        <div class="table-container">
          <table id="projectsTable">
            <thead>
              <tr>
                <th>Project ID</th>
                <th>Road Name</th>
                <th>Issue Type</th>
                <th>Approved Budget</th>
                <th>Funding Source</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="projectsTableBody">
              <!-- Projects will be populated by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Public Map -->
      <div class="card">
        <h3 class="card-title">Public Road Map</h3>
        <p class="card-description">Visual display of verified road issues and repair locations.</p>

        <div class="map-container">
          <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <h4>GIS Map Integration</h4>
            <p>Interactive map showing road issues and repair progress</p>
          </div>
        </div>
      </div>

      <!-- Road Status Table -->
      <div class="card">
        <h3 class="card-title">Verified Road Issues & Repair Status</h3>
        <p class="card-description">Publicly accessible information about reported road issues.</p>

        <div class="table-container">
          <table id="issuesTable">
            <thead>
              <tr>
                <th>Road Name</th>
                <th>Issue Type</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Reported Date</th>
              </tr>
            </thead>
            <tbody id="issuesTableBody">
              <!-- Issues will be populated by JavaScript -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Fetch transparency data on page load
    document.addEventListener('DOMContentLoaded', function() {
      fetchTransparencyData();
    });

    async function fetchTransparencyData() {
      try {
        const response = await fetch('api/get_transparency_data.php');
        const data = await response.json();
        
        if (data.success) {
          displayTransparencyData(data.data);
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        console.error('Error fetching transparency data:', error);
        document.getElementById('loadingState').innerHTML = `
          <div class="loading">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger); margin-bottom: 15px;"></i>
            <p>Failed to load transparency data. Please try again later.</p>
          </div>
        `;
      }
    }

    function displayTransparencyData(data) {
      // Hide loading state
      document.getElementById('loadingState').style.display = 'none';
      document.getElementById('contentContainer').style.display = 'block';

      // Display statistics
      displayStatistics(data.statistics);
      
      // Display projects
      displayProjects(data.projects);
      
      // Display road issues
      displayRoadIssues(data.road_issues);
    }

    function displayStatistics(stats) {
      const statsGrid = document.getElementById('statsGrid');
      statsGrid.innerHTML = `
        <div class="stat-card">
          <div class="stat-icon total">
            <i class="fas fa-clipboard-list"></i>
          </div>
          <div class="stat-value">${stats.total_reports || 0}</div>
          <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon pending">
            <i class="fas fa-hourglass-half"></i>
          </div>
          <div class="stat-value">${stats.pending_reports || 0}</div>
          <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon repair">
            <i class="fas fa-tools"></i>
          </div>
          <div class="stat-value">${stats.under_repair || 0}</div>
          <div class="stat-label">Under Repair</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon completed">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-value">${stats.completed_repairs || 0}</div>
          <div class="stat-label">Completed Repairs</div>
        </div>
      `;
    }

    function displayProjects(projects) {
      const projectsTableBody = document.getElementById('projectsTableBody');
      
      if (projects.length === 0) {
        projectsTableBody.innerHTML = `
          <tr>
            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
              <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
              No approved projects found.
            </td>
          </tr>
        `;
        return;
      }

      projectsTableBody.innerHTML = projects.map(project => `
        <tr>
          <td><strong>${project.project_id}</strong></td>
          <td>${project.road_name}</td>
          <td>${project.project_name}</td>
          <td>₱${project.approved_budget}</td>
          <td>${project.funding_source}</td>
          <td>
            <span class="status-badge status-${project.status.toLowerCase().replace(' ', '_')}">
              ${project.status.replace('_', ' ').toUpperCase()}
            </span>
          </td>
        </tr>
      `).join('');
    }

    function displayRoadIssues(issues) {
      const issuesTableBody = document.getElementById('issuesTableBody');
      
      if (issues.length === 0) {
        issuesTableBody.innerHTML = `
          <tr>
            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
              <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
              No road issues found.
            </td>
          </tr>
        `;
        return;
      }

      issuesTableBody.innerHTML = issues.map(issue => `
        <tr>
          <td>${issue.road_name}</td>
          <td>${issue.issue}</td>
          <td>
            <span class="status-badge status-${issue.severity.toLowerCase()}">
              ${issue.severity}
            </span>
          </td>
          <td>
            <span class="status-badge status-${issue.status.toLowerCase().replace(' ', '_')}">
              ${issue.status}
            </span>
          </td>
          <td>${issue.reported_date}</td>
        </tr>
      `).join('');
    }
  </script>
</body>

</html>