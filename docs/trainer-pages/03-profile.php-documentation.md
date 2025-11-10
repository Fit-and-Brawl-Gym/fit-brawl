# Trainer Profile Page Documentation
**File:** `public/php/trainer/profile.php`  
**Purpose:** View and edit trainer account information  
**User Access:** Trainers only (role-based authentication)

---

## What This Page Does

The trainer profile page is your account management hub. View your trainer information (name, specialization, ID), check account details (email, join date), and edit your profile (username, email, password, profile picture). Think of it as your trainer business card combined with account settings—everything about your FitXBrawl trainer identity in one place.

### Who Can Access This Page
- **Trainers only:** Must have `role = 'trainer'`
- **Login required:** Redirects non-authenticated users
- **Active accounts only:** Soft-deleted trainers cannot access

### What It Shows
- **Profile header:** Your photo, name, email, role
- **Trainer status:** Specialization, next payout, trainer ID
- **Account information:** Member since, account type, status
- **Edit profile form:** Update personal information and password
- **Security notice:** Warning if using default password

---

## The Page Experience

### **1. Security Notice (Conditional)**

**When You See This:**
- Using default admin-assigned password
- Haven't changed password since account creation
- `password_changed = 0` in database

**What It Shows:**

**Alert Box (Yellow/Gold):**
- ⚠️ Exclamation triangle icon
- Header: "Security Notice: Default Password Detected"
- Message: "You are currently using the default password assigned by the administrator. For your account security, please change your password immediately using the form below."
- **"Change Now" button:**
  - 🔑 Key icon
  - Text: "Change Now"
  - Gold button with dark text
  - Clicks this to open edit form and focus password field

**Why This Appears:**
- Default passwords are insecure
- Encourages immediate password change
- Security best practice
- Protects your trainer account

**How to Dismiss:**
- Change your password using edit form
- Next login: Alert won't show
- Flag updates to `password_changed = 1`

---

### **2. Profile Header**

**Left Side: Profile Avatar**

**Avatar Display:**
- Large circular profile picture
- Shows custom uploaded photo if you have one
- Shows default account icon if no custom photo
- Responsive sizing

**Visual States:**
- **Custom Photo:** Full-color user image
- **Default Icon:** Generic account icon (silhouette)

---

**Right Side: Profile Information**

**Trainer Name:**
- Your full name from trainers table
- Example: "Mike Johnson"
- Large, bold text
- Primary identifier

**Email Address:**
- Your registered email
- Example: "mike.johnson@fitxbrawl.com"
- Secondary text color
- Contact information

**Role Badge:**
- Text: "Trainer"
- Role identifier
- Distinguishes from member/admin accounts

**Action Buttons:**

**Edit Profile Button:**
- 📝 Edit icon
- Text: "Edit Profile"
- Blue/gold primary button
- Toggles edit form visibility

**Logout Button:**
- 🚪 Sign-out icon
- Text: "Logout"
- Red/gray secondary button
- Ends session and redirects to public site

---

### **3. Trainer Status Section**

**Section Header:**
- "Trainer Status"
- Information about your trainer account

**Information Rows:**

**Row 1: Class Type**
- Label: "Class Type"
- Value: Your specialization (highlighted in gold)
- Examples:
  - "Boxing & Muay Thai"
  - "MMA"
  - "General Fitness"
  - "Strength Training"
- Shows what you teach
- Defined by admin during account creation

**Row 2: Next Payout**
- Label: "Next Payout"
- Value: Date of next payment
- Format: "November 1, 2025"
- Calculated as: First day of next month
- Payment schedule information

**Row 3: Trainer ID**
- Label: "Trainer ID"
- Value: Your unique trainer identifier
- Format: "#42"
- Database primary key
- Used for bookings and internal tracking

---

### **4. Account Information Section**

**Section Header:**
- "Account Information"
- General account details

**Information Display:**

**Account Type:**
- "Trainer"
- Confirms your account role

**Member Since:**
- Registration date
- Format: "October 15, 2024"
- When admin created your account
- Account age tracker

**Status:**
- "Active" (green text)
- Account is operational
- Can receive bookings
- Fully functional

---

### **5. Edit Profile Section** (Hidden by Default)

**When It Appears:**
- Click "Edit Profile" button
- Section slides down below account info
- Shows editable form

**Section Header:**
- "EDIT PROFILE" (all caps, gold color)
- Bold, prominent styling

---

#### **Form Fields**

**Field 1: Profile Picture Upload**

**Label:** "Profile Picture"

**Current Avatar Preview:**
- Shows current photo (circular)
- Same image as header
- Updates preview when file selected

**Choose Photo Button:**
- 📷 Camera icon
- Text: "Choose Photo"
- Opens file browser
- Accepts image files only (jpg, png, gif)

**File Size Hint:**
- Text: "Maximum file size: 2MB"
- Gray, small text
- Reminds of upload limit

**Remove Photo Button** (Only if custom photo exists):
- 🗑️ Trash icon
- Text: "Remove Photo"
- Red button
- Deletes custom avatar
- Reverts to default icon

---

**Field 2: Username**

**Label:** "Username"

**Input:**
- Text field
- Pre-filled with current username
- Example: "MikeJ_Trainer"
- Required field
- Used for login (if applicable)

---

**Field 3: Email**

**Label:** "Email"

**Input:**
- Email field
- Pre-filled with current email
- Example: "mike.johnson@fitxbrawl.com"
- Required field
- Must be valid email format

---

**Field 4: New Password**

**Label:** "New Password (Leave blank to keep current)"

**Input:**
- Password field (hidden characters)
- Placeholder: "Enter new password"
- Optional (leave blank to keep current password)
- No minimum requirements shown
- Use strong password

---

**Field 5: Confirm New Password**

**Label:** "Confirm New Password"

**Input:**
- Password field (hidden characters)
- Placeholder: "Confirm new password"
- Must match "New Password" field
- Required if changing password
- Validation prevents mismatches

---

#### **Form Actions**

**Save Changes Button:**
- 💾 Save icon
- Text: "Save Changes"
- Green/gold primary button
- Submits form
- Updates database

**Cancel Button:**
- ❌ X icon
- Text: "Cancel"
- Gray secondary button
- Closes edit form without saving
- Discards changes

---

## How Features Work

### **1. Edit Profile Toggle**

**Opening Edit Form:**
1. Click "Edit Profile" button in header
2. Edit section slides down (smooth animation)
3. Form fields populate with current data
4. Page scrolls to form automatically

**Closing Edit Form:**
1. Click "Cancel" button in form
2. Edit section slides up (hidden)
3. No changes saved
4. Returns to view-only mode

---

### **2. Avatar Upload**

**Uploading New Photo:**
1. Click "Choose Photo" button
2. File browser opens
3. Select image file (JPG, PNG, GIF)
4. Preview updates immediately (JavaScript)
5. File staged for upload (not saved yet)
6. Click "Save Changes" to upload

**Preview Update:**
- JavaScript reads selected file
- Shows preview before uploading
- Confirms selection before saving
- Can change mind and select different file

**Remove Photo:**
1. Click "Remove Photo" button (only if custom photo exists)
2. Preview changes to default icon
3. Hidden flag set: `remove_avatar = 1`
4. Click "Save Changes" to confirm deletion

---

### **3. Password Change**

**Changing Password:**
1. Enter new password in "New Password" field
2. Re-enter same password in "Confirm New Password" field
3. Both fields must match
4. Leave blank to keep current password
5. Click "Save Changes"

**Validation:**
- Server checks if passwords match
- If mismatch: Error message, form resets
- If match: Password updated, `password_changed = 1` set
- Security notice disappears on next login

**Security:**
- Passwords hashed before storage
- Never stored in plain text
- Old password replaced
- Can't recover old password

---

### **4. Form Submission**

**What Happens When You Click "Save Changes":**

```
1. USER CLICKS "SAVE CHANGES"
   ↓
   Form submits to update_profile.php
   ↓
2. SERVER VALIDATION
   ↓
   - Check username not empty
   - Check email valid format
   - Check passwords match (if changing)
   - Check image size < 2MB (if uploading)
   ↓
3. DATABASE UPDATE
   ↓
   - UPDATE users SET username=?, email=?, password=? WHERE email=?
   - UPDATE trainers SET password_changed=1 WHERE email=? (if changed password)
   - Upload avatar to /uploads/avatars/ (if new photo)
   - Delete old avatar (if removing or replacing)
   ↓
4. RESPONSE
   ↓
   Success:
      - Redirect to profile.php
      - Success message: "Profile updated successfully"
      - Session updated with new data
   
   Error:
      - Return to profile.php with error
      - Display error message
      - Form fields keep entered data
```

---

## Common Trainer Scenarios

### Scenario 1: New Trainer Changing Default Password

**What Happens:**
1. Coach Lisa hired yesterday
2. Admin created account with default password "Trainer123"
3. Lisa logs in for first time
4. Sees yellow security notice: "Default Password Detected"
5. Clicks "Change Now" button
6. Edit form opens, password field focused
7. Types new password: "MySecurePass2024!"
8. Confirms password: "MySecurePass2024!"
9. Clicks "Save Changes"
10. Password updated successfully
11. Next login: Security notice gone
12. Account more secure

---

### Scenario 2: Updating Profile Picture

**What Happens:**
1. Coach Mike wants professional photo
2. Opens profile page
3. Sees default account icon
4. Clicks "Edit Profile"
5. Form appears
6. Clicks "Choose Photo"
7. Selects headshot photo from computer
8. Preview updates with new photo
9. Clicks "Save Changes"
10. Photo uploads to server
11. Page reloads
12. New photo appears in header and form
13. "Remove Photo" button now visible

---

### Scenario 3: Removing Profile Picture

**What Happens:**
1. Coach Sarah has old photo uploaded
2. Wants to remove it temporarily
3. Clicks "Edit Profile"
4. Clicks "Remove Photo" button
5. Preview changes to default icon
6. Clicks "Save Changes"
7. Custom photo deleted from server
8. Default icon now shows
9. "Remove Photo" button hidden (no photo to remove)

---

### Scenario 4: Changing Email Address

**What Happens:**
1. Coach David switches from personal to professional email
2. Opens profile page
3. Clicks "Edit Profile"
4. Changes email: "david@gmail.com" → "david.trainer@fitxbrawl.com"
5. Leaves password fields blank (not changing)
6. Clicks "Save Changes"
7. Email updated in database
8. Session updated with new email
9. Uses new email for future logins

---

### Scenario 5: Viewing Trainer Status

**What Happens:**
1. Coach Maria opens profile page
2. Sees "Trainer Status" section
3. Reads information:
   - Class Type: "Boxing & Muay Thai"
   - Next Payout: "December 1, 2025"
   - Trainer ID: "#18"
4. Knows specialization for marketing
5. Marks payout date on calendar
6. References ID if contacting admin

---

### Scenario 6: Checking Account Age

**What Happens:**
1. Coach Alex curious how long they've worked at gym
2. Opens profile page
3. Sees "Account Information"
4. Reads: "Member Since: January 15, 2023"
5. Calculates: Almost 2 years as trainer
6. Proud of tenure

---

## Important Notes and Limitations

### Things to Know

1. **Trainer Role Required**
   - Must have `role = 'trainer'` in session
   - Non-trainers redirected to login
   - No member/admin access

2. **Email Used for Login**
   - Email is primary login credential
   - Changing email changes login
   - Use new email next time you log in
   - Keep email accessible

3. **Password Change Recommended**
   - Default passwords insecure
   - Security notice nags until changed
   - Change immediately after account creation
   - Use strong, unique password

4. **Avatar Upload Limits**
   - Maximum 2MB file size
   - Accepts: JPG, PNG, GIF
   - Recommended: Square images (500x500px)
   - Large files rejected

5. **Next Payout Calculated**
   - Automatic calculation (first day of next month)
   - Not actual payment amount
   - Just a date estimate
   - Contact admin for payment details

6. **Trainer ID Fixed**
   - Cannot change trainer ID
   - Assigned during account creation
   - Permanent identifier
   - Linked to all bookings

7. **Specialization Set by Admin**
   - Cannot edit class type yourself
   - Contact admin to change specialization
   - Reflects your training expertise
   - Appears on member booking pages

### What This Page Doesn't Do

- **Doesn't show earnings** (no payment history)
- **Doesn't show booking stats** (use schedule page)
- **Doesn't allow deactivation** (contact admin)
- **Doesn't change specialization** (admin-only)
- **Doesn't show member reviews** (feedback page does that)
- **Doesn't allow schedule editing** (members book directly)
- **Doesn't export data** (no download option)
- **Doesn't show payment methods** (contact admin)
- **Doesn't allow account deletion** (contact admin)
- **Doesn't show training history** (schedule page for bookings)

---

## Navigation

### How Trainers Arrive Here
- **From dashboard:** "Profile" link in nav menu
- **From schedule:** "Profile" link in nav menu
- **From feedback:** "Profile" link in nav menu
- **After login:** Can navigate via menu
- **Direct URL:** `fitxbrawl.com/public/php/trainer/profile.php`

### Where Trainers Go Next
- **Dashboard** (`index.php`) - View upcoming sessions
- **Schedule** (`schedule.php`) - Full booking calendar
- **Feedback** (`feedback.php`) - Member reviews
- **Logout** - End session
- **Update Profile** (`update_profile.php`) - Form submission endpoint (not navigable)

---

## Visual Design

### Profile Header Layout

```
┌──────────────────────────────────────────────┐
│  ┌────┐                                      │
│  │    │  Mike Johnson                        │
│  │ 📷 │  mike.johnson@email.com              │
│  │    │  Trainer                             │
│  └────┘                                      │
│         [Edit Profile] [Logout]              │
└──────────────────────────────────────────────┘
```

### Information Sections Layout

```
┌───────────────────┐  ┌───────────────────┐
│ Trainer Status    │  │ Account Info      │
│                   │  │                   │
│ Class Type:       │  │ Type: Trainer     │
│ Boxing & Muay Thai│  │ Since: Oct 2024   │
│                   │  │ Status: Active    │
│ Next Payout:      │  │                   │
│ Dec 1, 2025       │  │                   │
│                   │  │                   │
│ Trainer ID: #18   │  │                   │
└───────────────────┘  └───────────────────┘
```

### Color Scheme

**Status Colors:**
- **Green:** Active status, success messages
- **Yellow/Gold:** Warnings (default password), highlights (class type)
- **Red:** Logout button, remove photo button
- **Blue/Gold:** Primary buttons (Edit Profile, Save Changes)
- **Gray:** Secondary buttons (Cancel), disabled states

---

## Technical Details (Simplified)

### Database Tables Used

**Table 1: `users`**
- `username` - Login username
- `email` - Email address
- `password` - Hashed password
- `avatar` - Filename of uploaded photo
- `created_at` - Account creation date
- `role` - 'trainer' value

**Table 2: `trainers`**
- `id` - Trainer ID (primary key)
- `name` - Full trainer name
- `email` - Email (matches users.email)
- `specialization` - Class type expertise
- `password_changed` - Boolean (0 = default, 1 = changed)
- `deleted_at` - Soft delete timestamp (NULL = active)

---

### Update Process

**Update Query:**
```sql
UPDATE users 
SET username = ?, 
    email = ?, 
    password = ? (if changed),
    avatar = ? (if uploaded)
WHERE email = ?
```

**Password Hashing:**
```
Raw Password: MyPassword123
↓
Hash Function: password_hash($password, PASSWORD_DEFAULT)
↓
Stored Hash: $2y$10$abc123xyz...
```

**Avatar Upload:**
```
1. Validate: Check file type (jpg/png/gif), size (<2MB)
2. Generate unique filename: user_id_timestamp.jpg
3. Move file: /uploads/avatars/42_1699123456.jpg
4. Update database: avatar = '42_1699123456.jpg'
5. Delete old avatar (if replacing)
```

---

### Security Features

**1. Role-Based Access:**
- Checks `role = 'trainer'` before loading
- Non-trainers redirected
- Protects trainer data

**2. Session Validation:**
- `SessionManager::isLoggedIn()` check
- Prevents unauthorized access
- Session timeout protection

**3. Password Security:**
- Bcrypt hashing (PASSWORD_DEFAULT)
- Never stored plain text
- One-way encryption
- Cannot reverse hash

**4. File Upload Security:**
- File type validation
- Size limit enforcement
- Unique filename generation
- Prevents malicious uploads

**5. SQL Injection Prevention:**
- Prepared statements
- Parameterized queries
- Secure data binding

**6. XSS Prevention:**
- `htmlspecialchars()` on all output
- Safe display of user data
- Prevents script injection

---

## Tips for Trainers

### Best Practices

1. **Change Default Password Immediately**
   - Don't ignore security notice
   - Use strong, unique password
   - Mix uppercase, lowercase, numbers, symbols
   - Don't share password

2. **Upload Professional Photo**
   - Use clear headshot
   - Professional appearance builds trust
   - Members see your photo when booking
   - Represents gym's brand

3. **Keep Email Current**
   - Use email you check regularly
   - Receives booking notifications
   - Admin contact method
   - Password reset destination

4. **Review Trainer Status**
   - Verify specialization is correct
   - Contact admin if needs update
   - Ensure accurate member representation
   - Affects which classes you can teach

5. **Note Next Payout Date**
   - Mark calendar for payment date
   - Expect payment first of month
   - Contact admin if payment missed
   - Track compensation

---

## Final Thoughts

The trainer profile page balances simplicity with functionality. It's not cluttered with unnecessary features—just the essentials: your identity (name, photo), your status (specialization, ID, payout), and account management (username, email, password). The security notice for default passwords is a thoughtful touch, nudging trainers toward better security without being intrusive.

The edit form's toggle design keeps the page clean when not editing, while making updates straightforward when needed. Whether you're a new trainer updating your profile for the first time or a veteran making occasional changes, this page provides quick access to your account essentials without overwhelming you with options. It's your professional profile and account settings in one streamlined interface.

