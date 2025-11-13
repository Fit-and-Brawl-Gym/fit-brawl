#!/bin/bash
# Fix all JS and CSS paths in admin PHP files to use PUBLIC_PATH

echo "🔧 Fixing admin PHP file paths..."

# List of admin PHP files
FILES=(
  "admin.php"
  "users.php"
  "trainers.php"
  "trainer_view.php"
  "trainer_add.php"
  "trainer_edit.php"
  "trainer_schedules.php"
  "trainer-schedules.php"
  "subscriptions.php"
  "reservations.php"
  "products.php"
  "equipment.php"
  "feedback.php"
  "contacts.php"
  "announcements.php"
  "activity-log.php"
  "system_status.php"
)

cd /home/ec2-user/fit-brawl/public/php/admin || exit

for file in "${FILES[@]}"; do
  if [ -f "$file" ]; then
    echo "Processing $file..."
    
    # Fix relative js/ paths to use PUBLIC_PATH
    sed -i 's|src="js/|src="<?= PUBLIC_PATH ?>/php/admin/js/|g' "$file"
    
    # Fix relative css/ paths to use PUBLIC_PATH (only if not already using PUBLIC_PATH)
    sed -i 's|href="css/\([^"]*\)"|href="<?= PUBLIC_PATH ?>/php/admin/css/\1"|g' "$file"
    
    echo "  ✓ Fixed $file"
  else
    echo "  ⚠ Skipped $file (not found)"
  fi
done

echo "✅ Done! All admin PHP files updated."
echo ""
echo "Summary of changes:"
echo "  - js/sidebar.js → <?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"
echo "  - js/*.js → <?= PUBLIC_PATH ?>/php/admin/js/*.js"
echo "  - css/*.css → <?= PUBLIC_PATH ?>/php/admin/css/*.css"
