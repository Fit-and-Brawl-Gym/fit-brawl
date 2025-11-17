# User Page Documentation: Sign-Up Page (sign-up.php)

**Page Name**: Sign Up / Registration Page
**Who Can Access**: Everyone (public)
**Last Updated**: November 10, 2025

---

## What This Page Does

The sign-up page allows new users to **create an account** for the FitXBrawl gym website. It handles:

1. **Account Registration** - Collects name, email, and password
2. **Password Validation** - Ensures strong passwords in real-time
3. **Email Verification** - Sends verification link to confirm email
4. **Terms Acceptance** - Users must agree to terms and conditions

---

## Page Sections

### 1. Hero Section

**What You See**:
- Background image of the gym
- Motivational headline: **"A STRONG BODY STARTS WITH A STRONG MIND"**
- Yellow accent on key words for emphasis

**Purpose**: Inspire visitors while they create their account

---

### 2. Sign-Up Form

**Form Fields**:

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| **Name** | Text input | Yes | No special validation (will be username) |
| **Email** | Email input | Yes | Valid email format + domain check |
| **Password** | Password input | Yes | 8+ chars, uppercase, lowercase, number, special char |
| **Confirm Password** | Password input | Yes | Must match password |
| **Terms Checkbox** | Checkbox | Yes | Must be checked to submit |

---

## How the Form Works

### Step-by-Step Process

```
1. User fills in name and email
   ↓
2. User types password → Real-time validation shows requirements
   ↓
3. User confirms password → Shows "Passwords match" or error
   ↓
4. User checks "Agree to Terms" (can view full terms in popup)
   ↓
5. User clicks "Sign up" button
   ↓
6. Server validates all inputs
   ↓
7. Creates account with unverified status
   ↓
8. Sends verification email
   ↓
9. User checks email and clicks verification link
   ↓
10. Account becomes active
```

---

## Password Requirements (Real-Time Validation)

### What Users See

When typing a password, a **requirements panel** appears showing:

**Requirements Checklist**:
- ✗ At least 12 characters → Changes to ✓ when met
- ✗ One uppercase letter (A-Z)
- ✗ One lowercase letter (a-z)
- ✗ One number (0-9)
- ✗ One special character (!@#$%^&*?)

**Password Strength Indicator**:
- **Weak** (0-2 requirements met) - Red bar
- **Medium** (3 requirements met) - Orange bar
- **Strong** (4-5 requirements met) - Green bar

### How It Works Technically

**JavaScript File**: `password-validation.js`

The validation happens **instantly** as you type:
- Uses regex patterns to check each requirement
- Updates the ✗ to ✓ when requirement is met
- Changes strength bar color based on how many requirements are met
- No server request needed (all happens in browser)

**Password Patterns Used**:
```
Length:     Minimum 8 characters
Uppercase:  At least one A-Z letter
Lowercase:  At least one a-z letter
Number:     At least one 0-9 digit
Special:    At least one of !@#$%^&*?
```

---

## Password Confirmation

### What Happens

As you type in the "Confirm Password" field:

**If passwords match**:
- Shows green message: ✓ "Passwords match"

**If passwords don't match**:
- Shows red message: ✗ "Passwords do not match"

**If field is empty**:
- No message shown

**Technical Note**: Uses JavaScript `input` event listener to compare both password fields in real-time without page refresh.

---

## Show/Hide Password Feature

**Eye Icon Toggle**:
- Click the eye icon (👁️) next to password field
- Switches between showing password as text or hiding it as dots
- Works for both password and confirm password fields independently

**How It Works**:
- Clicking toggles the input `type` attribute between `password` and `text`
- Icon changes between `fa-eye` (closed) and `fa-eye-slash` (open)

---

## Terms and Conditions Modal

### How to Access

Click the link **"Terms and Conditions"** in the checkbox label.

### What Opens

A **full-screen popup** with:

**10 Sections**:
1. Use of Our Website
2. Booking and Scheduling
3. Payments and Fees
4. Cancellations and Refunds
5. Health and Safety
6. Memberships and Subscriptions
7. Intellectual Property
8. Limitation of Liability
9. Privacy Policy
10. Changes to These Terms

**Navigation**:
- **Desktop**: Sidebar menu on the left for quick section jumping
- **Mobile**: Dropdown menu at top to select sections
- **Scroll**: Can scroll through all sections

**Last Updated**: October 7, 2025

### How to Close

**Three Ways**:
1. Click **× button** (top right)
2. Click **"Decline"** button (bottom left) - Closes modal, checkbox stays unchecked
3. Click **"Accept"** button (bottom right) - Closes modal AND auto-checks the checkbox
4. Click outside the modal (on dark background)

**Important**: If you click "Accept", the terms checkbox is automatically checked for you.

---

## Server-Side Validation (After Clicking Sign Up)

When you click the "Sign up" button, the server performs additional checks:

### Validation Checks

**1. Required Fields Check**
- Ensures name, email, password, and confirm password are all filled
- **Error**: "All fields are required."

**2. Email Format Validation**
- Checks if email follows proper format (e.g., user@domain.com)
- **Error**: "Please enter a valid email address."

**3. Email Domain Check**
- Verifies the email domain actually exists (DNS check)
- Prevents fake emails like user@fakegym123.com
- **Error**: "Invalid email domain. Please use a real email address."

**4. Password Match Validation**
- Server double-checks that passwords match
- **Error**: "Passwords do not match."

**5. Password Requirements Re-check**
- Even though JavaScript validates, server validates again for security
- Prevents bypassing client-side validation
- **Error**: Lists which requirements are not met

**6. Duplicate Account Check**
- Checks database if username or email already exists
- **Error**: "Username or email already exists."

### Technical Process

**Database Check**:
```
Queries users table for:
- Existing username (your name)
- Existing email

If found → Show error
If not found → Proceed to create account
```

**Password Hashing**:
- Your password is **NOT stored as plain text**
- Uses `password_hash()` with bcrypt algorithm
- Even database admins can't see your actual password
- Hashed example: `$2y$10$eImiTXu...` (irreversible)

---

## Email Verification System

### How It Works

**1. Account Creation**
- Account is created with `is_verified = 0` (not verified)
- A unique verification token is generated (64 random characters)
- Token is stored in database

**2. Email Sent**
- Uses PHPMailer library
- Sends HTML email to your provided address
- Email contains a verification link

**3. Verification Link**
- Format: `https://yoursite.com/public/php/verify-email.php?token=abc123...`
- Token in URL matches the one in database
- Link is unique to your account

**4. Clicking the Link**
- Opens verify-email.php page
- Checks if token exists in database
- If valid: Sets `is_verified = 1`
- Shows success message
- Account is now active

**5. Login Restriction**
- You **cannot log in** until email is verified
- Login page checks `is_verified` status
- Prevents fake/spam accounts

### Email Configuration

**SMTP Settings** (from `.env` file):
- Uses Gmail/custom SMTP server
- Secure connection (STARTTLS encryption)
- Email sender shows as "FitXBrawl"

**Email Template** includes:
- FitXBrawl header/branding
- Welcome message with your name
- Clickable verification link
- Footer with gym information

---

## Success and Error Messages

### Where They Appear

Messages appear at the **top of the form** in a colored box.

### Success Messages

**Green Box with Checkmark**:
- "Account created! Please check your email to verify your account."

**When Shown**:
- After successfully creating account
- Email was sent successfully

**What to Do**:
- Check your email inbox (and spam folder)
- Click the verification link
- Then return to log in

### Error Messages

**Red Box with Exclamation Icon**:
Shows various errors depending on what went wrong.

**Common Errors**:

| Error Message | What It Means | How to Fix |
|---------------|---------------|------------|
| "All fields are required." | You left a field empty | Fill in all fields |
| "Please enter a valid email address." | Email format is wrong | Use format: name@domain.com |
| "Invalid email domain." | Email domain doesn't exist | Use a real email provider |
| "Passwords do not match." | Password fields are different | Retype passwords carefully |
| "Username or email already exists." | Account with this info exists | Use different email or log in instead |
| "Password must be at least 12 characters long" | Password too short | Make password longer |
| "Password must contain..." | Missing password requirement | Add uppercase/number/special char |

### Auto-Scroll Feature

**Technical Enhancement**:
- When an error or success message appears, the page automatically scrolls to it
- Uses JavaScript `scrollIntoView()` function
- Message pulses (slight animation) to grab attention
- Ensures you see the message even on mobile

**File**: `signup-error-handler.js`

---

## Form Validation Summary

### Client-Side (JavaScript - Instant Feedback)

**✓ Real-time password validation**
- Shows requirements as you type
- Strength indicator updates live
- Password match checker

**✓ Visual feedback**
- Eye toggle for password visibility
- Checkmark animations
- Colored strength bars

**Why It's Good**:
- Instant feedback without waiting
- Reduces server load
- Better user experience

### Server-Side (PHP - Final Verification)

**✓ All validations repeated**
- Required fields
- Email format and domain
- Password requirements
- Duplicate check

**Why It's Needed**:
- Security (users can disable JavaScript)
- Database checks (duplicate accounts)
- Final authority before saving data

**Double Validation** = Better Security + Better UX

---

## Navigation Options

### Already Have an Account?

At the bottom of the form:
- Link text: **"Already have an account? Sign in here."**
- Clicking takes you to: `login.php`

### Other Navigation

**Header Menu** (same on all pages):
- Home
- Login
- Sign Up (current page)
- Membership Plans
- Contact

---

## Security Features

### What Protects Your Account

**1. Password Hashing**
- Passwords encrypted using bcrypt
- Cannot be reversed even if database is compromised
- Industry-standard security

**2. Email Verification**
- Confirms you own the email address
- Prevents spam registrations
- Reduces fake accounts

**3. HTTPS (if enabled on server)**
- Encrypted connection between browser and server
- Prevents password interception

**4. Session Management**
- Secure session cookies
- HttpOnly flag (JavaScript can't access)
- Prevents session hijacking

**5. Input Sanitization**
- All inputs cleaned before database insertion
- Prevents SQL injection attacks
- Uses `htmlspecialchars()`, `trim()`, `stripslashes()`

**6. DNS Email Validation**
- Checks if email domain has valid MX records
- Blocks obviously fake emails
- Additional layer beyond format check

---

## Technical Details

### Files Involved

**Main PHP File**: `sign-up.php`
- Handles form submission
- Validates inputs
- Creates user account
- Sends verification email

**JavaScript Files**:
1. `password-validation.js` - Real-time password checking
2. `signup-error-handler.js` - Auto-scroll to messages
3. `terms-modal.js` - Terms popup functionality

**CSS Files**:
1. `sign-up.css` - Page-specific styles
2. `terms-modal.css` - Modal popup styles

**Database Table**: `users`

**Columns Used**:
```
id                 - Auto-increment primary key
username           - User's name (from form)
email              - User's email (from form)
password           - Hashed password
role               - Set to "member" by default
verification_token - Random 64-character token
is_verified        - 0 (unverified) or 1 (verified)
created_at         - Timestamp of registration
```

### Form Submission Method

**HTTP Method**: POST
- Form data sent in request body (not URL)
- More secure than GET
- Supports larger data

**Form Action**: `sign-up.php` (submits to itself)
- Same page processes the form
- Allows showing errors on same page
- Redirects after success

---

## What Happens After Sign-Up

### Immediate Next Steps

**1. Email Sent**
- Check your inbox for "Verify Your Email - FitXBrawl"
- Email comes from FitXBrawl system email
- Contains verification link

**2. Verification Required**
- Click link in email
- Opens verification page
- Shows success confirmation

**3. Login**
- Return to website
- Click "Log in" or go to `login.php`
- Enter your email and password
- Access member dashboard

### If Email Doesn't Arrive

**Check These**:
1. Spam/Junk folder
2. Email address typed correctly
3. Email domain is real
4. Wait 5-10 minutes (may be delayed)

**If Still No Email**:
- Contact gym support
- Account exists but is unverified
- Support can manually verify or resend email

---

## Common User Flows

### Flow 1: Successful Registration

```
1. User fills form correctly
   ↓
2. All validations pass
   ↓
3. Account created (unverified)
   ↓
4. Email sent successfully
   ↓
5. Success message shown
   ↓
6. User checks email
   ↓
7. Clicks verification link
   ↓
8. Account verified
   ↓
9. User logs in
   ↓
10. Access to member features
```

### Flow 2: Email Already Exists

```
1. User fills form with existing email
   ↓
2. Form submitted
   ↓
3. Database check finds duplicate
   ↓
4. Error: "Username or email already exists."
   ↓
5. User options:
   → Use different email
   → Click "Sign in here" to login instead
```

### Flow 3: Weak Password

```
1. User types password
   ↓
2. Real-time validation shows unmet requirements (red ✗)
   ↓
3. User tries to submit anyway
   ↓
4. Server validates and rejects
   ↓
5. Error shown with specific requirements missing
   ↓
6. User fixes password
   ↓
7. Resubmits successfully
```

### Flow 4: Email Verification Delay

```
1. Account created successfully
   ↓
2. User doesn't receive email immediately
   ↓
3. User tries to log in
   ↓
4. Login blocked: "Please verify your email"
   ↓
5. User checks spam folder
   ↓
6. Finds email, clicks link
   ↓
7. Verification successful
   ↓
8. User can now log in
```

---

## Error Handling

### Client-Side Issues

**JavaScript Disabled**:
- Password validation panel won't work
- Password match checker won't work
- Server-side validation still protects form
- Terms modal might not open
- Form can still be submitted, server handles validation

**Slow Internet**:
- Form submission may take longer
- "Loading" state not clearly indicated (potential improvement)
- User might click submit multiple times
- Server handles duplicate submissions (shows existing account error)

### Server-Side Issues

**Email Sending Fails**:
- Account still created
- Error message: "Account created but verification email could not be sent. Error: [details]"
- Contact support to manually verify or resend

**Database Connection Fails**:
- Error: "Database error: [error details]"
- Account not created
- Try again later or contact support

**Email Domain DNS Lookup Fails**:
- May reject valid email if DNS is temporarily down
- Rare occurrence
- Try again or use different email

---

## Accessibility Features

### Keyboard Navigation

**Tab Order**:
1. Name field
2. Email field
3. Password field
4. Eye toggle (password)
5. Confirm password field
6. Eye toggle (confirm)
7. Terms checkbox
8. Terms link
9. Sign up button
10. Sign in link

**Enter Key**:
- Pressing Enter submits the form
- Same as clicking "Sign up" button

### Screen Reader Support

**Form Labels**:
- All inputs have proper labels
- Error messages announced
- Success messages announced

**Icon Meanings**:
- Eye icon announced as "toggle password visibility"
- Checkmark icons have text equivalents

**Modal Accessibility**:
- Focus trapped inside modal when open
- ESC key closes modal
- Proper ARIA labels for navigation

---

## Mobile Responsiveness

### How It Adapts

**Mobile Phones** (< 768px):
- Form takes full width
- Larger input fields for easier tapping
- Password requirements panel stacks vertically
- Terms modal uses dropdown navigation instead of sidebar
- Touch-friendly button sizes

**Tablets** (768px - 1023px):
- Form centered with padding
- Sidebar navigation in terms modal
- Similar to desktop layout

**Desktop** (1024px+):
- Form centered in modal box
- Sidebar navigation in terms modal
- Hover effects on buttons

### Touch Features

**Mobile-Specific**:
- Larger tap targets (buttons at least 44x44px)
- No hover effects (replaced with active states)
- Dropdown for terms navigation instead of sidebar

---

## Best Practices for Users

### Creating a Strong Account

**✓ Do**:
- Use a unique password (not used elsewhere)
- Use a real email you check regularly
- Read terms and conditions before accepting
- Keep verification email for records

**✗ Don't**:
- Use common passwords (password123, qwerty, etc.)
- Use fake email addresses
- Share your password with others
- Ignore verification email

### After Registration

**Remember to**:
- Verify email within 24 hours
- Save login credentials securely
- Use a password manager if needed
- Check email for important gym updates

---

## Known Limitations

### Current Issues

**1. No Username Availability Check**
- Can't check if username is taken before submitting
- Only shows error after form submission
- **Improvement**: Add real-time username checker (like `username-checker.js` exists but not implemented here)

**2. No Resend Verification Email**
- If email is lost, must contact support
- No self-service resend option
- **Improvement**: Add "Resend verification email" link

**3. No Password Strength Enforcement**
- Can submit with "weak" password as long as requirements met
- Server accepts any password meeting minimum requirements
- **Improvement**: Require "medium" or "strong" rating

**4. Terms Modal Long Content**
- All 10 sections in one long scroll
- No summary or highlights
- **Improvement**: Add "Terms Summary" section

**5. Email Sending Dependency**
- If email server is down, account created but unusable
- User can't verify without email
- **Improvement**: Alternative verification methods (SMS, admin approval)

---

## Future Enhancements

### Planned Improvements

**1. Social Login**
- Sign up with Google/Facebook
- Faster registration
- Automatic email verification

**2. Two-Factor Authentication (2FA)**
- Optional extra security
- SMS or authenticator app
- Protects against password theft

**3. Email Verification Expiry**
- Tokens expire after 24 hours
- Forces timely verification
- Increases security

**4. Password Strength Meter**
- Visual bar showing strength
- Currently shows but could be more detailed
- Tips for improving password

**5. CAPTCHA Integration**
- Prevents bot registrations
- Reduces spam accounts
- reCAPTCHA v3 (invisible)

---

## Summary

### What This Page Does Well

**✓ Strong Security**:
- Password hashing
- Email verification
- Input sanitization
- Double validation (client + server)

**✓ Great User Experience**:
- Real-time feedback
- Clear error messages
- Auto-scroll to errors
- Show/hide password toggle

**✓ Comprehensive Terms**:
- Full T&C available
- Easy-to-read sections
- Accept/Decline options

### What Could Be Better

**⚠️ Minor Issues**:
- No resend verification option
- No username availability pre-check
- Email sending dependency
- No clear loading state on submit

### User Tips

1. **Use a strong, unique password**
2. **Use a real email you check often**
3. **Verify email quickly** (within 24 hours)
4. **Read terms before accepting**
5. **Keep your login info secure**

---

**Page Status**: ✅ Fully functional
**Required for**: Creating a member account
**Next Step**: Verify email → Log in → Purchase membership or book services

**Documentation Version**: 1.0
**Last Updated**: November 10, 2025
