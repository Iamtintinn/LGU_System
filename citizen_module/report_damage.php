<?php
// report_damage.php - Citizen Road Damage Reporting Page
session_start();
require_once '../config/auth.php';
$auth->requireAnyRole(['citizen', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Road Damage | LGU Portal</title>
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
            margin: 0; padding: 0; box-sizing: border-box; font-family: "Inter", sans-serif;
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
            position: absolute; inset: 0;
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

        .header {
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .divider {
            border: none;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px 0;
        }

        .form-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-main);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-submit:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .image-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview {
            width: 100%;
            aspect-ratio: 1;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .form-card {
                padding: 20px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .btn-submit {
                padding: 12px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }

            .form-card {
                padding: 15px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .btn-submit {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar_citizen.php'; ?>

    <main class="main-content">
        <header class="header">
            <h1><i class="fas fa-exclamation-triangle"></i> Report Road Damage</h1>
            <p>Help us improve our community by reporting any road issues you encounter.</p>
            <hr class="divider">
        </header>

        <div class="form-card">
            <form id="reportForm">
                <div class="form-group">
                    <label for="location"><i class="fas fa-map-marker-alt"></i> Location / Landmark</label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Near Market, Quezon Ave cor. EDSA" required>
                </div>

                <div class="form-group">
                    <label for="barangay"><i class="fas fa-building"></i> Barangay</label>
                    <select id="barangay" name="barangay" class="form-control" required>
                        <option value="" disabled selected>Select barangay</option>
                        <option value="Barangay 1">Barangay 1</option>
                        <option value="Barangay 2">Barangay 2</option>
                        <option value="Barangay 3">Barangay 3</option>
                        <option value="Barangay 4">Barangay 4</option>
                        <option value="Barangay 5">Barangay 5</option>
                        <option value="Poblacion">Poblacion</option>
                        <option value="San Isidro">San Isidro</option>
                        <option value="San Jose">San Jose</option>
                        <option value="San Juan">San Juan</option>
                        <option value="San Miguel">San Miguel</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="damage_type"><i class="fas fa-category"></i> Damage Type</label>
                    <select id="damage_type" name="damage_type" class="form-control" required>
                        <option value="" disabled selected>Select damage type</option>
                        <option value="pothole">Pothole</option>
                        <option value="crack">Road Crack</option>
                        <option value="flooding">Poor Drainage / Flooding</option>
                        <option value="sidewalk">Damaged Sidewalk</option>
                        <option value="obstruction">Road Obstruction</option>
                        <option value="scoured">Scoured Shoulder</option>
                        <option value="fallen_debris">Fallen Debris</option>
                        <option value="erosion">Soil Erosion</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="severity"><i class="fas fa-signal"></i> Estimated Severity</label>
                    <select id="severity" name="severity" class="form-control" required>
                        <option value="low">Low (Minor issues, safe to drive)</option>
                        <option value="medium" selected>Medium (Visible damage, requires caution)</option>
                        <option value="high">High (Significant damage, poses risk)</option>
                        <option value="urgent">Urgent (Dangerous, immediate repair needed)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> Description</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Provide more details about the damage (e.g., size of pothole, when it started, traffic impact)..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="estimated_size"><i class="fas fa-ruler"></i> Estimated Size</label>
                    <input type="text" id="estimated_size" name="estimated_size" class="form-control" placeholder="e.g. 2 feet diameter, 5 meters long">
                </div>

                <div class="form-group">
                    <label for="traffic_impact"><i class="fas fa-car"></i> Traffic Impact</label>
                    <select id="traffic_impact" name="traffic_impact" class="form-control">
                        <option value="none">No impact</option>
                        <option value="minor">Minor slowdown</option>
                        <option value="moderate" selected>Moderate congestion</option>
                        <option value="severe">Severe traffic</option>
                        <option value="blocked">Lane blocked</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reportImages"><i class="fas fa-camera"></i> Upload Photos</label>
                    <input type="file" id="reportImages" name="images[]" class="form-control" accept="image/*" multiple>
                    <div id="imagePreview" class="image-preview-container"></div>
                    <small style="color: var(--text-muted); margin-top: 5px; display: block;">
                        <i class="fas fa-info-circle"></i> Upload up to 5 photos. Maximum file size: 5MB per image.
                    </small>
                </div>

                <div class="form-group">
                    <label for="contact_number"><i class="fas fa-phone"></i> Contact Number (Optional)</label>
                    <input type="tel" id="contact_number" name="contact_number" class="form-control" placeholder="For follow-up questions">
                </div>

                <div class="form-group">
                    <label for="anonymous_report">
                        <input type="checkbox" id="anonymous_report" name="anonymous_report" value="1">
                        Submit report anonymously
                    </label>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Submit Report
                </button>
            </form>
        </div>
    </main>

    <div id="toast-container"></div>

    <script>
        // Image Preview with validation
        document.getElementById('reportImages').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            const files = e.target.files;
            const maxFiles = 5;
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (files.length > maxFiles) {
                showToast(`Maximum ${maxFiles} images allowed`, 'error');
                e.target.value = '';
                return;
            }
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (file.size > maxSize) {
                    showToast(`File ${file.name} is too large. Maximum size is 5MB`, 'error');
                    e.target.value = '';
                    preview.innerHTML = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.className = 'image-preview';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(file);
            }
        });

        // Form Submission
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('api/handle_report.php', {
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
                    this.reset();
                    document.getElementById('imagePreview').innerHTML = '';
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error(error);
                showToast(error.message || 'An error occurred during submission', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });

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
    </script>
</body>
</html>
