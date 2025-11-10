# User Profile Page Documentation
**File:** `public/php/user_profile.php`  
**Purpose:** View and edit personal account information  
**User Access:** Members only (must be logged in)

---

## What This Page Does

The user profile page is your personal account dashboard where you can view your membership details, recent training activity, and update your account information. Think of it as your digital identity card at FitXBrawl—everything about your account in one place, with the ability to customize how you appear in the system.

### Who Can Use This Page
- **Logged-in members only:** Any registered user with an active session
- **Non-logged-in users:** Automatically redirected to login page
- **All member types:** Works for regular members, trainers, and admins

---

## The Page Layout

The profile page is divided into two main sections: a viewing mode (default) and an editing mode (activated when you click "Edit Profile").

### **1. Profile Header Section**

This is what you see immediately when the page loads:

**Profile Avatar:**
- Large circular profile picture displayed prominently
- Shows your uploaded photo if you have one
- Shows default account icon (gray silhouette) if no custom avatar
- Visual indicator of your identity across the platform

**Profile Information:**
- **Username:** Displayed as large heading (e.g., "JohnDoe2024")
- **Email:** Shown below username in gray text
- Easy-to-read format highlighting your identity

**Action Buttons:**

**Edit Profile Button:**
- Yellow/gold button with pencil icon
- Text: "✏️ Edit Profile"
- Click to reveal the edit form below
- Primary action for updating account

**Logout Button:**
- White/outline style button
- Text: "🚪 Logout"
- Logs you out and redirects to login page
- Secondary action for ending session

---

### **2. Profile Content Grid**

Two information cards display your account status:

#### **Membership Status Card**

**What It Shows:**
- **Membership Plan:** Your current plan name
  - Examples: "Gladiator", "Champion", "Resolution"
  - Shows "N/A" if no active membership
- **Next Payment:** Expiration date of current membership
  - Format: "December 31, 2025"
  - Shows "N/A" if no active membership

**Purpose:**
Quick glance at your membership tier and when it expires, so you know when to renew.

#### **Recent Activity Card**

**What It Shows:**
- **Last Training Session:** Date of your most recent session
  - Format: "November 10, 2025"
  - Shows "N/A" if no training sessions recorded
- **Type:** The class type you trained
  - Examples: "Boxing", "MMA", "Muay Thai"
- **Trainer:** Name of the trainer who coached you
  - Example: "John Smith"

**Purpose:**
Track your training history and see your engagement with the gym at a glance.

---

### **3. Edit Profile Section (Initially Hidden)**

When you click "Edit Profile", a comprehensive form slides into view below the profile content:

#### **Profile Picture Upload**

**Avatar Preview:**
- Shows current profile picture (or default icon)
- Large circular display matching header avatar
- Updates in real-time when you select new image

**Choose Photo Button:**
- Camera icon with "📷 Choose Photo" text
- Click to open file browser
- Accepts image files only (JPG, JPEG, PNG, GIF)

**File Size Limit:**
- Maximum: 2MB
- Hint text displayed: "Maximum file size: 2MB"
- Files larger than 2MB are rejected with alert message

**Remove Photo Button:**
- Trash icon with "🗑️ Remove Photo" text
- Only visible if you have uploaded custom avatar
- Click to revert to default account icon
- Doesn't delete immediately—saved when you submit form

**How Upload Works:**
1. Click "Choose Photo"
2. Select image from your computer
3. Preview appears instantly (before saving)
4. Image validated for size and type
5. If valid, preview updates
6. If invalid, alert shows and selection clears

**Validation Rules:**
- **File type:** Must be image (JPG, JPEG, PNG, GIF)
- **File size:** Cannot exceed 2MB (2,097,152 bytes)
- **Alert on failure:** Clear error message explaining issue

---

#### **Username Field**

**Input Field:**
- Label: "Username"
- Pre-filled with current username
- Required field (cannot be empty)
- Text input, no special characters required

**What You Can Change:**
- Your display name across the platform
- Shown in bookings, profile, and communications
- Must be unique (system validates)

---

#### **Email Field**

**Input Field:**
- Label: "Email"
- Pre-filled with current email address
- Required field (cannot be empty)
- Email format validated

**What You Can Change:**
- Your login email address
- Contact email for gym communications
- Updates session data on save

**Important:**
Changing your email updates your login credentials. Use new email on next login.

---

#### **Password Change Fields**

**Three-Field System:**

**1. Current Password Field (Conditional)**
- Label: "Current Password (Required to change password)"
- Initially hidden
- Appears automatically when you type in "New Password" or "Confirm Password" fields
- Required only if you're changing password
- Validates against your current password in database

**Purpose:** Security measure to prevent unauthorized password changes.

**2. New Password Field**
- Label: "New Password (Leave blank to keep current)"
- Optional—only fill if you want to change password
- Shows password requirements modal on focus
- Real-time validation as you type

**3. Confirm Password Field**
- Label: "Confirm New Password"
- Must match "New Password" exactly
- Shows match/no-match indicator as you type
- Required only if new password is entered

---

#### **Password Requirements Modal**

When you click or focus on the "New Password" field, a helpful requirements panel appears:

**Requirements List:**
Five checkpoints displayed:
- ✓ At least 8 characters
- ✓ One uppercase letter (A-Z)
- ✓ One lowercase letter (a-z)
- ✓ One number (0-9)
- ✓ One special character (!@#$%^&*)

**Real-Time Validation:**
As you type, each requirement updates:
- **Met:** Green checkmark (✅)
- **Not met:** Red bullet or X (❌)

**Same Password Warning:**
Below the requirements, a special warning appears if your new password matches your current password:
- ⚠️ "Cannot be the same as current password"
- Prevents reusing current password
- Shows in red/orange to catch attention

---

#### **Password Match Indicator**

Below the "Confirm Password" field:

**Match Status:**
- **Passwords match:** Green message "✅ Passwords match"
- **Passwords don't match:** Red message "❌ Passwords do not match"
- **Empty field:** No message shown

**Live Updates:**
Changes as you type in either password field. No need to submit to see if they match.

---

#### **Form Action Buttons**

**Save Changes Button:**
- Green/primary button with save icon
- Text: "💾 Save Changes"
- Submits form to update_profile.php
- Processes all changes at once

**Cancel Button:**
- Red/secondary button with X icon
- Text: "❌ Cancel"
- Hides edit form without saving
- Smoothly scrolls back to profile header
- Discards any unsaved changes

---

## How the Page Works

### Viewing Your Profile

**When You First Arrive:**
1. Page loads with profile header visible
2. Your username, email, and avatar display
3. Membership status and recent activity cards show
4. Edit form is hidden
5. Page is in "view-only" mode

**What You Can Do:**
- See your current account information
- Check membership status at a glance
- Review last training session
- Logout if needed
- Click "Edit Profile" to make changes

---

### Editing Your Profile

**Activating Edit Mode:**
1. Click "Edit Profile" button in header
2. Edit form slides into view with smooth animation
3. Page automatically scrolls to edit section
4. All fields pre-filled with current data
5. Avatar preview shows current picture

**Making Changes:**

**Change Avatar Only:**
1. Click "Choose Photo"
2. Select image (under 2MB)
3. Preview updates instantly
4. Click "Save Changes"
5. Avatar uploads and updates everywhere

**Change Username/Email Only:**
1. Edit text in username or email field
2. Leave password fields blank
3. Click "Save Changes"
4. Account info updates

**Change Password:**
1. Click in "New Password" field
2. "Current Password" field appears automatically
3. Enter your current password (for security)
4. Enter new password (meeting 5 requirements)
5. Confirm new password (must match exactly)
6. Click "Save Changes"
7. Password updates, session continues

**Change Everything:**
1. Upload new avatar
2. Edit username and email
3. Fill password fields
4. Click "Save Changes"
5. All changes saved at once

**Cancel Changes:**
- Click "Cancel" button anytime
- Form hides without saving
- Original data preserved
- Smooth scroll back to top

---

### Form Validation System

The page performs extensive validation before and after submission:

#### **Client-Side Validation (Before Submission)**

**Avatar Validation:**
1. **File type check:**
   - Accepts: JPG, JPEG, PNG, GIF
   - Rejects: All other file types
   - Alert: "Please select a valid image file (JPG, JPEG, PNG, or GIF)."

2. **File size check:**
   - Maximum: 2MB (2,097,152 bytes)
   - Alert: "File size exceeds 2MB limit. Please choose a smaller image."
   - Clears file input automatically

**Password Validation:**
1. **Current password requirement:**
   - If new password entered, current password required
   - Shows warning: "Please enter your current password to change your password."
   - Form blocks submission until filled

2. **Password match check:**
   - New password must match confirm password
   - Shows error: "Passwords do not match"
   - Focuses on confirm password field

3. **Same password check:**
   - New password cannot equal current password
   - Shows warning: "Cannot be the same as current password"
   - Highlights requirement modal
   - Scrolls to password field

4. **Requirements validation:**
   - All 5 requirements must be met
   - Visual feedback as you type
   - Cannot submit with weak password

#### **Server-Side Validation (After Submission)**

**Password Validation:**
1. **Current password verification:**
   - System retrieves password hash from database
   - Uses `password_verify()` to check match
   - Error if incorrect: "Current password is incorrect."
   - Redirects back to profile with error message

2. **Same password prevention:**
   - Verifies new password against current hash
   - Error if same: "New password cannot be the same as your current password."
   - Additional security layer beyond client-side

3. **Password match confirmation:**
   - Ensures new password and confirm password match
   - Error: "Passwords do not match."

**Email/Username Validation:**
- Sanitizes input (removes dangerous characters)
- Trims whitespace
- Prevents HTML injection
- Validates email format

**Avatar Upload Security:**
- Uses SecureFileUpload class
- Validates MIME type (actual file type, not just extension)
- Checks file size again (server-side confirmation)
- Sanitizes filename
- Stores in secure uploads directory
- Error handling for upload failures

---

### Update Process Flow

When you click "Save Changes":

```
1. CLIENT-SIDE VALIDATION
   ↓
   - Check avatar file size/type
   - Verify password fields (if changing password)
   - Ensure current password entered (if needed)
   - Confirm passwords match
   - Validate password requirements
   ↓
2. FORM SUBMISSION
   ↓
   Form data sent to update_profile.php
   ↓
3. SERVER-SIDE VALIDATION
   ↓
   - Sanitize all inputs
   - Check if current password correct (if changing)
   - Verify new password not same as current
   - Validate avatar upload
   ↓
4. DATABASE UPDATE
   ↓
   - Update username/email (if changed)
   - Upload and save avatar (if changed)
   - Hash and save new password (if changed)
   ↓
5. SESSION UPDATE
   ↓
   - Update $_SESSION['username']
   - Update $_SESSION['email']
   - Update $_SESSION['avatar']
   ↓
6. REDIRECT & FEEDBACK
   ↓
   - Redirect back to user_profile.php
   - Show success message: "Profile updated successfully!"
   - Or show error message if failed
   ↓
7. PAGE RELOAD
   ↓
   Profile displays with updated information
```

---

## Smart Features

### Conditional Field Display

**Current Password Field:**
- Hidden by default
- Appears when you type in "New Password" field
- Appears when you type in "Confirm Password" field
- Hides again if both new password fields cleared
- Warning message clears when fields emptied

**Remove Avatar Button:**
- Only shows if you have custom avatar uploaded
- Hidden if using default account icon
- Prevents confusion (can't remove what isn't there)

### Real-Time Feedback

**Avatar Preview:**
- Updates instantly when file selected
- Shows before form submission
- Reverts if you click "Remove Photo"
- Visual confirmation of upload choice

**Password Strength Indicator:**
- 5 requirements tracked separately
- Each turns green when met
- All must be green to submit
- Live updates character by character

**Password Match Display:**
- Shows as soon as confirm field has value
- Updates on every keystroke
- Clear visual (green = good, red = bad)
- Reduces form submission errors

### Smooth User Experience

**Scroll Behavior:**
- Click "Edit Profile" → Smooth scroll to form
- Click "Cancel" → Smooth scroll back to header
- Offset for sticky header (doesn't hide content)
- No jarring jumps

**Form Pre-Population:**
- All fields filled with current data
- No need to re-enter unchanged info
- Edit only what you want to change
- Saves time and reduces errors

---

## Common User Scenarios

### Scenario 1: Update Profile Picture

**What Happens:**
1. Member clicks "Edit Profile"
2. Form appears with current avatar
3. Member clicks "Choose Photo"
4. Selects selfie photo (1.8MB, JPG)
5. File passes size/type validation
6. Preview updates to show new photo
7. Member clicks "Save Changes"
8. Photo uploads to server
9. Database updates avatar filename
10. Session updates with new avatar
11. Success message appears
12. Profile reloads showing new picture everywhere

### Scenario 2: Change Password Only

**What Happens:**
1. Member clicks "Edit Profile"
2. Clicks in "New Password" field
3. Requirements modal appears
4. "Current Password" field appears automatically
5. Member enters current password: "OldPass123!"
6. Member enters new password: "NewPass456@"
7. Requirements turn green one by one
8. Member enters confirm password: "NewPass456@"
9. Match indicator shows green "Passwords match"
10. Member clicks "Save Changes"
11. System verifies current password correct
12. System confirms new ≠ current
13. New password hashed and saved
14. Success: "Profile updated successfully!"
15. Member can now login with new password

### Scenario 3: Try to Use Same Password

**What Happens:**
1. Member wants to "change" password
2. Enters current password: "MyPass123!"
3. Enters new password: "MyPass123!" (same)
4. Enters confirm: "MyPass123!"
5. All requirements show green ✅
6. Match indicator shows green ✅
7. Member clicks "Save Changes"
8. Client-side check: ⚠️ Detects same password
9. Warning appears: "Cannot be the same as current password"
10. Requirements modal highlights warning in red
11. Page scrolls to password field
12. Form does not submit
13. Member must choose different password

### Scenario 4: Upload Oversized Image

**What Happens:**
1. Member clicks "Choose Photo"
2. Selects high-res photo (3.5MB)
3. File loaded into browser
4. JavaScript checks file size
5. Detects: 3.5MB > 2MB limit
6. Alert appears: "File size exceeds 2MB limit. Please choose a smaller image."
7. File input clears automatically
8. Preview remains unchanged
9. Member must choose smaller image
10. Prevents wasted upload time

### Scenario 5: Forget Current Password When Changing

**What Happens:**
1. Member enters new password: "NewSecure789#"
2. Enters confirm password: "NewSecure789#"
3. Forgets to enter current password
4. Clicks "Save Changes"
5. Form validation checks current password field
6. Detects: Empty current password
7. Warning appears: "Please enter your current password to change your password."
8. Red border on current password field
9. Focus moves to current password field
10. Form does not submit
11. Member enters current password
12. Can now save successfully

### Scenario 6: Remove Profile Picture

**What Happens:**
1. Member has custom avatar uploaded
2. Clicks "Edit Profile"
3. Sees "Remove Photo" button (visible)
4. Clicks "Remove Photo"
5. Preview immediately changes to default icon
6. Avatar input cleared
7. Hidden flag set: remove_avatar=1
8. Member clicks "Save Changes"
9. Server processes removal
10. Database updates: avatar='default-avatar.png'
11. Session updates to default
12. Profile shows gray account icon
13. "Remove Photo" button now hidden

---

## Key Features Summary

| Feature | Description | Benefit |
|---------|-------------|---------|
| **View/Edit Modes** | Toggle between viewing and editing | Clean interface, focused actions |
| **Avatar Upload** | Upload custom profile pictures | Personalization and identity |
| **Password Security** | 5 requirements + current password check | Strong password enforcement |
| **Real-Time Validation** | Live feedback on password strength | Fewer submission errors |
| **Smart Field Display** | Fields appear only when needed | Reduced clutter, better UX |
| **Smooth Scrolling** | Animated navigation between sections | Professional feel, no jarring jumps |
| **Session Sync** | Updates session data immediately | Changes reflect across entire site |
| **File Size Limits** | 2MB maximum for avatars | Prevents server overload |
| **Same Password Prevention** | Cannot reuse current password | Forces password rotation |
| **Pre-Populated Form** | Current data auto-filled | Faster editing, less typing |

---

## What Makes This Page Special

### 1. **Progressive Disclosure**
The page reveals complexity gradually:
- Start simple: Just view mode
- Click edit: Form appears
- Focus password: Requirements appear
- Type new password: Current password field appears

This prevents overwhelming users with too many fields at once.

### 2. **Multiple Validation Layers**
Every change is validated at least twice:
- Client-side: Fast feedback, no server round-trip
- Server-side: Security guarantee, prevents bypass

This ensures data integrity while maintaining speed.

### 3. **Intelligent Form Behavior**
The form adapts to your actions:
- Only requires current password if you're changing it
- Shows remove button only if custom avatar exists
- Displays warnings only when relevant
- Focuses appropriate fields on errors

This creates a conversation-like experience rather than rigid form filling.

### 4. **Security Without Friction**
Strong security measures that don't annoy users:
- Password requirements clear and visible
- Current password required but appears only when needed
- File type/size validation prevents errors before upload
- Cannot accidentally weaken security

---

## Important Notes and Limitations

### Things to Know

1. **Email Changes Affect Login**
   - Changing email updates your login credentials
   - Use new email address on next login
   - No email confirmation sent (immediate change)

2. **Avatar File Restrictions**
   - Maximum size: 2MB (not 2.1MB, not 3MB—exactly 2MB)
   - Allowed types: JPG, JPEG, PNG, GIF only
   - No animated GIFs supported (static only)
   - Uploads stored in /uploads/avatars/

3. **Password Change Requires Current Password**
   - Security measure to prevent unauthorized changes
   - Must know current password to set new one
   - Cannot reset via profile (use forgot password instead)

4. **Default Avatar Behavior**
   - Default icon is 'default-avatar.png'
   - Removing avatar reverts to default
   - Default icon not stored in uploads folder
   - Lives in /images/ directory

5. **Username Uniqueness**
   - System should validate unique usernames (implementation detail)
   - May see error if username already taken
   - Case-sensitive or case-insensitive depends on database

6. **Membership Info Read-Only**
   - Cannot change membership plan from profile
   - Cannot update payment dates
   - Must visit membership page or contact admin

### What This Page Doesn't Do

- **Doesn't show full membership history** (only current plan)
- **Doesn't list all past training sessions** (only most recent)
- **Doesn't allow deleting account** (must contact admin)
- **Doesn't send email confirmations** for profile changes
- **Doesn't support 2FA setup** (if available, would be separate)
- **Doesn't show payment methods** (separate billing page)
- **Doesn't allow changing role** (member/trainer/admin is fixed)
- **Doesn't validate password against breach databases** (only local rules)

---

## Navigation Flow

### How Users Arrive Here
- Click profile icon in header navigation
- From dashboard "My Profile" link
- Direct URL: `fitxbrawl.com/public/php/user_profile.php`
- After signup (first-time profile visit)

### Where Users Go Next
From this page, users typically:
- **Stay on page** - After editing profile (redirects to self)
- **Logout** - Click logout button → Login page
- **Dashboard** - Navigate using header menu
- **Membership page** - If they need to change plan
- **Back to previous page** - Using browser back button

---

## Final Thoughts

The user profile page is your account control center at FitXBrawl. It strikes a balance between simplicity (view mode shows just what you need) and power (edit mode gives you full control). Every validation, every animated transition, every conditional field serves a purpose: making it easy to manage your account while keeping your information secure.

Whether you're uploading your first profile picture, updating your email, or strengthening your password, the page guides you through the process with clear feedback and helpful guardrails. It's a perfect example of form design that respects both user autonomy and security best practices.