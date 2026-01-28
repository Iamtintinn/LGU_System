<?php
// GIS Mapping View - Citizen Module
session_start();
require_once '../config/auth.php';
$auth->requireAnyRole(['citizen', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GIS Mapping | Citizen Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

        /* Main Content */
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

        /* Map Container */
        .map-section {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid var(--glass-border);
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            flex: 1;
            display: flex;
            flex-direction: column;
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

        /* Map Controls */
        .map-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .control-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .filter-btn:hover {
            background: #f1f5f9;
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Map */
        #map {
            height: 500px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            margin-bottom: 20px;
        }

        /* Legend */
        .legend {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .legend h3 {
            color: #1e40af;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .legend-text {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .info-card .icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .info-card .icon i {
            font-size: 1.2rem;
            color: white;
        }

        .info-card h3 {
            color: #1e40af;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .info-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .info-card .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 5px;
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

        /* Loading State */
        .map-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 500px;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #cbd5e1;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar_citizen.php'; ?>

    <main class="main-content">
        <header class="module-header">
            <h1>GIS Mapping</h1>
            <p>Interactive map showing road conditions, infrastructure projects, and construction zones</p>
            <hr class="header-divider">
        </header>

        <!-- Info Cards -->
        <div class="info-cards">
            <div class="info-card">
                <div class="icon">
                    <i class="fas fa-road"></i>
                </div>
                <div class="number">156</div>
                <h3>Total Roads Mapped</h3>
                <p>Comprehensive coverage of all major and minor roads in the area</p>
            </div>

            <div class="info-card">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="number">24</div>
                <h3>Active Issues</h3>
                <p>Currently reported road conditions requiring attention</p>
            </div>

            <div class="info-card">
                <div class="icon">
                    <i class="fas fa-hard-hat"></i>
                </div>
                <div class="number">8</div>
                <h3>Construction Zones</h3>
                <p>Areas with ongoing infrastructure development projects</p>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2 class="section-title">Interactive Road Map</h2>
            <p class="section-desc">Click on map elements to view detailed information about road conditions and projects</p>
            
            <!-- Map Controls -->
            <div class="map-controls">
                <div class="control-group">
                    <button class="filter-btn active" onclick="filterMap('all')">
                        <i class="fas fa-layer-group"></i> All Layers
                    </button>
                    <button class="filter-btn" onclick="filterMap('issues')">
                        <i class="fas fa-exclamation-triangle"></i> Issues
                    </button>
                    <button class="filter-btn" onclick="filterMap('projects')">
                        <i class="fas fa-hard-hat"></i> Projects
                    </button>
                    <button class="filter-btn" onclick="filterMap('completed')">
                        <i class="fas fa-check-circle"></i> Completed
                    </button>
                </div>
                
                <div class="control-group">
                    <button class="filter-btn" onclick="resetMap()">
                        <i class="fas fa-redo"></i> Reset View
                    </button>
                </div>
            </div>

            <!-- Map Container -->
            <div id="map">
                <div class="map-loading">
                    <div class="spinner"></div>
                </div>
            </div>

            <!-- Legend -->
            <div class="legend">
                <h3>Map Legend</h3>
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-color" style="background: #dc2626;"></div>
                        <span class="legend-text">Critical Issues</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #f59e0b;"></div>
                        <span class="legend-text">Medium Priority</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #10b981;"></div>
                        <span class="legend-text">Good Condition</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #3b82f6;"></div>
                        <span class="legend-text">Construction Zone</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #8b5cf6;"></div>
                        <span class="legend-text">Planned Project</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background: #6b7280;"></div>
                        <span class="legend-text">Completed Work</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        let map;
        let markers = [];
        let currentFilter = 'all';

        function initMap() {
            // Create map centered on a default location (you can adjust this)
            map = L.map('map').setView([14.5995, 120.9842], 13); // Manila coordinates as example

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Add sample markers for road issues and projects
            addSampleData();
        }

        function addSampleData() {
            // Sample data for road issues
            const issues = [
                { lat: 14.5995, lng: 120.9842, type: 'critical', title: 'Main Road Pothole', desc: 'Large pothole causing traffic issues' },
                { lat: 14.6055, lng: 120.9902, type: 'medium', title: 'Market Street Crack', desc: 'Surface cracks needing repair' },
                { lat: 14.5955, lng: 120.9782, type: 'good', title: 'High Street', desc: 'Good road condition' },
                { lat: 14.6025, lng: 120.9872, type: 'construction', title: 'Bridge Repair', desc: 'Ongoing bridge maintenance' },
                { lat: 14.5985, lng: 120.9912, type: 'planned', title: 'New Road Project', desc: 'Planned road expansion' }
            ];

            issues.forEach(issue => {
                const color = getColorByType(issue.type);
                const marker = L.circleMarker([issue.lat, issue.lng], {
                    radius: 8,
                    fillColor: color,
                    color: '#fff',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(map);

                marker.bindPopup(`
                    <strong>${issue.title}</strong><br>
                    ${issue.desc}<br>
                    <small>Type: ${issue.type}</small>
                `);

                marker.type = issue.type;
                markers.push(marker);
            });
        }

        function getColorByType(type) {
            const colors = {
                'critical': '#dc2626',
                'medium': '#f59e0b',
                'good': '#10b981',
                'construction': '#3b82f6',
                'planned': '#8b5cf6',
                'completed': '#6b7280'
            };
            return colors[type] || '#6b7280';
        }

        function filterMap(filter) {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Add active class to clicked button
            event.target.closest('.filter-btn').classList.add('active');

            currentFilter = filter;

            // Show/hide markers based on filter
            markers.forEach(marker => {
                if (filter === 'all') {
                    marker.addTo(map);
                } else if (filter === 'issues' && (marker.type === 'critical' || marker.type === 'medium')) {
                    marker.addTo(map);
                } else if (filter === 'projects' && (marker.type === 'construction' || marker.type === 'planned')) {
                    marker.addTo(map);
                } else if (filter === 'completed' && marker.type === 'completed') {
                    marker.addTo(map);
                } else {
                    map.removeLayer(marker);
                }
            });
        }

        function resetMap() {
            // Reset map view to default
            map.setView([14.5995, 120.9842], 13);
            
            // Reset filter to all
            filterMap('all');
        }

        // Initialize map when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initMap, 1000); // Small delay to ensure map container is ready
        });
    </script>
</body>
</html>
