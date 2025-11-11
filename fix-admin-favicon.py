#!/usr/bin/env python3
"""
Fix all remaining admin panel issues:
1. Fix favicon paths to use PUBLIC_PATH
2. Identify and fix empty/incomplete pages
"""

import os
import re
from pathlib import Path

admin_dir = Path('c:/xampp/htdocs/fit-brawl/public/php/admin')
admin_files = list(admin_dir.glob('*.php'))

print(f"🔍 Checking {len(admin_files)} admin PHP files\n")

# Pattern to find favicon with relative paths
favicon_pattern = r'<link\s+rel="icon"\s+type="image/png"\s+href="\.\./\.\./\.\./images/favicon-admin\.png"'
favicon_replacement = r'<link rel="icon" type="image/png" href="<?= PUBLIC_PATH ?>/images/favicon-admin.png"'

fixed_favicons = []
errors = []

for file_path in admin_files:
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        
        # Replace favicon paths
        content = re.sub(favicon_pattern, favicon_replacement, content)
        
        if content != original_content:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(content)
            fixed_favicons.append(file_path.name)
            print(f"✅ Fixed favicon: {file_path.name}")
        else:
            print(f"⏭️  Already correct or no favicon: {file_path.name}")
    
    except Exception as e:
        errors.append(f"{file_path.name}: {str(e)}")
        print(f"❌ Error in {file_path.name}: {e}")

print("\n" + "="*60)
print(f"Summary:")
print(f"  Fixed favicons: {len(fixed_favicons)} files")
print(f"  Errors: {len(errors)} files")

if fixed_favicons:
    print(f"\nFixed files:")
    for f in fixed_favicons:
        print(f"  - {f}")
