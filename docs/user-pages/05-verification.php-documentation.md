# User Page Documentation: OTP Verification Page (verification.php)

**Page Name**: OTP Verification / Account Verification  
**Who Can Access**: Users in password reset flow  
**Last Updated**: November 10, 2025

---

## What This Page Does

The verification page is part of the **password reset process**. It:

1. **Sends OTP Code** - Generates 6-digit one-time password via email
2. **Verifies Code** - Checks if entered code matches and hasn't expired
3. **Manages Timer** - Shows countdown timer (5 minutes first attempt, 3 minutes resend)
4. **Allows Resend** - Can request new OTP if needed
5. **Proceeds to Reset** - Goes to change-password.php after successful verification

**Note**: This page is accessed during **Forgot Password flow**, not during initial account sign-up verification.

---

## How You Get Here

### Password Reset Flow

```
1. Click "Forgot Password?" on login page
   ↓
2. Enter email on forgot-password.php
   ↓
3. Email verified in database
   ↓
4. Redirected to verification.php (this page)
   ↓
5. OTP sent to your email
   ↓
6. Enter OTP code
   ↓
7. Redirect to change-password.php
```

**Required**: Must come from `forgot-password.php` with email in session

**If Direct Access**: Redirects back to `forgot-password.php` (no email in session)

---

## Page Sections

### 1. Hero Section

**What You See**:
- Background image
- Motivational headline: **"STRONG TODAY STRONGER TOMORROW"**
- Yellow accent on "STRONGER"

**Purpose**: Consistent branding while you verify

---

### 2. Verification Form

**Form Elements**:

| Element | Type | Details |
|---------|------|---------|
| **OTP Input** | Text field | 6-digit number only |
| **Resend Button** | Button | Circular ↻ icon |
| **Countdown Timer** | Text display | Shows time remaining |
| **Verify Button** | Submit button | Validates OTP |

**Input Validation**:
- **Pattern**: Must be exactly 6 digits (0-9)
- **Max length**: 6 characters
- **Placeholder**: "000000"
- **Required**: Cannot submit empty

---

## OTP (One-Time Password) System

### How OTP is Generated

**Automatic on Page Load**:
1. Page checks if OTP already sent (session flag)
2. If not sent yet:
   - Generates random 6-digit number (000000 to 999999)
   - Sets expiry time: Current time + 5 minutes
   - Stores in database: `users.otp` and `users.otp_expiry`
   - Sends email with OTP code
   - Sets session flag: `$_SESSION['otp_sent'] = true`

**Technical Generation**:
```php
$otp = sprintf("%06d", random_int(0, 999999));
```
- Creates random integer 0-999999
- Formats with leading zeros (e.g., 000123, 042567)

**Database Storage**:
```
Table: users
- otp: "042567" (example)
- otp_expiry: "2025-11-10 14:35:00"
```

---

### OTP Email

**Email Contents**:
- **Subject**: "Your FitXBrawl OTP"
- **Body**: 
  - "Your OTP for password reset is: **042567**"
  - "This OTP will expire in 5 minutes."
- **Template**: Uses FitXBrawl header/footer branding
- **Sender**: "FitXBrawl" (from gym email)

**SMTP Details**:
- Uses PHPMailer library
- Secure STARTTLS connection
- Settings from `.env` file

**If Email Fails**:
- Error: "Failed to send OTP. Please try again."
- OTP not saved to database
- Page remains accessible to retry

---

## Timer System

### Countdown Display

**What You See**:
- Text: "OTP expires in: 4:32" (minutes:seconds)
- Updates every second
- Changes to "OTP has expired" when time runs out

### Two Timer Durations

| Attempt | Duration | When Used |
|---------|----------|-----------|
| **First Attempt** | 5 minutes | Initial OTP sent on page load |
| **Resend Attempts** | 3 minutes | Each time you click resend button |

**Why Different Times?**:
- First attempt: More generous (5 min) to check email
- Resends: Shorter (3 min) to encourage timely use
- Prevents abuse of resend system

### How Timer Works Technically

**Session Storage**:
```javascript
sessionStorage.setItem('otpExpiryTime', expiryTimestamp)
sessionStorage.setItem('originalOtpExpiryTime', firstExpiryTime)
sessionStorage.setItem('hasAttemptedResend', 'true')
```

**Timer Calculation**:
1. Get expiry time from session storage
2. Get current time
3. Calculate difference in seconds
4. Format as MM:SS
5. Update display using `requestAnimationFrame()`

**Why Session Storage?**:
- Persists across page refreshes
- Timer continues even if you refresh page
- Cleared when browser tab closes
- Cannot be manipulated easily

---

## Resend OTP Feature

### Resend Button

**Appearance**:
- Circular button with ↻ (redo) icon
- Located next to OTP input field
- Disabled while timer is active (except first load)

**Button States**:

| Scenario | Button State | Why |
|----------|--------------|-----|
| **First page load** | Enabled | Can resend immediately if email delayed |
| **After first resend** | Disabled until timer expires | Prevents spam |
| **Timer expired** | Enabled | Can request new OTP |

### How Resend Works

**User Action**:
1. Click resend button (↻ icon)
2. Button disables temporarily
3. JavaScript sends request to `resend-otp.php`
4. New OTP generated
5. New email sent
6. Timer resets to 3 minutes
7. Success message: "New OTP sent to your email"

**Technical Process**:
```
1. Fetch request to resend-otp.php (AJAX)
   ↓
2. Server generates new OTP
   ↓
3. Updates database (new code + expiry)
   ↓
4. Sends new email
   ↓
5. Returns JSON: {success: true}
   ↓
6. JavaScript resets timer
   ↓
7. Updates session storage
```

**Rate Limiting**:
- First resend: Allowed immediately
- After first: Must wait for 3-minute timer
- Prevents email spam

---

## OTP Verification

### When You Click "Verify Code"

**Validation Process**:

```
1. User types 6-digit code
   ↓
2. Clicks "Verify Code" button
   ↓
3. Form submits to verification.php (POST)
   ↓
4. Server retrieves OTP from database
   ↓
5. Compares entered code with stored code
   ↓
6. Checks if expiry time still valid
   ↓
7. If both match and not expired → Success
   ↓
8. Clear OTP from database
   ↓
9. Clear session storage (timer data)
   ↓
10. Redirect to change-password.php
```

### Verification Checks

**Check 1: Code Match**
```php
if($user['otp'] == $entered_otp)
```
- Compares strings exactly
- Case-sensitive (but all digits anyway)
- Must match completely

**Check 2: Expiry Time**
```php
if(strtotime($user['otp_expiry']) >= time())
```
- Converts expiry timestamp to Unix time
- Compares with current time
- Must be in future (not expired)

**Both Must Pass**: Code correct AND not expired

---

### Success: Redirect to Password Reset

**What Happens**:
1. OTP cleared from database (`SET otp = NULL, otp_expiry = NULL`)
2. Session storage cleared (timer data removed)
3. Redirect to `change-password.php`
4. Email still in session for password reset

**Why Clear OTP?**:
- Prevents reuse of same code
- Security measure
- Forces new OTP if needed again

---

### Errors

**Error 1: "Invalid OTP. Please try again."**

**Causes**:
- Typed wrong digits
- Typo in code
- Copy/paste error
- OTP from old email (if resent)

**What to Do**:
- Double-check email for correct code
- Retype carefully
- Use most recent email if resent
- Click resend if unsure

---

**Error 2: "OTP has expired. Please request a new one."**

**Causes**:
- More than 5 minutes since first send
- More than 3 minutes since resend
- Timer ran out

**What to Do**:
- Click resend button (↻)
- Wait for new email
- Use new code within 3 minutes

---

**Error 3: "Failed to send OTP. Please try again."**

**Causes**:
- Email server down
- SMTP connection failed
- Invalid email configuration
- Network issue

**What to Do**:
- Refresh page to retry
- Check internet connection
- Wait a few minutes
- Contact support if persists

---

## Security Features

### Why OTP is Secure

**1. Time-Limited**
- Expires in 5 minutes (first attempt)
- Expires in 3 minutes (resend)
- Cannot be used after expiration
- Forces timely password reset

**2. Single-Use**
- Cleared from database after verification
- Cannot be reused
- Must request new OTP for new attempt

**3. Random Generation**
- Cryptographically secure random number
- 1 million possible combinations (000000-999999)
- Unpredictable sequence

**4. Email Delivery**
- Sent to registered email only
- Proves email ownership
- Can't intercept without email access

**5. Session Requirement**
- Must come from forgot-password flow
- Email stored in server session
- Can't access page directly with arbitrary email

**6. Database Storage**
- OTP stored in database, not session
- Prevents client-side manipulation
- Server-side verification only

---

## Session Storage Usage

### What's Stored in Browser

**Three Storage Keys**:

| Key | Purpose | Example Value |
|-----|---------|---------------|
| `otpExpiryTime` | Current expiry timestamp | 1699624500000 |
| `originalOtpExpiryTime` | First attempt expiry | 1699624500000 |
| `hasAttemptedResend` | Resend flag | "true" |

**Why Session Storage vs Cookies**:
- Cleared when tab closes (security)
- Larger storage capacity
- Not sent with every request
- Prevents server overhead

**Auto-Clear**:
- Cleared on successful verification
- Cleared when navigating to change-password.php
- Prevents timer persistence after reset

---

## Common User Scenarios

### Scenario 1: Successful Verification (Fast)

```
1. Arrive from forgot-password.php
   ↓
2. OTP sent automatically
   ↓
3. Check email (arrives in 10 seconds)
   ↓
4. Type 6-digit code: 123456
   ↓
5. Click "Verify Code"
   ↓
6. Code correct, not expired (only 1 min used)
   ↓
7. Success! Redirect to change-password.php
```

**Time Elapsed**: ~1 minute

---

### Scenario 2: Email Delayed, Need Resend

```
1. Arrive on verification page
   ↓
2. Wait 2 minutes, no email
   ↓
3. Click resend button (↻)
   ↓
4. Message: "New OTP sent to your email"
   ↓
5. Timer resets to 3:00
   ↓
6. Email arrives (new code)
   ↓
7. Enter new code
   ↓
8. Verify successfully
```

**Time Elapsed**: ~3 minutes

---

### Scenario 3: Code Expired

```
1. Arrive on verification page
   ↓
2. OTP sent: 042567
   ↓
3. User distracted, 6 minutes pass
   ↓
4. Timer shows: "OTP has expired"
   ↓
5. User types old code anyway
   ↓
6. Clicks "Verify Code"
   ↓
7. Error: "OTP has expired. Please request a new one."
   ↓
8. Click resend button
   ↓
9. New OTP: 789012
   ↓
10. Enter new code within 3 minutes
   ↓
11. Success!
```

**Time Elapsed**: ~8 minutes (with delay)

---

### Scenario 4: Wrong Code Entered

```
1. OTP sent: 123456
   ↓
2. User checks email
   ↓
3. Mistypes as: 123465 (reversed last two)
   ↓
4. Clicks "Verify Code"
   ↓
5. Error: "Invalid OTP. Please try again."
   ↓
6. User rechecks email
   ↓
7. Retypes correctly: 123456
   ↓
8. Clicks "Verify Code"
   ↓
9. Success!
```

---

### Scenario 5: Multiple Resend Attempts

```
1. First OTP sent, 5-minute timer starts
   ↓
2. User clicks resend immediately
   ↓
3. New OTP sent, 3-minute timer starts
   ↓
4. Resend button disabled
   ↓
5. User waits 3 minutes
   ↓
6. Timer expires, resend re-enabled
   ↓
7. Clicks resend again
   ↓
8. Third OTP sent, new 3-minute timer
   ↓
9. Uses latest code, verifies successfully
```

---

## Technical Details

### Files Involved

**Main PHP File**: `verification.php`
- Generates OTP
- Sends email
- Verifies entered code
- Manages session

**JavaScript**: `verification.js`
- Countdown timer display
- Resend button handler
- Session storage management
- AJAX request for resend

**Email Function**: `mail_config.php`
- `sendOTPEmail()` function
- PHPMailer configuration
- SMTP settings

**Resend Endpoint**: `resend-otp.php`
- Generates new OTP
- Updates database
- Sends new email
- Returns JSON response

### Database Table

**Table**: `users`

**Relevant Columns**:
```
email        - User's email address
otp          - Current OTP code (6 digits)
otp_expiry   - Expiry timestamp (DATETIME)
```

**Updates**:
- OTP set when generated
- Cleared after successful verification
- Updated when resend requested

### Session Variables

**Server-Side** (PHP):
```
$_SESSION['reset_email']  - Email being reset
$_SESSION['otp_sent']     - Flag: OTP already sent
```

**Client-Side** (JavaScript):
```
sessionStorage.otpExpiryTime
sessionStorage.originalOtpExpiryTime
sessionStorage.hasAttemptedResend
```

---

## Known Limitations

### Current Issues

**1. No Rate Limiting on Page**
- Can refresh page to get infinite new OTPs
- Each refresh generates new code
- Potential email spam
- **Improvement**: Track attempts in database

**2. No Maximum Resend Limit**
- Can keep clicking resend after timer expires
- Unlimited email sending possible
- **Improvement**: Max 5 resends per hour

**3. OTP in Plain Text in Database**
- Code stored as plain text (not hashed)
- Database admin could see codes
- **Improvement**: Hash OTP like passwords

**4. No Lockout for Failed Attempts**
- Can try unlimited wrong codes
- Brute force possible (though unlikely with 6 digits)
- **Improvement**: Lock after 10 failed attempts

**5. Email in Session Only**
- If session expires, must restart flow
- Lost progress if session times out
- **Improvement**: Store in secure cookie

**6. No Visual Feedback During Resend**
- Button just disables
- No loading spinner
- User unsure if request sent
- **Improvement**: Add loading animation

**7. Timer Persists Across Page Refreshes**
- Technically good for UX
- But session storage can be manipulated
- **Improvement**: Verify server-side expiry always

---

## Mobile Responsiveness

### How It Adapts

**Mobile Phones** (< 768px):
- Form takes full width
- OTP input and resend button stack on small screens
- Larger tap target for resend button
- Bigger font for OTP input

**Tablets** (768px - 1023px):
- Centered modal
- Touch-friendly buttons
- Similar to desktop

**Desktop** (1024px+):
- Centered modal box
- Inline OTP input and resend button
- Hover effects

---

## Accessibility Features

### Keyboard Navigation

**Tab Order**:
1. OTP input field
2. Resend button
3. Verify Code button

**Enter Key**:
- In OTP field: Submits form
- Same as clicking "Verify Code"

### Screen Reader Support

**Labels**:
- Input has proper label context
- Error messages announced
- Countdown timer updates announced

**Icons**:
- Font Awesome icons decorative
- Button text provides context

---

## Best Practices for Users

### ✓ Do This

- **Check email immediately** after page loads
- **Check spam folder** if no email in 1 minute
- **Type carefully** - OTP is case-sensitive (though all digits)
- **Use code quickly** - Don't wait until last second
- **Use latest code** if you resent
- **Keep page open** while checking email

### ✗ Avoid This

- **Don't refresh page** unnecessarily (generates new OTP)
- **Don't use old codes** after resending
- **Don't wait too long** - timer expires
- **Don't close tab** - loses session
- **Don't share OTP** - it's for you only

---

## Troubleshooting Guide

### Email Not Arriving?

**Checklist**:
1. ☑ Check spam/junk folder
2. ☑ Wait 1-2 minutes (may be delayed)
3. ☑ Verify email was correct in previous step
4. ☑ Click resend button
5. ☑ Check email server isn't down

### "Invalid OTP" Error?

**Common Causes**:
- Typo in code
- Using old code after resend
- Copied extra spaces
- Wrong email checked

**Solutions**:
- Retype carefully
- Use most recent email
- Don't copy/paste (type manually)

### Timer Expired?

**Fix**:
- Click resend button (↻)
- Wait for new email
- Use new code within 3 minutes
- Don't get distracted this time

### Resend Button Disabled?

**Causes**:
- Timer still running (after first resend)
- Must wait for 3-minute countdown

**Fix**:
- Wait for "OTP has expired" message
- Button will re-enable automatically

---

## After Successful Verification

### What Happens Next

**Immediate Actions**:
1. OTP cleared from database
2. Session storage cleared
3. Redirect to `change-password.php`
4. Email still in session for password reset

**On change-password.php**:
- Enter new password
- Confirm new password
- Submit to update database
- Redirect to login page

**Complete Flow**:
```
forgot-password.php → verification.php → change-password.php → login.php
```

---

## Summary

### What This Page Does Well

**✓ Security**:
- Time-limited OTPs
- Email verification required
- Single-use codes
- Session-based flow

**✓ User Experience**:
- Visual countdown timer
- Resend option available
- Clear error messages
- Automatic email sending

**✓ Flexibility**:
- 5 minutes first attempt (generous)
- 3 minutes resend (encourages timely use)
- Can resend if email delayed

### What Could Be Better

**⚠️ Security Gaps**:
- No rate limiting on page refresh
- No maximum resend limit
- OTP stored in plain text
- No failed attempt lockout

**⚠️ UX Improvements**:
- No loading state on resend
- No visual feedback during send
- Could show "Email sent to: user@example.com"

### Quick User Guide

1. **Check email immediately** after page loads
2. **Type 6-digit code** from email
3. **Click "Verify Code"** to proceed
4. **Use resend (↻)** if email delayed
5. **Act quickly** - timer is 5 minutes first, 3 minutes after

---

**Page Status**: ✅ Fully functional  
**Part of**: Password reset flow  
**Required for**: Changing forgotten password  
**Security Level**: ⚠️ Good but could improve (add rate limiting, hash OTP)

**Documentation Version**: 1.0  
**Last Updated**: November 10, 2025
