# Testing Guide: Membership Expiration Booking Restrictions

## Setup
1. Login as user: **John Doe** (user_id: 7)
2. Use the SQL file: `test-membership-expiration.sql`
3. Navigate to: `http://localhost/fit-brawl/public/php/reservations.php`

---

## Test Scenarios

### **SCENARIO 1: Expiring in 2 Days** ⚠️
**SQL to Run:**
```sql
UPDATE user_memberships 
SET end_date = '2025-11-12'
WHERE user_id = 7;
```

**Expected Results:**
- ✅ Warning banner appears: "Membership Expiring Soon"
- ✅ Shows: "5 days remaining including grace period"
- ✅ Calendar dates AFTER Nov 15 are disabled (red, strikethrough)
- ✅ Can book up to Nov 15, 2025
- ✅ Clicking disabled date shows toast: "Cannot book beyond your membership expiration..."

---

### **SCENARIO 2: Expiring Tomorrow** 🚨
**SQL to Run:**
```sql
UPDATE user_memberships 
SET end_date = '2025-11-11'
WHERE user_id = 7;
```

**Expected Results:**
- ✅ Warning banner appears
- ✅ Shows: "4 days remaining including grace period"
- ✅ Calendar dates AFTER Nov 14 are disabled
- ✅ Can only book until Nov 14, 2025

---

### **SCENARIO 3: Expired Today (Grace Period Active)** 🔴
**SQL to Run:**
```sql
UPDATE user_memberships 
SET end_date = '2025-11-10'
WHERE user_id = 7;
```

**Expected Results:**
- ✅ Warning banner appears
- ✅ Shows: "3 days remaining including grace period"
- ✅ Calendar dates AFTER Nov 13 are disabled
- ✅ Can only book until Nov 13, 2025 (grace period)

---

### **SCENARIO 4: Grace Period Ending Soon** ⏰
**SQL to Run:**
```sql
UPDATE user_memberships 
SET end_date = '2025-11-09'
WHERE user_id = 7;
```

**Expected Results:**
- ✅ Warning banner appears
- ✅ Shows: "2 days remaining including grace period"
- ✅ Calendar dates AFTER Nov 12 are disabled
- ✅ Can only book until Nov 12, 2025

---

### **SCENARIO 5: Grace Period Passed** ❌
**SQL to Run:**
```sql
UPDATE user_memberships 
SET end_date = '2025-11-05'
WHERE user_id = 7;
```

**Expected Results:**
- ✅ Warning banner appears
- ✅ ALL future dates are disabled (grace period ended Nov 8)
- ✅ Cannot book any sessions
- ✅ Clicking any date shows expiration message

---

### **SCENARIO 6: Long-Term Valid Membership** ✅
**SQL to Run:**
```sql
UPDATE user_memberships 
SET end_date = '2025-11-30'
WHERE user_id = 7;
```

**Expected Results:**
- ❌ NO warning banner (more than 7 days remaining)
- ✅ Can book up to Dec 3, 2025 (Nov 30 + 3 days grace)
- ✅ Normal booking experience
- ✅ Only dates after Dec 3 are disabled

---

## How to Test

### **Step 1: Run SQL**
1. Open phpMyAdmin or your MySQL client
2. Select the `fitbrawl` database
3. Copy ONE scenario's SQL from `test-membership-expiration.sql`
4. Execute the UPDATE query

### **Step 2: Refresh Browser**
1. Go to reservations page: `http://localhost/fit-brawl/public/php/reservations.php`
2. Hard refresh: `Ctrl + Shift + R` (to clear cache)

### **Step 3: Check Visual Elements**
- [ ] Warning banner (if applicable)
- [ ] Calendar dates styling
- [ ] Disabled dates have red tint + strikethrough

### **Step 4: Test Interaction**
- [ ] Click on a normal date → Should select
- [ ] Click on a disabled date (past expiration + grace) → Should show toast notification
- [ ] Try to proceed with booking → Backend should validate

### **Step 5: Test Backend Validation**
1. Open browser DevTools (F12)
2. Go to Network tab
3. Try to book a session on a disabled date (if you bypass frontend)
4. Check API response from `book_session.php`
5. Should return error: `"failed_check": "membership_expiration"`

---

## Verification Queries

**See current status:**
```sql
SELECT 
    um.user_id,
    u.username,
    um.plan_name,
    um.end_date,
    DATE_ADD(um.end_date, INTERVAL 3 DAY) as 'Max Booking Date',
    DATEDIFF(um.end_date, CURDATE()) as 'Days Until Expiration',
    DATEDIFF(DATE_ADD(um.end_date, INTERVAL 3 DAY), CURDATE()) as 'Days Until Grace Ends'
FROM user_memberships um
JOIN users u ON um.user_id = u.id
WHERE um.user_id = 7;
```

---

## Restore Original Data

**When done testing:**
```sql
UPDATE user_memberships 
SET end_date = '2025-12-10'
WHERE user_id = 7;
```

---

## Expected Calendar Behavior

| Scenario | End Date | Max Booking Date | Disabled Dates |
|----------|----------|------------------|----------------|
| Scenario 1 | Nov 12 | Nov 15 | After Nov 15 |
| Scenario 2 | Nov 11 | Nov 14 | After Nov 14 |
| Scenario 3 | Nov 10 | Nov 13 | After Nov 13 |
| Scenario 4 | Nov 9 | Nov 12 | After Nov 12 |
| Scenario 5 | Nov 5 | Nov 8 | ALL future dates |
| Scenario 6 | Nov 30 | Dec 3 | After Dec 3 |

---

## Troubleshooting

**If changes don't appear:**
1. Hard refresh browser: `Ctrl + Shift + R`
2. Clear browser cache
3. Check browser console for JavaScript errors
4. Verify SQL update worked:
   ```sql
   SELECT * FROM user_memberships WHERE user_id = 7;
   ```

**If backend validation doesn't work:**
1. Check `book_session.php` for errors
2. Look at Network tab in DevTools
3. Check server error logs in XAMPP

---

## Success Criteria ✅

All these should work:
- [x] Warning banner shows when expiring ≤7 days
- [x] Calendar visually disables dates past expiration + grace
- [x] Toast notification appears when clicking disabled dates
- [x] Backend rejects bookings past expiration + grace
- [x] Grace period (3 days) is applied consistently
- [x] Different scenarios show appropriate date ranges
