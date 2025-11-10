#!/usr/bin/env python3
"""
Fix all admin page JS paths to use PUBLIC_PATH
This script updates all admin PHP files to use proper paths for JavaScript files
"""

import os
import re
from pathlib import Path

# Define the admin directory
admin_dir = Path('c:/xampp/htdocs/fit-brawl/public/php/admin')

# Find all PHP files in admin directory
admin_files = list(admin_dir.glob('*.php'))

print(f"Found {len(admin_files)} admin PHP files")

# Pattern to find JS script tags with relative paths
pattern = r'<script\s+src="js/([^"]+)"'
replacement = r'<script src="<?= PUBLIC_PATH ?>/php/admin/js/\1"'

fixed_files = []
errors = []

for file_path in admin_files:
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Check if file has JS with relative paths
        if re.search(pattern, content):
            # Replace the pattern
            new_content = re.sub(pattern, replacement, content)
            
            # Write back
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(new_content)
            
            fixed_files.append(file_path.name)
            print(f"✅ Fixed: {file_path.name}")
        else:
            print(f"⏭️  Skipped (no JS paths): {file_path.name}")
    
    except Exception as e:
        errors.append(f"{file_path.name}: {str(e)}")
        print(f"❌ Error in {file_path.name}: {e}")

print("\n" + "="*60)
print(f"Summary:")
print(f"  Fixed: {len(fixed_files)} files")
print(f"  Errors: {len(errors)} files")

if fixed_files:
    print(f"\nFixed files:")
    for f in fixed_files:
        print(f"  - {f}")

if errors:
    print(f"\nErrors:")
    for e in errors:
        print(f"  - {e}")
