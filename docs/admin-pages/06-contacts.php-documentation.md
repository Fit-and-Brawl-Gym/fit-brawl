# Contact Inquiries Page Documentation
**File:** `public/php/admin/contacts.php`  
**Purpose:** Manage and respond to customer messages and inquiries  
**User Access:** Admins only (role-based authentication)

---

## What This Page Does

The contact inquiries page is your customer communication inbox. When website visitors submit contact forms (questions, feedback, requests), those messages appear here for admin review and response. View all inquiries, filter by read/unread status, search by name/email, read full messages, reply directly via email, and mark as read/archived. Think of it as a CRM inbox—centralized customer communication management.

### Who Can Access This Page
- **Admins only:** Must have `role = 'admin'`
- **Login required:** Redirects non-authenticated users
- **Action permissions:** Reply, mark read/unread, archive, delete

### What It Shows
- **All contact submissions:** Customer inquiries from website contact form
- **Contact details:** Name, email, phone, message, submission date
- **Read/Unread status:** Visual indicators for new vs. handled inquiries
- **Statistics:** Total inquiries count, unread count (badge)
- **Filtering options:** All, Unread, Read tabs + search
- **Reply functionality:** Email responses directly from interface

---

## The Page Experience

### **1. Page Header**

**Title:**
- "Contact Inquiries"
- Large, clear heading

**Subtitle:**
- "Manage and respond to customer messages"
- Explains page purpose

---

### **2. Toolbar Section**

#### **Search Box (Left)**

**What It Shows:**
- 🔍 Magnifying glass icon
- Input field: "Search by name or email..."
- Full-width search bar

**What It Searches:**
- Contact first name + last name
- Contact email address
- Real-time filtering as you type
- Case-insensitive

**Example:**
- Type "john" → Shows "John Smith", "Johnny Doe", "john@email.com"
- Type "gmail" → Shows all Gmail addresses

---

#### **Stats Summary (Right)**

**What It Shows:**
- **Label:** "Total Inquiries:"
- **Count:** Bold number (e.g., "47")
- **Divider:** "|" separator
- **Label:** "Unread:"
- **Count:** Bold number with badge styling (e.g., "8")

**Unread Badge:**
- Highlighted in color (orange/red)
- Draws attention to pending inquiries
- Updates in real-time as status changes

---

### **3. Filter Tabs**

**Three Tab Options:**

1. **All** (Default - Active on load)
   - Shows all inquiries regardless of status
   - Total count displayed in stats

2. **Unread**
   - Shows only unread inquiries
   - New/pending messages
   - Requires action

3. **Read**
   - Shows only read inquiries
   - Handled/reviewed messages
   - Historical reference

**Tab Behavior:**
- Click tab to filter
- Active tab highlighted (blue/gold)
- Inactive tabs gray
- One active at a time
- Instant filtering (no page reload)

---

### **4. Contacts List Section**

**Loading State:**
- 🔄 Spinner icon (rotating)
- Text: "Loading contacts..."
- Brief display while fetching data

**Empty State:**
- 📧 Envelope open icon
- Title: "No Contacts Found"
- Message: "No contact inquiries match your current filter"
- Appears when filters produce no results

---

### **Contact Row Structure**

Each contact displayed as expandable row:

#### **Collapsed View (Default)**

**Expand Icon (Left):**
- ▶️ Chevron right icon
- Indicates expandable
- Click to expand

**Contact ID:**
- Format: "#[ID]"
- Example: "#42"
- Unique identifier

**Contact Info (Center):**
- **Contact Name:**
  - Full name (First + Last)
  - Large, bold text
  - Example: "John Smith"
  
- **Message Preview:**
  - First 80 characters of message
  - Truncated with "..." if longer
  - Example: "I'm interested in boxing classes. Do you offer beginner sessions? What are..."
  - Smaller, gray text

**Date (Right):**
- Submission date and time
- Format: "Nov 10, 2025, 2:30 PM"
- When inquiry was submitted

**Status Badge (Far Right):**
- Color-coded badge
- **Unread:** Orange/gold badge, "unread"
- **Read:** Gray/muted badge, "read"

**Unread Row Styling:**
- Rows with "unread" status have highlighted background (light orange/yellow)
- Makes pending inquiries visually stand out
- Read rows have normal white background

---

#### **Expanded View (Click Header to Expand)**

**Chevron Icon Changes:**
- ▶️ becomes ▼ (chevron down)
- Indicates expanded state

**Details Grid Shows:**

**Row 1: Email**
- **Label:** "Email"
- **Value:** Clickable email link
- **Example:** `john.smith@email.com`
- **Action:** Click to open default email client (mailto)

**Row 2: Phone**
- **Label:** "Phone"
- **Value:** Clickable phone link (or "N/A" if not provided)
- **Example:** `555-1234`
- **Action:** Click to dial (tel link)

**Full Message Section:**
- **Label:** "Full Message"
- **Value:** Complete inquiry text
- **No truncation:** Shows entire message
- **Preserves line breaks**
- **Example:**
  ```
  I'm interested in boxing classes. Do you offer beginner sessions? 
  What are your rates and class schedules? 
  I'm available weekday evenings.
  ```

---

**Actions Section:**

Four action buttons displayed:

1. **Reply Button** (Blue/Primary)
   - **Icon:** ↩️ Reply arrow
   - **Text:** "Reply"
   - **Action:** Opens reply modal with pre-filled recipient

2. **Mark as Read Button** (Green) *[Shows if unread]*
   - **Icon:** ✅ Check mark
   - **Text:** "Mark as Read"
   - **Action:** Changes status to "read", updates badge

3. **Mark as Unread Button** (Gray) *[Shows if read]*
   - **Icon:** 📧 Envelope icon
   - **Text:** "Mark as Unread"
   - **Action:** Changes status to "unread", adds to pending queue

4. **Archive Button** (Orange)
   - **Icon:** 📦 Box archive icon
   - **Text:** "Archive"
   - **Action:** Removes from active list (soft archive)

5. **Delete Button** (Red/Danger)
   - **Icon:** 🗑️ Trash icon
   - **Text:** "Delete"
   - **Action:** Permanently deletes contact (with confirmation)

---

### **5. Reply Modal (Popup)**

**When It Appears:**
- Click "Reply" button on any contact row
- Overlays entire page
- Modal dialog center-screen

**Click Outside to Close:**
- Click darkened background overlay
- Closes modal without sending

---

#### **Modal Header**

**Title:**
- "Reply to Inquiry"

**Close Button:**
- ❌ X icon
- Top-right corner
- Closes modal

---

#### **Modal Body (Reply Form)**

**Hidden Field:**
- Contact ID (for tracking)

**To Field:**
- **Label:** "To:"
- **Value:** Contact's email (read-only, pre-filled)
- **Example:** `john.smith@email.com`
- **Gray background:** Indicates not editable

**Subject Field:**
- **Label:** "Subject:"
- **Value:** Pre-filled with "Re: Contact Inquiry from [Name]"
- **Example:** "Re: Contact Inquiry from John Smith"
- **Editable:** Admin can modify
- **Required field**

**Your Reply Field:**
- **Label:** "Your Reply:"
- **Type:** Large textarea (8 rows)
- **Placeholder:** "Type your response here..."
- **Required field**
- **Admin types response message here**

**Send Copy Checkbox:**
- **Label:** "Send copy to admin email"
- **Default:** Checked (enabled)
- **Purpose:** Admin receives copy of sent reply for records

---

#### **Modal Footer (Form Actions)**

**Cancel Button** (Gray, left)
- **Text:** "Cancel"
- **Action:** Closes modal without sending
- **Discards typed message**

**Send Reply Button** (Primary, right)
- **Icon:** ✉️ Paper plane
- **Text:** "Send Reply"
- **Action:** Sends email response
- **Loading state:** Shows spinner while sending ("Sending...")
- **Disabled during send:** Prevents duplicate submissions

---

## How Features Work

### **1. Expandable Contact Rows**

**Click Row Header:**
1. User clicks anywhere on contact header row
2. JavaScript toggles `.expanded` class
3. Row expands (slides down animation)
4. Details grid and actions appear
5. Chevron rotates (▶️ → ▼)

**Click Again:**
1. Row collapses (slides up)
2. Details hidden
3. Chevron rotates back (▼ → ▶️)

**Multiple Rows:**
- Can expand multiple contacts simultaneously
- Each row independent
- No auto-collapse of others

---

### **2. Filter Tabs**

**Click Tab:**
1. User clicks "Unread" tab
2. JavaScript sets `currentFilter = 'unread'`
3. Calls `filterAndRenderContacts()`
4. Filters `allContacts` array: `c.status === 'unread'`
5. Re-renders list with only unread contacts
6. Tab becomes active (highlighted)
7. Other tabs deactivated
8. No page reload, instant filtering

---

### **3. Real-Time Search**

**Search Input:**
1. User types in search box
2. JavaScript captures input event
3. Filters contacts as you type
4. Matches against:
   - First name + Last name (combined)
   - Email address
5. Case-insensitive matching
6. Instant results (no search button)

**Clear Search:**
- Delete text from search box
- Full list returns
- Maintains tab filter

---

### **4. Mark as Read**

**Click "Mark as Read" Button:**
```
1. ADMIN CLICKS "MARK AS READ"
   ↓
2. JAVASCRIPT SENDS API REQUEST
   ↓
   POST api/contact_actions.php
   Body: { action: 'mark_read', id: 42 }
   ↓
3. SERVER UPDATES DATABASE
   ↓
   UPDATE contacts SET status = 'read' WHERE id = 42
   ↓
4. SERVER RETURNS SUCCESS
   ↓
   { "success": true }
   ↓
5. JAVASCRIPT UPDATES LOCAL STATE
   ↓
   contact.status = 'read'
   ↓
6. RE-RENDER CONTACTS
   ↓
   - Badge changes to "read" (gray)
   - Row background changes (highlighted → normal)
   - Button changes to "Mark as Unread"
   ↓
7. UPDATE STATS
   ↓
   Unread count: 8 → 7
   ↓
8. SHOW SUCCESS TOAST
   ↓
   "Contact marked as read"
```

**Mark as Unread:**
- Same flow, opposite status
- Useful for flagging inquiry for follow-up

---

### **5. Reply to Contact**

**Reply Process:**
```
1. ADMIN CLICKS "REPLY" BUTTON
   ↓
2. JAVASCRIPT OPENS MODAL
   ↓
   Pre-fill:
   - Contact ID: 42
   - To: john.smith@email.com
   - Subject: "Re: Contact Inquiry from John Smith"
   - Message: (empty, admin types)
   - Send Copy: (checked)
   ↓
3. ADMIN TYPES RESPONSE
   ↓
   Example: "Hi John, thank you for your inquiry. We do offer beginner 
            boxing classes on Tuesdays and Thursdays at 6 PM..."
   ↓
4. ADMIN CLICKS "SEND REPLY"
   ↓
5. BUTTON SHOWS LOADING STATE
   ↓
   Disabled: true
   Text: "Sending..." (with spinner)
   Prevents double-submission
   ↓
6. JAVASCRIPT SENDS API REQUEST
   ↓
   POST api/send_reply.php
   Body: {
     contact_id: 42,
     to: "john.smith@email.com",
     subject: "Re: Contact Inquiry from John Smith",
     message: "[admin's response]",
     original_message: "[John's original inquiry]",
     send_copy: true
   }
   Timeout: 30 seconds (email sending takes time)
   ↓
7. SERVER SENDS EMAIL
   ↓
   To: john.smith@email.com
   From: admin@fitxbrawlgym.com (or configured email)
   Subject: "Re: Contact Inquiry from John Smith"
   Body: 
     [Admin's response]
     
     ---
     Original Message:
     [John's original inquiry]
   
   If send_copy=true:
     CC: admin@fitxbrawlgym.com
   ↓
8. SERVER LOGS REPLY (optional)
   ↓
   INSERT INTO contact_replies
   (contact_id, admin_id, sent_at)
   VALUES (42, admin_id, NOW())
   ↓
9. SERVER RETURNS SUCCESS
   ↓
   { "success": true }
   ↓
10. JAVASCRIPT CLOSES MODAL
   ↓
   Form resets
   Button re-enabled
   ↓
11. AUTO MARK AS READ
   ↓
   Automatically marks contact #42 as "read"
   (Reply implies handled)
   ↓
12. SHOW SUCCESS TOAST
   ↓
   "Reply sent successfully!"
   ↓
13. ADMIN DONE
   - Email sent to John
   - Admin has copy in inbox
   - Contact marked as handled
```

**Error Handling:**
- Network timeout (30s): Shows error, button re-enabled
- Invalid response: Shows error, logs to console
- Email send failure: Shows specific error message

---

### **6. Archive Contact**

**Click "Archive" Button:**
```
1. ADMIN CLICKS "ARCHIVE"
   ↓
2. CONFIRMATION PROMPT
   ↓
   "Are you sure you want to archive this contact?"
   ↓
3. ADMIN CONFIRMS
   ↓
4. JAVASCRIPT SENDS API REQUEST
   ↓
   POST api/contact_actions.php
   Body: { action: 'archive', id: 42 }
   ↓
5. SERVER UPDATES DATABASE
   ↓
   UPDATE contacts SET archived = 1 WHERE id = 42
   (or moves to archived table)
   ↓
6. SERVER RETURNS SUCCESS
   ↓
   { "success": true }
   ↓
7. JAVASCRIPT REMOVES FROM LOCAL STATE
   ↓
   allContacts = allContacts.filter(c => c.id !== 42)
   ↓
8. RE-RENDER CONTACTS
   ↓
   Contact #42 removed from list
   ↓
9. UPDATE STATS
   ↓
   Total inquiries: 47 → 46
   ↓
10. SHOW SUCCESS TOAST
   ↓
   "Contact archived successfully"
```

**Archived Contacts:**
- No longer visible on main page
- Not deleted, preserved in database
- Can be accessed via separate "Archived" view (if implemented)
- Cleanup mechanism for handled/old inquiries

---

### **7. Delete Contact**

**Click "Delete" Button:**
```
1. ADMIN CLICKS "DELETE"
   ↓
2. CONFIRMATION PROMPT
   ↓
   "Are you sure you want to delete this contact? 
    This action cannot be undone."
   ↓
3. ADMIN CONFIRMS
   ↓
4. JAVASCRIPT SENDS API REQUEST
   ↓
   POST api/contact_actions.php
   Body: { action: 'delete', id: 42 }
   ↓
5. SERVER DELETES FROM DATABASE
   ↓
   DELETE FROM contacts WHERE id = 42
   ↓
6. SERVER RETURNS SUCCESS
   ↓
   { "success": true }
   ↓
7. JAVASCRIPT REMOVES FROM LOCAL STATE
   ↓
   allContacts = allContacts.filter(c => c.id !== 42)
   ↓
8. RE-RENDER CONTACTS
   ↓
   Contact #42 permanently removed
   ↓
9. UPDATE STATS
   ↓
   Total inquiries: 47 → 46
   ↓
10. SHOW SUCCESS TOAST
   ↓
   "Contact deleted successfully"
```

**Warning:** Permanent deletion, cannot be undone. Use Archive for safer removal.

---

### **8. Auto-Refresh**

**Background Refresh:**
- JavaScript sets interval: Every 30 seconds
- Automatically calls `loadContacts()`
- Fetches updated inquiry list from server
- Silently updates interface
- Admin sees new inquiries without manual refresh

**Purpose:**
- Real-time updates
- Multiple admins can work simultaneously
- New website submissions appear automatically

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
   - Show search box and stats (0 initially)
   - Show filter tabs
   - Show loading state in contacts list
   ↓
3. FETCH CONTACTS VIA API
   ↓
   GET api/get_contacts.php
   ↓
4. API QUERIES DATABASE
   ↓
   SELECT id, first_name, last_name, email, phone_number,
          message, status, date_submitted
   FROM contacts
   WHERE archived = 0
   ORDER BY date_submitted DESC
   ↓
5. API RETURNS JSON
   ↓
   {
     "success": true,
     "contacts": [
       {
         "id": 42,
         "first_name": "John",
         "last_name": "Smith",
         "email": "john.smith@email.com",
         "phone_number": "555-1234",
         "message": "I'm interested in boxing classes...",
         "status": "unread",
         "date_submitted": "2025-11-10 14:30:00"
       },
       ...
     ]
   }
   ↓
6. JAVASCRIPT PROCESSES DATA
   ↓
   - Store in allContacts array
   - Calculate stats (total count, unread count)
   - Update stats display
   ↓
7. JAVASCRIPT RENDERS CONTACTS
   ↓
   - Loop through contacts
   - Create contact row HTML for each
   - Display in collapsed state
   - Apply unread row styling
   ↓
8. START AUTO-REFRESH
   ↓
   setInterval(loadContacts, 30000)
   ↓
9. READY FOR ADMIN INTERACTION
```

---

## Common Admin Scenarios

### Scenario 1: Morning Inbox Review

**What Happens:**
1. Admin arrives at 8:00 AM
2. Opens Contact Inquiries page
3. Sees stats: Total 52, Unread 12
4. Clicks "Unread" tab
5. List filters to 12 pending inquiries
6. Starts from top (oldest first)
7. Clicks first inquiry (ID: #41)
8. Expands, reads message: "What are your membership prices?"
9. Clicks "Reply"
10. Types response with pricing details
11. Clicks "Send Reply"
12. Email sent, inquiry auto-marked "read"
13. Moves to next inquiry
14. Repeats for all 12
15. Unread count: 12 → 0
16. Morning inbox cleared

---

### Scenario 2: Quick Contact Lookup

**What Happens:**
1. Member calls front desk: "Did you get my contact form?"
2. Admin searches "john smith"
3. List filters to 1 result
4. Expands row: Submitted Nov 9, 2:15 PM
5. Reads message: "I'd like to schedule a tour"
6. Tells member: "Yes, received Nov 9. We'll reply via email shortly."
7. Clicks "Reply" immediately
8. Types: "Hi John, we'd love to show you around! Are you available tomorrow at 3 PM?"
9. Sends reply
10. Member gets email in real-time during call
11. Tour scheduled
12. Excellent customer service

---

### Scenario 3: Handling Spam

**What Happens:**
1. Admin reviews "Unread" tab
2. Sees inquiry: "BUY CHEAP SUPPLEMENTS CLICK HERE!!!"
3. Clearly spam
4. Expands row to confirm
5. Email: "spammer@sketchy.com"
6. Clicks "Delete" button
7. Confirms: "Are you sure? Cannot be undone."
8. Confirms deletion
9. Spam inquiry permanently removed
10. Unread count updates
11. Inbox cleaned

---

### Scenario 4: Archiving Old Inquiries

**What Happens:**
1. Admin reviews "Read" tab (handled inquiries)
2. Sees inquiries from 6 months ago
3. All already replied to and resolved
4. Decides to clean up list
5. Clicks "Archive" on old inquiries (one by one)
6. Confirms each archival
7. Old inquiries removed from main list
8. Total count decreases
9. Inbox focused on recent/active inquiries
10. Better organization

---

### Scenario 5: Flagging for Follow-Up

**What Happens:**
1. Admin receives complex inquiry requiring manager approval
2. Reads message, needs more information
3. Clicks "Mark as Unread" (even though already read)
4. Sets status back to "unread"
5. Row highlighted again (orange background)
6. Unread count increases
7. Later, manager reviews unread tab
8. Sees flagged inquiry
9. Provides answer
10. Admin replies to customer
11. Marks as read
12. Using "unread" as a flag system

---

### Scenario 6: Sending Copy to Self

**What Happens:**
1. Admin replies to inquiry about class schedule
2. Wants to remember what was promised
3. Ensures "Send copy to admin email" is checked (default)
4. Sends reply
5. Customer receives email: "Classes are Tuesdays 6-7 PM..."
6. Admin receives copy in personal inbox
7. Admin can reference later
8. Documentation of communication
9. Accountability and record-keeping

---

## Important Notes and Limitations

### Things to Know

1. **Admin Role Required**
   - Must have `role = 'admin'`
   - Non-admins cannot access
   - Customer contact form submissions stored in database

2. **Real-Time Search**
   - Searches first name + last name combined
   - Searches email address
   - Does NOT search message content
   - Case-insensitive

3. **Auto-Refresh Enabled**
   - Fetches new contacts every 30 seconds
   - Useful for multiple admins
   - New submissions appear automatically

4. **Reply Sends Email**
   - Requires server email configuration (SMTP)
   - Uses PHPMailer or similar
   - 30-second timeout for sending
   - If fails, shows error message

5. **Archive vs Delete**
   - **Archive:** Soft removal, preserved in database
   - **Delete:** Permanent removal, cannot undo
   - Use archive for safety

6. **Auto Mark as Read on Reply**
   - Sending reply automatically marks inquiry as "read"
   - Assumes replied = handled
   - Can manually mark as unread again if needed

7. **No Bulk Actions**
   - Cannot select multiple contacts
   - Cannot bulk delete/archive/mark read
   - One at a time only

8. **Original Message Included in Reply**
   - Reply email includes original inquiry at bottom
   - Provides context for recipient
   - Professional email format

### What This Page Doesn't Do

- **Doesn't create inquiries** (customers submit via contact form)
- **Doesn't show archived contacts** (separate view if implemented)
- **Doesn't search message content** (name/email only)
- **Doesn't categorize inquiries** (no tags/categories)
- **Doesn't assign to specific admins** (shared inbox)
- **Doesn't track conversation threads** (one-way replies)
- **Doesn't integrate with email inbox** (standalone system)
- **Doesn't show reply history** (can't see previous admin replies)
- **Doesn't export data** (no CSV/Excel download)
- **Doesn't have templates** (no canned responses)

---

## Navigation

### How Admins Arrive Here
- **Dashboard:** Click "Review Now →" on "Unread Messages" card
- **Sidebar menu:** "Contacts" or "Inquiries" link
- **Direct URL:** `fitxbrawl.com/public/php/admin/contacts.php`

### Where Admins Go Next
- **Dashboard** (`admin.php`) - Return to overview
- **Users** (`users.php`) - Check if inquirer is existing member
- **Membership** (`membership.php`) - Provide pricing info
- **Activity Log** (`activity-log.php`) - Audit reply actions

---

## Tips for Admins

### Best Practices

1. **Check Unread Daily**
   - Start each day reviewing unread tab
   - Clear inbox to zero
   - Prompt responses improve customer satisfaction

2. **Reply Promptly**
   - Goal: Respond within 24 hours
   - Faster replies = higher conversion
   - Use reply modal for efficiency

3. **Always Send Copy to Self**
   - Keep "Send copy" checked (default)
   - Maintain email trail
   - Reference later if customer follows up

4. **Use Search for Quick Lookups**
   - Customer calls? Search their name
   - Faster than scrolling
   - Instant results

5. **Archive, Don't Delete**
   - Preserve customer communication
   - Delete only spam
   - Archive keeps records

6. **Flag with "Mark as Unread"**
   - Need manager input? Mark as unread
   - Complex inquiry? Keep highlighted
   - Use as to-do flag

7. **Include Details in Replies**
   - Pricing, schedules, locations
   - Answer all questions in message
   - Reduce back-and-forth

---

## Final Thoughts

The contact inquiries page is your customer communication hub—simple, efficient, and focused. The expandable rows keep the interface clean (you can scan many inquiries at once), while the expand-on-click reveals details only when needed. The three-tab filtering (All/Unread/Read) gives you instant inbox organization without complex filters.

The reply modal is brilliantly streamlined: click Reply, type response, send—done. Pre-filled subject lines and auto-included original messages make replies professional without extra work. The "send copy to self" default ensures you always have a record. The auto-mark-as-read after replying is smart: if you replied, you've handled it.

The unread row highlighting (orange background) makes pending inquiries visually jump out—no missed messages. The auto-refresh (30 seconds) means new inquiries appear without you lifting a finger. The archive feature keeps your inbox clean without losing data.

It's not a full CRM system—no conversation threading, no assignment features, no categories—but for a gym's contact management, it's exactly right: lightweight, fast, and focused on what matters: reading messages and sending replies.

