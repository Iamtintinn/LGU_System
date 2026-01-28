<?php
// Start session and include authentication
session_start();
require_once '../config/auth.php';

// Require login and engineer/admin role to access this page
$auth->requireAnyRole(['engineer', 'admin']);

// Log page access
$auth->logActivity('page_access', 'Accessed damage assessment and cost estimation dashboard');


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LGU Damage Assessment & Cost Estimation</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
      background: url("assets/img/cityhall.jpeg") center/cover no-repeat fixed;
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

    /* Sidebar */
    .sidebar-nav {
      position: fixed;
      width: 250px;
      height: 100vh;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(18px);
      box-shadow: 0 4px 25px rgba(0, 0, 0, 0.25);
      z-index: 1000;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .site-logo {
      padding: 20px;
      text-align: center;
    }

    .site-logo img {
      width: 120px;
      border-radius: 10px;
    }

    .nav-list {
      list-style: none;
      padding: 0 20px;
    }

    .nav-link {
      display: block;
      padding: 12px 20px;
      color: #000;
      text-decoration: none;
      border-radius: 8px;
      margin-bottom: 6px;
      transition: 0.3s;
    }

    .nav-link:hover {
      background: #97a4c2;
      transform: translateX(5px);
    }

    .nav-link.active {
      background: #3762c8;
      color: #fff;
    }

    .user-info {
      text-align: center;
      padding: 20px;
    }

    .logout-btn {
      margin-top: 8px;
      padding: 8px 14px;
      background: #3762c8;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    /* Main Content */
    .main-content {
      position: relative;
      margin-left: 260px;
      height: 100vh;
      padding: 40px 60px;
      display: flex;
      flex-direction: column;
      gap: 24px;
      overflow-y: auto;
      z-index: 1;
    }

    .card {
      background: rgba(255, 255, 255, 0.82);
      backdrop-filter: blur(15px);
      border-radius: 18px;
      padding: 32px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
      transition: all 0.3s ease;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    h3 {
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.2rem;
      font-weight: 600;
      color: #1e293b;
    }

    h3 i {
      color: #2563eb;
      font-size: 1.1rem;
    }

    .form-group {
      margin-bottom: 16px;
    }

    .form-group label {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      color: #64748b;
      margin-bottom: 6px;
      text-transform: uppercase;
    }

    .form-group label i {
      font-size: 0.7rem;
      color: #2563eb;
    }

    input,
    select {
      width: 100%;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid rgba(0, 0, 0, 0.1);
      background: rgba(255, 255, 255, 0.6);
      font-size: 0.9rem;
      margin-bottom: 0;
      transition: all 0.2s;
      font-family: "Poppins", sans-serif;
    }

    input:focus,
    select:focus {
      outline: none;
      border-color: #2563eb;
      background: white;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232563eb' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 36px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    th,
    td {
      padding: 14px 12px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      text-align: left;
    }

    th {
      background: rgba(248, 250, 252, 0.6);
      font-weight: 600;
      font-size: 0.75rem;
      text-transform: uppercase;
      color: #64748b;
      letter-spacing: 0.5px;
    }

    th i {
      margin-right: 6px;
      color: #2563eb;
      font-size: 0.7rem;
    }

    tr:hover td {
      background: rgba(248, 250, 252, 0.5);
    }

    td {
      font-size: 0.9rem;
    }

    .btn-primary {
      padding: 12px 24px;
      border-radius: 12px;
      border: none;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      font-size: 0.95rem;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
      width: 100%;
      margin-top: 8px;
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, #1d4ed8, #1e40af);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
    }

    .btn-primary i {
      font-size: 0.9rem;
    }

    .page-header {
      grid-column: span 2;
      color: white;
      margin-bottom: 10px;
    }

    .page-header h1 {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .page-header h1 i {
      font-size: 1.4rem;
      opacity: 0.9;
    }

    .card p {
      color: #64748b;
      font-size: 0.9rem;
      margin-bottom: 16px;
    }
  </style>
</head>

<body>
  <!-- SIDEBAR (REUSED) -->
  <?php include '../sidebar/sidebar.php'; ?>

  <!-- Main Content -->

  <div class="main-content">
    <!-- Damage Assessment -->
    <header class="page-header">
      <h1 style="font-size: 1.5rem; font-weight: 700">
        <i class="fas fa-calculator"></i> Damage Assessment & Cost Estimation
      </h1>
      <p style="opacity: 0.8; font-size: 0.9rem">
        Assess road damage severity and estimate repair costs for infrastructure projects.
      </p>

      <!-- Divider -->
      <hr class="divider" />
    </header>

    <div class="card">
      <h3><i class="fas fa-clipboard-check"></i> Damage Assessment</h3>
      <p>Input assessment details for a reported damage.</p>
      <div class="form-group">
        <label><i class="fas fa-hashtag"></i> Damage Report ID</label>
        <input type="text" placeholder="Enter Report ID (e.g., RD-001)" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-exclamation-triangle"></i> Severity Level</label>
        <select>
          <option>Select Severity Level</option>
          <option>Low</option>
          <option>Medium</option>
          <option>High</option>
          <option>Critical</option>
        </select>
      </div>
      <div class="form-group">
        <label><i class="fas fa-tools"></i> Recommended Repair Type</label>
        <select>
          <option>Select Repair Type</option>
          <option>Patch Repair</option>
          <option>Resurfacing</option>
          <option>Full Reconstruction</option>
        </select>
      </div>
      <button class="btn-primary">
        <i class="fas fa-save"></i> Save Assessment
      </button>
    </div>

    <!-- Cost Estimation Input -->
    <div class="card">
      <h3><i class="fas fa-money-bill-wave"></i> Cost Estimation</h3>
      <p>
        Enter estimated costs; these will be stored in the system and used for
        funding requests.
      </p>
      <div class="form-group">
        <label><i class="fas fa-box"></i> Materials Cost</label>
        <input type="number" placeholder="Enter amount in ₱" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-users"></i> Labor Cost</label>
        <input type="number" placeholder="Enter amount in ₱" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-truck"></i> Equipment Cost</label>
        <input type="number" placeholder="Enter amount in ₱" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-receipt"></i> Other Costs</label>
        <input type="number" placeholder="Enter amount in ₱" />
      </div>
      <button class="btn-primary">
        <i class="fas fa-save"></i> Save Cost Estimation
      </button>
    </div>

    <!-- Saved Records -->
    <div class="card">
      <h3><i class="fas fa-database"></i> Saved Assessments & Cost Records</h3>
      <p>Stored in the system database.</p>
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> Report ID</th>
            <th><i class="fas fa-peso-sign"></i> Total Cost</th>
            <th><i class="fas fa-info-circle"></i> Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>RD-001</strong></td>
            <td><i class="fas fa-peso-sign" style="color: #2563eb; margin-right: 4px;"></i>250,000</td>
            <td><span
                style="padding: 4px 10px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #059669; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><i
                  class="fas fa-check-circle"></i> Ready for Funding</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Funding Request -->
    <div class="card">
      <h3><i class="fas fa-file-invoice-dollar"></i> Funding Request Generation</h3>
      <p>
        Generate payload for Infrastructure Project Management & Urban
        Planning systems.
      </p>
      <button class="btn-primary">
        <i class="fas fa-file-export"></i> Generate Funding Request
      </button>
    </div>
  </div>
  <script src="/js/sidebar.js"></script>
</body>

</html>