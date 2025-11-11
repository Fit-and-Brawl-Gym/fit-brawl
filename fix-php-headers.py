#!/usr/bin/env python3
"""
Fix trailing whitespace after PHP closing tags in include files
This prevents "headers already sent" errors
"""

import os
import re
from pathlib import Path

includes_dir = Path('c:/xampp/htdocs/fit-brawl/includes')
files_to_fix = [
    'email_template.php',
    'membership_mailer.php',
    'rate_limiter.php',
    'test_email_config.php'
]

print("🔧 Fixing trailing whitespace in PHP files\n")

for filename in files_to_fix:
    file_path = includes_dir / filename
    
    if not file_path.exists():
        print(f"⏭️  {filename} - not found")
        continue
    
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Remove closing ?> tag and any trailing whitespace
        # Pattern: ?> followed by any whitespace at end of file
        cleaned = re.sub(r'\?>\s*$', '', content)
        
        # Make sure file ends with single newline
        cleaned = cleaned.rstrip() + '\n'
        
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(cleaned)
        
        print(f"✅ {filename} - fixed")
    
    except Exception as e:
        print(f"❌ {filename} - error: {e}")

print("\n✅ Done! All files fixed.")
print("\nThis prevents 'headers already sent' errors when uploading files.")
