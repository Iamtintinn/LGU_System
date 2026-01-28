<?php
// Road Reporting Overview - LGU Officer Module
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

$auth->requireAnyRole(['lgu_officer', 'admin']);

// Mock data (Replace with real data later)
$reports = [
    ['id' => 'RD-001', 'location' => 'Main Road, Brgy. Central', 'type' => 'Pothole', 'status' => 'Pending'],
    ['id' => 'RD-002', 'location' => 'Riverside Blvd.', 'type' => 'Crack', 'status' => 'Under Review'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Road Reporting | LGU Officer</title>
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

        .main-content {
            position: relative;
            margin-left: 250px;
            height: 100vh;
            padding: 30px 40px;
            display: grid;
            grid-template-columns: 450px 1fr;
            gap: 25px;
            overflow-y: auto;
            z-index: 1;
        }

        /* Module Header */
        .module-header {
            grid-column: 1 / -1;
            color: white;
            margin-bottom: 10px;
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

        /* Generic Card Style */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 25px;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea.form-control {
            resize: none;
            height: 100px;
        }

        /* Photo Upload Styling */
        .photo-upload-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: 0.2s;
            background: rgba(248, 250, 252, 0.5);
        }

        .photo-upload-zone:hover {
            background: rgba(248, 250, 252, 0.8);
            border-color: var(--primary);
        }

        .photo-upload-zone i {
            font-size: 1.5rem;
            color: #64748b;
            margin-bottom: 8px;
        }

        .photo-upload-zone p {
            font-size: 0.85rem;
            color: #64748b;
        }

        .photo-upload-zone span {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Table/Right Side Styling */
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-controls {
            display: flex;
            gap: 20px;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .control-label {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .control-select {
            padding: 6px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #1e293b;
        }

        .damage-table {
            width: 100%;
            border-collapse: collapse;
        }

        .damage-table th {
            text-align: left;
            padding: 12px 15px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 2px solid #f1f5f9;
        }

        .damage-table td {
            padding: 15px;
            font-size: 0.9rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .damage-table tr:last-child td {
            border-bottom: none;
        }

        .id-cell {
            font-weight: 700;
            color: #1e293b;
        }

        /* Scrollbar */
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        .main-content::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.05);
        }
        .main-content::-webkit-scrollbar-thumb {
            background: rgba(37,99,235,0.5);
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1>Infrastructure Maintenance</h1>
            <p>Submit and track road repair requests</p>
            <hr class="header-divider">
        </header>

        <!-- Left Side: Report Form -->
        <div class="glass-card">
            <h2 class="card-title">Report Road Damage</h2>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" placeholder="Street / Barangay Name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Damage Type</label>
                    <select class="form-control" required>
                        <option value="Pothole">Pothole</option>
                        <option value="Crack">Crack</option>
                        <option value="Landslide">Landslide</option>
                        <option value="Flooding">Flooding</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Severity Level</label>
                    <select class="form-control" required>
                        <option value="Low">Low (Minor wear)</option>
                        <option value="Medium">Medium (Moderate damage)</option>
                        <option value="High">High (Severe/Dangerous)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" placeholder="Describe the damage extent..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Evidence Photos</label>
                    <div class="photo-upload-zone">
                        <i class="fas fa-camera"></i>
                        <p>Drag & drop or <span>browse</span></p>
                        <input type="file" style="display: none;" id="photo-input">
                    </div>
                </div>

                <button type="submit" class="btn-submit">Submit Official Report</button>
            </form>
        </div>

        <!-- Right Side: Recent Reports -->
        <div class="glass-card">
            <div class="table-header">
                <h2 class="card-title" style="margin-bottom: 0;">Reported Damages</h2>
                
                <div class="table-controls">
                    <div class="control-group">
                        <label class="control-label">Sort by Date</label>
                        <select class="control-select">
                            <option>Latest</option>
                            <option>Oldest</option>
                        </select>
                    </div>
                    <div class="control-group">
                        <label class="control-label">Filter by Status</label>
                        <select class="control-select">
                            <option>All</option>
                            <option>Pending</option>
                            <option>Under Review</option>
                            <option>Approved</option>
                        </select>
                    </div>
                </div>
            </div>

            <table class="damage-table">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th width="120">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                    <tr>
                        <td class="id-cell"><?php echo $report['id']; ?></td>
                        <td><?php echo $report['location']; ?></td>
                        <td><?php echo $report['type']; ?></td>
                        <td><?php echo $report['status']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Trigger file input when clicking the zone
        document.querySelector('.photo-upload-zone').addEventListener('click', () => {
            document.getElementById('photo-input').click();
        });
    </script>
</body>
</html>

