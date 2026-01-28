<?php
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

// Require engineer role
$auth->requireRole('engineer');

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Workflow | Engineer Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #f59e0b;
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

        .workflow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .workflow-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .workflow-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .card-icon.review {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .card-icon.view {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
        }

        .card-icon.lgu {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .workflow-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
        }

        .workflow-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .card-action.view-action {
            color: var(--success);
        }

        .card-action.lgu-action {
            color: var(--warning);
        }

        .stats-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.primary {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .stat-icon.success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
        }

        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .stat-icon.danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger);
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .notification.success {
            background: var(--success);
        }

        .notification.error {
            background: var(--danger);
        }

        .notification.warning {
            background: var(--warning);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-container {
            background: white;
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            border-radius: 20px;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 24px 32px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-title i {
            color: var(--primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
            padding: 8px;
            border-radius: 8px;
        }

        .modal-close:hover {
            color: #1e293b;
            background: #f1f5f9;
        }

        .modal-body {
            padding: 32px;
            overflow-y: auto;
        }

        .modal-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
            color: var(--text-muted);
        }

        .modal-loading i {
            font-size: 2rem;
            margin-right: 15px;
            animation: spin 1s linear infinite;
        }

        .modal-content-wrapper {
            width: 100%;
            overflow-x: hidden;
            background: url('../user_and_access_management_module/assets/img/cityhall.jpeg') center/cover no-repeat fixed;
            position: relative;
            min-height: 100vh;
            color: var(--text-main);
        }

        .modal-content-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            backdrop-filter: blur(8px);
            background: rgba(15, 23, 42, 0.45);
            z-index: -1;
        }

        .modal-content-wrapper .main-content {
            position: relative;
            z-index: 1;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            max-height: 90vh;
        }

        .modal-content-wrapper .module-header {
            color: white;
            margin-bottom: 30px;
        }

        .modal-content-wrapper .module-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .modal-content-wrapper .module-header p {
            font-size: 1rem;
            opacity: 0.9;
            letter-spacing: 0.5px;
        }

        .modal-content-wrapper .header-divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 15px 0;
        }

        .modal-content-wrapper .stats-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .modal-content-wrapper .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .modal-content-wrapper .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-content-wrapper .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .modal-content-wrapper .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .modal-content-wrapper .stat-icon.primary {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .modal-content-wrapper .stat-icon.success {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
        }

        .modal-content-wrapper .stat-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .modal-content-wrapper .stat-icon.danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger);
        }

        .modal-content-wrapper .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
        }

        .modal-content-wrapper .stat-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .modal-content-wrapper .workflow-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .modal-content-wrapper .workflow-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .modal-content-wrapper .workflow-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-content-wrapper .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .modal-content-wrapper .card-icon.review {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .modal-content-wrapper .card-icon.view {
            background: rgba(22, 163, 74, 0.1);
            color: var(--success);
        }

        .modal-content-wrapper .card-icon.lgu {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .modal-content-wrapper .workflow-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
        }

        .modal-content-wrapper .workflow-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .modal-content-wrapper .card-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .modal-content-wrapper .card-action.view-action {
            color: var(--success);
        }

        .modal-content-wrapper .card-action.lgu-action {
            color: var(--warning);
        }
    </style>
</head>
<body>
    <?php include 'sidebar_engineer.php'; ?>

    <div class="main-content">
        <header class="module-header">
            <h1><i class="fas fa-clipboard-check"></i> Inspection Workflow</h1>
            <p>Manage inspection reports, reviews, and LGU workflows</p>
            <hr class="header-divider">
        </header>

        <!-- Stats Cards -->
        <div class="stats-cards-container">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3 id="totalReports">0</h3>
                        <p>Total Reports</p>
                    </div>
                    <div class="stat-icon primary">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3 id="pendingReports">0</h3>
                        <p>Pending Approval</p>
                    </div>
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3 id="approvedReports">0</h3>
                        <p>Approved Reports</p>
                    </div>
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-card-header">
                    <div>
                        <h3 id="rejectedReports">0</h3>
                        <p>Rejected Reports</p>
                    </div>
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow Cards -->
        <div class="workflow-grid">
            <!-- Inspection Review Card -->
            <div class="workflow-card" onclick="openModal('review')">
                <div class="card-icon review">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h3>Inspection Review</h3>
                <p>Review and approve pending inspection reports submitted by field inspectors. Assess priority, costs, and provide recommendations.</p>
                <div class="card-action">
                    <i class="fas fa-arrow-right"></i>
                    Open Review
                </div>
            </div>

            <!-- Inspection View Card -->
            <div class="workflow-card" onclick="openModal('view')">
                <div class="card-icon view">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Inspection View</h3>
                <p>View detailed inspection reports, photos, and repair task information. Track status and review history.</p>
                <div class="card-action view-action">
                    <i class="fas fa-arrow-right"></i>
                    Open View
                </div>
            </div>

            <!-- LGU Workflow Card -->
            <div class="workflow-card" onclick="openModal('lgu')">
                <div class="card-icon lgu">
                    <i class="fas fa-building"></i>
                </div>
                <h3>LGU Workflow</h3>
                <p>Create and manage inspection reports for LGU officer approval. Submit detailed reports with photos and cost estimates.</p>
                <div class="card-action lgu-action">
                    <i class="fas fa-arrow-right"></i>
                    Open LGU
                </div>
            </div>

            <!-- Notifications Card -->
            <div class="workflow-card" onclick="openModal('notifications')">
                <div class="card-icon" style="background: rgba(34, 197, 94, 0.1); color: #f59e0b;">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Notifications</h3>
                <p>View and manage your notifications, alerts, and system messages.</p>
                <div class="card-action" style="color: #f59e0b;">
                    <i class="fas fa-arrow-right"></i>
                    Open Notifications
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="workflowModal">
        <div class="modal-container">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">
                    <i class="fas fa-clipboard-check"></i>
                    <span id="modalTitleText">Loading...</span>
                </h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="modal-loading">
                    <i class="fas fa-spinner"></i>
                    <span>Loading content...</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load statistics when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadInspectionStats();
        });

        // Load inspection statistics
        async function loadInspectionStats() {
            try {
                const response = await fetch('api/get_inspections.php');
                const inspections = await response.json();
                
                if (response.ok) {
                    const stats = {
                        total: inspections.length,
                        pending: inspections.filter(i => i.status === 'pending').length,
                        approved: inspections.filter(i => i.status === 'approved').length,
                        rejected: inspections.filter(i => i.status === 'rejected').length
                    };
                    
                    document.getElementById('totalReports').textContent = stats.total;
                    document.getElementById('pendingReports').textContent = stats.pending;
                    document.getElementById('approvedReports').textContent = stats.approved;
                    document.getElementById('rejectedReports').textContent = stats.rejected;
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Modal functions
        function openModal(type) {
            const modal = document.getElementById('workflowModal');
            const modalTitle = document.getElementById('modalTitleText');
            const modalBody = document.getElementById('modalBody');
            const modalTitleIcon = document.querySelector('.modal-title i');
            
            // Set title and icon based on type
            switch(type) {
                case 'review':
                    modalTitle.textContent = 'Inspection Review';
                    modalTitleIcon.className = 'fas fa-clipboard-check';
                    loadPageContent('inspection_review.php');
                    break;
                case 'view':
                    modalTitle.textContent = 'Inspection View';
                    modalTitleIcon.className = 'fas fa-eye';
                    loadPageContent('inspection_view.php');
                    break;
                case 'lgu':
                    modalTitle.textContent = 'LGU Workflow';
                    modalTitleIcon.className = 'fas fa-building';
                    loadPageContent('lgu_workflow.php');
                    break;
                case 'notifications':
                    modalTitle.textContent = 'Notifications';
                    modalTitleIcon.className = 'fas fa-bell';
                    loadPageContent('notifications_view.php');
                    break;
            }
            
            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('workflowModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        async function loadPageContent(page) {
            const modalBody = document.getElementById('modalBody');
            
            try {
                // Show loading state
                modalBody.innerHTML = `
                    <div class="modal-loading">
                        <i class="fas fa-spinner"></i>
                        <span>Loading content...</span>
                    </div>
                `;
                
                // Fetch page content
                const response = await fetch(page);
                const html = await response.text();
                
                if (response.ok) {
                    // Parse the HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Extract and append CSS from head
                    const styles = doc.querySelectorAll('style');
                    const links = doc.querySelectorAll('link[rel="stylesheet"]');
                    
                    // Create a temporary container for styles
                    const styleContainer = document.createElement('div');
                    styleContainer.style.display = 'none';
                    
                    // Add external CSS links
                    links.forEach(link => {
                        const newLink = document.createElement('link');
                        newLink.rel = 'stylesheet';
                        newLink.href = link.href;
                        document.head.appendChild(newLink);
                        styleContainer.appendChild(newLink);
                    });
                    
                    // Add inline styles
                    styles.forEach(style => {
                        const newStyle = document.createElement('style');
                        newStyle.textContent = style.textContent;
                        document.head.appendChild(newStyle);
                        styleContainer.appendChild(newStyle);
                    });
                    
                    // Extract the main content
                    const mainContent = doc.querySelector('.main-content') || doc.querySelector('body');
                    
                    if (mainContent) {
                        // Clear modal body and add content with proper wrapper structure
                        modalBody.innerHTML = `
                            <div class="main-content">
                                ${mainContent.innerHTML}
                            </div>
                        `;
                        
                        // Execute any scripts in the loaded content
                        const scripts = mainContent.querySelectorAll('script');
                        scripts.forEach(script => {
                            if (script.src) {
                                // Load external script
                                const newScript = document.createElement('script');
                                newScript.src = script.src;
                                modalBody.appendChild(newScript);
                            } else {
                                // Execute inline script with a slight delay
                                setTimeout(() => {
                                    try {
                                        eval(script.textContent);
                                    } catch (error) {
                                        console.error('Error executing script:', error);
                                    }
                                }, 100);
                            }
                        });
                        
                        // Add a cleanup function to remove styles when modal closes
                        window.currentModalStyles = styleContainer;
                        
                    } else {
                        throw new Error('Could not find main content');
                    }
                } else {
                    throw new Error('Failed to load page');
                }
            } catch (error) {
                console.error('Error loading page content:', error);
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 60px; color: var(--danger);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                        <p>Failed to load content. Please try again.</p>
                        <button class="btn btn-primary" onclick="loadPageContent('${page}')" style="margin-top: 15px;">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `;
            }
        }

        function closeModal() {
            const modal = document.getElementById('workflowModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            // Cleanup modal styles
            if (window.currentModalStyles) {
                const styles = window.currentModalStyles.querySelectorAll('link, style');
                styles.forEach(style => {
                    if (style.parentNode) {
                        style.parentNode.removeChild(style);
                    }
                });
                window.currentModalStyles = null;
            }
        }

        // Close modal when clicking overlay
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('workflowModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
