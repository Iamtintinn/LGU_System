<?php
// Document Management - LGU Officer Module
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

$auth->requireAnyRole(['lgu_officer', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document & Report | LGU Officer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
        
        :root {
            --primary: #2563eb;
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --text-main: #1e293b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
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
            padding: 40px 60px;
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

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .card-header {
            margin-bottom: 25px;
        }

        .card-header h2 {
            color: var(--text-main);
            margin-bottom: 10px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header p {
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
        }

        .table tr:hover {
            background: #fdfdfd;
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

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-upload {
            background: #3b82f6;
            color: white;
        }

        .btn-upload:hover {
            background: #2563eb;
        }

        .btn-download {
            background: #10b981;
            color: white;
        }

        .btn-download:hover {
            background: #059669;
        }

        .btn-send {
            background: #8b5cf6;
            color: white;
        }

        .btn-send:hover {
            background: #7c3aed;
        }

        .main-content::-webkit-scrollbar-thumb:hover { background: #555; background-clip: content-box; }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 25px;
            border-radius: 15px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        /* Toast Grid */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
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
    
    <div class="main-content">
      <header class="module-header">
        <h1><i class="fas fa-file-alt"></i> Document & Report</h1>
        <p style="margin-top: 10px;">Manage and track all system-generated reports and documents</p>
        <hr class="header-divider">
      </header>

      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-chart-bar"></i> Generated Reports</h2>
          <p>All reports are system-generated and verified for accuracy.</p>
        </div>
        <div class="card-body">
          <table class="table report-table">
            <thead>
              <tr>
                <th><i class="fas fa-hashtag"></i> Report ID</th>
                <th><i class="fas fa-file-alt"></i> Report Type</th>
                <th><i class="fas fa-cube"></i> Module</th>
                <th><i class="fas fa-info-circle"></i> Status</th>
                <th><i class="fas fa-cogs"></i> Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>RPT-001</td>
                <td><i class="fas fa-exclamation-triangle"></i> Damage Assessment Report</td>
                <td><i class="fas fa-dollar-sign"></i> Cost Estimation</td>
                <td><span class="status-badge approved"><i class="fas fa-check-circle"></i> Approved</span></td>
                <td>
                  <button class="btn btn-upload" onclick="openUploadModal('RPT-001', 'Damage Assessment Report')"><i class="fas fa-upload"></i> Upload</button>
                  <button class="btn btn-download" onclick="downloadReport('RPT-001')"><i class="fas fa-download"></i> Download</button>
                  <button class="btn btn-send" onclick="sendToSystems('RPT-001', 'Damage Assessment Report')"><i class="fas fa-paper-plane"></i> Send to Systems</button>
                </td>
              </tr>
              <tr>
                <td>RPT-002</td>
                <td><i class="fas fa-clipboard-list"></i> Inspection Summary</td>
                <td><i class="fas fa-tasks"></i> Inspection & Workflow</td>
                <td><span class="status-badge approved"><i class="fas fa-check-circle"></i> Approved</span></td>
                <td>
                  <button class="btn btn-upload" onclick="openUploadModal('RPT-002', 'Inspection Summary')"><i class="fas fa-upload"></i> Upload</button>
                  <button class="btn btn-download" onclick="downloadReport('RPT-002')"><i class="fas fa-download"></i> Download</button>
                  <button class="btn btn-send" onclick="sendToSystems('RPT-002', 'Inspection Summary')"><i class="fas fa-paper-plane"></i> Send to Systems</button>
                </td>
              </tr>
              <tr>
                <td>RPT-003</td>
                <td><i class="fas fa-wrench"></i> Repair Progress Report</td>
                <td><i class="fas fa-road"></i> Road Maintenance</td>
                <td><span class="status-badge completed"><i class="fas fa-check-circle"></i> Completed</span></td>
                <td>
                  <button class="btn btn-upload" onclick="openUploadModal('RPT-003', 'Repair Progress Report')"><i class="fas fa-upload"></i> Upload</button>
                  <button class="btn btn-download" onclick="downloadReport('RPT-003')"><i class="fas fa-download"></i> Download</button>
                  <button class="btn btn-send" onclick="sendToSystems('RPT-003', 'Repair Progress Report')"><i class="fas fa-paper-plane"></i> Send to Systems</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> Upload Document</h3>
                <span style="cursor:pointer" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="uploadForm">
                    <input type="hidden" id="modal_report_id" name="report_id">
                    <input type="hidden" id="modal_report_type" name="title">
                    <div class="form-group">
                        <label>Report ID: <span id="display_report_id" style="font-weight:700"></span></label>
                    </div>
                    <div class="form-group">
                        <label for="documentFile">Select File (PDF, Images, etc.)</label>
                        <input type="file" id="documentFile" name="document" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-upload" style="width:100%; justify-content:center; margin-top:10px;">
                        <i class="fas fa-check"></i> Submit Upload
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container"></div>

    <script>
        function showToast(message, type = 'primary') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.style.borderLeftColor = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#2563eb');
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}" style="color:${toast.style.borderLeftColor}"></i> ${message}`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function openUploadModal(reportId, reportType) {
            document.getElementById('modal_report_id').value = reportId;
            document.getElementById('modal_report_type').value = reportType;
            document.getElementById('display_report_id').innerText = reportId;
            document.getElementById('uploadModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('uploadModal')) {
                closeModal();
            }
        }

        // Handle Form Submission
        document.getElementById('uploadForm').onsubmit = function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            btn.disabled = true;

            fetch('api/handle_document.php?action=upload', {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    throw new Error(text || 'Invalid server response');
                }
            })
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500); // Reload to reflect changes
                } else {
                    const errorMsg = data.debug ? `${data.message} (${data.debug})` : data.message;
                    showToast(errorMsg, 'error');
                }
            })
            .catch(error => {
                console.error('Upload Error:', error);
                showToast(error.message || 'An error occurred during upload', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        };

        // Handle Download
        function downloadReport(reportId) {
            showToast('Fetching document...', 'primary');
            fetch(`api/handle_document.php?action=download&report_id=${reportId}`)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const link = document.createElement('a');
                    link.href = data.url;
                    link.download = ''; // Let the server/browser decide filename or force it
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    showToast('Download started', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => showToast('Error fetching document', 'error'));
        }

        // Handle Send to Systems
        function sendToSystems(reportId, reportType) {
            if(!confirm(`Are you sure you want to send ${reportId} to the Publication Management module?`)) return;

            showToast('Sending to systems...', 'primary');
            
            const formData = new FormData();
            formData.append('report_id', reportId);
            formData.append('report_type', reportType);

            fetch('api/handle_document.php?action=send', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => showToast('Failed to connect to system', 'error'));
        }
    </script>
</body>
</html>
