<?php
// Debug version without session requirements
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo "<h2>Debug: Testing API Step by Step</h2>";

// Step 1: Test database connection
echo "<h3>Step 1: Database Connection</h3>";
try {
    require_once '../../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Step 2: Check if inspections table exists
echo "<h3>Step 2: Check Inspections Table</h3>";
try {
    $result = $conn->query("SHOW TABLES LIKE 'inspections'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Inspections table exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Inspections table does not exist</p>";
        echo "<p>Please run the SQL setup file first.</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking table: " . $e->getMessage() . "</p>";
    exit;
}

// Step 3: Count inspections
echo "<h3>Step 3: Count Inspections</h3>";
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM inspections");
    $row = $result->fetch_assoc();
    echo "<p style='color: blue;'>📊 Found {$row['count']} inspections</p>";
    
    if ($row['count'] == 0) {
        echo "<p style='color: orange;'>⚠️ No inspections found. Sample data may not have been inserted.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error counting inspections: " . $e->getMessage() . "</p>";
}

// Step 4: Try the actual query
echo "<h3>Step 4: Test Actual Query</h3>";
try {
    $stmt = $conn->prepare("
        SELECT 
            i.inspection_id,
            i.location,
            i.inspection_date,
            i.status
        FROM inspections i
        ORDER BY i.inspection_date DESC
        LIMIT 3
    ");
    
    if (!$stmt) {
        echo "<p style='color: red;'>❌ Failed to prepare statement: " . $conn->error . "</p>";
        exit;
    }
    
    $stmt->execute();
    $inspections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<p style='color: green;'>✅ Query successful, found " . count($inspections) . " inspections</p>";
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Location</th><th>Date</th><th>Status</th></tr>";
    foreach ($inspections as $inspection) {
        echo "<tr>";
        echo "<td>{$inspection['inspection_id']}</td>";
        echo "<td>{$inspection['location']}</td>";
        echo "<td>{$inspection['inspection_date']}</td>";
        echo "<td>{$inspection['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Query failed: " . $e->getMessage() . "</p>";
}

// Step 5: Test JSON output
echo "<h3>Step 5: Test JSON Output</h3>";
try {
    $stmt = $conn->prepare("
        SELECT 
            i.inspection_id,
            i.location,
            i.inspection_date,
            i.status
        FROM inspections i
        ORDER BY i.inspection_date DESC
        LIMIT 2
    ");
    
    $stmt->execute();
    $inspections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $formatted = array_map(function($inspection) {
        return [
            'inspection_id' => $inspection['inspection_id'],
            'location' => $inspection['location'],
            'date' => date('M j, Y', strtotime($inspection['inspection_date'])),
            'status' => $inspection['status']
        ];
    }, $inspections);
    
    echo "<p style='color: green;'>✅ JSON formatted successfully</p>";
    echo "<pre>" . json_encode($formatted, JSON_PRETTY_PRINT) . "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ JSON formatting failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<p>If all steps above show ✅ green checkmarks, then:</p>";
echo "<ol>";
echo "<li>1. Make sure you're logged in as an engineer</li>";
echo "<li>2. Check browser console for JavaScript errors</li>";
echo "<li>3. Test the API directly: <a href='get_inspections.php'>Click here to test API</a></li>";
echo "</ol>";
?>
