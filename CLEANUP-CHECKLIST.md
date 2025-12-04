# Pre-Demo Cleanup Checklist

This checklist will help you prepare a clean slate repository for your capstone demonstration.

## Files to Remove/Clean

### ✓ SQL Files in Root Directory
Remove these files from the root folder (they're listed in .gitignore now):
- [ ] `backup_pre_migration_20251116_115149.sql`
- [ ] `backup_pre_migration_20251116_115217.sql`
- [ ] `fix_all_auto_increment.sql`
- [ ] `fix_auto_increment.sql`
- [ ] `fix_trainer_coverage.sql`
- [ ] `fix_trainer_shifts_correct_ids.sql`
- [ ] `fix_user_memberships_auto_increment.sql`
- [ ] `seed_blocked_schedule.sql`
- [ ] `seed_defense_data.sql`
- [ ] `seed_memberships.sql`
- [ ] `seed_trainers_data.sql`
- [ ] `test-membership-expiration.sql`
- [ ] `update_reservations_trainers.sql`

**Keep only:** `docs/database/schema.sql`

### ✓ Environment & Sensitive Files
Make sure these are NOT in the repository:
- [ ] `.env` (personal configuration)
- [ ] `app.yaml` (if exists)
- [ ] Any files with passwords or API keys

**Keep:** `.env.example` (template without sensitive data)

### ✓ Dependency Folders
Remove these (will be reinstalled during demo):
- [ ] `vendor/` (Composer dependencies)
- [ ] `server-renderer/node_modules/` (Node.js dependencies)

These will be installed with:
- `composer install`
- `npm install` (in server-renderer folder)

### ✓ Uploads & User Data
Clean out user-generated content:
- [ ] `uploads/avatars/*` (except .gitkeep)
- [ ] `uploads/receipts/*` (except .gitkeep)
- [ ] `uploads/equipment/*` (except .gitkeep)
- [ ] `uploads/products/*` (except .gitkeep)
- [ ] `uploads/trainers/*` (except .gitkeep)

### ✓ Logs & Cache Files
Remove temporary files:
- [ ] Any `.log` files in the root or includes folder
- [ ] `logs/` folder if exists
- [ ] `cache/` folder if exists
- [ ] `tmp/` folder if exists

### ✓ Development Files
Remove editor-specific files (already in .gitignore):
- [ ] `.vscode/` folder
- [ ] `.idea/` folder
- [ ] Any `.bak` or `~` backup files

## Files to Keep

### ✓ Core Application Files
- [x] All PHP files in `public/`, `includes/`, `scripts/`
- [x] CSS, JavaScript files
- [x] `composer.json` and `composer.lock`
- [x] `package.json` (in server-renderer)
- [x] `Dockerfile` and `docker-compose.yml`
- [x] `README.md`

### ✓ New Setup Files
- [x] `SETUP.md` - Installation guide
- [x] `.env.example` - Configuration template
- [x] `.gitignore` - Updated ignore rules
- [x] `quick-setup.bat` - Setup helper script
- [x] `docs/database/README.md` - Database documentation
- [x] `docs/database/schema.sql` - Database structure

## Git Commands for Cleanup

After removing files, update your repository:

```bash
# Stage all changes (deletions and new files)
git add -A

# Commit the cleanup
git commit -m "Prepare repository for clean setup demonstration"

# Push to remote
git push origin main
```

## Demo Preparation Steps

1. **Clone to a fresh directory** (or use a different machine)
2. **Follow your own SETUP.md** to test the installation process
3. **Record the video** showing:
   - Installing Composer dependencies (`composer install`)
   - Installing Node.js dependencies (`npm install`)
   - Creating `.env` from `.env.example`
   - Creating database in phpMyAdmin
   - Importing `schema.sql`
   - Configuring `.env` settings
   - Running the application

## Tips for Video Recording

- **Show each step clearly** - Don't skip any command
- **Explain what you're doing** - "Now I'm installing PHP dependencies..."
- **Show successful outputs** - Console messages showing successful installs
- **Demonstrate the running application** - Homepage loading successfully
- **Keep it under 10-15 minutes** - Be concise but thorough

## Verification Before Demo

Test the entire setup process yourself:
- [ ] Clone/download the repository to a new folder
- [ ] Can you run `composer install` successfully?
- [ ] Can you run `npm install` successfully?
- [ ] Does `.env.example` have all necessary settings?
- [ ] Can you import `schema.sql` without errors?
- [ ] Does the application run at `http://localhost/fit-brawl/public/`?
- [ ] Can you create an admin user and login?

## Questions to Ask Your Adviser

Before recording, confirm with your panelist:
- Do they want to see Docker setup too? (you have docker-compose.yml)
- Should you show both Windows (XAMPP) and Docker approaches?
- How long should the video be?
- Do they want to see the application features or just setup?

---

**Good luck with your demonstration!** 🎬
