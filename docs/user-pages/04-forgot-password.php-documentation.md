# Forgot Password Page Documentation
**File:** `public/php/forgot-password.php`  
**Purpose:** Initiate password recovery process for locked-out users  
**User Access:** Public (unauthenticated users who forgot their password)

---

## What This Page Does

The forgot password page is the entry point for account recovery when you can't remember your login credentials. It's a simple, single-purpose page: verify your email address to begin the password reset process. Think of it as the "who are you?" checkpoint before FitXBrawl sends you a verification code to prove account ownership.

### When You Need This Page
- **Forgot your password:** Can't log in, need to reset
- **Account locked:** Multiple failed login attempts
- **First login confusion:** Set up password initially but can't remember it
- **Security concern:** Want to change password but can't access account

### What It Does
1. Accepts your email address
2. Verifies the email exists in the system
3. Stores your email for the next step
4. Redirects you to the verification page (to enter OTP code)

---

## The Page Experience

### **Page Layout**

The page has a clean, focused design with minimal distractions:

**Hero Section:**
- Full-screen background with gym imagery
- Motivational tagline: "STRONG TODAY **STRONGER** TOMORROW"
- Decorative lines above and below title
- Gold "STRONGER" highlight for emphasis

**Centered Modal Card:**
- White modal box overlaying the hero background
- Modal contains the email verification form
- Clean, distraction-free interface
- Focus on single task: enter email

---

### **Modal Header**

**Title:**
- "Enter email to verify your account"
- Clear, direct instruction
- No technical jargon
- User knows exactly what to do

---

### **Motivational Subheading**

**Text:**
- "A LITTLE STEPBACK BEFORE THE BEST VERSION OF YOU!"
- Positive framing of password reset
- Encourages users (setback is temporary)
- Maintains gym's motivational brand voice

---

### **Email Input Field**

**What It Shows:**
- ✉️ Envelope icon (left side)
- Input field with placeholder: "Email"
- Required field (cannot submit empty)
- Full-width within modal

**Input Requirements:**
- Must be valid email format (contains @)
- Must exist in FitXBrawl database
- No password needed at this step
- Case-insensitive email matching

**Visual Design:**
- Icon helps identify field purpose
- Clean, modern input styling
- Placeholder disappears when typing
- Focused state highlights active field

---

### **Continue Button**

**What It Shows:**
- Large, prominent button
- Text: "Continue"
- Gold/yellow color (call-to-action)
- Full-width within modal

**What It Does:**
- Submits email to server
- Validates email exists in database
- On success: Redirects to verification page
- On failure: Shows error message

**Button States:**
- **Default:** Ready to click, gold background
- **Hover:** Slightly darker shade
- **Disabled:** Not shown (button always clickable)
- **Submitting:** No loading state (fast submission)

---

### **Error Messages**

If something goes wrong, an error appears above the form:

**Error Display:**
- Red background box
- White text for contrast
- Appears above input field
- Explains what went wrong

**Possible Error:**
- **"Email address not found in our records."**
  - Means: Email doesn't exist in database
  - What to do: Check spelling, try different email, or sign up

**What Error Messages Look Like:**
```
┌─────────────────────────────────────────┐
│ ⚠️ Email address not found in our records. │
└─────────────────────────────────────────┘
```

---

## Step-by-Step Process

### **Step 1: Arrive at Page**

**How You Get Here:**
- Click "Forgot Password?" link on login page
- Direct URL: `fitxbrawl.com/public/php/forgot-password.php`
- From email link (if notified of account issue)

**What You See:**
- Hero background with motivational text
- Centered modal with email form
- Clear instruction: "Enter email to verify your account"

---

### **Step 2: Enter Email**

**What You Do:**
- Click email input field
- Type your registered email address
- Example: `john.doe@example.com`

**Field Behavior:**
- Placeholder text disappears as you type
- Email format automatically validated by browser
- Must include @ symbol and domain
- No character limit

**Important:**
- Use the email you registered with
- Check for typos (common mistake)
- Case doesn't matter (john@email.com = JOHN@email.com)

---

### **Step 3: Click Continue**

**What Happens:**
- Form submits to server
- Page checks if email exists in `users` table
- Processing happens instantly (no loading spinner)

**Behind the Scenes:**
- Server queries database: `SELECT email FROM users WHERE email = ?`
- If found: Email stored in session (`$_SESSION['reset_email']`)
- If not found: Error message displays

---

### **Step 4a: Success Path**

**If Email Exists:**
1. Email stored in session variable
2. Page redirects to `verification.php`
3. Verification page sends OTP to your email
4. You enter OTP to confirm identity
5. Then you can reset password

**What You See:**
- Immediate redirect (no success message on this page)
- Land on verification page
- Verification page shows: "Enter the code sent to [your email]"

**Session Data Stored:**
```
$_SESSION['reset_email'] = "john.doe@example.com"
```

This tells the verification page which account is being reset.

---

### **Step 4b: Error Path**

**If Email Not Found:**
1. Page reloads
2. Red error box appears above form
3. Message: "Email address not found in our records."
4. Email field cleared
5. You can try again

**Why This Happens:**
- Email never registered in FitXBrawl system
- Typo in email address
- Used different email than you remember
- Account might have been deleted

**What to Do:**
- Check spelling carefully
- Try alternative email addresses
- Consider signing up if you don't have account
- Contact support if you're certain you registered

---

## Data Flow

### Form Submission Process

```
1. USER ENTERS EMAIL
   ↓
   john.doe@example.com
   ↓
2. USER CLICKS "CONTINUE"
   ↓
   Form submits via POST method
   ↓
3. SERVER VALIDATES EMAIL
   ↓
   - Sanitizes input (removes malicious code)
   - Strips whitespace, slashes, special chars
   - Validates email format
   ↓
4. SERVER QUERIES DATABASE
   ↓
   SELECT email FROM users WHERE email = 'john.doe@example.com'
   ↓
5a. EMAIL FOUND (Success)
   ↓
   - Store email in session: $_SESSION['reset_email']
   - Redirect to verification.php
   - Verification page sends OTP email
   
5b. EMAIL NOT FOUND (Error)
   ↓
   - Set error message: "Email address not found..."
   - Page reloads with error displayed
   - User can try again
```

---

### Session Storage

**What Gets Stored:**
- **Key:** `reset_email`
- **Value:** Your email address (sanitized)
- **Purpose:** Track which account is being reset
- **Duration:** Until password is reset or session expires

**Why Session Storage:**
- Carry data to next page (verification.php)
- Don't expose email in URL (security)
- Temporary storage (cleared after reset)
- Server-side storage (user can't tamper)

---

## Security Features

### 1. **Input Sanitization**

**What It Does:**
- Strips HTML tags (prevents XSS attacks)
- Removes extra whitespace
- Escapes special characters
- Validates email format

**Function Used:**
```
test_input() function:
- trim() - removes whitespace
- stripslashes() - removes backslashes
- htmlspecialchars() - converts < > & " ' to safe HTML entities
```

**Why This Matters:**
- Prevents injection attacks
- Ensures clean database queries
- Protects against malicious input
- Standard security practice

---

### 2. **Prepared Statements**

**What It Does:**
- Uses parameterized SQL queries
- Separates SQL code from user data
- Prevents SQL injection attacks

**Example:**
```
Safe: SELECT email FROM users WHERE email = ?
Unsafe: SELECT email FROM users WHERE email = '$email'
```

**Why This Matters:**
- Attackers can't inject malicious SQL
- Database stays secure
- Industry best practice

---

### 3. **No Account Enumeration (Partial)**

**What the Page Does:**
- Shows error if email not found
- This reveals whether email exists in system

**Security Consideration:**
- Allows attackers to test which emails are registered
- Trade-off between usability and security
- Most sites accept this risk for better UX

**Alternative Approach (Not Used Here):**
- Always show "If email exists, code was sent"
- User doesn't know if email was found
- More secure but confusing if you typo

---

### 4. **Session-Based Workflow**

**What It Does:**
- Stores email server-side (not in URL or cookies)
- Creates temporary link between forgot password → verification
- Expires after password reset

**Why This Matters:**
- Email not visible in browser URL
- Can't be intercepted or modified
- Secure data transfer between pages

---

## Common User Scenarios

### Scenario 1: Successful Password Reset Initiation

**What Happens:**
1. Sarah forgot her password
2. Goes to login page, clicks "Forgot Password?"
3. Lands on forgot password page
4. Sees modal: "Enter email to verify your account"
5. Types: `sarah.johnson@email.com`
6. Clicks "Continue"
7. Page redirects to verification page
8. Receives email with 6-digit OTP code
9. Enters OTP on verification page
10. Proceeds to change password page
11. Sets new password
12. Successfully logs in

### Scenario 2: Email Not Found Error

**What Happens:**
1. Mike can't remember which email he used
2. Visits forgot password page
3. Types: `mike.old.email@gmail.com`
4. Clicks "Continue"
5. Page reloads with red error:
   - "Email address not found in our records."
6. Mike realizes he used work email
7. Tries again: `mike.smith@company.com`
8. Success! Redirects to verification page
9. Continues password reset

### Scenario 3: Typo in Email

**What Happens:**
1. Alex types email quickly
2. Enters: `aelx.taylor@email.com` (typo: "aelx" instead of "alex")
3. Clicks "Continue"
4. Error appears: "Email address not found..."
5. Alex notices typo
6. Re-enters correctly: `alex.taylor@email.com`
7. Success! Proceeds to verification

### Scenario 4: Never Registered User

**What Happens:**
1. Jamie visits FitXBrawl for first time
2. Tries to log in but has no account
3. Clicks "Forgot Password?" (thinking they forgot)
4. Enters email on forgot password page
5. Gets error: "Email address not found..."
6. Realizes they never signed up
7. Goes to sign-up page instead
8. Creates new account

---

## Important Notes and Limitations

### Things to Know

1. **Email Must Be Registered**
   - Only emails in database will work
   - New users must sign up first
   - Deleted accounts won't be found
   - Check which email you used to register

2. **Immediate Redirect**
   - No confirmation message on this page
   - Success means instant redirect to verification.php
   - If you stay on page, email wasn't found
   - Error message is only feedback

3. **Case-Insensitive Matching**
   - `john@email.com` = `JOHN@email.com`
   - Uppercase/lowercase doesn't matter
   - Database handles case conversion
   - Don't worry about exact capitalization

4. **No Password Required**
   - This page only asks for email
   - Password reset happens later
   - Just proving you own the account
   - OTP verification comes next

5. **Session Dependency**
   - Your email stored in server session
   - Don't close browser before completing reset
   - Session expires after inactivity
   - Start over if session times out

### What This Page Doesn't Do

- **Doesn't send OTP email** (verification page does that)
- **Doesn't reset password** (change-password page does that)
- **Doesn't verify identity** (just checks email exists)
- **Doesn't create accounts** (use sign-up page)
- **Doesn't show password** (security risk)
- **Doesn't allow username recovery** (email only)
- **Doesn't validate current password** (that's login page)
- **Doesn't provide account info** (security risk)

---

## Navigation Flow

### How Users Arrive Here
- **From login page:** Click "Forgot Password?" link
- **Direct URL:** Navigate to `forgot-password.php`
- **Email link:** Support email with reset instructions
- **Search engines:** Googling "FitXBrawl password reset"

### Where Users Go Next

**Success Path:**
- **Verification page** (`verification.php`) - Enter OTP code sent to email
  - Then: **Change password page** (`change-password.php`) - Set new password
  - Then: **Login page** (`login.php`) - Log in with new password
  - Then: **Dashboard** (`loggedin-index.php`) - Back to normal

**Error Path:**
- **Sign-up page** (`sign-up.php`) - If email not found, create account
- **Contact page** (`contact.php`) - If confused, contact support
- **Login page** (`login.php`) - If remember password, try logging in

---

## Design and User Experience

### Visual Hierarchy

**1. Hero Background (Largest)**
- Full-screen image
- Motivational text in large font
- Sets energetic, positive tone

**2. Modal Card (Focal Point)**
- Centered on screen
- White background (stands out)
- Contains all interactive elements
- Natural focus for user attention

**3. Form Elements (Clear Order)**
- Header text (what to do)
- Motivational subtext (encouragement)
- Email input (what to enter)
- Continue button (what to click)

### Accessibility

**Keyboard Navigation:**
- Tab to email field
- Type email
- Tab to Continue button
- Press Enter to submit

**Screen Reader Support:**
- Input labels for assistive tech
- Error messages announced
- Button purposes clear
- Semantic HTML structure

### Mobile Responsiveness

**Small Screens:**
- Modal scales to fit screen
- Full-width input and button
- Touch-friendly button size
- Vertical layout (no side-by-side)

**Large Screens:**
- Centered modal with breathing room
- Hero background visible around modal
- Consistent proportions
- Balanced whitespace

---

## Technical Details (Simplified)

### Form Method: POST

**Why POST instead of GET:**
- Email not visible in URL
- More secure than GET
- Standard for form submissions
- Prevents accidental sharing of email in URL

### Form Action: Self-Submit

**What It Means:**
- Form submits to same page (forgot-password.php)
- Page checks if POST data exists
- If yes: Process form
- If no: Show form

**Benefit:**
- Single file handles both display and processing
- Simpler code structure
- Error messages stay on same page

### Database Query

**What It Does:**
```
Check if email exists:
- Table: users
- Column: email
- Match: Exact email match
- Result: Found or Not Found
```

**No Password Checked:**
- Only verifies email exists
- Doesn't validate credentials
- Authentication happens later (via OTP)

---

## Password Reset Workflow (Complete Journey)

This page is step 1 of a 4-step process:

### **Step 1: Forgot Password Page** (You Are Here)
- **Purpose:** Verify email exists
- **Action:** Enter email
- **Result:** Redirect to verification

### **Step 2: Verification Page**
- **Purpose:** Confirm account ownership
- **Action:** Enter OTP sent to email
- **Result:** Redirect to change password

### **Step 3: Change Password Page**
- **Purpose:** Set new password
- **Action:** Enter and confirm new password
- **Result:** Password updated in database

### **Step 4: Login Page**
- **Purpose:** Log in with new credentials
- **Action:** Enter email and new password
- **Result:** Access granted, redirect to dashboard

**Total Time:** ~2-5 minutes (depending on email delivery speed)

---

## Troubleshooting

### "Email address not found in our records"

**Possible Causes:**
1. **Typo in email** → Check spelling carefully
2. **Wrong email** → Try alternative emails
3. **Never registered** → Go to sign-up page
4. **Account deleted** → Contact support

**Solutions:**
- Double-check email spelling
- Try different email addresses
- Verify which email you used to sign up
- Contact support with proof of identity

### Page Won't Submit

**Possible Causes:**
1. **Empty email field** → Browser blocks submission
2. **Invalid email format** → Must contain @ symbol
3. **JavaScript disabled** → Shouldn't affect (pure HTML form)
4. **Server error** → Rare, contact support

**Solutions:**
- Ensure email field has text
- Check for @ symbol in email
- Try different browser
- Clear cache and cookies

### Redirected to Wrong Page

**Possible Causes:**
1. **Session expired** → Start over
2. **Browser redirect loop** → Clear cookies
3. **Server misconfiguration** → Contact support

**Solutions:**
- Start password reset from beginning
- Clear browser cache/cookies
- Try incognito/private browsing
- Contact support if persists

---

## Final Thoughts

The forgot password page is intentionally simple—one field, one button, one purpose. It's the gateway to account recovery, asking just enough information to confirm you're trying to reset your own account. Unlike complex multi-step forms, this page respects your urgency when locked out, getting you to the verification step in seconds.

The motivational messaging ("A LITTLE STEPBACK BEFORE THE BEST VERSION OF YOU!") reflects FitXBrawl's brand—even in account recovery, the gym encourages you. It's not "You forgot your password" (negative), it's "This is a minor detour on your fitness journey" (positive).

Security and simplicity balance perfectly here: secure enough to protect accounts, simple enough to not frustrate users who are already stressed from being locked out. Whether you misremembered your password or genuinely forgot it, this page gets you one step closer to accessing your account and getting back to your fitness goals.