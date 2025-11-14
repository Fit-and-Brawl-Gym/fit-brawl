<?php
session_start();
require_once 'includes/db_connect.php';

echo "=== Session Check ===\n";
echo "Logged in?: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Session user_id type: " . gettype($_SESSION['user_id'] ?? null) . "\n";
echo "Session username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n\n";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "=== Checking Membership for user_id: $user_id ===\n\n";
    
    // Try the exact query from reservations.php
    $gracePeriodDays = 3;
    $membership_query = "SELECT um.*, m.plan_name, m.class_type
                        FROM user_memberships um
                        JOIN memberships m ON um.plan_id = m.id
                        WHERE um.user_id = ? 
                        AND um.membership_status = 'active' 
                        AND DATE_ADD(um.end_date, INTERVAL ? DAY) >= CURDATE()
                        ORDER BY um.end_date DESC
                        LIMIT 1";
    
    $stmt = $conn->prepare($membership_query);
    $stmt->bind_param("si", $user_id, $gracePeriodDays);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "✅ MEMBERSHIP FOUND!\n";
        echo "Plan: " . $row['plan_name'] . "\n";
        echo "Status: " . $row['membership_status'] . "\n";
        echo "End Date: " . $row['end_date'] . "\n";
        echo "Class Types: " . $row['class_type'] . "\n";
    } else {
        echo "❌ NO ACTIVE MEMBERSHIP FOUND\n\n";
        
        // Try without the 'active' status check
        echo "Trying without status check...\n";
        $stmt2 = $conn->prepare("SELECT um.*, m.plan_name FROM user_memberships um LEFT JOIN memberships m ON um.plan_id = m.id WHERE um.user_id = ?");
        $stmt2->bind_param("s", $user_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        if ($row2 = $result2->fetch_assoc()) {
            echo "Found record:\n";
            echo "Request Status: " . $row2['request_status'] . "\n";
            echo "Membership Status: " . $row2['membership_status'] . "\n";
            echo "End Date: " . $row2['end_date'] . "\n";
        } else {
            echo "No record found for this user_id at all.\n";
        }
        $stmt2->close();
    }
    
    $stmt->close();
}

$conn->close();
