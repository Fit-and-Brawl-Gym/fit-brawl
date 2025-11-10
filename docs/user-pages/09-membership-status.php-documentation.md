# Membership Status Page Documentation
**File:** `public/php/membership-status.php`  
**Purpose:** Track membership payment approval status  
**User Access:** Logged-in users only (members who submitted payment)

---

## What This Page Does

The membership status page is your payment tracking dashboard—it shows whether your membership payment has been approved, is still pending review, or was rejected by the admin. Think of it as a "where's my order?" page, but for gym memberships. After you submit payment proof for a membership plan, this page tells you the current status and what happens next.

### When You Need This Page
- **After purchasing membership:** Check if admin approved your payment
- **Waiting for activation:** See if your membership is ready to use
- **Payment rejected:** Understand why and what to do next
- **Track pending requests:** Monitor approval progress

### What It Shows
- **Pending status:** Payment submitted, awaiting admin approval
- **Approved status:** Membership activated, ready to use
- **Rejected status:** Payment declined, need to resubmit
- **No requests:** No active membership submissions

---

## The Page Experience

### **Access Requirements**

**Login Required:**
- Must be logged in to view this page
- Not logged in? → Redirects to `login.php`
- Session expired? → Redirects to `login.php`

**Who Can Access:**
- Any logged-in user
- Typically used after submitting membership payment
- Both new members and existing members upgrading plans

---

## Status Displays

The page shows different messages based on your membership request status:

---

### **1. Pending Status (Yellow Theme)**

**When You See This:**
- You submitted payment proof
- Admin hasn't reviewed it yet
- Membership not yet active

**What It Shows:**

**Header:**
- 🟡 "Payment Submitted"

**Main Message:**
```
Thank you for submitting your payment for the [Plan Name] plan.

Your request is currently pending admin approval.
Please wait for confirmation before using your new plan.
```

**Date Information:**
- 📅 Calendar icon
- Text: "Submitted on [Date]"
- Example: "Submitted on November 10, 2025"

**Visual Design:**
- Yellow/gold border and accents
- Warning-style color scheme (not bad, just "in progress")
- Warm, encouraging tone
- Clear status indicator

**What It Means:**
- Your payment was received
- Admin will review soon (usually within 24-48 hours)
- Don't use membership features yet (not active)
- Check back later or wait for email notification

---

### **2. Rejected Status (Red Theme)**

**When You See This:**
- Admin reviewed your payment
- Payment was declined/rejected
- Membership not activated

**What It Shows:**

**Header:**
- 🔴 "Payment Rejected"

**Main Message:**
```
Your payment for the [Plan Name] plan was rejected.

Please contact support or submit a new payment.
```

**Date Information:**
- 📅 Calendar icon
- Text: "Submitted on [Date]"
- Shows original submission date (not rejection date)

**Visual Design:**
- Red border and accents
- Error/alert color scheme
- Clear but not harsh tone
- Action-oriented messaging

**What It Means:**
- Admin determined payment was invalid
- Common reasons:
  - Receipt didn't match plan amount
  - Receipt was fake/edited
  - Wrong payment method used
  - Incomplete payment information
  - Technical issue with upload

**What To Do:**
1. Contact support to understand why
2. Submit new payment with correct receipt
3. Ensure receipt matches plan price exactly
4. Upload clear, unedited receipt image

---

### **3. No Pending Requests (Neutral Theme)**

**When You See This:**
- No active membership requests
- Either never submitted or all requests resolved
- Clean slate

**What It Shows:**

**Header:**
- ℹ️ "No Pending Requests"

**Main Message:**
```
You currently have no active or pending membership requests.
Select a plan to become a member.
```

**Link:**
- "Select a plan" → Links to `membership.php`

**Visual Design:**
- Neutral gray/blue color scheme
- Informational (not warning or error)
- Encouraging call-to-action
- Clean, minimal design

**What It Means:**
- Your previous request was processed (approved or rejected)
- Or you never submitted a membership request
- Or your approved membership is now active (not pending anymore)

**What To Do:**
- If new user: Click "Select a plan" to choose membership
- If existing member: Your membership is active, no action needed
- If rejected previously: Submit new payment

---

### **4. Approved Status (Green Theme)** *(Rarely Seen on This Page)*

**When You See This:**
- Payment was approved
- Membership activated
- Success!

**What It Shows:**

**Header:**
- ✅ "Membership Approved!"

**Main Message:**
```
Your membership payment for the [Plan Name] plan has been approved.

Enjoy your membership privileges!
```

**Date Information:**
- 📅 Calendar icon
- Text: "Approved on [Date]"
- Shows approval date

**Visual Design:**
- Green border and accents
- Success color scheme
- Celebratory tone
- Positive messaging

**Note:** This status is rarely seen on this page because once approved, the system usually marks the request as "active" rather than "approved," so the user sees "No Pending Requests" instead. The approved state exists in the database but is typically transitioned quickly.

---

## Navigation Buttons

At the bottom of every status display, two buttons appear:

### **Return to Home Button**

**What It Shows:**
- 🏠 House icon
- Text: "Return to Home"
- Primary button style

**What It Does:**
- Redirects to `loggedin-index.php` (member dashboard)
- Takes you back to main logged-in homepage
- Easy way to leave status page

**When to Use:**
- Done checking status
- Want to access other features
- Return to normal browsing

---

### **View Plans Button**

**What It Shows:**
- Text: "View Plans"
- Secondary button style (less prominent)

**What It Does:**
- Redirects to `membership.php` (plans catalog)
- Shows all available membership plans
- Allows purchasing or upgrading

**When to Use:**
- **If rejected:** Submit new payment for same or different plan
- **If no requests:** Browse plans to purchase
- **If pending:** View plan details while waiting
- **Upgrade consideration:** Check higher-tier plans

---

## How Users Arrive at This Page

### **1. From Dashboard (After Payment Submission)**

**Journey:**
1. Member selects plan on `membership.php`
2. Uploads payment receipt
3. Submits payment form
4. Redirected to `membership-status.php`
5. Sees "Pending" status

**Why This Happens:**
- Automatic redirect after successful payment submission
- Confirmation that payment was received
- Immediate feedback on status

---

### **2. From Email Link**

**Journey:**
1. Admin approves or rejects payment
2. System sends email notification
3. Email contains link to membership status page
4. User clicks link
5. Sees updated status (approved/rejected)

**Email Examples:**
- "Your membership for Gladiator plan has been approved!"
- "Your membership request requires attention"

---

### **3. From Dashboard Status Check Link**

**Journey:**
1. User on `loggedin-index.php` (dashboard)
2. Dashboard shows membership status widget
3. Widget says "Pending approval"
4. User clicks "Check Status" link
5. Redirected to `membership-status.php`
6. Sees detailed pending status

---

### **4. From Manual Navigation**

**Journey:**
1. User remembers they submitted payment yesterday
2. Types URL: `membership-status.php`
3. Lands on status page
4. Checks if approved yet

---

## Data Flow

### Page Load Process

```
1. USER ACCESSES PAGE
   ↓
   Check if logged in
   ↓
2. SESSION CHECK
   ↓
   If not logged in:
      → Redirect to login.php
   If logged in:
      → Continue
   ↓
3. GET USER ID FROM SESSION
   ↓
   $_SESSION['user_id'] → $user_id
   ↓
4. QUERY DATABASE
   ↓
   SELECT request_status, plan_name, date_submitted
   FROM user_memberships
   WHERE user_id = $user_id
   AND request_status IN ('pending', 'rejected')
   ORDER BY date_submitted DESC
   LIMIT 1
   ↓
5. PROCESS RESULT
   ↓
   If membership request found:
      → Display status (pending/rejected/approved)
   If no request found:
      → Display "No Pending Requests"
   ↓
6. RENDER PAGE
   ↓
   - Status message box (colored by status)
   - Plan name (e.g., "Gladiator")
   - Date (formatted: "November 10, 2025")
   - Navigation buttons
```

---

### Database Query Logic

**What the Query Does:**
- Looks for your most recent membership request
- Only shows "pending" or "rejected" requests
- Ignores "active" or "expired" memberships
- Orders by newest first (DESC)
- Returns only 1 result (your latest request)

**Why "active" Not Shown:**
- Active memberships shown on dashboard, not here
- This page tracks payment approval, not active usage
- Once approved → becomes active → not shown here anymore

**Why LIMIT 1:**
- Shows only your most recent submission
- Ignores older requests (already processed)
- Avoids confusion from multiple pending requests

---

## Common User Scenarios

### Scenario 1: New Member Checking Pending Payment

**What Happens:**
1. Alex signs up for FitXBrawl
2. Selects "Gladiator" plan (PHP 2,000/month)
3. Pays via GCash, uploads receipt screenshot
4. Submits payment form
5. Redirected to membership-status page
6. Sees: "Payment Submitted" (yellow theme)
7. Message says: "Your request is currently pending admin approval"
8. Date shows: "Submitted on November 10, 2025"
9. Alex knows to wait for admin review
10. Returns to dashboard via "Return to Home" button

**24 Hours Later:**
1. Alex receives email: "Your membership has been approved!"
2. Clicks link in email
3. Lands on membership-status page
4. Sees: "No Pending Requests" (request now active, not pending)
5. Returns to dashboard
6. Dashboard now shows active "Gladiator" membership
7. Alex can book training sessions, access gym

---

### Scenario 2: Payment Rejected, Resubmit

**What Happens:**
1. Maria submits payment for "Champion" plan
2. Accidentally uploads wrong receipt (different amount)
3. Admin reviews, marks as "Rejected"
4. Maria receives rejection email
5. Clicks email link to membership-status page
6. Sees: "Payment Rejected" (red theme)
7. Message says: "Please contact support or submit a new payment"
8. Maria clicks "View Plans" button
9. Redirected to membership.php
10. Selects "Champion" again
11. This time uploads correct receipt
12. Submits new payment
13. Redirected back to status page
14. Now shows: "Payment Submitted" (pending)
15. Admin approves second attempt
16. Maria's membership activates

---

### Scenario 3: Checking Status Multiple Times

**What Happens:**
1. John submits payment on Monday morning
2. Status page shows "Pending"
3. John checks again Monday afternoon → Still "Pending"
4. Checks Tuesday morning → Still "Pending"
5. Checks Tuesday afternoon → "No Pending Requests"
6. John goes to dashboard
7. Sees active membership (approved!)
8. Didn't get email notification (spam folder?)
9. Still successfully activated

---

### Scenario 4: No Membership Requests Yet

**What Happens:**
1. Sarah creates account
2. Explores website as free user
3. Visits membership-status.php out of curiosity
4. Sees: "No Pending Requests"
5. Message says: "Select a plan to become a member"
6. Clicks "Select a plan" link
7. Redirected to membership.php
8. Browses available plans
9. Decides to purchase "Brawler" plan
10. Submits payment
11. Now sees "Pending" on status page

---

### Scenario 5: Upgrading Membership

**What Happens:**
1. Mike has active "Resolution Regular" plan
2. Wants to upgrade to "Gladiator"
3. Goes to membership.php
4. Selects "Gladiator" upgrade option
5. Pays difference (upgrade price)
6. Submits payment receipt
7. Redirected to membership-status.php
8. Sees: "Payment Submitted" for "Gladiator"
9. Still has access to "Resolution Regular" while pending
10. Admin approves upgrade
11. Status changes to "No Pending Requests"
12. Dashboard shows "Gladiator" as active plan
13. "Resolution Regular" automatically deactivated

---

## Important Notes and Limitations

### Things to Know

1. **Login Required Always**
   - Cannot view without logging in
   - Session must be active
   - Redirects to login if not authenticated

2. **Shows Only Pending/Rejected**
   - Active memberships not shown here
   - Expired memberships not shown here
   - Only tracks payment approval process
   - Once approved → moves to "active" (not shown)

3. **Most Recent Request Only**
   - Shows latest submission
   - Older requests ignored
   - No history of past requests
   - One request at a time

4. **Manual Refresh Required**
   - Page doesn't auto-update
   - Must refresh to see status change
   - No real-time notifications on page
   - Email notification when status changes

5. **Admin Approval Needed**
   - Cannot self-approve
   - Requires manual admin review
   - Approval times vary (usually 24-48 hours)
   - No automatic approval

6. **No Cancellation Option**
   - Cannot cancel pending request from this page
   - Must contact admin to cancel
   - Cannot edit submission after submitting
   - Cannot delete request

### What This Page Doesn't Do

- **Doesn't show active memberships** (dashboard does that)
- **Doesn't show membership history** (no past requests shown)
- **Doesn't allow editing payment** (submit new one instead)
- **Doesn't explain rejection reason** (contact support for details)
- **Doesn't show approval timeline** (no ETA provided)
- **Doesn't send notifications** (email system handles that)
- **Doesn't allow payment upload** (go to membership.php)
- **Doesn't show other users' statuses** (only your own)
- **Doesn't provide refund options** (contact support)
- **Doesn't show membership benefits** (membership.php shows that)

---

## Page Design and User Experience

### Visual Status Indicators

**Color Coding:**
- 🟡 **Yellow/Gold** = Pending (waiting, in progress)
- 🔴 **Red** = Rejected (action required)
- 🟢 **Green** = Approved (success, rare to see here)
- ⚪ **Gray/Blue** = No requests (neutral, informational)

**Why Color Matters:**
- Instant visual recognition
- Universal color meanings (red=stop, yellow=wait, green=go)
- Accessible to most users
- Reduces reading requirement

---

### Messaging Tone

**Pending Status:**
- Reassuring: "Thank you for submitting..."
- Instructive: "Please wait for confirmation..."
- Neutral: States facts, no urgency

**Rejected Status:**
- Direct but not harsh: "Your payment was rejected"
- Action-oriented: "Please contact support or submit new payment"
- Helpful: Provides next steps

**No Requests:**
- Informational: "You currently have no..."
- Encouraging: "Select a plan to become a member"
- Positive: Uses invitation, not negation

---

### Accessibility Features

**Clear Hierarchy:**
1. Status header (most important)
2. Main message (context)
3. Date information (details)
4. Action buttons (what to do next)

**Icon Usage:**
- 📅 Calendar icon for dates (visual learners)
- 🏠 House icon for home (familiar symbol)
- Icons support text, not replace it

**Button Clarity:**
- "Return to Home" vs "View Plans"
- Different visual weights (primary vs secondary)
- Clear purpose for each button

---

## Technical Details (Simplified)

### Session Management

**What Happens:**
```
1. Page checks: SessionManager::isLoggedIn()
2. If false → header('Location: login.php')
3. If true → Continue to page
4. Get user ID: $_SESSION['user_id']
5. Use ID to fetch membership data
```

**Why Session Check:**
- Protect private information
- Ensure user sees only their data
- Prevent unauthorized access

---

### Database Query

**Table:** `user_memberships`

**Columns Selected:**
- `request_status` → pending/rejected/approved
- `plan_name` → Gladiator, Champion, etc.
- `date_submitted` → When payment was uploaded

**Filter Conditions:**
- `user_id = ?` → Only your requests
- `request_status IN ('pending','rejected')` → Only relevant statuses
- `ORDER BY date_submitted DESC` → Newest first
- `LIMIT 1` → Only one result

**Why This Query:**
- Efficient (only fetches what's needed)
- Secure (parameterized, prevents SQL injection)
- Accurate (only pending/rejected shown)

---

### Date Formatting

**Raw Database Format:**
- `2025-11-10 14:30:00` (YYYY-MM-DD HH:MM:SS)

**Displayed Format:**
- `November 10, 2025` (Month Day, Year)

**Code:**
```
$date = new DateTime($membershipRequest['date_submitted']);
$formattedDate = $date->format('F j, Y');
```

**Why Format:**
- More readable for users
- Familiar date style
- No time needed (only date matters)
- Professional appearance

---

## Troubleshooting

### "Page Redirects to Login"

**Cause:** Not logged in or session expired

**Solution:**
1. Log in again
2. Navigate back to membership-status.php
3. Check "Remember me" on login if available

---

### "Shows No Pending Requests" But I Just Submitted

**Possible Causes:**
1. **Approved already** (very fast admin)
2. **Submission failed** (didn't actually save)
3. **Wrong account** (logged into different account)
4. **Browser cache** (showing old data)

**Solutions:**
1. Check dashboard for active membership
2. Try submitting payment again
3. Verify you're logged into correct account
4. Refresh page (Ctrl+F5)
5. Clear browser cache

---

### "Status Hasn't Changed in Days"

**Possible Causes:**
1. **Admin backlog** (busy period)
2. **Weekend/holiday** (admin not working)
3. **Issue with payment** (admin waiting for clarification)
4. **System error** (rare)

**Solutions:**
1. Wait 48 hours before worrying
2. Contact support after 48 hours
3. Check email for admin communication
4. Verify payment receipt was clear/correct

---

## Final Thoughts

The membership status page is a simple but essential transparency tool. Instead of leaving users wondering "Did my payment go through?", it provides clear, color-coded feedback on approval status. The design prioritizes clarity over complexity—you land on the page and immediately know if you're approved, pending, or rejected.

This page reduces support inquiries by answering the most common post-purchase question: "When will my membership be active?" The visual status indicators (yellow=wait, red=problem, green=success) communicate instantly, even before reading the text. For a gym management system handling real money transactions, this transparency builds trust and reduces anxiety during the approval waiting period.

Whether you're a new member eagerly waiting for access or an upgrading member tracking your higher-tier approval, this page delivers one thing: peace of mind through clear status communication.

