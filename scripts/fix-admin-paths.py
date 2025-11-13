#!/usr/bin/env python3
"""
Fix all admin page paths to use PUBLIC_PATH constant for proper environment handling
"""

import os
import re

# Admin files to fix
ADMIN_DIR = "c:/xampp/htdocs/fit-brawl/public/php/admin"

# Patterns to fix
FIXES = [
    # JavaScript files
    (r'<script src="js/', r'<script src="<?= PUBLIC_PATH ?>/php/admin/js/'),
    # If there are any absolute /public/ paths we missed
    (r'src="/public/php/admin/js/', r'src="<?= PUBLIC_PATH ?>/php/admin/js/'),
    (r'href="/public/php/admin/css/', r'href="<?= PUBLIC_PATH ?>/php/admin/css/'),
]

def fix_file(filepath):
    """Fix paths in a single file"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original = content
        changes = []
        
        for pattern, replacement in FIXES:
            if re.search(pattern, content):
                content = re.sub(pattern, replacement, content)
                changes.append(f"  - Fixed: {pattern} -> {replacement}")
        
        if content != original:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✅ Fixed: {os.path.basename(filepath)}")
            for change in changes:
                print(change)
            return True
        return False
    except Exception as e:
        print(f"❌ Error fixing {filepath}: {e}")
        return False

def main():
    """Fix all admin PHP files"""
    print("🔧 Fixing admin page paths...\n")
    
    if not os.path.exists(ADMIN_DIR):
        print(f"❌ Admin directory not found: {ADMIN_DIR}")
        return
    
    files_fixed = 0
    for filename in os.listdir(ADMIN_DIR):
        if filename.endswith('.php') and filename != 'admin_sidebar.php':
            filepath = os.path.join(ADMIN_DIR, filename)
            if fix_file(filepath):
                files_fixed += 1
    
    print(f"\n✅ Done! Fixed {files_fixed} files")
    print("\n📋 Next steps:")
    print("1. git add public/php/admin/*.php")
    print("2. git commit -m 'Fix: Admin JS paths to use PUBLIC_PATH'")
    print("3. git push origin main")
    print("4. Deploy to production")

if __name__ == "__main__":
    main()
