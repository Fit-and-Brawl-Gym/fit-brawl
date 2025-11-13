<?php
session_start();
require_once 'includes/db_connect.php';

echo "=== DEBUG Reservations Membership Check ===\n\n";

$user_id = $_SESSION['user_id'] ?? null;
echo "Session user_id: " . ($user_id ?? 'NOT SET') . "\n";
echo "user_id type: " . gettype($user_id) . "\n\n";

if (!$user_id) {
    echo "ERROR: No user_id in session\n";
    exit;
}

echo "=== Testing the exact query from reservations.php ===\n\n";

$activeMembership = null;
$membershipClassTypes = [];
$gracePeriodDays = 3;

if ($user_id) {
    $membership_query = "SELECT um.*, m.plan_name, m.class_type
                        FROM user_memberships um
                        JOIN memberships m ON um.plan_id = m.id
                        WHERE um.user_id = ? 
                        AND um.membership_status = 'active' 
                        AND DATE_ADD(um.end_date, INTERVAL ? DAY) >= CURDATE()
                        ORDER BY um.end_date DESC
                        LIMIT 1";
    
    echo "Query: " . str_replace('?', "'%s'", $membership_query) . "\n";
    echo "Parameters: user_id='$user_id' (string), gracePeriodDays=$gracePeriodDays (int)\n\n";
    
    $stmt = $conn->prepare($membership_query);
    
    if (!$stmt) {
        echo "ERROR: Failed to prepare statement\n";
        echo "Error: " . $conn->error . "\n";
        exit;
    }
    
    // Using "si" - string for user_id, integer for gracePeriodDays
    $stmt->bind_param("si", $user_id, $gracePeriodDays);
    
    if (!$stmt->execute()) {
        echo "ERROR: Failed to execute statement\n";
        echo "Error: " . $stmt->error . "\n";
        exit;
    }
    
    $result = $stmt->get_result();
    
    echo "Rows returned: " . $result->num_rows . "\n\n";
    
    if ($row = $result->fetch_assoc()) {
        $activeMembership = $row;
        echo "✅ MEMBERSHIP FOUND!\n";
        echo "Plan: " . $row['plan_name'] . "\n";
        echo "Status: " . $row['membership_status'] . "\n";
        echo "End Date: " . $row['end_date'] . "\n";
        echo "Class Types: " . $row['class_type'] . "\n\n";
        
        // Parse class types from membership
        if (!empty($row['class_type'])) {
            $classTypes = preg_split('/\s*(?:,|and)\s*/i', $row['class_type']);
            $membershipClassTypes = array_filter(array_map('trim', $classTypes));
            echo "Parsed class types: " . implode(', ', $membershipClassTypes) . "\n";
        }
    } else {
        echo "❌ NO MEMBERSHIP FOUND\n";
        echo "This means the query returned 0 rows.\n";
    }
    $stmt->close();
}

echo "\n=== Result ===\n";
echo "activeMembership is " . ($activeMembership ? "SET (truthy)" : "NULL (falsy)") . "\n";
echo "\nIn reservations.php, this will show:\n";
echo $activeMembership ? "✅ The booking calendar" : "❌ 'Get Started with a Membership' message";
echo "\n";

$conn->close();
