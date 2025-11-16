# User Page Documentation: Change Password Page (change-password.php)

**Page Name**: Change Password / Password Reset
**Who Can Access**: Users who completed OTP verification
**Last Updated**: November 10, 2025

---

## What This Page Does

The change password page is the **final step** in the password reset process. It:

1. **Sets New Password** - Allows you to create a new password
2. **Validates Requirements** - Ensures strong password (real-time feedback)
3. **Prevents Reuse** - Blocks using same password as current one
4. **Confirms Match** - Verifies password confirmation matches
5. **Completes Reset** - Updates database and redirects to login

**Note**: This page is accessed **only after OTP verification**, not directly.

---

## How You Get Here

### Password Reset Flow

```
forgot-password.php → verification.php → change-password.php (this page) → login.php
```

**Step-by-Step**:
1. Forgot password? → Enter email
2. Verify OTP code from email
3. **Arrive here** to set new password
4. Create new password
5. Redirect to login with new password

**Required**: Must have `$_SESSION['reset_email']` from verification process

**If Direct Access**: Redirects to `forgot-password.php` (no email in session)

---

## Page Sections

### 1. Hero Section

**What You See**:
- Background image
- Motivational headline: **"STRONG TODAY STRONGER TOMORROW"**
- Yellow accent on "STRONGER"
- Subtitle: "A LITTLE STEP BACK BEFORE THE BEST VERSION OF YOU!"

**Purpose**: Encouraging message during password reset

---

### 2. Change Password Form

**Form Fields**:

| Field | Type | Required | Features |
|-------|------|----------|----------|
| **New Password** | Password input | Yes | Show/hide toggle, real-time validation |
| **Re-enter Password** | Password input | Yes | Show/hide toggle, match checker |

**Interactive Elements**:
- Eye icon (👁️) to show/hide password
- Password requirements panel (real-time)
- Password strength indicator
- Password match message
- Change Password button
- Cancel button (links to user_profile.php)

---

## Password Requirements (Real-Time Validation)

### Requirements Checklist

**When typing password, panel shows**:

| Requirement | Pattern | Example |
|-------------|---------|---------|
| **At least 12 characters** | Minimum length | "Password1234" ✓ |
| **One uppercase letter** | A-Z | "Password" has P ✓ |
| **One lowercase letter** | a-z | "Password" has assword ✓ |
| **One number** | 0-9 | "Password1" has 1 ✓ |
| **One special character** | !@#$%^&*? | "Password1!" has ! ✓ |

**Visual Feedback**:
- ✗ (red) = Requirement not met
- ✓ (green) = Requirement met
- Updates instantly as you type

### Password Strength Indicator

**Three Levels**:

| Strength | Requirements Met | Bar Color | Message |
|----------|------------------|-----------|---------|
| **Weak** | 0-2 requirements | Red | "Strength: Weak" |
| **Medium** | 3 requirements | Orange | "Strength: Medium" |
| **Strong** | 4-5 requirements | Green | "Strength: Strong" |

**Progress Bar**:
- Visual bar fills based on requirements met
- Color changes with strength level
- Encourages stronger passwords

---

## Password Confirmation

### Match Checker

**As you type in "Re-enter Password" field**:

**If passwords match**:
- Green message: ✓ "Passwords match"

**If passwords don't match**:
- Red message: ✗ "Passwords do not match"

**If field is empty**:
- No message shown

**Technical**: Uses JavaScript `input` event to compare in real-time

---

## Show/Hide Password Feature

### Eye Icon Toggle

**Both Password Fields**:
- Click eye icon (👁️) next to password field
- **Hidden**: Dots (●●●●●●) with closed eye icon
- **Visible**: Plain text with open eye icon

**Independent Toggle**:
- New password and confirm password toggle separately
- Each has its own eye icon

**How It Works**:
- Switches input `type` between `password` and `text`
- Icon toggles between `fa-eye` and `fa-eye-slash`

---

## Server-Side Validation

### When You Click "Change Password"

**Validation Steps**:

```
1. Form submits (POST request)
   ↓
2. Server receives new password + confirmation
   ↓
3. Check 1: Passwords match?
   ↓
4. Check 2: Meets all requirements?
   ↓
5. Check 3: Different from current password?
   ↓
6. All pass → Hash password
   ↓
7. Update database
   ↓
8. Clear reset session
   ↓
9. Redirect to login.php
```

---

### Validation Check 1: Password Match

**What's Checked**:
```php
if ($new_password !== $confirm_password)
```

**Error**: "Passwords do not match!"

**Why**:
- Ensures you didn't make a typo
- Must type same password twice
- Exact character-for-character match

---

### Validation Check 2: Password Requirements

**Same Rules as Sign-Up**:
- Minimum 8 characters
- At least one uppercase letter (A-Z)
- At least one lowercase letter (a-z)
- At least one number (0-9)
- At least one special character (!@#$%^&*?)

**Error Examples**:
- "Password must be at least 12 characters long"
- "Password must contain at least one uppercase letter"
- "Password must contain at least one number"

**Multiple Errors**:
- If multiple requirements fail, all errors shown
- Separated by line breaks
- Example: "Password must be at least 12 characters long<br>Password must contain at least one number"

---

### Validation Check 3: Different from Current Password

**Unique Check**:
1. Retrieves your current password hash from database
2. Uses `password_verify()` to check if new password matches old hash
3. If match → Error: "New password must be different from your current password."

**Why This Matters**:
- Prevents "forgetting" same password
- Encourages actual password change
- Security best practice

**Technical Process**:
```php
SELECT password FROM users WHERE email = ?
password_verify($new_password, $current_hash)
If true → Reject
```

**Example**:
- Old password: "OldPass123!"
- Try to set new: "OldPass123!"
- System detects match and rejects

---

## Password Hashing

### How New Password is Stored

**Not Plain Text**:
```php
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
```

**Algorithm**: Bcrypt (PHP default)
- Industry-standard encryption
- Irreversible hash
- Includes random salt
- Example hash: `$2y$10$eImiTXuZrF...`

**Database Update**:
```sql
UPDATE users SET password = ? WHERE email = ?
```
- Only hashed version stored
- Even database admins can't see your actual password
- Same hashing as during sign-up

---

## Success Flow

### After Successful Password Change

**What Happens**:

```
1. Password updated in database
   ↓
2. Clear $_SESSION['reset_email']
   ↓
3. Set $_SESSION['password_changed'] = true
   ↓
4. Redirect to login.php
   ↓
5. Login page can show success message
   ↓
6. Log in with new password
```

**Session Cleanup**:
- `reset_email` removed (flow complete)
- `password_changed` flag set (for success message)
- OTP already cleared in verification step

**Next Step**: Log in with your new password

---

## Error Messages

### Common Errors

**Error 1: "Passwords do not match!"**

**Cause**:
- Typed different passwords in two fields
- Typo in confirmation field

**Fix**:
- Retype both passwords carefully
- Use show/hide toggle to verify
- Copy/paste not recommended (defeats purpose)

---

**Error 2: "Password must be at least 12 characters long"**

**Cause**:
- Password too short (7 or fewer characters)

**Fix**:
- Add more characters to reach minimum 8
- Example: "Pass1!" (6) → "Password1!" (10) ✓

---

**Error 3: "Password must contain at least one uppercase letter"**

**Cause**:
- All lowercase or no letters
- Example: "password123!" (no uppercase)

**Fix**:
- Add capital letter
- Example: "password123!" → "Password123!" ✓

---

**Error 4: "Password must contain at least one number"**

**Cause**:
- No digits 0-9 in password
- Example: "Password!" (no number)

**Fix**:
- Add at least one digit
- Example: "Password!" → "Password1!" ✓

---

**Error 5: "Password must contain at least one special character (!@#$%^&*?)"**

**Cause**:
- No special characters
- Example: "Password123" (no special char)

**Fix**:
- Add one of: ! @ # $ % ^ & * ?
- Example: "Password123" → "Password123!" ✓

---

**Error 6: "New password must be different from your current password."**

**Cause**:
- Trying to reset to same password you already have
- System detected match with current password

**Fix**:
- Choose a completely different password
- Change at least a few characters
- Don't just add/remove one character

---

**Error 7: "Error updating password. Please try again."**

**Cause**:
- Database update failed
- Connection issue
- SQL error

**Fix**:
- Try again
- Refresh page
- Check internet connection
- Contact support if persists

---

## Security Features

### What Protects This Page

**1. Session Requirement**
- Must have `reset_email` in session
- Can only access after OTP verification
- Prevents arbitrary password changes

**2. Email in Session (Not URL)**
- Email stored server-side in session
- Not visible in URL
- Can't manipulate which account to change

**3. Password Hashing**
- New password hashed with bcrypt
- Same security as initial sign-up
- Database admins can't see password

**4. Double Validation**
- Client-side (JavaScript) - instant feedback
- Server-side (PHP) - final security check
- Can't bypass by disabling JavaScript

**5. Current Password Check**
- Prevents setting same password
- Forces actual change
- Uses secure password_verify()

**6. Input Sanitization**
- Passwords sanitized before processing
- Uses `trim()`, `stripslashes()`, `htmlspecialchars()`
- Prevents injection attacks

**7. Session Clearing**
- Reset email removed after success
- Prevents reusing reset flow
- One-time password change per verification

---

## Technical Details

### Files Involved

**Main PHP File**: `change-password.php`
- Validates password requirements
- Checks against current password
- Updates database
- Manages session

**JavaScript**: `password-validation.js` (shared with sign-up)
- Real-time requirement checking
- Password strength indicator
- Match validation
- Show/hide toggle

**CSS Files**:
1. `change-password.css` - Page-specific styles
2. `sign-up.css` - Shared password requirement styles
3. `terms-modal.css` - Component styles

### Database Update

**Table**: `users`

**Column Updated**: `password`

**SQL Query**:
```sql
UPDATE users
SET password = '$2y$10$hashed_password_here'
WHERE email = 'user@example.com'
```

**Prepared Statement**: Uses bound parameters to prevent SQL injection

### Session Variables

**Before This Page**:
- `$_SESSION['reset_email']` - Email being reset (from forgot-password flow)

**After Success**:
- `$_SESSION['reset_email']` - Removed/unset
- `$_SESSION['password_changed']` - Set to true (for login page message)

---

## JavaScript Functionality

### Reused from Sign-Up Page

**password-validation.js** provides:

**1. Real-Time Requirement Checking**
- Monitors password input
- Updates checkmarks (✗ → ✓)
- Uses regex patterns for each rule

**2. Strength Indicator**
- Counts requirements met
- Updates progress bar
- Changes color (red/orange/green)

**3. Password Match Checker**
- Compares both password fields
- Shows match/mismatch message
- Updates on every keystroke

**4. Show/Hide Password**
- Toggles input type
- Changes eye icon
- Works for both fields

**Technical**: Same code as sign-up page, no duplication

---

## Cancel Button

### Alternative Exit

**What It Does**:
- Link styled as button
- Text: "Cancel"
- Destination: `user_profile.php`

**When Clicked**:
- Does NOT change password
- Abandons reset process
- Goes to user profile page

**Use Cases**:
- Changed your mind
- Remembered old password
- Want to try logging in first

**Note**: Session data (`reset_email`) remains until logout or expiry

---

## Common User Scenarios

### Scenario 1: Successful Password Change

```
1. Arrive from verification.php (OTP verified)
   ↓
2. Type new password: "NewPassword123!"
   ↓
3. Requirements: All ✓ (green)
   ↓
4. Strength: Strong (green bar)
   ↓
5. Confirm password: "NewPassword123!"
   ↓
6. Match message: ✓ "Passwords match"
   ↓
7. Click "Change Password"
   ↓
8. Server validates: All checks pass
   ↓
9. Different from old password: Yes
   ↓
10. Database updated
   ↓
11. Redirected to login.php
   ↓
12. Log in with new password
```

**Time**: ~2 minutes

---

### Scenario 2: Password Requirements Not Met

```
1. Type weak password: "pass"
   ↓
2. Requirements panel shows:
   - ✗ At least 12 characters (only 4)
   - ✗ One uppercase letter (none)
   - ✗ One number (none)
   - ✗ One special character (none)
   ✓ One lowercase letter (has lowercase)
   ↓
3. Strength: Weak (red bar)
   ↓
4. Confirm: "pass"
   ↓
5. Match: ✓ "Passwords match"
   ↓
6. Click "Change Password"
   ↓
7. Server error: Lists all missing requirements
   ↓
8. User sees errors, strengthens password
   ↓
9. Try again with: "Password123!"
   ↓
10. All requirements ✓
   ↓
11. Success!
```

---

### Scenario 3: Passwords Don't Match

```
1. Type new password: "Password123!"
   ↓
2. All requirements: ✓
   ↓
3. Confirm password: "Password124!" (typo)
   ↓
4. Match message: ✗ "Passwords do not match"
   ↓
5. Click "Change Password" anyway
   ↓
6. Server error: "Passwords do not match!"
   ↓
7. User notices typo
   ↓
8. Retype confirmation: "Password123!"
   ↓
9. Match: ✓ "Passwords match"
   ↓
10. Submit again
   ↓
11. Success!
```

---

### Scenario 4: Trying to Use Same Password

```
1. Old password was: "OldPass123!"
   ↓
2. Try to set new: "OldPass123!" (same)
   ↓
3. Client validation: All ✓
   ↓
4. Confirmation matches: ✓
   ↓
5. Click "Change Password"
   ↓
6. Server checks against current password
   ↓
7. Detects match with current hash
   ↓
8. Error: "New password must be different from your current password."
   ↓
9. User creates different password: "NewPass123!"
   ↓
10. Submit again
   ↓
11. Success!
```

---

### Scenario 5: Using Show/Hide Password

```
1. Type new password: "P@ssw0rd!" (hidden dots)
   ↓
2. Click eye icon to show
   ↓
3. See: "P@ssw0rd!" in plain text
   ↓
4. Verify it's correct
   ↓
5. Click eye again to hide
   ↓
6. Back to dots: ●●●●●●●●●
   ↓
7. Type confirmation (still hidden)
   ↓
8. Click eye on confirmation field
   ↓
9. Verify both match visually
   ↓
10. Hide both
   ↓
11. Submit successfully
```

---

## Known Limitations

### Current Issues

**1. No Password History**
- Only checks against current password
- Doesn't prevent reusing very old passwords
- **Improvement**: Store last 5 password hashes

**2. No Strength Enforcement**
- Can submit with "Weak" rating
- Only requirements enforced, not strength
- **Improvement**: Require "Medium" or "Strong"

**3. Cancel Goes to Profile**
- Assumes user has account/is logged in
- May cause confusion if not logged in
- **Improvement**: Check session, redirect to login if needed

**4. No Success Message on Login Page**
- `password_changed` flag set but not used
- Login page doesn't show confirmation
- **Improvement**: Display "Password changed successfully" on login

**5. No Email Confirmation**
- No email sent after password change
- User isn't notified of account activity
- **Improvement**: Send "Password changed" confirmation email

**6. Session Persists if Cancelled**
- `reset_email` remains in session
- Could access page again without new OTP
- **Improvement**: Clear session on cancel

**7. No Rate Limiting**
- Unlimited password change attempts
- Could try to brute force current password check
- **Improvement**: Limit to 5 attempts per hour

---

## Mobile Responsiveness

### How It Adapts

**Mobile Phones** (< 768px):
- Form takes full width
- Password requirements panel stacks
- Larger input fields for touch
- Eye icons remain accessible
- Buttons stack vertically

**Tablets** (768px - 1023px):
- Centered modal
- Similar to desktop
- Touch-friendly buttons

**Desktop** (1024px+):
- Centered modal box
- Hover effects
- Standard keyboard navigation

---

## Accessibility Features

### Keyboard Navigation

**Tab Order**:
1. New password field
2. Show/hide toggle (new password)
3. Confirm password field
4. Show/hide toggle (confirm)
5. Change Password button
6. Cancel button

**Enter Key**:
- Submits form
- Same as clicking "Change Password"

### Screen Reader Support

**Labels**:
- All inputs properly labeled
- Error messages announced
- Success messages announced

**Icons**:
- Checkmarks have text equivalents
- Eye icons described as "toggle visibility"

---

## Best Practices for Users

### ✓ Do This

- **Create strong, unique password** - Don't reuse from other sites
- **Use password manager** - Save securely
- **Check all requirements** - Ensure all green checkmarks
- **Verify confirmation matches** - Use show/hide to check
- **Choose different password** - Don't use old one
- **Remember new password** - Write it down securely if needed

### ✗ Avoid This

- **Don't use same password** - Must be different from old
- **Don't make it too simple** - Meet all requirements
- **Don't submit without checking** - Verify match first
- **Don't cancel unless sure** - Would need to restart flow
- **Don't share password** - Keep it private

---

## After Password Change

### What to Do Next

**Immediate**:
1. Redirected to login.php
2. Use new password to log in
3. Access your account normally

**Recommended**:
1. Update password in password manager
2. Remember new password
3. Don't write on paper (if possible)
4. Log in from trusted devices only

**Security Tips**:
- Don't reuse this password elsewhere
- Change password every 6 months
- Use unique password per site
- Enable two-factor auth (if available)

---

## Complete Password Reset Flow

### Full Journey Overview

```
1. Forgot password?
   ↓
2. forgot-password.php
   - Enter email
   - Email verified in database
   ↓
3. verification.php
   - OTP sent to email
   - Enter 6-digit code
   - Code verified (5 min timer)
   ↓
4. change-password.php (THIS PAGE)
   - Create new password
   - Meet all requirements
   - Different from old password
   ↓
5. login.php
   - Log in with new password
   - Access account
```

**Total Time**: 5-10 minutes

---

## Summary

### What This Page Does Well

**✓ Security**:
- Bcrypt password hashing
- Checks against current password
- Double validation (client + server)
- Session-based access control

**✓ User Experience**:
- Real-time feedback
- Visual strength indicator
- Password match checker
- Show/hide password toggle
- Clear error messages

**✓ Same Standards as Sign-Up**:
- Identical password requirements
- Same validation logic
- Consistent UX (shared components)
- No confusion about rules

### What Could Be Better

**⚠️ Missing Features**:
- No password history (last 5 passwords)
- No email confirmation of change
- No success message on login
- No strength enforcement (only requirements)

**⚠️ Edge Cases**:
- Cancel button assumes logged in
- Session not cleared on cancel
- No rate limiting on attempts

### Quick User Guide

1. **Create strong password** meeting all 5 requirements
2. **Check green checkmarks** - all must be ✓
3. **Confirm password** matches exactly
4. **Must differ** from old password
5. **Click "Change Password"** to save
6. **Log in** with new password

---

**Page Status**: ✅ Fully functional
**Part of**: Password reset flow (final step)
**Required for**: Completing forgot password process
**Security Level**: ✅ Strong (bcrypt hashing, double validation, session control)

**Documentation Version**: 1.0
**Last Updated**: November 10, 2025
