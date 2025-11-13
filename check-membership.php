<?php
require_once 'includes/db_connect.php';

echo "=== Checking User Memberships ===\n\n";

$query = "SELECT um.*, m.plan_name, m.class_type, 
          um.membership_status, um.end_date, 
          DATE_ADD(um.end_date, INTERVAL 3 DAY) as grace_end,
          CURDATE() as today,
          (DATE_ADD(um.end_date, INTERVAL 3 DAY) >= CURDATE()) as is_within_grace
          FROM user_memberships um 
          LEFT JOIN memberships m ON um.plan_id = m.id 
          ORDER BY um.id DESC 
          LIMIT 10";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "User ID: " . $row['user_id'] . "\n";
        echo "Plan: " . ($row['plan_name'] ?? 'N/A') . "\n";
        echo "Request Status: " . $row['request_status'] . "\n";
        echo "Membership Status: " . $row['membership_status'] . "\n";
        echo "End Date: " . $row['end_date'] . "\n";
        echo "Grace End: " . $row['grace_end'] . "\n";
        echo "Today: " . $row['today'] . "\n";
        echo "Within Grace?: " . ($row['is_within_grace'] ? 'YES' : 'NO') . "\n";
        echo "Class Types: " . ($row['class_type'] ?? 'N/A') . "\n";
        echo "---\n";
    }
} else {
    echo "No memberships found.\n";
}

$conn->close();
