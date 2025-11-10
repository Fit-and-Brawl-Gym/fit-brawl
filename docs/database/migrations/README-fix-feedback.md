# Database Migration - Fix Feedback User ID

## Issue
The feedback table had `user_id` set as `NOT NULL`, which prevented anonymous (non-logged-in) users from submitting feedback.

## Changes Made

### 1. Schema Update (`docs/database/schema.sql`)
- Changed `user_id INT NOT NULL` to `user_id INT NULL`

### 2. Code Fix (`public/php/api/submit_feedback.php`)
- Simplified the parameter binding logic
- Removed redundant conditional binding for logged-in vs anonymous users
- Now properly handles NULL user_id for anonymous submissions

### 3. Migration Script (`docs/database/migrations/fix_feedback_user_id_nullable.sql`)
- Drops existing foreign key constraint
- Modifies `user_id` column to allow NULL
- Re-adds foreign key with `ON DELETE SET NULL` for better handling

## How to Apply the Migration

### Option 1: Using MySQL Command Line
```bash
mysql -u root -p fit_brawl < docs/database/migrations/fix_feedback_user_id_nullable.sql
```

### Option 2: Using phpMyAdmin
1. Open phpMyAdmin
2. Select the `fit_brawl` database
3. Go to the SQL tab
4. Copy and paste the contents of `docs/database/migrations/fix_feedback_user_id_nullable.sql`
4. Click "Go" to execute

### Option 3: Manual Execution
Execute these commands in your MySQL client:

```sql
-- First, drop the foreign key constraint
ALTER TABLE feedback DROP FOREIGN KEY feedback_ibfk_1;

-- Modify user_id to allow NULL values
ALTER TABLE feedback MODIFY COLUMN user_id INT NULL;

-- Re-add the foreign key constraint
ALTER TABLE feedback 
ADD CONSTRAINT feedback_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE SET NULL;
```

## Verification

After running the migration, you can verify the change by running:

```sql
DESCRIBE feedback;
```

The `user_id` column should show `NULL` as `YES` in the output.

## Testing

After applying the migration:
1. Test anonymous feedback submission (not logged in)
2. Test logged-in user feedback submission
3. Both should work without the "Column 'user_id' cannot be null" error

## Rollback (if needed)

If you need to revert this change:

```sql
-- Remove foreign key
ALTER TABLE feedback DROP FOREIGN KEY feedback_ibfk_1;

-- Update any NULL user_ids to a default value first
UPDATE feedback SET user_id = 0 WHERE user_id IS NULL;

-- Make user_id NOT NULL again
ALTER TABLE feedback MODIFY COLUMN user_id INT NOT NULL;

-- Re-add foreign key
ALTER TABLE feedback 
ADD CONSTRAINT feedback_ibfk_1 
FOREIGN KEY (user_id) REFERENCES users(id) 
ON DELETE CASCADE;
```
