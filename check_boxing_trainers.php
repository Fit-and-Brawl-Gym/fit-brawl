<?php
require_once 'includes/db_connect.php';

// Check for Boxing trainers
$query = "SELECT id, name, specialization, status, deleted_at 
          FROM trainers 
          WHERE specialization = 'Boxing'
          ORDER BY name";

$result = $conn->query($query);

echo "=== Boxing Trainers in Database ===\n\n";

if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " Boxing trainer(s):\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "Name: " . $row['name'] . "\n";
        echo "Specialization: " . $row['specialization'] . "\n";
        echo "Status: " . $row['status'] . "\n";
        echo "Deleted: " . ($row['deleted_at'] ? 'Yes (' . $row['deleted_at'] . ')' : 'No') . "\n";
        echo "---\n\n";
    }
} else {
    echo "❌ No Boxing trainers found!\n\n";
    
    // Check what specializations exist
    $spec_query = "SELECT DISTINCT specialization FROM trainers WHERE deleted_at IS NULL";
    $spec_result = $conn->query($spec_query);
    
    echo "Available specializations in database:\n";
    while ($spec_row = $spec_result->fetch_assoc()) {
        echo "  - " . $spec_row['specialization'] . "\n";
    }
}

// Check total trainers
$total = $conn->query("SELECT COUNT(*) as count FROM trainers WHERE deleted_at IS NULL")->fetch_assoc();
echo "\nTotal active trainers: " . $total['count'] . "\n";

$conn->close();
