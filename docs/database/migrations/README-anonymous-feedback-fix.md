# Anonymous Feedback Fix - Documentation

## Problem
Anonymous feedback submissions were failing with the error:
```
Column 'user_id' cannot be null
```

## Root Cause
The `feedback` table had `user_id` defined as `NOT NULL`, but the API code was setting `$user_id = null` for anonymous submissions.

## Solution

### 1. Database Schema Change
Modified the `feedback` table to allow NULL values for `user_id`:

```sql
ALTER TABLE `feedback` DROP FOREIGN KEY `feedback_ibfk_1`;
ALTER TABLE `feedback` MODIFY COLUMN `user_id` varchar(15) DEFAULT NULL;
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1`
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
  ON DELETE CASCADE;
```

### 2. API Code
The existing API code in `public/php/api/submit_feedback.php` already handles this correctly:
- For logged-in users: Uses `$_SESSION['user_id']`
- For anonymous users: Sets `$user_id = null`

### 3. Database Behavior
- When `user_id` is NULL: Feedback is stored as anonymous
- When `user_id` has a value: Foreign key constraint ensures it references a valid user
- The foreign key constraint still works correctly with NULL values (MySQL allows this)

## Verification
Run this SQL to verify the schema:
```sql
DESCRIBE feedback;
```

Expected result: `user_id` column should show `Null | YES`

## Testing
Anonymous feedback can now be submitted without errors. The system will:
1. Accept NULL user_id values
2. Generate an anonymous username if not provided
3. Use "anonymous@fitxbrawl.com" as default email if not provided
4. Store the feedback successfully

## Files Modified
- `docs/database/migrations/fix-anonymous-feedback.sql` (new)
- `docs/database/schema.sql` (updated documentation)
