<?php
include 'db_connect.php';

if ($conn) {
    echo "✅ Database connection successful!";
} else {
    echo "❌ Connection failed.";
}
?>
