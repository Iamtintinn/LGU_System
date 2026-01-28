<?php
// Start session and include authentication
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role to access this page
$auth->requireRole('engineer');

// Log page access
$auth->logActivity('page_access', 'Accessed damage assessment module');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damage Assessment & Cost Estimation | Engineer Portal</title>
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
            --success: #22c55e;
            --success-hover: #16a34a;
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

        /* Content Card */
        .content-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .content-card h2 {
            color: var(--text-main);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .content-card p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        /* Form Styling */
        .form-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: var(--text-main);
            margin-bottom: 25px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-main);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .btn-primary {
            background: var(--primary) !important;
            color: white !important;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-success {
            background: var(--success) !important;
            color: white !important;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-success:hover {
            background: var(--success-hover) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 197, 94, 0.3);
        }

        /* Table Styling */
        .records-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .records-table thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .records-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .records-table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-ready {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-funding {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-funding:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
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
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <div class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-road"></i> Damage Assessment & Cost Estimation</h1>
            <p>Assess road damage severity and estimate repair costs for infrastructure projects.</p>
        </header>

        <!-- Damage Assessment Section -->
        <div class="form-section">
            <h2><i class="fas fa-clipboard-check"></i> Damage Assessment</h2>
            <p>Input assessment details for a reported damage.</p>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="damage_report_id"><i class="fas fa-hashtag"></i> DAMAGE REPORT ID</label>
                    <input type="text" id="damage_report_id" name="damage_report_id" class="form-control" placeholder="Enter Report ID (e.g., RD-001)" required>
                </div>
                <div class="form-group">
                    <label for="severity_level"><i class="fas fa-exclamation-triangle"></i> SEVERITY LEVEL</label>
                    <select id="severity_level" name="severity_level" class="form-control" required>
                        <option value="">Select Severity Level</option>
                        <option value="minor">Minor</option>
                        <option value="moderate">Moderate</option>
                        <option value="severe">Severe</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="repair_type"><i class="fas fa-tools"></i> RECOMMENDED REPAIR TYPE</label>
                    <select id="repair_type" name="repair_type" class="form-control" required>
                        <option value="">Select Repair Type</option>
                        <option value="patching">Patching</option>
                        <option value="resurfacing">Resurfacing</option>
                        <option value="reconstruction">Reconstruction</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <button type="submit" name="save_assessment" class="btn-success">
                    <i class="fas fa-save"></i> Save Assessment
                </button>
            </form>
        </div>

        <!-- Cost Estimation Section -->
        <div class="form-section">
            <h2><i class="fas fa-coins"></i> Cost Estimation</h2>
            <p>Enter estimated costs; these will be stored in the system and used for funding requests.</p>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="materials_cost"><i class="fas fa-cube"></i> MATERIALS COST</label>
                    <input type="number" id="materials_cost" name="materials_cost" class="form-control" placeholder="Enter amount in ₱" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="labor_cost"><i class="fas fa-users"></i> LABOR COST</label>
                    <input type="number" id="labor_cost" name="labor_cost" class="form-control" placeholder="Enter amount in ₱" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="equipment_cost"><i class="fas fa-cogs"></i> EQUIPMENT COST</label>
                    <input type="number" id="equipment_cost" name="equipment_cost" class="form-control" placeholder="Enter amount in ₱" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="other_costs"><i class="fas fa-ellipsis-h"></i> OTHER COSTS</label>
                    <input type="number" id="other_costs" name="other_costs" class="form-control" placeholder="Enter amount in ₱" step="0.01" min="0">
                </div>
                <button type="submit" name="save_cost" class="btn-primary">
                    <i class="fas fa-calculator"></i> Save Cost Estimation
                </button>
            </form>
        </div>

        <!-- Saved Assessments & Cost Records -->
        <div class="form-section">
            <h2><i class="fas fa-database"></i> Saved Assessments & Cost Records</h2>
            <p>View previously saved assessments and their associated cost estimates.</p>
            <div class="records-table-container">
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>REPORT ID</th>
                            <th>TOTAL COST</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>RD-001</strong></td>
                            <td>₱ 250,000</td>
                            <td>
                                <span class="status-badge status-ready">
                                    <i class="fas fa-check-circle"></i> Ready for Funding
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Funding Request Generation -->
        <div class="form-section">
            <h2><i class="fas fa-hand-holding-usd"></i> Funding Request Generation</h2>
            <p>Generate payload for Infrastructure Project Management & Urban Planning systems.</p>
            <button class="btn-funding" onclick="generateFundingRequest()">
                <i class="fas fa-rocket"></i> Generate Funding Request
            </button>
        </div>
    </div>

    <script>
        function generateFundingRequest() {
            // Add loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            button.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check"></i> Request Generated!';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            }, 1500);
        }

        // Auto-calculate total cost
        document.querySelectorAll('#materials_cost, #labor_cost, #equipment_cost, #other_costs').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        function calculateTotal() {
            const materials = parseFloat(document.getElementById('materials_cost').value) || 0;
            const labor = parseFloat(document.getElementById('labor_cost').value) || 0;
            const equipment = parseFloat(document.getElementById('equipment_cost').value) || 0;
            const other = parseFloat(document.getElementById('other_costs').value) || 0;
            const total = materials + labor + equipment + other;
            
            // You can display the total somewhere if needed
            console.log('Total Cost:', total);
        }
    </script>
</body>
</html>
