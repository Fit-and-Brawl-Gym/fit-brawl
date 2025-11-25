#!/bin/bash
# This script adds incremental IDs to each booking

# First, get a clean version by removing all existing id lines except in column definitions
sed -E 's/^[[:space:]]*id,[[:space:]]*$//g' seed_user_MBR-25-0025_bookings_v2_backup.sql | \
# Add id to INSERT column list if not present  
sed '/INSERT INTO user_reservations (/a\    id,' | \
# Add incremental ID values after ) VALUES (
awk 'BEGIN{id=2} 
/\) VALUES \(/ {print; printf "    %d,\n", id++; next}
{print}' > seed_user_MBR-25-0025_bookings_final.sql

echo "Created seed_user_MBR-25-0025_bookings_final.sql with IDs 2-17"
