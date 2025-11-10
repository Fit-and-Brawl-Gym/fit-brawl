# Manage Subscriptions Page Documentation
**File:** `public/php/admin/subscriptions.php`  
**Purpose:** Approve or reject membership payment submissions  
**User Access:** Admins only (role-based authentication)

---

## What This Page Does

The subscriptions management page is your payment approval center. When members submit membership payments (with QR receipt proof), those submissions appear here as "Processing Subscriptions" awaiting admin review. You approve legitimate payments (activating memberships) or reject fraudulent/incorrect ones (with reason). Think of it as quality control for membership payments—the critical gateway between payment submission and membership activation.

### Who Can Access This Page
- **Admins only:** Must have `role = 'admin'`
- **Login required:** Redirects non-authenticated users
- **Action-based:** Approve/Reject permissions

### What It Shows
- **Processing Subscriptions:** Pending payments needing approval
- **Rejected Submissions:** Payments rejected with reasons
- **QR Proof:** Clickable links to view payment receipts
- **Member details:** Member name, plan, submission date
- **Action history:** Approved/rejected status tracking

---

## The Page Experience

### **1. Page Header**

**Title:**
- "Manage Subscriptions"
- Large, bold heading
- Clear page identifier

**Subtitle:**
- "Approve or reject membership payments."
- Explains page purpose
- Direct call to action

---

### **2. Processing Subscriptions Section**

**Section Title:**
- "Processing Subscriptions"
- Large heading
- Most important section

**Description:**
- "Pending payments that need admin action."
- Muted gray text
- Clarifies what's shown

---

#### **Refresh Button**

**What It Shows:**
- Button labeled "Refresh"
- Top-right of section
- Simple, clean design

**What It Does:**
- Reloads page to fetch latest submissions
- Updates all tables
- Useful when multiple admins working simultaneously

**When to Use:**
- After approving/rejecting (auto-refreshes anyway)
- When expecting new submissions
- To check for updates without closing page

---

#### **Processing Table**

**Table Columns:**

1. **ID**
   - Submission ID
   - Unique identifier
   - Example: "142"
   - Useful for reference/support

2. **Member**
   - Member's full name
   - Example: "John Smith"
   - Who submitted payment

3. **Plan**
   - Membership plan name
   - Example: "Gladiator", "Champion"
   - What they're purchasing

4. **QR Proof**
   - Link to view receipt image
   - Text: "View"
   - Opens in new tab
   - Shows uploaded QR payment proof

5. **Date Submitted**
   - When member submitted payment
   - Format: "Nov 10, 2025, 2:30 PM"
   - Date and time
   - Shows submission order

6. **Action**
   - Two buttons per row:
     - **Approve** (green/gold button)
     - **Reject** (red button)
   - Primary action area

---

**Table States:**

**Loading State:**
- Shows while fetching data
- Text: "Loading..." (centered, gray)
- Brief display

**Empty State:**
- When no pending submissions
- Text: "No records found." (centered, gray)
- Normal state when queue is clear

**Populated State:**
- Shows all pending submissions
- Sorted by submission date (earliest first)
- First-in-first-out queue

---

### **3. Rejected Submissions Section**

**Section Title:**
- "Rejected Submissions"
- Clear heading
- Secondary section

**Description:**
- "Subscriptions that were rejected by admin."
- Explains content
- Historical record

---

#### **Rejected Table**

**Table Columns:**

1. **ID**
   - Submission ID
   - Same as processing table
   - Cross-reference capability

2. **Member**
   - Member's full name
   - Who submitted rejected payment

3. **Plan**
   - Plan they attempted to purchase
   - Context for rejection

4. **Reason**
   - Admin's rejection reason
   - Example: "Invalid QR code", "Blurry receipt", "Wrong amount"
   - Shows "No reason provided" if blank
   - Key information

5. **Date Submitted**
   - Original submission date/time
   - Format: "Nov 10, 2025, 2:30 PM"
   - Timeline tracking

---

**Table States:**

**Loading State:**
- "Loading..." (centered, gray)

**Empty State:**
- "No records found." (centered, gray)
- Good sign (no rejections)

**Populated State:**
- All rejected submissions
- Sorted by submission date
- Permanent record

---

### **4. Confirmation Modal (Popup)**

**When It Appears:**
- Click "Approve" or "Reject" button
- Overlays entire page
- Modal dialog center-screen

---

#### **Approve Modal**

**Modal Title:**
- "Approve Subscription"

**Modal Message:**
- "Are you sure you want to approve this subscription?"
- Confirmation prompt
- No additional fields

**Buttons:**
- **Cancel** (gray, left) - Closes modal, no action
- **Confirm** (blue/gold, right) - Executes approval

**Flow:**
1. Click "Approve" on a row
2. Modal appears
3. Click "Confirm"
4. Modal closes
5. Table refreshes
6. Row moves to approved (not shown on this page)
7. Member's membership activates

---

#### **Reject Modal**

**Modal Title:**
- "Reject Subscription"

**Modal Message:**
- "Please provide a reason for rejection:"

**Rejection Reason Field:**
- Large text area
- Placeholder: "Enter rejection reason..."
- Required field
- Multiple lines

**Example Reasons:**
- "Invalid QR code - cannot verify payment"
- "Receipt shows wrong amount"
- "Blurry image - cannot read details"
- "Payment for different account"

**Buttons:**
- **Cancel** (gray, left) - Closes modal, no action
- **Confirm** (blue/gold, right) - Executes rejection (requires reason)

**Validation:**
- If reason field empty: Alert "Please provide a reason for rejection."
- Focus returns to reason field
- Cannot proceed without reason

**Flow:**
1. Click "Reject" on a row
2. Modal appears with reason field
3. Type rejection reason
4. Click "Confirm"
5. Modal closes
6. Table refreshes
7. Row moves to rejected table
8. Reason saved permanently

---

## How Features Work

### **1. Viewing QR Proof**

**Click "View" Link:**
1. Opens receipt image in new browser tab
2. URL: `uploads/receipts/[filename].jpg` or `.png`
3. Full-size image
4. Member's uploaded QR payment proof

**What to Check:**
- Is QR code clear and readable?
- Does amount match plan price?
- Is payment reference number visible?
- Is image authentic (not screenshot of screenshot)?
- Date on receipt matches submission date?

**Common Issues:**
- **Blurry:** Can't verify details → Reject
- **Wrong amount:** Doesn't match plan price → Reject
- **Duplicate:** Already processed → Reject
- **Fake:** Screenshot or edited → Reject
- **Old receipt:** Date too old → Investigate, possibly reject

---

### **2. Approving Subscriptions**

**Approval Process:**

```
1. ADMIN CLICKS "APPROVE"
   ↓
2. MODAL APPEARS
   ↓
   Title: "Approve Subscription"
   Message: "Are you sure you want to approve this subscription?"
   ↓
3. ADMIN CLICKS "CONFIRM"
   ↓
4. JAVASCRIPT SENDS API REQUEST
   ↓
   POST api/admin_subscriptions_api.php?action=approve
   Body: { id: 142 }
   ↓
5. SERVER UPDATES DATABASE
   ↓
   UPDATE user_memberships
   SET membership_status = 'active',
       start_date = NOW(),
       end_date = DATE_ADD(NOW(), INTERVAL duration DAY)
   WHERE id = 142
   ↓
6. SERVER SENDS EMAIL TO MEMBER
   ↓
   Subject: "Membership Approved - Welcome to FitXBrawl!"
   Content: Plan details, start/end dates, access info
   ↓
7. SERVER LOGS ACTION
   ↓
   INSERT INTO activity_log
   (admin_id, action, details)
   VALUES (admin_id, 'Approved subscription', 'Subscription #142 for [Member]')
   ↓
8. API RETURNS SUCCESS
   ↓
   { "success": true, "message": "Subscription approved" }
   ↓
9. JAVASCRIPT REFRESHES TABLES
   ↓
   - Removes row from Processing table
   - Member can now access gym features
   - Membership active immediately
```

---

### **3. Rejecting Subscriptions**

**Rejection Process:**

```
1. ADMIN CLICKS "REJECT"
   ↓
2. MODAL APPEARS
   ↓
   Title: "Reject Subscription"
   Message: "Please provide a reason for rejection:"
   Reason field: Empty text area (required)
   ↓
3. ADMIN TYPES REASON
   ↓
   Example: "Invalid QR code - cannot verify payment"
   ↓
4. ADMIN CLICKS "CONFIRM"
   ↓
   [If reason empty: Alert "Please provide a reason", stops here]
   ↓
5. JAVASCRIPT SENDS API REQUEST
   ↓
   POST api/admin_subscriptions_api.php?action=reject
   Body: { id: 142, remarks: "Invalid QR code - cannot verify payment" }
   ↓
6. SERVER UPDATES DATABASE
   ↓
   UPDATE user_memberships
   SET membership_status = 'rejected',
       remarks = "Invalid QR code - cannot verify payment"
   WHERE id = 142
   ↓
7. SERVER SENDS EMAIL TO MEMBER
   ↓
   Subject: "Membership Submission Update"
   Content: "Unfortunately, your membership submission has been rejected. 
            Reason: Invalid QR code - cannot verify payment.
            Please resubmit with a clear receipt."
   ↓
8. SERVER LOGS ACTION
   ↓
   INSERT INTO activity_log
   (admin_id, action, details)
   VALUES (admin_id, 'Rejected subscription', 'Subscription #142 - Invalid QR code')
   ↓
9. API RETURNS SUCCESS
   ↓
   { "success": true, "message": "Subscription rejected" }
   ↓
10. JAVASCRIPT REFRESHES TABLES
   ↓
   - Removes row from Processing table
   - Adds row to Rejected table
   - Member sees rejection reason on their membership status page
```

---

### **4. Refresh Functionality**

**Manual Refresh:**
1. Click "Refresh" button
2. Page reloads (`location.reload()`)
3. All tables refetch data
4. Shows latest submissions

**Auto Refresh:**
- After approve/reject actions
- Both tables update automatically
- No manual refresh needed

---

## Data Flow

### Page Load Process

```
1. ADMIN ACCESSES PAGE
   ↓
   Role check: Is admin?
   ↓
2. RENDER PAGE STRUCTURE
   ↓
   - Display header
   - Show Processing table (loading state)
   - Show Rejected table (loading state)
   ↓
3. FETCH PROCESSING SUBSCRIPTIONS
   ↓
   GET api/admin_subscriptions_api.php?action=fetch&type=processing
   ↓
4. API QUERIES DATABASE
   ↓
   SELECT um.id, u.name as member, m.plan_name as plan,
          um.qr_proof, um.date_submitted, um.membership_status
   FROM user_memberships um
   JOIN users u ON um.user_id = u.id
   JOIN memberships m ON um.plan_id = m.id
   WHERE um.membership_status = 'processing'
   ORDER BY um.date_submitted ASC
   ↓
5. API RETURNS JSON
   ↓
   {
     "success": true,
     "data": [
       {
         "id": 142,
         "member": "John Smith",
         "plan": "Gladiator",
         "qr_proof": "receipt_142_1699999999.jpg",
         "date_submitted": "2025-11-10 14:30:00"
       },
       ...
     ]
   }
   ↓
6. JAVASCRIPT RENDERS PROCESSING TABLE
   ↓
   - Sort by date (earliest first)
   - Create row for each submission
   - Add "Approve" and "Reject" buttons
   - Display in Processing section
   ↓
7. FETCH REJECTED SUBSCRIPTIONS
   ↓
   Same API, different type: ?type=rejected
   ↓
8. RENDER REJECTED TABLE
   ↓
   - Show ID, member, plan, reason, date
   - No action buttons (already rejected)
   ↓
9. READY FOR ADMIN INTERACTION
```

---

## Common Admin Scenarios

### Scenario 1: Morning Approval Queue

**What Happens:**
1. Admin arrives at 8:00 AM
2. Opens Manage Subscriptions page
3. Sees 7 pending submissions in Processing table
4. Clicks "View" on first submission (ID: 138)
5. Receipt opens: Gladiator plan, ₱6,000, clear QR code
6. Closes tab, returns to page
7. Clicks "Approve" on row 138
8. Confirms modal
9. Row disappears from Processing table
10. Repeats for next 6 submissions
11. All valid → All approved
12. Processing table now empty: "No records found."
13. Seven members now have active memberships
14. Morning queue cleared in 10 minutes

---

### Scenario 2: Rejecting Invalid Receipt

**What Happens:**
1. Admin reviews submission ID: 145
2. Member: "Jane Doe", Plan: "Champion"
3. Clicks "View" to check receipt
4. Receipt is extremely blurry, can't read QR code
5. Closes tab
6. Clicks "Reject" button
7. Modal appears with reason field
8. Types: "Receipt image too blurry - cannot verify QR code details. Please upload clearer photo."
9. Clicks "Confirm"
10. Modal closes, tables refresh
11. Row disappears from Processing
12. Row appears in Rejected table with reason
13. Jane receives email notification with rejection reason
14. Jane can resubmit with better photo
15. Quality control maintained

---

### Scenario 3: Handling Wrong Amount

**What Happens:**
1. Admin checks submission ID: 151
2. Member: "Alex Chen", Plan: "Brawler" (₱1,800/month)
3. Clicks "View" receipt
4. Receipt shows payment of ₱1,000 (not ₱1,800)
5. Wrong amount detected
6. Closes tab
7. Clicks "Reject"
8. Types reason: "Payment amount (₱1,000) does not match Brawler plan price (₱1,800). Please pay remaining ₱800 or submit correct plan."
9. Confirms
10. Alex receives specific rejection explanation
11. Alex can either:
    - Pay remaining ₱800 and resubmit
    - Request downgrade to cheaper plan
12. Clear communication prevents confusion

---

### Scenario 4: Duplicate Submission

**What Happens:**
1. Admin sees two submissions from same member:
   - ID: 156 - "Maria Garcia", submitted Nov 10, 2:30 PM
   - ID: 159 - "Maria Garcia", submitted Nov 10, 2:45 PM
2. Both show same receipt image
3. Checks member's current status:
   - Already has active Gladiator membership (approved from ID 156)
4. ID 156 already processed earlier
5. ID 159 is duplicate submission (member clicked twice)
6. Clicks "Reject" on ID 159
7. Types: "Duplicate submission - your membership was already approved (ID: 156). No further action needed."
8. Confirms
9. Prevents duplicate billing
10. Member receives clarification

---

### Scenario 5: Checking Rejected History

**What Happens:**
1. Member calls: "Why was my payment rejected?"
2. Admin opens Manage Subscriptions
3. Scrolls to Rejected table
4. Searches for member's name (no search feature, so scrolls manually)
5. Finds row: ID: 142, Member: "John Doe"
6. Reads reason: "Invalid QR code - cannot verify payment"
7. Tells member: "Your QR code was unclear in the image. Please upload a sharper photo."
8. Member understands and resubmits
9. Historical record provides context
10. Quick, informed support

---

### Scenario 6: Bulk Approval Session

**What Happens:**
1. Friday evening: 15 pending submissions accumulated
2. Admin sets aside 20 minutes
3. Opens Manage Subscriptions
4. Works through queue systematically:
   - ID 201: View → Clear receipt → Approve
   - ID 202: View → Clear receipt → Approve
   - ID 203: View → Blurry → Reject ("Blurry image")
   - ID 204: View → Clear receipt → Approve
   - ID 205: View → Wrong plan (paid for Resolution, says Gladiator) → Reject ("Plan mismatch")
   - ...continues through all 15
5. Final tally:
   - 12 approved
   - 3 rejected
6. Processing table empty
7. 12 members activated
8. 3 members notified to resubmit
9. Weekend memberships ready

---

### Scenario 7: Investigating Suspicious Payment

**What Happens:**
1. Admin checks submission ID: 178
2. Member: "Suspicious User", Plan: "Gladiator" (₱6,000)
3. Clicks "View" receipt
4. Receipt looks like screenshot of another screenshot (poor quality, borders visible)
5. QR code is pixelated
6. Date on receipt: 3 months ago (old payment, not fresh)
7. Red flags detected
8. Clicks "Reject"
9. Types: "Receipt appears to be screenshot and date is outdated. Please submit original, current payment proof directly from your banking app."
10. Confirms
11. Prevents potential fraud
12. Protects gym from illegitimate memberships

---

## Important Notes and Limitations

### Things to Know

1. **Admin Role Required**
   - Must have `role = 'admin'`
   - Non-admins cannot access
   - Trainers/members redirected

2. **Processing Status Only**
   - Only shows `membership_status = 'processing'` in Processing table
   - Approved memberships don't show here (use users.php)
   - Expired memberships not shown

3. **Rejection Reason Required**
   - Cannot reject without providing reason
   - Enforced by validation
   - Important for member communication

4. **Email Notifications Sent**
   - Approval: Member receives welcome email
   - Rejection: Member receives rejection email with reason
   - Automatic, no admin action needed

5. **Immediate Activation**
   - Approval activates membership instantly
   - Start date: Current date/time
   - End date: Calculated from duration (30/90/180 days)

6. **First-In-First-Out Queue**
   - Processing table sorted by submission date (earliest first)
   - Fair processing order
   - Prevents old submissions from being ignored

7. **Permanent Record**
   - Rejected table keeps historical rejections
   - No deletion feature
   - Audit trail

8. **No Undo Feature**
   - Once approved/rejected, cannot undo from this page
   - Would require database manual intervention
   - Be certain before confirming

### What This Page Doesn't Do

- **Doesn't show approved members** (use users.php for that)
- **Doesn't allow editing** (can't change plan/price here)
- **Doesn't handle refunds** (separate process)
- **Doesn't search/filter** (no search bar, must scroll)
- **Doesn't show payment amounts** (only plan name, not price)
- **Doesn't send custom emails** (emails are templated)
- **Doesn't show member contact info** (no email/phone on this page)
- **Doesn't handle pending reservations** (different page)
- **Doesn't export data** (no CSV/report generation)
- **Doesn't bulk approve/reject** (one at a time only)

---

## Navigation

### How Admins Arrive Here
- **Dashboard:** Click "Review Now →" on "Pending Subscriptions" card
- **Sidebar menu:** "Subscriptions" or "Manage Subscriptions" link
- **Direct URL:** `fitxbrawl.com/public/php/admin/subscriptions.php`

### Where Admins Go Next
- **Users** (`users.php`) - View approved members
- **Dashboard** (`admin.php`) - Check pending count updates
- **Contacts** (`contacts.php`) - Respond to member inquiries
- **Activity Log** (`activity-log.php`) - Audit approval/rejection actions

---

## Visual Design

### Processing Table Row

```
┌─────┬──────────────┬───────────┬──────────┬───────────────────────┬─────────────────────┐
│ ID  │ Member       │ Plan      │ QR Proof │ Date Submitted        │ Action              │
├─────┼──────────────┼───────────┼──────────┼───────────────────────┼─────────────────────┤
│ 142 │ John Smith   │ Gladiator │ [View]   │ Nov 10, 2025, 2:30 PM │ [Approve] [Reject]  │
└─────┴──────────────┴───────────┴──────────┴───────────────────────┴─────────────────────┘
```

### Rejected Table Row

```
┌─────┬──────────────┬───────────┬───────────────────────────────┬───────────────────────┐
│ ID  │ Member       │ Plan      │ Reason                        │ Date Submitted        │
├─────┼──────────────┼───────────┼───────────────────────────────┼───────────────────────┤
│ 138 │ Jane Doe     │ Champion  │ Invalid QR code - cannot...   │ Nov 9, 2025, 4:15 PM  │
└─────┴──────────────┴───────────┴───────────────────────────────┴───────────────────────┘
```

### Confirmation Modal

**Approve Modal:**
```
┌─────────────────────────────────────────┐
│  Approve Subscription                   │
├─────────────────────────────────────────┤
│                                         │
│  Are you sure you want to approve      │
│  this subscription?                     │
│                                         │
│                                         │
│              [Cancel]  [Confirm]        │
└─────────────────────────────────────────┘
```

**Reject Modal:**
```
┌─────────────────────────────────────────┐
│  Reject Subscription                    │
├─────────────────────────────────────────┤
│                                         │
│  Please provide a reason for rejection: │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │ Enter rejection reason...         │  │
│  │                                   │  │
│  │                                   │  │
│  └───────────────────────────────────┘  │
│                                         │
│              [Cancel]  [Confirm]        │
└─────────────────────────────────────────┘
```

---

## Technical Details (Simplified)

### API Endpoints

**Fetch Subscriptions:**
- **URL:** `api/admin_subscriptions_api.php?action=fetch&type=processing`
- **Method:** GET
- **Parameters:** `type` (processing, approved, rejected)
- **Returns:** JSON array of subscriptions

**Approve Subscription:**
- **URL:** `api/admin_subscriptions_api.php?action=approve`
- **Method:** POST
- **Body:** `{ id: 142 }`
- **Returns:** `{ "success": true, "message": "..." }`

**Reject Subscription:**
- **URL:** `api/admin_subscriptions_api.php?action=reject`
- **Method:** POST
- **Body:** `{ id: 142, remarks: "Invalid QR code" }`
- **Returns:** `{ "success": true, "message": "..." }`

---

### Database Queries

**Get Processing Subscriptions:**
```sql
SELECT 
    um.id,
    u.name as member,
    m.plan_name as plan,
    um.qr_proof,
    um.date_submitted
FROM user_memberships um
JOIN users u ON um.user_id = u.id
JOIN memberships m ON um.plan_id = m.id
WHERE um.membership_status = 'processing'
ORDER BY um.date_submitted ASC
```

**Approve Subscription:**
```sql
UPDATE user_memberships
SET 
    membership_status = 'active',
    start_date = NOW(),
    end_date = DATE_ADD(NOW(), INTERVAL duration DAY)
WHERE id = 142
```

**Reject Subscription:**
```sql
UPDATE user_memberships
SET 
    membership_status = 'rejected',
    remarks = 'Invalid QR code - cannot verify payment'
WHERE id = 142
```

---

## Security Features

### 1. **Role-Based Access**
- Checks `role = 'admin'` before page loads
- Non-admins redirected immediately
- Protects payment data

### 2. **Confirmation Modals**
- Prevents accidental approvals/rejections
- Requires deliberate confirmation
- Reduces errors

### 3. **Required Rejection Reason**
- Enforces accountability
- Prevents arbitrary rejections
- Provides audit trail

### 4. **Activity Logging**
- All approvals/rejections logged
- Tracks which admin performed action
- Audit trail for disputes

### 5. **XSS Prevention**
- `escapeHtml()` on all displayed data
- Prevents script injection
- Safe output

---

## Tips for Admins

### Best Practices

1. **Check Receipts Carefully**
   - Always view QR proof before approving
   - Look for clarity, correct amount, recent date
   - Don't approve blindly

2. **Provide Clear Rejection Reasons**
   - Be specific: "Blurry image" not just "Invalid"
   - Helps members fix and resubmit
   - Reduces back-and-forth

3. **Process in Order**
   - Table shows earliest first
   - Follow queue order for fairness
   - Don't skip old submissions

4. **Check for Duplicates**
   - Look for same member, similar times
   - View receipt to confirm if it's same payment
   - Reject duplicates with explanation

5. **Refresh When Needed**
   - If multiple admins working, refresh occasionally
   - Prevents double-processing
   - Shows current queue state

6. **Review Rejected Table**
   - Periodically check rejected reasons
   - Identify common rejection patterns
   - Improve member submission guidance

---

## Final Thoughts

The subscriptions management page is your quality control checkpoint. Every membership payment passes through here, and your approval decisions directly impact member experience. Approvals activate memberships and send welcome emails—members can immediately book classes and access features. Rejections send members back to fix issues, so clear rejection reasons are crucial.

The workflow is intentionally simple: view proof, approve or reject, provide reason if rejecting. No complex forms, no multi-step processes. The modal confirmations prevent accidental clicks, and the required rejection reason ensures you can't reject without explanation. It's efficient for bulk processing (morning queues) but careful enough to catch fraud attempts.

The rejected table serves as your historical record—useful for member support calls ("Why was I rejected?") and for identifying patterns (lots of blurry images? Maybe update submission guidelines). This page sits at the intersection of finance, member activation, and quality control—handle it with care, but work through it efficiently.

