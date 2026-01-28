<?php
// Debug script to check notifications table
require_once '../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Checking if notifications table exists...\n";

// Check if table exists
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($result && $result->num_rows > 0) {
    echo "Notifications table exists.\n";
} else {
    echo "Notifications table does NOT exist. Creating it...\n";
    
    // Create the table
    $sql = "
    CREATE TABLE IF NOT EXISTS `notifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `type` varchar(50) NOT NULL,
      `title` varchar(255) NOT NULL,
      `message` text NOT NULL,
      `data` json DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `read_status` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`),
      KEY `type` (`type`),
      KEY `read_status` (`read_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    if ($conn->query($sql)) {
        echo "Notifications table created successfully.\n";
    } else {
        echo "Error creating notifications table: " . $conn->error . "\n";
    }
}

$conn->close();
?>
