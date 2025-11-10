# Members Management Page Documentation
**File:** `public/php/admin/users.php`  
**Purpose:** View and manage all gym members and their memberships  
**User Access:** Admins only (role-based authentication)

---

## What This Page Does

The members management page is your comprehensive member directory and membership tracker. View all registered members, filter by membership plan, search by name, see membership details (duration, payment, dates), and review complete membership history for each member. Think of it as your member database with instant filtering and detailed record access—essential for member support, billing inquiries, and membership monitoring.

### Who Can Access This Page
- **Admins only:** Must have `role = 'admin'`
- **Login required:** Redirects non-authenticated users
- **Read-only view:** Information display, no editing on this page

### What It Shows
- **All gym members:** Complete member list with active memberships
- **Plan filtering:** Filter by membership tier (Gladiator, Champion, etc.)
- **Search functionality:** Find members by name
- **Member details:** Email, payment, start/end dates, country
- **Membership history:** All past and current memberships per member
- **Total member count:** Real-time statistics

---

## The Page Experience

### **1. Page Header**

**Title:**
- "Members Management"
- Large, bold heading
- Clear page identifier

**Subtitle:**
- "View and manage gym members and their memberships"
- Explains page purpose
- Professional tone

---

### **2. Toolbar Section**

#### **Search Box (Left Side)**

**What It Shows:**
- 🔍 Magnifying glass icon
- Input field: "Search members by name..."
- Full-width search bar
- Prominent placement

**How It Works:**
- Type member name (full or partial)
- Real-time filtering as you type
- Case-insensitive search
- Searches member names only (not email/plan)

**Search Examples:**
- "John" - Finds John Smith, Johnathan, etc.
- "maria" - Finds Maria Garcia, Maria Chen
- "doe" - Finds Jane Doe, John Doe

---

#### **Stats Summary (Right Side)**

**What It Shows:**
- Label: "Total Members:"
- Number: Bold count
- Example: "142"
- Real-time count

**What It Means:**
- Total members in database
- Updates based on filters
- Changes when searching/filtering
- Quick headcount reference

---

### **3. Plan Filter Tabs**

**Tab Options:**

1. **All** (Default - Active on load)
   - Shows all members regardless of plan
   - No filtering
   - Complete member list

2. **Gladiator**
   - All-access premium plan members only
   - Highest tier filter

3. **Brawler**
   - Muay Thai focused plan members only

4. **Champion**
   - Boxing specialized plan members only

5. **Clash**
   - MMA training plan members only

6. **Resolution**
   - "Resolution Regular" plan members
   - Gym-only members (regular and student combined)

**Tab Behavior:**
- Click tab to filter
- Active tab highlighted (gold/blue)
- Inactive tabs gray
- One active at a time
- Works with search (combined filters)

---

### **4. Members List Section**

**Loading State:**
- Shows while fetching data
- Spinner icon (rotating)
- Text: "Loading members..."
- Brief display

**Empty State:**
- 👥❌ Users slash icon
- Title: "No Members Found"
- Message: "No members match your current filter"
- Appears when filters produce no results

---

### **Member Row Structure**

Each member displayed as expandable row:

#### **Collapsed View (Default)**

**Expand Icon (Left):**
- ▶️ Chevron right icon
- Indicates expandable
- Click to expand

**Member Info (Center):**
- **Member Name:**
  - Full name
  - Large, bold text
  - Example: "John Smith"
  
- **Member Plan:**
  - Plan name below name
  - Smaller text
  - Example: "Gladiator"
  - Color-coded by plan

**Duration (Right):**
- Membership duration
- Format: "30 days"
- Quick reference
- Example: "90 days", "180 days"

---

#### **Expanded View (Click to Expand)**

**Chevron Icon Changes:**
- ▶️ becomes ▼ (chevron down)
- Indicates expanded state

**Details Grid Shows:**

**Row 1: Email**
- Label: "Email"
- Value: Clickable email link
- Example: `john.smith@email.com`
- Click to open default email client

**Row 2: Total Payment**
- Label: "Total Payment"
- Value: Amount in Philippine Pesos
- Format: "₱2,000"
- Highlighted in gold/yellow
- Shows what member paid

**Row 3: Start Date**
- Label: "Start Date"
- Value: Membership activation date
- Format: "Nov 10, 2025"
- When membership began

**Row 4: End Date**
- Label: "End Date"
- Value: Membership expiration date
- Format: "Dec 10, 2025"
- When membership expires

**Row 5: Date Submitted**
- Label: "Date Submitted"
- Value: Payment submission date
- Format: "Nov 9, 2025"
- When member submitted payment proof

**Row 6: Country**
- Label: "Country"
- Value: Member's country
- Example: "Philippines"
- Shows "N/A" if not provided

---

**Actions Section:**

**View History Button:**
- 🕐 Clock rotate icon
- Text: "View History"
- Blue/gold button
- Opens side panel with full membership history

---

### **5. History Side Panel**

**When It Appears:**
- Click "View History" on any member row
- Slides in from right side
- Overlay darkens main content
- Modal-style interaction

---

#### **Panel Header**

**Title:**
- "[Member Name]'s Membership History"
- Example: "John Smith's Membership History"
- Personalized to selected member

**Close Button:**
- ❌ X icon
- Top-right corner
- Closes panel

---

#### **Panel Body**

**Timeline Display:**
- Vertical timeline of all memberships
- Most recent at top
- Historical order

**Empty State (If No History):**
- 🕐 Clock icon
- Message: "No membership history found"
- Appears for new members

---

#### **History Item Structure**

Each past/current membership shown as card:

**History Header:**
- **Plan Name:**
  - Left side
  - Example: "Gladiator"
  - Bold text
  
- **Date Submitted:**
  - Right side
  - Format: "Nov 10, 2025"
  - When payment was submitted

**History Details Grid:**

**Row 1: Duration**
- Label: "Duration"
- Value: "30 days", "90 days", etc.

**Row 2: Status**
- Label: "Status"
- Value: Status at that time
- Examples: "active", "expired", "cancelled"

**Row 3: Start Date**
- Label: "Start Date"
- Value: When that membership began
- Format: "Oct 1, 2025"

**Row 4: End Date**
- Label: "End Date"
- Value: When that membership ended
- Format: "Oct 31, 2025"

**Payment Footer:**
- "Total Payment: ₱2,000"
- Highlighted in gold
- Shows amount paid for that membership

---

**Example History Timeline:**

```
┌─────────────────────────────────────────┐
│ John Smith's Membership History         │
│                                   [×]   │
├─────────────────────────────────────────┤
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ Gladiator          Nov 10, 2025    │ │
│ │                                     │ │
│ │ Duration: 90 days                   │ │
│ │ Status: active                      │ │
│ │ Start: Nov 10, 2025                 │ │
│ │ End: Feb 8, 2026                    │ │
│ │                                     │ │
│ │ Total Payment: ₱6,000               │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ Champion           Aug 1, 2025     │ │
│ │                                     │ │
│ │ Duration: 30 days                   │ │
│ │ Status: expired                     │ │
│ │ Start: Aug 1, 2025                  │ │
│ │ End: Aug 31, 2025                   │ │
│ │                                     │ │
│ │ Total Payment: ₱2,000               │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## How Features Work

### **1. Plan Filtering**

**Tab Click:**
1. Click a plan tab (e.g., "Gladiator")
2. Tab becomes active (highlighted)
3. Other tabs deactivate
4. Member list filters instantly
5. Only Gladiator members show
6. Total count updates

**"All" Tab:**
- Resets filter
- Shows all members
- Default state

**Combined with Search:**
- Tab filter + search work together
- Example: "Gladiator" tab + search "John" = Gladiator members named John
- Flexible filtering

---

### **2. Real-Time Search**

**Search Input:**
1. User types in search box
2. JavaScript captures input
3. Filters member list as you type
4. No "Search" button needed
5. Instant results

**How It Filters:**
```javascript
filtered = members.filter(m =>
    m.name.toLowerCase().includes(searchTerm.toLowerCase())
);
```

**Clear Search:**
- Delete text from search box
- Full list returns
- Maintains tab filter

---

### **3. Expand/Collapse Rows**

**Click Member Row:**
1. User clicks anywhere on member header
2. Row expands (slides down animation)
3. Details grid appears
4. Chevron rotates (▶️ → ▼)

**Click Again:**
1. Row collapses (slides up)
2. Details hidden
3. Chevron rotates back (▼ → ▶️)

**Multiple Rows:**
- Can expand multiple rows simultaneously
- Each row independent
- No auto-collapse of others

---

### **4. View History**

**Click "View History" Button:**
1. JavaScript fetches history via API
2. API call: `api/get_member_history.php?user_id=42`
3. Server queries all memberships for that user
4. Returns JSON array of membership records
5. JavaScript builds timeline HTML
6. Side panel slides in from right
7. Overlay appears over main content
8. History displayed in chronological order

**Close Panel:**
- Click X button (top-right)
- Click overlay (outside panel)
- Panel slides out to right
- Returns to member list

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
   - Show search box and stats
   - Show plan tabs
   - Show loading state in member list
   ↓
3. FETCH MEMBERS VIA API
   ↓
   JavaScript calls: api/get_members.php
   ↓
4. API QUERIES DATABASE
   ↓
   SELECT u.id as user_id, u.name, u.email, um.plan_name, 
          um.duration, um.total_payment, um.start_date, 
          um.end_date, um.date_submitted, u.country
   FROM users u
   LEFT JOIN user_memberships um ON u.id = um.user_id
   WHERE u.role = 'member'
   AND um.membership_status = 'active'
   ORDER BY u.name ASC
   ↓
5. API RETURNS JSON
   ↓
   {
     "success": true,
     "members": [
       {
         "user_id": 1,
         "name": "John Smith",
         "email": "john@email.com",
         "plan_name": "Gladiator",
         "duration": 90,
         "total_payment": "6000.00",
         "start_date": "2025-11-10",
         "end_date": "2026-02-08",
         "date_submitted": "2025-11-09",
         "country": "Philippines"
       },
       ...
     ]
   }
   ↓
6. JAVASCRIPT RENDERS MEMBERS
   ↓
   - Store all members in `allMembers` array
   - Update total count
   - Loop through members
   - Create member row HTML for each
   - Display in collapsed state
   ↓
7. USER INTERACTS
   ↓
   - Filter by tab
   - Search by name
   - Expand/collapse rows
   - View history
```

---

### History Fetching Process

```
1. USER CLICKS "VIEW HISTORY"
   ↓
   Pass user_id to viewHistory() function
   ↓
2. API CALL
   ↓
   GET api/get_member_history.php?user_id=42
   ↓
3. SERVER QUERIES
   ↓
   SELECT um.*, m.plan_name
   FROM user_memberships um
   JOIN memberships m ON um.plan_id = m.id
   WHERE um.user_id = 42
   ORDER BY um.date_submitted DESC
   ↓
4. RETURNS JSON
   ↓
   {
     "success": true,
     "history": [
       {
         "plan_name": "Gladiator",
         "duration": 90,
         "total_payment": "6000.00",
         "membership_status": "active",
         "start_date": "2025-11-10",
         "end_date": "2026-02-08",
         "date_submitted": "2025-11-10"
       },
       {
         "plan_name": "Champion",
         "duration": 30,
         "total_payment": "2000.00",
         "membership_status": "expired",
         "start_date": "2025-08-01",
         "end_date": "2025-08-31",
         "date_submitted": "2025-07-31"
       }
     ]
   }
   ↓
5. JAVASCRIPT BUILDS TIMELINE
   ↓
   - Loop through history array
   - Create history item HTML for each
   - Display in side panel
   - Show panel with slide-in animation
```

---

## Common Admin Scenarios

### Scenario 1: Finding a Specific Member

**What Happens:**
1. Admin receives call from "Maria Garcia"
2. Opens Members Management page
3. Types "Maria" in search box
4. List filters instantly to 3 results:
   - Maria Garcia
   - Maria Chen
   - Maria Lopez
5. Finds correct Maria Garcia
6. Clicks row to expand
7. Sees details:
   - Email: maria.garcia@email.com
   - Plan: Gladiator
   - Payment: ₱6,000
   - End Date: Dec 15, 2025
8. Answers member's question about expiration date
9. Quick, efficient support

---

### Scenario 2: Checking Gladiator Members

**What Happens:**
1. Admin wants to know Gladiator member count
2. Opens Members Management
3. Clicks "Gladiator" tab
4. List filters to show only Gladiator members
5. Sees "Total Members: 45" (updates to Gladiator count)
6. Scrolls through all 45 Gladiator members
7. Notes high-value customer base
8. Plans retention campaign for Gladiators

---

### Scenario 3: Reviewing Member History

**What Happens:**
1. Member "John Smith" asks about upgrade options
2. Admin opens Members Management
3. Searches "John Smith"
4. Expands John's row
5. Current plan: "Champion"
6. Clicks "View History"
7. Side panel opens with history:
   - Current: Champion (90 days, active)
   - Previous: Resolution Regular (30 days, expired)
   - First: Clash (30 days, expired)
8. Sees John started with Clash, tried Resolution, now on Champion
9. Suggests Gladiator as next upgrade (all-access)
10. Provides informed recommendation

---

### Scenario 4: Investigating Payment Discrepancy

**What Happens:**
1. Finance team reports payment mismatch
2. Admin searches member "Alex Taylor"
3. Expands row to see:
   - Plan: Brawler (₱1,800/month for 30 days)
   - Total Payment: ₱2,000
4. Notes discrepancy: Should be ₱1,800, shows ₱2,000
5. Clicks "View History"
6. Sees previous Brawler membership also ₱2,000
7. Realizes old pricing (before price update)
8. Confirms with finance: Grandfathered pricing
9. Mystery solved

---

### Scenario 5: Monthly Member Count by Plan

**What Happens:**
1. Admin needs end-of-month report
2. Opens Members Management
3. Clicks "All" tab: Total Members: 142
4. Clicks "Gladiator" tab: Total: 38
5. Clicks "Brawler" tab: Total: 22
6. Clicks "Champion" tab: Total: 31
7. Clicks "Clash" tab: Total: 18
8. Clicks "Resolution" tab: Total: 33
9. Records breakdown:
   - Gladiator: 38 (27%)
   - Champion: 31 (22%)
   - Resolution: 33 (23%)
   - Brawler: 22 (15%)
   - Clash: 18 (13%)
10. Submits report to management

---

### Scenario 6: Supporting Member Renewal

**What Happens:**
1. Member calls: "When does my membership end?"
2. Admin searches member name
3. Expands row
4. Sees "End Date: Nov 15, 2025"
5. Tells member: "Expires in 5 days"
6. Member asks: "What plan was I on?"
7. Admin clicks "View History"
8. Sees: Current plan is Gladiator (90 days)
9. Tells member: "You're on Gladiator, 90-day plan"
10. Member renews same plan
11. Quick, accurate support

---

## Important Notes and Limitations

### Things to Know

1. **Admin Role Required**
   - Must have `role = 'admin'`
   - Non-admins redirected to login
   - No member/trainer access

2. **Shows Active Memberships**
   - Displays members with `membership_status = 'active'`
   - Expired members may not appear in main list
   - Full history accessible via "View History"

3. **Read-Only View**
   - Cannot edit member details here
   - Cannot update payments here
   - Cannot change plans here
   - Information display only
   - Use other pages for modifications

4. **Real-Time Search/Filter**
   - No page reloads
   - Instant filtering via JavaScript
   - All filtering client-side (fast)
   - Data fetched once on page load

5. **History Per Member**
   - "View History" shows all memberships for that member
   - Past and current included
   - Chronological order (newest first)
   - Complete membership record

6. **Total Count Updates**
   - Changes based on active filter
   - "All" tab: Total all members
   - Specific tab: Total for that plan
   - Search applied: Total matching results

### What This Page Doesn't Do

- **Doesn't allow editing** (view-only)
- **Doesn't approve memberships** (use subscriptions.php)
- **Doesn't send emails** (no communication features)
- **Doesn't show pending requests** (use subscriptions.php)
- **Doesn't delete members** (use users API or dedicated page)
- **Doesn't show trainers** (trainers.php for that)
- **Doesn't show payments** (no receipt viewer)
- **Doesn't allow exporting** (no CSV/Excel download)
- **Doesn't show login activity** (use activity log)
- **Doesn't track attendance** (no check-in data)

---

## Navigation

### How Admins Arrive Here
- **Dashboard:** Not directly linked (must use sidebar)
- **Sidebar menu:** "Members" or "Users" link
- **Direct URL:** `fitxbrawl.com/public/php/admin/users.php`

### Where Admins Go Next
- **Subscriptions** (`subscriptions.php`) - Approve pending memberships
- **Contacts** (`contacts.php`) - Respond to member inquiries
- **Dashboard** (`admin.php`) - Return to overview
- **Activity Log** (`activity-log.php`) - Audit member-related actions

---

## Visual Design

### Member Row Layout (Collapsed)

```
┌───────────────────────────────────────────────────┐
│ ▶️  John Smith                          90 days  │
│     Gladiator                                    │
└───────────────────────────────────────────────────┘
```

### Member Row Layout (Expanded)

```
┌───────────────────────────────────────────────────┐
│ ▼  John Smith                          90 days   │
│     Gladiator                                    │
├───────────────────────────────────────────────────┤
│  Email: john.smith@email.com                     │
│  Total Payment: ₱6,000                           │
│  Start Date: Nov 10, 2025                        │
│  End Date: Feb 8, 2026                           │
│  Date Submitted: Nov 9, 2025                     │
│  Country: Philippines                            │
│                                                  │
│  [🕐 View History]                               │
└───────────────────────────────────────────────────┘
```

### Tab Styling

**Active Tab:**
- Gold/blue background
- White text
- Bottom border accent

**Inactive Tab:**
- Gray background
- Dark text
- Hover effect

---

## Technical Details (Simplified)

### API Endpoints

**Get Members:**
- **URL:** `api/get_members.php`
- **Method:** GET
- **Returns:** JSON array of members with active memberships

**Get Member History:**
- **URL:** `api/get_member_history.php?user_id=42`
- **Method:** GET
- **Parameters:** `user_id` (member's user ID)
- **Returns:** JSON array of all memberships for that user

---

### Database Query (Get Members)

```sql
SELECT 
    u.id as user_id,
    u.name,
    u.email,
    u.country,
    um.id,
    um.plan_name,
    um.duration,
    um.total_payment,
    um.start_date,
    um.end_date,
    um.date_submitted
FROM users u
LEFT JOIN user_memberships um ON u.id = um.user_id
WHERE u.role = 'member'
AND um.membership_status = 'active'
ORDER BY u.name ASC
```

---

### JavaScript Filtering Logic

**Plan Filter:**
```javascript
if (currentFilter !== 'all') {
    filtered = members.filter(m => m.plan_name === currentFilter);
}
```

**Search Filter:**
```javascript
if (searchTerm) {
    filtered = filtered.filter(m =>
        m.name.toLowerCase().includes(searchTerm.toLowerCase())
    );
}
```

**Combined:**
- Both filters applied sequentially
- Results must match both conditions
- Flexible, powerful filtering

---

## Security Features

### 1. **Role-Based Access**
- Checks `role = 'admin'` before page loads
- Non-admins redirected immediately
- Protects member data

### 2. **Session Validation**
- Requires active admin session
- Prevents unauthorized access

### 3. **Data Sanitization**
- `escapeHtml()` on all displayed data
- Prevents XSS attacks
- Safe output of member names, emails

### 4. **API Security**
- Backend API checks admin role
- Prepared statements in queries
- Prevents SQL injection

---

## Tips for Admins

### Best Practices

1. **Use Search for Speed**
   - Don't scroll through 142 members
   - Type name, find instantly
   - Saves time on support calls

2. **Filter Before Searching**
   - Know member's plan? Filter first
   - Then search within that plan
   - Narrow results faster

3. **Check History for Context**
   - Member support: View history first
   - Understand member journey
   - Provide informed answers

4. **Monitor Plan Distribution**
   - Use tabs to see plan popularity
   - Identify best-selling plans
   - Adjust marketing accordingly

5. **Expand Rows Sparingly**
   - Only expand when needed
   - Details take screen space
   - Collapse after viewing

---

## Final Thoughts

The members management page strikes a balance between simplicity and depth. The collapsed row design keeps the list scannable (you can see many members at once), while the expand-on-click reveals details only when needed. The plan tabs and search work seamlessly together, giving you flexible filtering without complex form controls.

The history panel is the standout feature—it gives complete membership context without leaving the page. Whether you're supporting a member, investigating a payment issue, or planning retention campaigns, this page delivers the information fast. It's not flashy, but it's efficient—exactly what an admin tool should be.

