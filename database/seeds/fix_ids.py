import re

# Read the backup file
with open('seed_user_MBR-25-0025_bookings_v2_backup.sql', 'r') as f:
    content = f.read()

# Pattern to find INSERT statements
pattern = r'INSERT INTO user_reservations \(\s*id,\s*id,\s*user_id,'
# Replace duplicate id with just id
content = re.sub(pattern, 'INSERT INTO user_reservations (\n    id,\n    user_id,', content)

# Pattern to find INSERT statements without id
pattern2 = r'INSERT INTO user_reservations \(\s*id,\s*user_id,'
replacement = 'INSERT INTO user_reservations (\n    id,\n    user_id,'
content = re.sub(pattern2, replacement, content)

# Now add ID values - find ) VALUES ( and add the ID number
id_counter = 2
def add_id(match):
    global id_counter
    result = match.group(0) + f"\n    {id_counter},"
    id_counter += 1
    return result

content = re.sub(r'\) VALUES \(\s*@user_id,', add_id, content)

# Write the fixed content
with open('seed_user_MBR-25-0025_bookings_v3.sql', 'w') as f:
    f.write(content)

print(f"Fixed file created with IDs from 2 to {id_counter-1}")
