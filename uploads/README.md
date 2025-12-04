# Uploads Directory

This folder stores user-generated content and uploaded files.

## Folder Structure

- **avatars/** - User profile pictures
- **receipts/** - Payment receipts and proof of payment
- **equipment/** - Equipment images for inventory
- **products/** - Product images for the store
- **trainers/** - Trainer profile photos

## Setup Instructions

These folders should be empty initially (except for .gitkeep files).
Make sure these folders have write permissions for the web server.

### For XAMPP on Windows
The default permissions should work fine.

### For Linux/Production
Set proper permissions:
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

## Notes

- Each subfolder contains a `.gitkeep` file to preserve the folder structure in Git
- Actual uploaded files are not tracked in Git (.gitignore excludes them)
- Maximum upload size depends on PHP configuration (php.ini)
