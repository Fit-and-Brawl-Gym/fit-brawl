# User Page Documentation: Login Page (login.php)

**Page Name**: Login / Sign In Page  
**Who Can Access**: Everyone (public, not logged in)  
**Last Updated**: November 10, 2025

---

## What This Page Does

The login page allows **existing users** to access their FitXBrawl accounts. It:

1. **Authenticates Users** - Verifies email and password
2. **Creates Sessions** - Keeps you logged in while browsing
3. **Routes by Role** - Sends different user types to appropriate dashboards
4. **Remembers Users** - Optional "Remember Me" feature
5. **Blocks Unverified Accounts** - Requires email verification before access

---

## Page Sections

### 1. Hero Section

**What You See**:
- Background image of gym/training
- Motivational headline: **"STRONG TODAY STRONGER TOMORROW"**
- Yellow accent on "STRONGER" for emphasis

**Purpose**: Motivational backdrop while logging in

---

### 2. Login Form

**Form Fields**:

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| **Email** | Email input | Yes | Your registered email address |
| **Password** | Password input | Yes | Your account password (hidden) |
| **Remember Me** | Checkbox | No | Keeps you logged in longer |

**Additional Options**:
- **"Forgot Password?"** link - Resets password if forgotten
- **"Create an account"** link - Goes to sign-up page for new users

---

## How Login Works

### Step-by-Step Process

```
1. User enters email and password
   ↓
2. User clicks "Log-in" button
   ↓
3. Server checks if email exists in database
   ↓
4. If email found → Verify password hash
   ↓
5. If password correct → Check email verification status
   ↓
6. If verified → Create session
   ↓
7. Set session variables (user_id, name, email, role, avatar)
   ↓
8. If "Remember Me" checked → Create remember token
   ↓
9. Redirect based on user role:
   - Admin → admin/admin.php
   - Trainer → trainer/schedule.php
   - Member → loggedin-index.php
```

---

## Password Verification

### How It Works Technically

**Not Plain Text Comparison**:
- Your password is NOT stored as plain text in database
- Database has a hashed version (e.g., `$2y$10$abc123...`)
- Login uses `password_verify()` function to compare

**Verification Process**:
```
1. You type: "MyPassword123!"
2. System fetches hash from database: "$2y$10$eImiTXu..."
3. password_verify() checks if they match
4. Returns true (correct) or false (incorrect)
```

**Why This Is Secure**:
- Even database admins can't see your actual password
- Hash cannot be reversed to get original password
- Each hash is unique (includes random salt)

---

## Email Verification Check

### Why It Matters

**Before Login**:
- System checks `is_verified` column in database
- Must be `1` (verified) to log in
- If `0` (unverified), login is blocked

**Error Message**:
- "Please verify your email before logging in."

**What to Do**:
1. Check your email inbox
2. Find verification email from FitXBrawl
3. Click the verification link
4. Return to login page
5. Try logging in again

**If Email Lost**:
- Contact gym support to resend verification
- Or request new account (if email unverified for long time)

---

## "Remember Me" Feature

### What It Does

**When Checked**:
- Creates a persistent token stored in database
- Keeps you logged in even after closing browser
- No need to log in again on next visit

**When Unchecked**:
- Session lasts only for current browser session
- Closing browser logs you out
- More secure for shared/public computers

### How It Works Technically

**Token Generation**:
1. Creates random 64-character token using `random_bytes(32)`
2. Hashes the token (like a password)
3. Stores hash in `remember_password` table

**Database Storage**:
```
Table: remember_password
- user_id: Your account ID
- token_hash: Hashed version of token
- created_at: When token was created
```

**Security Note**:
- Token is stored in your session (`$_SESSION['remember_password']`)
- Validated on future visits
- If token matches, auto-login happens

**Best Practice**:
- ✓ Use "Remember Me" on personal devices
- ✗ DON'T use on public/shared computers

---

## Role-Based Redirection

### Where You Go After Login

The system automatically sends you to the right place based on your account type:

| User Role | Redirects To | What You See |
|-----------|--------------|--------------|
| **Member** | `loggedin-index.php` | Member dashboard with bookings, profile, membership status |
| **Trainer** | `trainer/schedule.php` | Trainer dashboard to manage classes and schedules |
| **Admin** | `admin/admin.php` | Admin panel for full system management |

**Technical Implementation**:
- Role stored in database `users.role` column
- Retrieved during login and stored in `$_SESSION['role']`
- Used for redirect logic and access control throughout site

---

## Session Management

### What Sessions Do

**Session Variables Created**:
```
$_SESSION['email']  - Your email (login identifier)
$_SESSION['user_id'] - Your unique user ID
$_SESSION['name']    - Your username
$_SESSION['role']    - Your role (member/trainer/admin)
$_SESSION['avatar']  - Your profile picture filename
```

**Session Security Features**:

**1. Timeouts**:
- **Idle Timeout**: 15 minutes of inactivity logs you out
- **Absolute Timeout**: 10 hours maximum session length
- Prevents old sessions from staying active forever

**2. Session Regeneration**:
- Session ID regenerated on login
- Prevents session fixation attacks
- New session ID created for each login

**3. Secure Cookies**:
- `HttpOnly` flag - JavaScript can't access session cookie
- `SameSite=Lax` - Prevents CSRF attacks
- Secure flag - HTTPS only (if SSL enabled)

**4. Anti-Cache Headers**:
```
Cache-Control: no-store, no-cache, must-revalidate
Pragma: no-cache
Expires: (past date)
```
- Prevents browser from caching logged-in pages
- Back button won't show old session data

---

## Already Logged In Behavior

### Auto-Redirect

**If You're Already Logged In**:
- Typing `login.php` in URL won't show login form
- Automatically redirected to your dashboard
- Based on your role (member/trainer/admin)

**Technical Check**:
```
SessionManager::isLoggedIn() checks if $_SESSION['email'] exists
If true → Redirect immediately
If false → Show login form
```

**Why This Matters**:
- Prevents confusion (why see login if already logged in?)
- Saves time (direct access to dashboard)
- Better user experience

---

## Error Messages

### Common Login Errors

**1. "Incorrect email or password."**

**Causes**:
- Email doesn't exist in database
- Password is wrong
- Typo in email or password

**What to Do**:
- Check email spelling carefully
- Check CAPS LOCK is off (passwords are case-sensitive)
- Try "Forgot Password?" if unsure
- Ensure you're using email (not username)

**Security Note**: System shows same message for both "email not found" and "wrong password" to prevent email enumeration attacks.

---

**2. "Please verify your email before logging in."**

**Cause**:
- Account exists but email not verified
- `is_verified = 0` in database

**What to Do**:
1. Check email inbox and spam folder
2. Find "Verify Your Email - FitXBrawl" email
3. Click verification link
4. Return and try logging in again

**If Email Not Found**:
- Contact gym support
- May need manual verification or resend

---

**3. "Database connection error. Please try again later."**

**Cause**:
- Database server is down
- Connection timeout
- Server maintenance

**What to Do**:
- Wait a few minutes and try again
- If persists, contact support
- Not your fault - server issue

---

**4. "Database error. Please try again later."**

**Cause**:
- SQL query preparation failed
- Database table issue
- Temporary database problem

**What to Do**:
- Refresh page and try again
- Clear browser cache
- Contact support if continues

---

## Password Recovery Flow

### "Forgot Password?" Link

**What Happens**:

```
1. Click "Forgot Password?" link
   ↓
2. Redirects to forgot-password.php
   ↓
3. Enter your email address
   ↓
4. System checks if email exists
   ↓
5. If found → Redirects to verification.php
   ↓
6. Enter OTP code sent to email
   ↓
7. If OTP correct → Go to change-password.php
   ↓
8. Enter new password
   ↓
9. Password updated
   ↓
10. Redirects back to login.php
```

**Security Features**:
- OTP (One-Time Password) verification required
- OTP expires after limited time
- Email must be registered in system
- Old password immediately invalidated

**Page**: See `forgot-password.php` documentation for full details

---

## Security Features

### What Protects Your Login

**1. Password Hashing**
- Passwords stored using bcrypt algorithm
- Cannot be reversed even if database compromised
- Each hash includes unique salt

**2. Session Security**
- Secure session handling via SessionManager
- HttpOnly cookies prevent JavaScript access
- SameSite prevents CSRF attacks
- Session regeneration on login

**3. Input Sanitization**
- Email and password cleaned before processing
- Uses `trim()`, `stripslashes()`, `htmlspecialchars()`
- Prevents SQL injection and XSS attacks

**4. Brute Force Protection** (Implied)
- Though not explicitly coded in this file
- Server-level rate limiting recommended
- Consider adding login attempt limits

**5. Email Verification Requirement**
- Unverified accounts cannot log in
- Prevents spam/fake registrations
- Confirms email ownership

**6. Error Message Ambiguity**
- Same error for "email not found" and "wrong password"
- Prevents attackers from discovering valid emails
- Security through obscurity

---

## Technical Details

### Files Involved

**Main PHP File**: `login.php`
- Handles authentication
- Creates session
- Redirects based on role

**Dependencies**:
- `db_connect.php` - Database connection
- `session_manager.php` - Session handling
- `config.php` - Configuration constants
- `header.php` - Page header/navigation
- `footer.php` - Page footer

**CSS**: `login.css` - Page styling

**JavaScript**: `hamburger-menu.js` - Mobile navigation

**Database Tables**:
1. `users` - User accounts
   - Columns: id, username, email, password, role, is_verified, avatar
2. `remember_password` - Remember Me tokens
   - Columns: user_id, token_hash, created_at

### Form Submission

**HTTP Method**: POST
- Form data sent securely in request body
- Not visible in URL
- More secure than GET

**Form Action**: Submits to itself (`login.php`)
- Same page handles display and processing
- Shows errors on same page
- Redirects after successful login

**CSRF Protection**: 
- Not explicitly implemented with tokens in this file
- Consider adding CSRF tokens for enhanced security

---

## Navigation Options

### Don't Have an Account?

**Link**: "Don't have an account yet? Create an account."
- Takes you to: `sign-up.php`
- For new users who need to register

### Forgot Password?

**Link**: "Forgot Password?"
- Takes you to: `forgot-password.php`
- For users who can't remember password

### Other Navigation

**Header Menu**:
- Home
- Login (current page)
- Sign Up
- Membership
- Contact

---

## What Happens After Login

### Immediate Actions

**1. Session Created**
- Session variables set with your info
- Session cookie created in browser
- Logged-in state active

**2. Role-Based Redirect**
- Automatically sent to appropriate dashboard
- No manual navigation needed
- Seamless experience

### Member Dashboard Access

**For Regular Members** (`loggedin-index.php`):
- View membership status
- Book classes and sessions
- View booking history
- Update profile
- Extend/upgrade membership

**For Trainers** (`trainer/schedule.php`):
- View assigned classes
- See student bookings
- Manage schedule
- Update availability

**For Admins** (`admin/admin.php`):
- Full system access
- User management
- Booking management
- Financial reports
- System settings

---

## Common User Flows

### Flow 1: Successful Login (Member)

```
1. Enter email and password
   ↓
2. Click "Log-in"
   ↓
3. Credentials verified
   ↓
4. Session created
   ↓
5. Redirected to loggedin-index.php
   ↓
6. See member dashboard
```

### Flow 2: Unverified Account

```
1. Enter email and password
   ↓
2. Click "Log-in"
   ↓
3. Email found, password correct
   ↓
4. Check: is_verified = 0
   ↓
5. Error: "Please verify your email"
   ↓
6. User checks email
   ↓
7. Clicks verification link
   ↓
8. Returns to login
   ↓
9. Successfully logs in
```

### Flow 3: Forgot Password

```
1. Clicks "Forgot Password?"
   ↓
2. Enters email on forgot-password.php
   ↓
3. Receives OTP via email
   ↓
4. Enters OTP on verification.php
   ↓
5. Creates new password on change-password.php
   ↓
6. Returns to login.php
   ↓
7. Logs in with new password
```

### Flow 4: Already Logged In

```
1. User types login.php in URL
   ↓
2. Session check: Already logged in
   ↓
3. Auto-redirect to dashboard
   ↓
4. Never sees login form
```

### Flow 5: Wrong Password

```
1. Enter email and password
   ↓
2. Click "Log-in"
   ↓
3. Email found but password wrong
   ↓
4. Error: "Incorrect email or password."
   ↓
5. User tries again or clicks "Forgot Password?"
```

---

## Mobile Responsiveness

### How It Adapts

**Mobile Phones** (< 768px):
- Login form takes full width
- Larger input fields for touch
- Stacked layout
- Touch-friendly buttons
- Hamburger menu in header

**Tablets** (768px - 1023px):
- Centered login modal
- Similar to desktop
- Touch-optimized

**Desktop** (1024px+):
- Centered modal box
- Hover effects
- Standard keyboard navigation

---

## Accessibility Features

### Keyboard Navigation

**Tab Order**:
1. Email field
2. Password field
3. Remember Me checkbox
4. Forgot Password link
5. Log-in button
6. Create account link

**Enter Key**:
- Pressing Enter submits form
- Same as clicking "Log-in" button

### Screen Reader Support

**Labels**:
- All inputs properly labeled
- Error messages announced
- Form structure semantic

**Icons**:
- Font Awesome icons decorative only
- Not critical for understanding

---

## Known Limitations

### Current Issues

**1. No Brute Force Protection**
- Unlimited login attempts allowed
- Could be targeted by automated attacks
- **Improvement**: Add rate limiting (max 5 attempts per 15 min)

**2. No CSRF Tokens**
- Form doesn't use CSRF protection
- Vulnerable to cross-site request forgery
- **Improvement**: Add CSRF token validation

**3. No Login Attempt Logging**
- Failed logins not tracked
- Can't detect suspicious activity
- **Improvement**: Log all login attempts with IP

**4. No "Show Password" Toggle**
- Unlike sign-up page, can't reveal password
- Must retype if unsure
- **Improvement**: Add eye icon toggle

**5. No Account Lockout**
- No temporary lock after multiple failures
- Security risk
- **Improvement**: Lock account after 10 failed attempts

**6. Remember Me Token No Expiry**
- Tokens don't expire
- Could be security risk if device stolen
- **Improvement**: Set 30-day expiry on tokens

**7. No Email Saved from Sign-Up**
- Even if you just signed up, must retype email
- **Improvement**: Pre-fill email if coming from sign-up success

---

## Security Best Practices for Users

### ✓ Do This

- **Use strong, unique passwords**
- **Don't share login credentials**
- **Log out on shared computers**
- **Don't check "Remember Me" on public devices**
- **Keep email password secure** (for account recovery)
- **Log out when done** (especially on mobile)

### ✗ Don't Do This

- **Don't use same password everywhere**
- **Don't write password on paper**
- **Don't stay logged in on public computers**
- **Don't share account with others**
- **Don't ignore unverified email warnings**

---

## Troubleshooting Guide

### Can't Log In?

**Checklist**:
1. ☑ Is email spelled correctly?
2. ☑ Is password correct? (check CAPS LOCK)
3. ☑ Did you verify your email?
4. ☑ Is your account active?
5. ☑ Try "Forgot Password" if unsure

### Email Verified But Still Can't Login?

**Possible Causes**:
- Database not updated after verification
- Verification failed silently
- Account deactivated by admin

**Solution**: Contact gym support

### Remember Me Not Working?

**Causes**:
- Cookies disabled in browser
- Private/incognito mode
- Browser cache cleared
- Token expired or deleted

**Solution**: 
- Enable cookies
- Use normal browser mode
- Log in again and re-check "Remember Me"

---

## Future Enhancements

### Planned Improvements

**1. Two-Factor Authentication (2FA)**
- SMS or authenticator app codes
- Extra security layer
- Optional for users

**2. Social Login**
- Login with Google/Facebook
- Faster login process
- OAuth integration

**3. Biometric Login**
- Fingerprint on mobile
- Face ID support
- Modern device features

**4. Login Activity Log**
- Show recent login history
- IP addresses and devices
- Detect suspicious activity

**5. Session Management Dashboard**
- View all active sessions
- Logout from other devices
- Security control

---

## Summary

### What This Page Does Well

**✓ Secure Authentication**:
- Password hashing with bcrypt
- Session security
- Email verification requirement
- Input sanitization

**✓ Smart Routing**:
- Role-based redirection
- Already-logged-in check
- Appropriate dashboard for each user type

**✓ User-Friendly**:
- Clear error messages
- "Forgot Password" recovery
- "Remember Me" convenience
- Simple, clean interface

### What Could Be Better

**⚠️ Security Gaps**:
- No brute force protection
- No CSRF tokens
- No login attempt tracking
- No account lockout

**⚠️ UX Improvements**:
- No show/hide password toggle
- No pre-filled email from referral
- No "stay logged in" duration info

### Quick Tips

1. **Verify your email before first login**
2. **Use "Remember Me" only on personal devices**
3. **Use "Forgot Password" if unsure**
4. **Check for typos in email/password**
5. **Contact support if issues persist**

---

**Page Status**: ✅ Fully functional  
**Required for**: Accessing member/trainer/admin features  
**Security Level**: ⚠️ Good but could improve (add rate limiting, CSRF, 2FA)

**Documentation Version**: 1.0  
**Last Updated**: November 10, 2025
