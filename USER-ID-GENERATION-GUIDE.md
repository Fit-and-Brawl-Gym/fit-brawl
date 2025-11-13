# User ID Generation System - Complete Guide

## 📋 Table of Contents

1. [What Are User IDs?](#what-are-user-ids)
2. [Why We Changed From Numbers to Formatted IDs](#why-we-changed)
3. [How Our ID System Works](#how-our-id-system-works)
4. [Understanding the Code](#understanding-the-code)
5. [Race Condition Problem & Solution](#race-condition-problem--solution)
6. [How to Use the System](#how-to-use-the-system)
7. [Troubleshooting](#troubleshooting)
8. [Technical Deep Dive](#technical-deep-dive)

---

## What Are User IDs?

A **User ID** is a unique identifier for each user in our system. Think of it like a customer number at a store - each person gets their own unique number so the system knows who they are.

### Example User IDs in Our System:

- `MBR-25-0001` - First member registered in 2025
- `MBR-25-0012` - Twelfth member registered in 2025
- `TRN-25-0003` - Third trainer registered in 2025
- `ADM-25-0001` - First admin registered in 2025

---

## Why We Changed From Numbers to Formatted IDs

### The Old System (Before)

```
User ID: 1, 2, 3, 4, 5...
```

**Problems:**

- ❌ Not human-readable (can't tell if someone is a member, trainer, or admin)
- ❌ Hard to track users over different years
- ❌ No context in the ID itself

### The New System (Now)

```
User ID: MBR-25-0001, TRN-25-0002, ADM-25-0001
```

**Benefits:**

- ✅ **Human-readable**: You can instantly see the role and year
- ✅ **Organized**: Separate sequences for each role
- ✅ **Professional**: Looks like a real business system
- ✅ **Yearly reset**: Each year starts fresh (MBR-26-0001 in 2026)

---

## How Our ID System Works

### ID Format Breakdown

```
MBR-25-0001
│   │  │
│   │  └─── Sequence Number (4 digits, zero-padded)
│   │       Starts at 0001, increments for each new user
│   │
│   └────── Year (last 2 digits: 25 = 2025)
│
└────────── Role Prefix
            MBR = Member
            TRN = Trainer
            ADM = Admin
```

### Real-World Example

Imagine you're registering users on January 15, 2025:

1. **First Member Signs Up**

   - System generates: `MBR-25-0001`
   - Stored in database

2. **Second Member Signs Up**

   - System checks: "What's the last member ID?"
   - Finds: `MBR-25-0001`
   - Extracts sequence: `0001`
   - Adds 1: `0001 + 1 = 0002`
   - Generates: `MBR-25-0002`

3. **First Trainer Signs Up**

   - System checks: "What's the last trainer ID?"
   - Finds: Nothing (first trainer)
   - Starts at: `0001`
   - Generates: `TRN-25-0001`

4. **Third Member Signs Up**
   - System checks: "What's the last member ID?"
   - Finds: `MBR-25-0002`
   - Extracts sequence: `0002`
   - Adds 1: `0002 + 1 = 0003`
   - Generates: `MBR-25-0003`

**Key Point:** Each role (Member, Trainer, Admin) has its own separate sequence!

---

## Understanding the Code

### Main File: `includes/user_id_generator.php`

This file contains all the functions for generating and validating user IDs.

#### Function 1: `getRolePrefix($role)`

**What it does:** Converts role name to prefix code.

```php
function getRolePrefix($role) {
    $prefixes = [
        'member' => 'MBR',
        'trainer' => 'TRN',
        'admin' => 'ADM'
    ];

    return $prefixes[strtolower($role)] ?? 'MBR';
}
```

**Example Usage:**

```php
getRolePrefix('member')  // Returns: 'MBR'
getRolePrefix('trainer') // Returns: 'TRN'
getRolePrefix('admin')   // Returns: 'ADM'
getRolePrefix('unknown') // Returns: 'MBR' (default fallback)
```

---

#### Function 2: `generateFormattedUserId($conn, $role)`

**What it does:** Generates the next available user ID for a specific role.

**Step-by-Step Breakdown:**

```php
function generateFormattedUserId($conn, $role) {
    // STEP 1: Get the prefix (MBR, TRN, or ADM)
    $prefix = getRolePrefix($role);

    // STEP 2: Get current year (25 for 2025)
    $year = date('y');

    // STEP 3: Create search pattern
    // Example: "MBR-25-%" (finds all members from 2025)
    $pattern = $prefix . '-' . $year . '-%';

    // STEP 4: Query database for last ID
    // FOR UPDATE = locks the row so nobody else can read it
    // This prevents duplicate IDs (more on this later!)
    $sql = "SELECT id FROM users WHERE id LIKE ? ORDER BY id DESC LIMIT 1 FOR UPDATE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();

    // STEP 5: Calculate next sequence number
    if ($row = $result->fetch_assoc()) {
        // Found existing ID: MBR-25-0012
        $lastId = $row['id'];

        // Split by dash: ['MBR', '25', '0012']
        $parts = explode('-', $lastId);

        // Get last part and convert to number: 0012 → 12
        $lastSequence = intval($parts[2]);

        // Add 1: 12 + 1 = 13
        $nextSequence = $lastSequence + 1;
    } else {
        // No existing ID found, start at 1
        $nextSequence = 1;
    }

    // STEP 6: Format and return
    // sprintf('%04d', 13) = '0013' (4 digits with leading zeros)
    return sprintf('%s-%s-%04d', $prefix, $year, $nextSequence);
    // Returns: MBR-25-0013
}
```

**Visual Example:**

```
Database State:
┌─────────────┬──────────┐
│ User ID     │ Username │
├─────────────┼──────────┤
│ MBR-25-0001 │ John     │
│ MBR-25-0002 │ Sarah    │
│ TRN-25-0001 │ Mike     │
└─────────────┴──────────┘

New member "Emma" signs up:
1. Query: "SELECT id FROM users WHERE id LIKE 'MBR-25-%' ORDER BY id DESC LIMIT 1"
2. Result: MBR-25-0002
3. Extract: 0002 → 2
4. Add 1: 2 + 1 = 3
5. Format: MBR-25-0003
6. Emma gets ID: MBR-25-0003
```

---

#### Function 3: `isValidFormattedUserId($userId)`

**What it does:** Checks if a user ID is in the correct format.

```php
function isValidFormattedUserId($userId) {
    // Pattern: {MBR|TRN|ADM}-{YY}-{NNNN}
    $pattern = '/^(MBR|TRN|ADM)-\d{2}-\d{4}$/';

    return preg_match($pattern, $userId) === 1;
}
```

**Examples:**

```php
isValidFormattedUserId('MBR-25-0001')  // ✅ TRUE
isValidFormattedUserId('TRN-25-0012')  // ✅ TRUE
isValidFormattedUserId('ADM-25-0001')  // ✅ TRUE
isValidFormattedUserId('MBR-25-1')     // ❌ FALSE (sequence must be 4 digits)
isValidFormattedUserId('XYZ-25-0001')  // ❌ FALSE (invalid prefix)
isValidFormattedUserId('MBR-2025-001') // ❌ FALSE (wrong year format)
isValidFormattedUserId('123')          // ❌ FALSE (old format)
```

---

#### Function 4: `getRoleFromUserId($userId)`

**What it does:** Extracts the role from a user ID.

```php
function getRoleFromUserId($userId) {
    if (!isValidFormattedUserId($userId)) {
        return false;
    }

    $parts = explode('-', $userId);
    $prefix = $parts[0];

    $roles = [
        'MBR' => 'member',
        'TRN' => 'trainer',
        'ADM' => 'admin'
    ];

    return $roles[$prefix] ?? false;
}
```

**Examples:**

```php
getRoleFromUserId('MBR-25-0001')  // Returns: 'member'
getRoleFromUserId('TRN-25-0005')  // Returns: 'trainer'
getRoleFromUserId('ADM-25-0001')  // Returns: 'admin'
getRoleFromUserId('invalid-id')   // Returns: false
```

---

## Race Condition Problem & Solution

### 🚨 The Problem: Duplicate IDs

Imagine two people signing up at the **exact same time**:

```
Timeline:
┌─────────────────────────────────────────────────────────┐
│ Time: 10:00:00.000                                      │
├─────────────────────────────────────────────────────────┤
│ User A clicks "Sign Up" → Starts registration          │
│ User B clicks "Sign Up" → Starts registration          │
│                                                         │
│ Time: 10:00:00.100                                      │
│ User A: Checks last ID → Finds MBR-25-0011            │
│ User B: Checks last ID → Finds MBR-25-0011            │ ⚠️ BOTH SEE SAME ID
│                                                         │
│ Time: 10:00:00.200                                      │
│ User A: Generates MBR-25-0012                          │
│ User B: Generates MBR-25-0012                          │ ⚠️ DUPLICATE!
│                                                         │
│ Time: 10:00:00.300                                      │
│ User A: Tries to insert → SUCCESS ✅                   │
│ User B: Tries to insert → ERROR ❌                     │
│         "Duplicate entry 'MBR-25-0012' for key PRIMARY"│
└─────────────────────────────────────────────────────────┘
```

**Result:** User B gets an error and can't sign up! 💥

---

### ✅ The Solution: Database Transactions + Locks

We use **transactions** and **row locks** to prevent this:

```php
// In sign-up.php
$conn->begin_transaction();  // 🔒 START TRANSACTION

try {
    // Generate ID with FOR UPDATE lock
    $userId = generateFormattedUserId($conn, $role);

    // Insert user
    $insertQuery->execute();

    $conn->commit();  // 🔓 RELEASE LOCK
} catch (Exception $e) {
    $conn->rollback();  // ⚠️ UNDO EVERYTHING
    // Show error to user
}
```

**How it works now:**

```
Timeline:
┌─────────────────────────────────────────────────────────┐
│ Time: 10:00:00.000                                      │
├─────────────────────────────────────────────────────────┤
│ User A clicks "Sign Up" → Starts transaction 🔒        │
│ User B clicks "Sign Up" → Starts transaction 🔒        │
│                                                         │
│ Time: 10:00:00.100                                      │
│ User A: Locks row with FOR UPDATE                      │
│         Checks last ID → MBR-25-0011                   │
│ User B: Tries to lock... ⏳ WAITING (blocked by A)     │
│                                                         │
│ Time: 10:00:00.200                                      │
│ User A: Generates MBR-25-0012                          │
│ User A: Inserts successfully                           │
│ User B: Still waiting... ⏳                             │
│                                                         │
│ Time: 10:00:00.300                                      │
│ User A: Commits and releases lock 🔓                   │
│ User B: NOW can proceed! ✅                            │
│         Checks last ID → MBR-25-0012 (updated!)        │
│                                                         │
│ Time: 10:00:00.400                                      │
│ User B: Generates MBR-25-0013                          │
│ User B: Inserts successfully ✅                        │
│ User B: Commits and releases lock 🔓                   │
└─────────────────────────────────────────────────────────┘
```

**Result:** Both users get unique IDs! 🎉

---

### Understanding Transactions

Think of a transaction like a **todo list that you can undo**:

```php
// START: Begin transaction
$conn->begin_transaction();

// STEP 1: Generate ID
$userId = generateFormattedUserId($conn, $role);

// STEP 2: Insert user
$stmt->execute();

// STEP 3: Send email
$mail->send();

// If ALL steps succeed:
$conn->commit();  // ✅ Save everything

// If ANY step fails:
$conn->rollback();  // ❌ Undo everything
```

**Real-world analogy:**

- Buying a car: Test drive → Negotiate price → Sign papers → Pay
- If you change your mind at ANY step, you can walk away (rollback)
- Only when you complete ALL steps do you get the car (commit)

---

### Understanding `FOR UPDATE` Lock

```sql
SELECT id FROM users WHERE id LIKE 'MBR-25-%' ORDER BY id DESC LIMIT 1 FOR UPDATE
```

**What `FOR UPDATE` does:**

- 🔒 **Locks** the row(s) returned by the query
- ⏳ Other queries with `FOR UPDATE` must **wait**
- 🔓 Lock is released when transaction ends (commit or rollback)

**Without `FOR UPDATE`:**

```
User A: SELECT last ID → 0011
User B: SELECT last ID → 0011  ⚠️ Can read at same time
Both generate 0012 → DUPLICATE ❌
```

**With `FOR UPDATE`:**

```
User A: SELECT ... FOR UPDATE → 0011 (🔒 locked)
User B: SELECT ... FOR UPDATE → ⏳ waiting...
User A: INSERT 0012 and COMMIT (🔓 unlocked)
User B: SELECT ... FOR UPDATE → 0012 (now can read)
User B: INSERT 0013 ✅
```

---

## How to Use the System

### For Registration (sign-up.php)

```php
// 1. Include the generator file
require_once '../../includes/user_id_generator.php';

// 2. Start a transaction
$conn->begin_transaction();

try {
    // 3. Generate user ID
    $role = 'member';  // or 'trainer' or 'admin'
    $userId = generateFormattedUserId($conn, $role);

    // 4. Insert user with generated ID
    $stmt = $conn->prepare("INSERT INTO users (id, username, email, ...) VALUES (?, ?, ?, ...)");
    $stmt->bind_param("sss...", $userId, $username, $email, ...);

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert user");
    }

    // 5. Commit transaction
    $conn->commit();

    echo "User created with ID: $userId";

} catch (Exception $e) {
    // 6. Rollback on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
```

---

### For Bulk Registration

```php
// Generate multiple IDs at once (for testing or bulk import)
$role = 'member';
$count = 10;  // Generate 10 IDs

$userIds = generateBulkFormattedUserIds($conn, $role, $count);

// Result:
// ['MBR-25-0001', 'MBR-25-0002', 'MBR-25-0003', ..., 'MBR-25-0010']
```

---

### For Validation

```php
// Check if an ID is valid before using it
$userId = 'MBR-25-0001';

if (isValidFormattedUserId($userId)) {
    echo "Valid ID!";

    // Get the role
    $role = getRoleFromUserId($userId);
    echo "Role: $role";  // Outputs: member
} else {
    echo "Invalid ID format!";
}
```

---

## Troubleshooting

### Problem: "Duplicate entry for key 'PRIMARY'"

**Cause:** The transaction wasn't used properly.

**Solution:**

```php
// ❌ WRONG - No transaction
$userId = generateFormattedUserId($conn, $role);
$stmt->execute();

// ✅ CORRECT - With transaction
$conn->begin_transaction();
try {
    $userId = generateFormattedUserId($conn, $role);
    $stmt->execute();
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
}
```

---

### Problem: "Undefined function generateFormattedUserId"

**Cause:** The file wasn't included.

**Solution:**

```php
// Add this at the top of your file
require_once __DIR__ . '/../../includes/user_id_generator.php';
```

---

### Problem: IDs not incrementing correctly

**Cause:** Wrong pattern or LIKE query issue.

**Debug:**

```php
// Add debugging
$prefix = getRolePrefix($role);
$year = date('y');
$pattern = $prefix . '-' . $year . '-%';

echo "Looking for pattern: $pattern<br>";

$sql = "SELECT id FROM users WHERE id LIKE '$pattern' ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($row = $result->fetch_assoc()) {
    echo "Last ID found: " . $row['id'];
} else {
    echo "No IDs found, starting at 0001";
}
```

---

### Problem: Year doesn't update automatically

**Cause:** This is expected behavior! The year is based on `date('y')`.

**What happens on January 1, 2026:**

- Old IDs: `MBR-25-0001`, `MBR-25-0002`, ...
- New IDs: `MBR-26-0001`, `MBR-26-0002`, ...
- Each year starts a fresh sequence! ✅

---

## Technical Deep Dive

### Database Requirements

**Table structure:**

```sql
CREATE TABLE users (
    id VARCHAR(15) PRIMARY KEY,  -- 'MBR-25-0001' needs 12 chars, we use 15 for safety
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('member', 'trainer', 'admin') NOT NULL,
    ...
);
```

**Important:**

- `id` must be **VARCHAR**, not INT
- `id` must be **PRIMARY KEY** (enforces uniqueness)
- Length must accommodate format (12 chars minimum)

---

### bind_param Type Change

**Old system (integer IDs):**

```php
$stmt->bind_param("i", $user_id);  // "i" = integer
```

**New system (formatted IDs):**

```php
$stmt->bind_param("s", $user_id);  // "s" = string
```

**Why this matters:**
If you use `"i"` with a formatted ID:

```php
$user_id = "MBR-25-0001";
$stmt->bind_param("i", $user_id);  // ❌ Converts to 0 (zero)!
```

Result: Query fails silently or returns wrong data.

**We fixed 32 instances across 15 files in this codebase!**

---

### Performance Considerations

**Query performance:**

```sql
-- GOOD: Uses index on primary key
SELECT id FROM users WHERE id LIKE 'MBR-25-%' ORDER BY id DESC LIMIT 1 FOR UPDATE

-- BAD: Full table scan (avoid this)
SELECT * FROM users ORDER BY id DESC LIMIT 1
```

**Locking duration:**

- Keep transactions **short**
- Don't do slow operations (like sending emails) inside the transaction
- Release locks ASAP with `commit()` or `rollback()`

---

### Security Considerations

1. **SQL Injection Prevention:**

   ```php
   // ✅ SAFE - Using prepared statements
   $stmt = $conn->prepare("SELECT id FROM users WHERE id LIKE ?");
   $stmt->bind_param("s", $pattern);
   ```

2. **Input Validation:**

   ```php
   // Always validate role before using
   $allowedRoles = ['member', 'trainer', 'admin'];
   if (!in_array($role, $allowedRoles)) {
       throw new Exception("Invalid role");
   }
   ```

3. **Error Handling:**
   ```php
   // Don't expose internal errors to users
   try {
       $userId = generateFormattedUserId($conn, $role);
   } catch (Exception $e) {
       // Log internally
       error_log($e->getMessage());

       // Show user-friendly message
       echo "Registration failed. Please try again.";
   }
   ```

---

## Summary

### Key Takeaways

1. **Format:** `{PREFIX}-{YEAR}-{SEQUENCE}`

   - PREFIX: MBR, TRN, ADM
   - YEAR: Last 2 digits (25 = 2025)
   - SEQUENCE: 4-digit number (0001, 0002, ...)

2. **Always use transactions** to prevent duplicate IDs

3. **Use `FOR UPDATE`** to lock rows during ID generation

4. **Bind as string** (`"s"`) not integer (`"i"`)

5. **Each role has separate sequences** (MBR-25-0001 and TRN-25-0001 can coexist)

6. **Sequences reset yearly** automatically

### Quick Reference

```php
// Include file
require_once 'includes/user_id_generator.php';

// Generate ID
$conn->begin_transaction();
$userId = generateFormattedUserId($conn, 'member');
// ... do insert ...
$conn->commit();

// Validate ID
if (isValidFormattedUserId($userId)) {
    $role = getRoleFromUserId($userId);
}
```

---

## Need Help?

If you encounter issues:

1. Check the [Troubleshooting](#troubleshooting) section
2. Verify transactions are being used
3. Confirm database column is VARCHAR(15)
4. Check all bind_param use "s" not "i"
5. Review error logs for specific issues

---

**Last Updated:** November 13, 2025  
**System Version:** 3.0  
**Files Involved:**

- `includes/user_id_generator.php`
- `public/php/sign-up.php`
- All files using `bind_param` with user_id (32 fixes applied)
