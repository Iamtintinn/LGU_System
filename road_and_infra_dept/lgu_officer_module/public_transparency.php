<?php
// Public Transparency - LGU Officer Module
session_start();
require_once '../config/auth.php';
$auth->requireAnyRole(['lgu_officer', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Transparency | LGU Officer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Sidebar Adjustment */
        .main-content {
            position: relative;
            margin-left: 250px;
            height: 100vh;
            padding: 40px 60px;
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
        }

        .module-header p {
            font-size: 1rem;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        .header-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 15px 0;
        }

        /* Content Sections */
        .transparency-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .section-title {
            color: #1e40af;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .section-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        /* Overview Grid */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .stat-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        .stat-box .label {
            display: block;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .stat-box .value {
            display: block;
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Table Styling */
        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-align: left;
            padding: 15px;
            font-size: 0.95rem;
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

        /* Map Placeholder */
        .map-placeholder {
            background: #f1f5f9;
            border-radius: 12px;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 1rem;
            text-align: center;
            border: 2px dashed #cbd5e1;
        }

        /* Scrollbar Styling */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }

        .main-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }

        .main-content::-webkit-scrollbar-thumb {
            background: rgba(37, 99, 235, 0.5);
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1>Public Transparency</h1>
            <p>********************** DESCRIPTION HERE ***************************</p>
            <hr class="header-divider">
        </header>

        <!-- Road Maintenance Overview -->
        <div class="transparency-card">
            <h2 class="section-title">Road Maintenance Overview</h2>
            <p class="section-desc">Summary of verified road conditions and repair progress.</p>
            <div class="overview-grid">
                <div class="stat-box">
                    <span class="label">Reported Issues</span>
                    <span class="value">120</span>
                </div>
                <div class="stat-box">
                    <span class="label">Under Repair</span>
                    <span class="value">35</span>
                </div>
                <div class="stat-box">
                    <span class="label">Completed Repairs</span>
                    <span class="value">72</span>
                </div>
            </div>
        </div>

        <!-- Cost Transparency -->
        <div class="transparency-card">
            <h2 class="section-title">Cost Transparency (Approved Projects)</h2>
            <p class="section-desc">Displays approved and verified cost information integrated from the Damage Assessment & Cost Estimation Module.</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Road Name</th>
                            <th>Approved Budget</th>
                            <th>Funding Source</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Road Repair Phase 1</td>
                            <td>Main Road</td>
                            <td>₱1,250,000</td>
                            <td>City Infrastructure Fund</td>
                            <td>Under Repair</td>
                        </tr>
                        <tr>
                            <td>Resurfacing Project</td>
                            <td>Market Street</td>
                            <td>₱780,000</td>
                            <td>National Grant</td>
                            <td>Completed</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Public Road Map -->
        <div class="transparency-card">
            <h2 class="section-title">Public Road Map</h2>
            <p class="section-desc">Visual display of verified road issues.</p>
            <div class="map-placeholder">
                Map View Placeholder (GIS Integration)
            </div>
        </div>

        <!-- Verified Road Issues -->
        <div class="transparency-card">
            <h2 class="section-title">Verified Road Issues & Repair Status</h2>
            <p class="section-desc">Publicly accessible and view-only information.</p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Road Name</th>
                            <th>Issue</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Main Road</td>
                            <td>Pothole</td>
                            <td>Under Repair</td>
                        </tr>
                        <tr>
                            <td>Market Street</td>
                            <td>Cracks</td>
                            <td>Completed</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>

