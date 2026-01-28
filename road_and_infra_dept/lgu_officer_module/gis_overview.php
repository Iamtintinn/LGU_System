<?php
// GIS Overview - LGU Officer Module
session_start();
require_once '../config/auth.php';
require_once '../config/database.php';

$auth->requireAnyRole(['lgu_officer', 'admin']);

$map_stats = [
    'active_incidents' => 8,
    'monitored_zones' => 12,
    'last_update' => date('Y-m-d H:i')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GIS Mapping Overview | LGU Officer</title>
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
        
        .main-content { 
            position: relative;
            flex: 1;
            margin-left: 250px; /* Account for fixed sidebar */
            height: 100vh;
            padding: 40px 60px;
            overflow-y: auto;
            z-index: 1;
        }


        .header {
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            margin-bottom: 2rem;
            color: var(--text-main);
        }

        .map-placeholder { 
            height: 500px; 
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            flex-direction: column;
            color: var(--text-main);
            border: 1px solid var(--glass-border);
            margin-bottom: 2rem;
        }

        .stats-panel { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
        .stat-card { 
            background: var(--glass-bg);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            padding: 24px;
            text-align: center;
        }

        .btn-primary {
            margin-top: 1.5rem;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    <main class="main-content">
        <header class="header">
            <h1>GIS Mapping Overview</h1>
            <p>Real-time visualization of road conditions and infrastructure projects.</p>
        </header>

        <div class="map-placeholder">
            <i class="fas fa-map-marked-alt fa-4x" style="margin-bottom: 1rem; color: var(--primary);"></i>
            <p style="font-size: 1.2rem; font-weight: 600;">Interactive Map View</p>
            <p style="opacity: 0.7;">Select layers to visualize data</p>
            <a href="../engineer_module/gis_mapping.php" class="btn-primary">
                Go to Full Map <i class="fas fa-external-link-alt"></i>
            </a>
        </div>

        <div class="stats-panel">
            <div class="stat-card">
                <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <h4>Active Incidents</h4>
                <p style="font-size: 2rem; font-weight: 700;"><?php echo $map_stats['active_incidents']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-draw-polygon" style="color: #10b981; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <h4>Monitored Zones</h4>
                <p style="font-size: 2rem; font-weight: 700;"><?php echo $map_stats['monitored_zones']; ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-sync" style="color: #3b82f6; font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                <h4>Last Update</h4>
                <p style="font-size: 1.2rem; font-weight: 600; margin-top: 0.5rem;"><?php echo $map_stats['last_update']; ?></p>
            </div>
        </div>
    </main>
</body>
</html>
