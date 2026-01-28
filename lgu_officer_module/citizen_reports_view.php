<?php
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Check if user is logged in and is LGU officer
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$auth->requireRole('lgu_officer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Reports - LGU Road Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border: #e5e7eb;
            --shadow: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('../user_and_access_management_module/assets/img/cityhall.jpeg') center/cover no-repeat fixed;
            position: relative;
            overflow: hidden;
            color: var(--text-primary);
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
            padding: 40px 60px;
            display: flex;
            flex-direction: column;
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

        
        .filters-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .filter-control {
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg-primary);
            transition: border-color 0.3s ease;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-refresh {
            padding: 12px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-refresh:hover {
            background: var(--primary-dark);
        }

        .reports-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .reports-header {
            margin-bottom: 20px;
        }

        .reports-title {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
        }

        .reports-table th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            text-align: left;
            padding: 15px;
            font-size: 0.9rem;
        }

        .reports-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.9rem;
            color: #334155;
        }

        .reports-table tr:last-child td {
            border-bottom: none;
        }

        .report-id {
            font-weight: 600;
            color: var(--primary);
        }

        .severity-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .severity-urgent {
            background: var(--danger);
            color: white;
        }

        .severity-high {
            background: #f59e0b;
            color: white;
        }

        .severity-medium {
            background: #3b82f6;
            color: white;
        }

        .severity-low {
            background: #10b981;
            color: white;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: var(--warning);
            color: white;
        }

        .status-under_review {
            background: #3b82f6;
            color: white;
        }

        .status-approved {
            background: var(--success);
            color: white;
        }

        .status-in_progress {
            background: #8b5cf6;
            color: white;
        }

        .status-completed {
            background: #10b981;
            color: white;
        }

        .status-rejected {
            background: var(--danger);
            color: white;
        }

        /* Action Buttons */
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 80px;
            justify-content: center;
            white-space: nowrap;
        }

        .btn-view {
            background: var(--primary);
            color: white;
        }

        .btn-view:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-manage {
            background: var(--warning);
            color: white;
        }

        .btn-manage:hover {
            background: #e59e0b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-delete {
            background: var(--danger);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsive Action Buttons */
        @media (max-width: 768px) {
            .action-btn {
                padding: 6px 8px;
                font-size: 0.75rem;
                min-width: 60px;
                gap: 4px;
            }

            .action-btn i {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .action-btn {
                padding: 4px 6px;
                font-size: 0.7rem;
                min-width: 50px;
                gap: 2px;
            }

            .action-btn i {
                font-size: 0.7rem;
            }

            .action-btn span {
                display: none; /* Hide text on very small screens */
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .filters-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .reports-table {
                font-size: 0.8rem;
            }

            .reports-table th,
            .reports-table td {
                padding: 10px 8px;
            }

            .action-btn {
                padding: 6px 8px;
                font-size: 0.75rem;
                min-width: 60px;
                gap: 4px;
            }

            .action-btn i {
                font-size: 0.8rem;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .reports-table {
                font-size: 0.7rem;
            }

            .reports-table th,
            .reports-table td {
                padding: 8px 4px;
            }

            .reports-table th:nth-child(n+3),
            .reports-table td:nth-child(n+3) {
                display: none; /* Hide less important columns on very small screens */
            }

            .action-btn {
                padding: 4px 6px;
                font-size: 0.7rem;
                min-width: 50px;
                gap: 2px;
            }

            .action-btn i {
                font-size: 0.7rem;
            }

            .action-btn span {
                display: none; /* Hide text on very small screens */
            }

            .modal-content {
                width: 98%;
                padding: 15px;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            background: none;
            border: none;
        }

        .close:hover {
            color: var(--text-main);
        }

        .modal-body {
            padding: 0;
            max-height: 70vh;
            overflow-y: auto;
        }

        .modal-body-content {
            padding: 30px;
        }

        .modal-footer-content {
            padding: 20px 30px;
            border-top: 1px solid var(--border);
            background: var(--bg-secondary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            background: var(--bg-primary);
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-control[readonly] {
            background: var(--bg-secondary);
            color: var(--text-muted);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .image-thumb {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .image-thumb:hover {
            transform: scale(1.05);
        }

        /* Custom scrollbar */
        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Toast */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
        }

        .toast {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-clipboard-list"></i> Citizen Reports</h1>
            <p>View and manage road damage reports submitted by citizens</p>
            <hr class="divider">
        </header>

        
        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label class="filter-label">Search</label>
                    <input type="text" id="searchInput" class="filter-control" placeholder="Search reports...">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select id="statusFilter" class="filter-control">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Severity</label>
                    <select id="severityFilter" class="filter-control">
                        <option value="all">All Levels</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Sort By</label>
                    <select id="sortFilter" class="filter-control">
                        <option value="latest">Latest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="severity_high">High Severity First</option>
                        <option value="severity_low">Low Severity First</option>
                    </select>
                </div>
            </div>
            <div id="activeFilters" class="active-filters" style="margin-top: 15px; padding: 10px; background: rgba(37, 99, 235, 0.1); border-radius: 8px; display: none;">
                <small style="color: var(--text-muted); font-weight: 600;">
                    <i class="fas fa-filter"></i> Active Filters: 
                    <span id="activeFiltersText"></span>
                </small>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="reports-container">
            <div class="reports-header">
                <h2 class="reports-title">Recent Reports</h2>
            </div>
            <div id="reportsTableContainer">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading reports...
                </div>
            </div>
        </div>
    </main>

    <!-- Report Details Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Report Details</h3>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        let currentReports = [];
        let currentReport = null;

        // Load reports on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadReports();
            
            // Add event listeners for filters
            document.getElementById('searchInput').addEventListener('input', debounce(loadReports, 500));
            document.getElementById('statusFilter').addEventListener('change', loadReports);
            document.getElementById('severityFilter').addEventListener('change', loadReports);
            document.getElementById('sortFilter').addEventListener('change', loadReports);
        });

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = 'all';
            document.getElementById('severityFilter').value = 'all';
            document.getElementById('sortFilter').value = 'latest';
            updateActiveFiltersDisplay();
            loadReports();
        }

        function updateActiveFiltersDisplay() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const severity = document.getElementById('severityFilter').value;
            const sort = document.getElementById('sortFilter').value;
            
            const activeFilters = [];
            
            if (search) activeFilters.push(`Search: "${search}"`);
            if (status !== 'all') activeFilters.push(`Status: ${status.replace('_', ' ').toUpperCase()}`);
            if (severity !== 'all') activeFilters.push(`Severity: ${severity.toUpperCase()}`);
            if (sort !== 'latest') activeFilters.push(`Sort: ${sort.replace('_', ' ').replace(/(\w+)/, '$1').toUpperCase()}`);
            
            const activeFiltersDiv = document.getElementById('activeFilters');
            const activeFiltersText = document.getElementById('activeFiltersText');
            
            if (activeFilters.length > 0) {
                activeFiltersDiv.style.display = 'block';
                activeFiltersText.textContent = activeFilters.join(' | ');
            } else {
                activeFiltersDiv.style.display = 'none';
            }
        }

        function loadReports() {
            console.log('loadReports called');
            console.log('Fetching from API...');
            
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const severity = document.getElementById('severityFilter').value;
            const sort = document.getElementById('sortFilter').value;

            console.log('Parameters:', { search, status, severity, sort });

            const params = new URLSearchParams({
                search: search,
                status: status,
                severity: severity,
                sort: sort
            });

            console.log('API URL:', 'api/get_citizen_reports.php?' + params.toString());

            fetch('api/get_citizen_reports.php?' + params.toString())
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Data received:', data);
                    console.log('Data type:', typeof data);
                    
                    // Check if data is valid JSON
                    if (!data || typeof data !== 'object') {
                        throw new Error('Invalid response: ' + JSON.stringify(data));
                    }
                    
                    if (data.success) {
                        console.log('Reports loaded successfully:', data.data.reports.length);
                        currentReports = data.data.reports;
                        renderReportsTable(currentReports);
                        updateActiveFiltersDisplay();
                    } else {
                        console.error('API Error:', data.message);
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showToast('Error loading reports: ' + error.message);
                });
        }

        
        function renderReportsTable(reports) {
            const container = document.getElementById('reportsTableContainer');
            
            if (reports.length === 0) {
                container.innerHTML = `
                    <div class="no-data">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No reports found matching your criteria.</p>
                    </div>
                `;
                return;
            }

            const tableHTML = `
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Reported</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${reports.map(report => `
                            <tr>
                                <td class="report-id">${report.report_id}</td>
                                <td>${report.location}</td>
                                <td>${report.damage_type}</td>
                                <td>
                                    <span class="severity-badge severity-${report.severity}">
                                        ${report.severity}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-${report.status}">
                                        ${report.status.replace('_', ' ')}
                                    </span>
                                </td>
                                <td>${report.created_at_formatted}</td>
                                <td>
                                    <button class="action-btn btn-view" onclick="viewReport('${report.report_id}')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn btn-manage" onclick="manageReport('${report.report_id}')">
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            
            container.innerHTML = tableHTML;
        }

        function viewReport(reportId) {
            // Prevent default behavior
            event.preventDefault();
            event.stopPropagation();
            
            console.log('=== VIEW BUTTON CLICKED ===');
            console.log('Event target:', event.target);
            console.log('Report ID:', reportId);
            console.log('Current reports available:', currentReports.length);
            
            // Visual feedback - change button temporarily
            const viewButton = event.target.closest('button') || event.target;
            const originalText = viewButton.innerHTML;
            viewButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Opening...';
            viewButton.disabled = true;
            viewButton.style.cursor = 'not-allowed';
            
            // Check if reports are loaded
            if (currentReports.length === 0) {
                console.log('ERROR: No reports loaded yet');
                showToast('Please wait for reports to load', 'error');
                // Reset button
                viewButton.innerHTML = originalText;
                viewButton.disabled = false;
                viewButton.style.cursor = 'pointer';
                return;
            }
            
            // Force focus to modal for debugging
            setTimeout(() => {
                console.log('Attempting to open modal...');
                openReportModal(reportId, 'view');
            }, 100);
            
            // Reset button after a delay
            setTimeout(() => {
                if (viewButton) {
                    viewButton.innerHTML = originalText;
                    viewButton.disabled = false;
                    viewButton.style.cursor = 'pointer';
                }
            }, 2000);
        }

        function manageReport(reportId) {
            console.log('Manage button clicked for report:', reportId);
            console.log('Opening modal for report:', reportId);
            console.log('Modal body element:', document.getElementById('modalBody'));
            console.log('Modal element:', document.getElementById('reportModal'));
            
            // Open manage modal
            openReportModal(reportId, 'manage');
        }

        function openReportModal(reportId, mode) {
            console.log('=== OPENING MODAL ===');
            console.log('Report ID:', reportId);
            console.log('Mode:', mode);
            console.log('Current reports:', currentReports.length);
            
            try {
                const report = currentReports.find(r => r.report_id === reportId);
                if (!report) {
                    console.error('ERROR: Report not found with ID: ' + reportId);
                    console.error('Available reports:', currentReports.map(r => r.report_id));
                    showToast('Error: Report not found', 'error');
                    return;
                }

                console.log('Found report:', report);
                currentReport = report;
                const modalBody = document.getElementById('modalBody');
                const modalElement = document.getElementById('reportModal');
                
                if (!modalBody || !modalElement) {
                    console.error('ERROR: Modal elements not found');
                    console.error('modalBody:', modalBody);
                    console.error('modalElement:', modalElement);
                    showToast('Error: Modal elements not found', 'error');
                    return;
                }
                
                console.log('Modal elements found, building content...');
                
                const imagesHtml = report.images && report.images.length > 0 ? `
                    <div class="form-group">
                        <label class="form-label">Evidence Photos</label>
                        <div class="image-gallery">
                            ${report.images.map(img => `
                                <img src="../../uploads/reports/${img}" alt="Report Image" class="image-thumb" 
                                     onclick="window.open('../../uploads/reports/${img}', '_blank')">
                            `).join('')}
                        </div>
                    </div>
                ` : '';

                if (mode === 'view') {
                    // Read-only view modal
                    modalBody.innerHTML = `
                        <div class="modal-body-content">
                            <div class="form-group">
                                <label class="form-label">Report ID</label>
                                <input type="text" class="form-control" value="${report.report_id}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reporter</label>
                                <input type="text" class="form-control" value="${report.reporter_name || 'Anonymous'}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" value="${report.location}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Damage Type</label>
                                <input type="text" class="form-control" value="${report.damage_type}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Severity</label>
                                <input type="text" class="form-control" value="${report.severity}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" readonly>${report.description}</textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reported</label>
                                <input type="text" class="form-control" value="${report.created_at_formatted}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Last Updated</label>
                                <input type="text" class="form-control" value="${report.updated_at_formatted}" readonly>
                            </div>

                            ${imagesHtml}

                            <div class="form-group">
                                <label class="form-label">Current Status</label>
                                <input type="text" class="form-control" value="${report.status.replace('_', ' ')}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">LGU Notes</label>
                                <textarea class="form-control" readonly>${report.lgu_notes || 'No notes added yet.'}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer-content">
                            <div style="margin-top: 20px; text-align: center;">
                                <button type="button" class="btn-submit" onclick="closeModal()" style="background: var(--primary); max-width: 200px;">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    // Manage modal with editing capabilities
                    modalBody.innerHTML = `
                        <div class="modal-body-content">
                            <div class="form-group">
                                <label class="form-label">Report ID</label>
                                <input type="text" class="form-control" value="${report.report_id}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reporter</label>
                                <input type="text" class="form-control" value="${report.reporter_name || 'Anonymous'}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" value="${report.location}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Damage Type</label>
                                <input type="text" class="form-control" value="${report.damage_type}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Severity</label>
                                <input type="text" class="form-control" value="${report.severity}" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" readonly>${report.description}</textarea>
                            </div>

                            ${imagesHtml}

                            <div class="form-group">
                                <label class="form-label">Current Status</label>
                                <select id="statusSelect" class="form-control">
                                    <option value="pending" ${report.status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="under_review" ${report.status === 'under_review' ? 'selected' : ''}>Under Review</option>
                                    <option value="approved" ${report.status === 'approved' ? 'selected' : ''}>Approved</option>
                                    <option value="in_progress" ${report.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="completed" ${report.status === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="rejected" ${report.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">LGU Notes</label>
                                <textarea id="lguNotes" class="form-control" placeholder="Add notes about this report...">${report.lgu_notes || ''}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer-content">
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="button" class="btn-submit" onclick="updateReportStatus()" style="flex: 1; background: var(--success);">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                                <button type="button" class="btn-submit" onclick="closeModal()" style="flex: 1; background: var(--text-muted);">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    `;
                }

                modalElement.classList.add('active');
            } catch (error) {
                console.error('Error opening modal:', error);
                alert('Error opening modal: ' + error.message);
            }
        }

        function closeModal() {
            console.log('closeModal called');
            const modal = document.getElementById('reportModal');
            const modalBody = document.getElementById('modalBody');
            if (modal && modalBody) {
                modal.classList.remove('active');
                modalBody.innerHTML = '';
                currentReport = null;
            }
        }

        function testModal() {
            console.log('=== TESTING MODAL ===');
            
            // Create a test report if no reports exist
            if (currentReports.length === 0) {
                const testReport = {
                    report_id: 'TEST-001',
                    location: 'Test Location',
                    damage_type: 'pothole',
                    severity: 'medium',
                    description: 'This is a test report to verify modal functionality.',
                    images: [],
                    status: 'pending',
                    reporter_name: 'Test User'
                };
                currentReports = [testReport];
                console.log('Created test report:', testReport);
            }
            
            // Test view modal with first report
            if (currentReports.length > 0) {
                viewReport(currentReports[0].report_id);
            }
        }

        function updateReportStatus() {
            if (!currentReport) return;

            const status = document.getElementById('statusSelect').value;
            const lguNotes = document.getElementById('lguNotes').value;

            const formData = new FormData();
            formData.append('report_id', currentReport.report_id);
            formData.append('status', status);
            formData.append('lgu_notes', lguNotes);

            fetch('api/update_report_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Report status updated successfully', 'success');
                    closeModal();
                    loadReports(); // Refresh the table
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating report:', error);
                showToast('Error updating report status', 'error');
            });
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.borderLeftColor = type === 'success' ? '#10b981' : '#ef4444';
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color:${toast.style.borderLeftColor}"></i> ${message}`;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
