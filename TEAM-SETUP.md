# 🚀 Team Setup Guide - AES Encryption

**For Team Members**: Quick setup after pulling the latest code.

---

## ⚡ Quick Setup (2 Minutes)

After pulling the latest code, run **ONE** command:

```bash
php setup_encryption.php
```

That's it! The script will:
- ✅ Generate encryption key (if needed)
- ✅ Update database structure
- ✅ Verify everything works
- ✅ Show you the results

---

## 📋 What You'll See

```
============================================================
  AES-256 Encryption Auto-Setup
============================================================

This script will set up encryption for your local environment.
It's safe to run multiple times - it won't break anything!

ℹ️  Step 1: Checking configuration file...
✅ Config file found!

ℹ️  Step 2: Checking encryption key...
✅ Encryption key already configured!

ℹ️  Step 3: Checking database connection...
✅ Database connected!

ℹ️  Step 4: Checking database structure...
✅ Database already has email_encrypted column!

ℹ️  Step 5: Checking if existing data needs encryption...
✅ Found 11 users with encrypted emails!

ℹ️  Step 6: Verifying encryption...
✅ Encryption test PASSED! ✨

============================================================
  Setup Complete! 🎉
============================================================

✅ Encryption key configured
✅ Database structure ready
✅ Encryption system verified
✅ 11 users encrypted

ℹ️  Next steps:
  1. Test the encryption: php test_encryption.php
  2. View statistics: php test_stats.php
  3. Check specific user: php test_user.php admin@fitxbrawl.com

  Your local environment is ready! 🚀
```

---

## 🔧 Alternative: Manual Setup

If you prefer manual setup:

### Step 1: Add Encryption Key

Edit `includes/config.php` and add:

```php
// AES-256 Encryption Key
if (!getenv('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', hex2bin('c32db2d06ee27bc655da88c949c576a15a963cce89fb8f0bf1ab37c03e2f5ae1'));
}
```

### Step 2: Update Database

```bash
mysql -u root fit_and_brawl_gym < database/migrations/add_encryption.sql
```

### Step 3: Verify

```bash
php test_encryption.php
```

---

## ❓ FAQ

### Q: Do I need to run this every time I pull?

**A:** No! Run it **once** after the first pull with encryption. The script is smart - it won't break anything if run multiple times.

### Q: What if the setup fails?

**A:** The script will tell you exactly what went wrong. Common issues:

1. **Database not running**: Start XAMPP MySQL
2. **Config file not found**: Run from project root
3. **Permission denied**: Check file permissions

### Q: Can I skip the data migration?

**A:** Yes! The script will ask you. You can encrypt data later with:
```bash
php migrate_encrypt_data.php
```

### Q: How do I test if it's working?

**A:** Run these commands:
```bash
php test_encryption.php    # Test encryption system
php test_stats.php          # View statistics
php test_user.php email@example.com  # Check specific user
```

---

## 🆘 Getting Help

If you encounter issues:

1. **Check the error message** - It usually tells you what's wrong
2. **Run diagnostics**:
   ```bash
   php test_encryption.php
   ```
3. **Check logs**:
   ```bash
   tail -20 C:/xampp/php/logs/php_error_log.txt
   ```
4. **Ask the team** - We're here to help!

---

## 📚 More Information

For detailed documentation, see:
- **Complete Guide**: `docs/security/AES-ENCRYPTION-GUIDE.md`
- **Quick Reference**: `ENCRYPTION-README.md`

---

## ⚠️ Important Notes

- ✅ Safe to run multiple times
- ✅ Won't overwrite existing encryption
- ✅ Won't break your local database
- ✅ Takes less than 2 minutes
- ❌ Don't commit `includes/config.php` changes (already in `.gitignore`)

---

**Welcome to the encrypted team! 🔐**
