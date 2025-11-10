# Admin Dashboard Documentation
**File:** `public/php/admin/admin.php`  
**Purpose:** Main admin control center with system overview and statistics  
**User Access:** Admins only (role-based authentication)

---

## What This Page Does

The admin dashboard is your command center for FitXBrawl gym management. It provides a high-level overview of gym operations: member count, trainer count, pending approvals, unread messages, and recent admin activity. Think of it as your morning briefing—one glance shows you what needs attention and what's running smoothly. It's the first page you see after logging in as an admin.

### Who Can Access This Page
- **Admins only:** Must have `role = 'admin'`
- **Login required:** Redirects non-authenticated users
- **Full system access:** Dashboard entry point to all admin features

### What It Shows
- **Welcome header:** Personalized greeting with admin username
- **Statistics cards:** 5 key metrics (members, trainers, pending items, messages)
- **Recent activity log:** Last 10 admin actions across the system
- **Quick action links:** Direct access to pending items
- **System health snapshot:** At-a-glance operational status

---

## The Page Experience

### **1. Page Header**

**Welcome Message:**
- "Welcome, [Admin Username]"
- Personalized with your admin username
- Example: "Welcome, JohnAdmin"
- Professional greeting

**Subtitle:**
- "Here's an overview of your gym's activity."
- Sets context for dashboard purpose
- Informative tone

---

### **2. Dashboard Statistics Cards**

The page displays 5 key metric cards in a grid layout:

---

#### **Card 1: Total Members**

**Icon:**
- 👥 Users icon (Font Awesome)
- Blue/gray color
- Represents member base

**Number:**
- Large, bold count
- Example: "142"
- Real-time from database

**Label:**
- "Total Members"
- Counts users with `role = 'member'`

**What It Means:**
- Current registered member count
- Includes active and inactive memberships
- Excludes trainers and admins
- Growth indicator

---

#### **Card 2: Active Trainers**

**Icon:**
- 🏋️ Dumbbell icon
- Gold/yellow color
- Represents training staff

**Number:**
- Large, bold count
- Example: "8"
- Real-time from database

**Label:**
- "Active Trainers"
- Counts users with `role = 'trainer'`

**What It Means:**
- Current trainer staff count
- All trainers (regardless of booking load)
- Staff capacity indicator
- Hiring needs tracker

---

#### **Card 3: Pending Subscriptions**

**Icon:**
- 🕐 Clock icon
- Orange color (if pending items exist)
- Represents waiting approvals

**Number:**
- Large, bold count
- Example: "5"
- Shows pending membership requests

**Label:**
- "Pending Subscriptions"

**Special Features:**

**Highlight State:**
- If count > 0: Card highlighted with gold border
- Visual indicator: Needs attention
- Class: `has-pending`

**Action Link:**
- "Review Now →" button (only if count > 0)
- Links to `subscriptions.php`
- Quick access to approval page
- Call-to-action styling

**What It Means:**
- Members submitted payment proofs
- Awaiting admin approval
- Revenue opportunities
- Member onboarding queue

---

#### **Card 4: Pending Reservations**

**Icon:**
- 📅✓ Calendar check icon
- Orange color (if pending items exist)
- Represents booking requests

**Number:**
- Large, bold count
- Example: "3"
- Shows pending training session requests

**Label:**
- "Pending Reservations"

**Special Features:**

**Highlight State:**
- If count > 0: Card highlighted with gold border
- Visual alert
- Class: `has-pending`

**Action Link:**
- "Review Now →" button (only if count > 0)
- Links to `reservations.php`
- Direct to approval page

**What It Means:**
- Training sessions awaiting confirmation
- Trainer schedule coordination needed
- Member experience pending
- Service delivery queue

---

#### **Card 5: Unread Messages**

**Icon:**
- ✉️ Envelope icon
- Red/orange color (if unread exist)
- Represents contact inquiries

**Number:**
- Large, bold count
- Example: "7"
- Shows unread contact form submissions

**Label:**
- "Unread Messages"

**Special Features:**

**Highlight State:**
- If count > 0: Card highlighted
- Class: `has-unread`
- Attention indicator

**Action Link:**
- "View Messages →" button (only if unread > 0)
- Links to `contacts.php`
- Quick access to inbox

**What It Means:**
- Contact form submissions from public site
- Member inquiries
- Potential new members
- Support requests
- Communication backlog

---

### **3. Recent Activity Log Section**

**Section Header:**
- "Recent Activity"
- Left-aligned title
- "View All" button (top-right)
  - Links to `activity-log.php`
  - Access full activity history

---

#### **Activity Table Structure**

**Table Headers:**

| Icon | Admin | Action | Details | Date |
|------|-------|--------|---------|------|

**Columns:**

**Column 1: Icon (40px wide)**
- Font Awesome icon representing action type
- Color-coded by action category
- Visual identification

**Column 2: Admin**
- Admin username who performed action
- Example: "JohnAdmin"
- Bold text
- Accountability tracker

**Column 3: Action**
- Action type description
- Examples:
  - "User Created"
  - "Membership Approved"
  - "Trainer Added"
  - "Equipment Updated"
  - "Subscription Rejected"
- Human-readable format (converts underscores to spaces, capitalizes)

**Column 4: Details**
- Specific action details
- Examples:
  - "Created member account for john.doe@email.com"
  - "Approved Gladiator membership for user #42"
  - "Added trainer Mike Johnson (Boxing)"
  - "Updated equipment: Treadmill #3 - Status: Maintenance"
- Contextual information

**Column 5: Date**
- Relative timestamp
- Examples:
  - "Just now" (< 1 minute ago)
  - "5 mins ago" (< 1 hour ago)
  - "2 hours ago" (< 24 hours ago)
  - "3 days ago" (< 7 days ago)
  - "Nov 10, 2025 2:30 PM" (> 30 days ago)
- Gray text, smaller font

---

#### **Activity Row Display**

**Recent Activity Examples:**

**Row 1:**
```
[👤] JohnAdmin | User Created | Created member account for sarah.j@email.com | 5 mins ago
```

**Row 2:**
```
[✅] AdminMike | Membership Approved | Approved Champion membership for user #18 | 1 hour ago
```

**Row 3:**
```
[➕] JohnAdmin | Trainer Added | Added trainer David Chen (MMA) | 2 hours ago
```

**Row 4:**
```
[❌] AdminMike | Subscription Rejected | Rejected Gladiator membership - Invalid receipt | 3 hours ago
```

**Row 5:**
```
[🛠️] JohnAdmin | Equipment Updated | Updated Punching Bag #2 - Status: Available | 1 day ago
```

---

#### **Empty State**

**When No Activity:**
- Single row spanning all columns
- Text: "No recent activity"
- Centered, gray text
- Large padding
- Indicates fresh system or low admin usage

---

## How Features Work

### **1. Real-Time Statistics**

**Data Fetching:**

**Total Members:**
```sql
SELECT COUNT(*) FROM users WHERE role = 'member'
```

**Active Trainers:**
```sql
SELECT COUNT(*) FROM users WHERE role = 'trainer'
```

**Pending Subscriptions:**
```sql
SELECT COUNT(*) FROM subscriptions WHERE status = 'Pending'
```
*(Or from `user_memberships` with `request_status = 'pending'`)*

**Pending Reservations:**
```sql
SELECT COUNT(*) FROM reservations WHERE status = 'Pending'
```

**Unread Messages:**
```sql
SELECT COUNT(*) FROM contact 
WHERE status = 'unread' 
AND (archived = 0 OR archived IS NULL) 
AND deleted_at IS NULL
```

**Refresh:**
- Stats update on page reload
- No auto-refresh (manual F5)
- Real-time on each visit

---

### **2. Card Highlighting System**

**Normal State:**
- White background
- Standard styling
- No border accent

**Highlighted State (Pending Items):**
- Gold/yellow border
- Slightly elevated (box shadow)
- Class: `has-pending` or `has-unread`
- Visual priority indicator

**Action Link Appearance:**
- Only shows if count > 0
- "Review Now →" or "View Messages →" text
- Gold/blue link color
- Clickable, underlines on hover

---

### **3. Activity Log System**

**Activity Logging:**
- `ActivityLogger::init($conn)` - Initializes logger
- `ActivityLogger::logActivity()` - Logs actions across system
- Tracks: Admin ID, action type, details, timestamp
- Stored in `admin_activity_log` table

**Display Logic:**
- `ActivityLogger::getActivities(10)` - Fetches last 10 activities
- `ActivityLogger::getActivityIcon()` - Returns icon and color for action
- Recent first (DESC order by timestamp)

**Icon Mapping Examples:**
- "user_created" → 👤 fa-user (blue)
- "membership_approved" → ✅ fa-check-circle (green)
- "membership_rejected" → ❌ fa-times-circle (red)
- "trainer_added" → ➕ fa-plus-circle (green)
- "equipment_updated" → 🛠️ fa-wrench (orange)

---

### **4. Time Ago Calculation**

**Function:** `timeAgo($timestamp)`

**Logic:**
- < 60 seconds: "Just now"
- < 1 hour: "X mins ago"
- < 24 hours: "X hours ago"
- < 7 days: "X days ago"
- < 30 days: "X weeks ago"
- > 30 days: "Nov 10, 2025 2:30 PM"

**Human-Friendly:**
- No exact timestamps for recent events
- Relative time for quick scanning
- Full date for old events

---

## Data Flow

### Page Load Process

```
1. ADMIN ACCESSES DASHBOARD
   ↓
   Check role: Is admin?
   ↓
2. ROLE VERIFICATION
   ↓
   If role ≠ 'admin':
      → Redirect to login.php
   If role = 'admin':
      → Continue
   ↓
3. FETCH STATISTICS
   ↓
   Query 1: Count members (role='member')
   Query 2: Count trainers (role='trainer')
   Query 3: Count pending subscriptions
   Query 4: Count pending reservations
   Query 5: Count unread messages
   ↓
4. FETCH RECENT ACTIVITY
   ↓
   Query: SELECT * FROM admin_activity_log
          ORDER BY timestamp DESC LIMIT 10
   ↓
5. RENDER DASHBOARD
   ↓
   - Display welcome header
   - Render 5 stat cards with counts
   - Highlight cards with pending items
   - Show action links if needed
   - Render activity log table
   - Calculate time ago for each activity
   ↓
6. USER INTERACTS
   ↓
   - Click "Review Now" → subscriptions.php
   - Click "View Messages" → contacts.php
   - Click "View All" → activity-log.php
   - Click sidebar menu → Other admin pages
```

---

## Common Admin Scenarios

### Scenario 1: Morning Dashboard Check

**What Happens:**
1. Admin "John" logs in at 8:00 AM
2. Lands on admin dashboard
3. Sees welcome: "Welcome, JohnAdmin"
4. Scans statistics cards:
   - Total Members: 142 (growing)
   - Active Trainers: 8 (stable)
   - Pending Subscriptions: 5 (needs attention!)
   - Pending Reservations: 3 (needs attention!)
   - Unread Messages: 7 (needs attention!)
5. Notes 3 cards highlighted (pending items)
6. Clicks "Review Now" on Pending Subscriptions card
7. Redirected to subscriptions.php
8. Approves 5 membership requests
9. Returns to dashboard
10. Pending Subscriptions now shows: 0 (no highlight)
11. Repeats for Pending Reservations and Unread Messages
12. Dashboard clear, ready for day

---

### Scenario 2: Monitoring Recent Activity

**What Happens:**
1. Admin "Sarah" wants to see what other admins did
2. Opens dashboard
3. Scrolls to "Recent Activity" section
4. Sees last 10 actions:
   - AdminMike approved 3 memberships (1 hour ago)
   - JohnAdmin added new trainer (2 hours ago)
   - AdminSarah updated equipment status (yesterday)
   - AdminMike rejected subscription (2 days ago)
5. Notices pattern: Mike handles memberships, John handles trainers
6. Good team distribution
7. Clicks "View All" for full history
8. Reviews last 100 actions for audit

---

### Scenario 3: Identifying System Issues

**What Happens:**
1. Admin checks dashboard
2. Sees unusual stats:
   - Total Members: 50 (was 142 yesterday - ERROR!)
   - Active Trainers: 0 (was 8 yesterday - ERROR!)
3. Realizes possible database issue
4. Checks activity log:
   - No suspicious deletions
5. Contacts technical support
6. Issue: Database connection to wrong table
7. Fixed by tech team
8. Dashboard stats return to normal

---

### Scenario 4: New Admin Orientation

**What Happens:**
1. New admin "Lisa" hired today
2. Account created by existing admin
3. Lisa logs in for first time
4. Sees dashboard
5. Reads welcome: "Welcome, LisaAdmin"
6. Reviews 5 stat cards:
   - Understands member count (gym size)
   - Understands trainer count (staff size)
   - Learns about pending approvals (daily tasks)
   - Sees unread messages (communication flow)
7. Clicks around cards to explore features
8. Clicks "Review Now" → sees subscriptions page
9. Backs out, explores other sidebar menu items
10. Returns to dashboard as home base
11. Comfortable with admin panel

---

### Scenario 5: End-of-Month Reporting

**What Happens:**
1. Admin needs monthly statistics
2. Opens dashboard on Nov 30, 2025
3. Records numbers:
   - Total Members: 158 (growth metric)
   - Active Trainers: 9 (staffing stable)
4. Clicks "View All" in activity log
5. Filters activity by date range (Nov 1-30)
6. Counts actions:
   - 42 memberships approved this month
   - 8 memberships rejected
   - 3 trainers added
   - 120 equipment updates
   - 87 contact messages handled
7. Compiles report for management
8. Shows healthy gym operations

---

## Important Notes and Limitations

### Things to Know

1. **Admin Role Required**
   - Must have `role = 'admin'` in database
   - Non-admins redirected to login
   - No member/trainer access

2. **Manual Refresh Required**
   - Statistics don't auto-update
   - Press F5 to see latest counts
   - No real-time WebSocket updates
   - Refresh after approving items to see changes

3. **Last 10 Activities Only**
   - Dashboard shows recent 10 actions
   - Click "View All" for full history
   - Full log in activity-log.php
   - Not a complete audit trail on dashboard

4. **Counts Include All Users**
   - Total Members: Active + inactive
   - No membership status filtering on dashboard
   - See detailed breakdowns in specific pages

5. **Pending Counts Context**
   - Subscriptions: Payment proofs awaiting approval
   - Reservations: Booking requests awaiting confirmation
   - Messages: Unread contact form submissions (not archived, not deleted)

6. **Activity Log Retention**
   - Logs stored indefinitely (unless manually deleted)
   - No automatic cleanup
   - Can grow large over time
   - Admin can delete old entries via activity-log.php

### What This Page Doesn't Do

- **Doesn't allow direct approvals** (click through to specific pages)
- **Doesn't show revenue/financial data** (no payment amounts)
- **Doesn't show member details** (just count, not names)
- **Doesn't show trainer schedules** (use trainer-schedules.php)
- **Doesn't show booking calendar** (use reservations.php)
- **Doesn't allow user management** (use users.php, trainers.php)
- **Doesn't show equipment inventory** (use equipment.php)
- **Doesn't show product catalog** (use products.php)
- **Doesn't display announcements** (use announcements.php)
- **Doesn't show system diagnostics** (use system_status.php)

---

## Navigation

### How Admins Arrive Here
- **After login:** Automatic redirect to admin dashboard
- **Sidebar "Dashboard" link:** Return to overview
- **Logo click:** Returns to dashboard (common pattern)
- **Direct URL:** `fitxbrawl.com/public/php/admin/admin.php`

### Where Admins Go Next
**From Dashboard Cards:**
- **Pending Subscriptions** → `subscriptions.php` (approve/reject memberships)
- **Pending Reservations** → `reservations.php` (confirm/cancel bookings)
- **Unread Messages** → `contacts.php` (read/reply to inquiries)

**From Sidebar Menu:**
- **Users** → `users.php` (member management)
- **Trainers** → `trainers.php` (trainer management)
- **Equipment** → `equipment.php` (gym equipment inventory)
- **Products** → `products.php` (gym store catalog)
- **Feedback** → `feedback.php` (member testimonials moderation)
- **Activity Log** → `activity-log.php` (full admin action history)
- **Announcements** → `announcements.php` (system announcements)
- **System Status** → `system_status.php` (server health monitoring)

---

## Visual Design

### Dashboard Layout

```
┌─────────────────────────────────────────────────────────────┐
│  Welcome, JohnAdmin                                         │
│  Here's an overview of your gym's activity.                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────┐
│  │ 👥       │ │ 🏋️       │ │ 🕐       │ │ 📅✓      │ │ ✉️ │
│  │  142     │ │    8     │ │    5     │ │    3     │ │  7 │
│  │ Members  │ │ Trainers │ │Pending   │ │Pending   │ │Unre│
│  │          │ │          │ │Subs      │ │Reserves  │ │ad  │
│  │          │ │          │ │[Review →]│ │[Review →]│ │[Vie│
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └────┘
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  Recent Activity                             [View All]     │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ Icon│Admin    │Action           │Details      │Date  │ │
│  ├─────┼─────────┼─────────────────┼─────────────┼──────┤ │
│  │ 👤  │JohnAdmin│User Created     │Created member│5 min │ │
│  │ ✅  │AdminMike│Membership Appr..│Approved Cham│1 hour│ │
│  │ ➕  │JohnAdmin│Trainer Added    │Added trainer│2 hour│ │
│  └─────┴─────────┴─────────────────┴─────────────┴──────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Card Color States

**Normal Card:**
- White background
- No border
- Standard shadow

**Pending Card (has-pending):**
- White background
- Gold left border (4px)
- Elevated shadow
- Action link visible

**Unread Card (has-unread):**
- Similar to pending
- Orange/red accent
- Action link visible

---

## Technical Details (Simplified)

### Database Queries

**Statistics:**
```sql
-- Total Members
SELECT COUNT(*) FROM users WHERE role = 'member'

-- Active Trainers
SELECT COUNT(*) FROM users WHERE role = 'trainer'

-- Pending Subscriptions
SELECT COUNT(*) FROM subscriptions WHERE status = 'Pending'
-- OR
SELECT COUNT(*) FROM user_memberships WHERE request_status = 'pending'

-- Pending Reservations
SELECT COUNT(*) FROM reservations WHERE status = 'Pending'

-- Unread Messages
SELECT COUNT(*) FROM contact 
WHERE status = 'unread' AND archived = 0 AND deleted_at IS NULL
```

**Recent Activity:**
```sql
SELECT * FROM admin_activity_log 
ORDER BY timestamp DESC 
LIMIT 10
```

---

### Activity Logger System

**Initialization:**
```php
ActivityLogger::init($conn);
```

**Logging Actions (from other admin pages):**
```php
ActivityLogger::logActivity(
    $admin_id,        // Admin performing action
    'membership_approved',  // Action type
    'Approved Gladiator membership for user #42'  // Details
);
```

**Retrieving Activities:**
```php
$activities = ActivityLogger::getActivities(10);  // Last 10
```

**Icon Mapping:**
```php
ActivityLogger::getActivityIcon('membership_approved');
// Returns: ['icon' => 'fa-check-circle', 'color' => '#28a745']
```

---

### Session Variables Used

**Authentication:**
- `$_SESSION['role']` - Must be `'admin'`
- `$_SESSION['username']` - Admin display name

**Display:**
- Used in header: "Welcome, [username]"

---

## Security Features

### 1. **Role-Based Access Control**
- Checks `role = 'admin'` before page loads
- Non-admins redirected immediately
- No data exposed to unauthorized users

### 2. **Session Validation**
- Requires active session
- Prevents access without authentication
- Session timeout protection

### 3. **Data Sanitization**
- `htmlspecialchars()` on all displayed data
- Prevents XSS attacks
- Safe output of admin names, activity details

### 4. **SQL Injection Prevention**
- Prepared statements (in ActivityLogger)
- Parameterized queries
- Secure database access

---

## Tips for Admins

### Best Practices

1. **Start Day with Dashboard**
   - Check pending counts
   - Clear pending approvals first
   - Read unread messages
   - Maintain zero backlog

2. **Monitor Activity Log**
   - Review what other admins did
   - Catch errors early
   - Audit suspicious actions
   - Learn from patterns

3. **Track Member Growth**
   - Note member count daily
   - Compare week-over-week
   - Identify growth trends
   - Plan capacity needs

4. **Respond to Highlights Promptly**
   - Gold borders = needs attention
   - Click "Review Now" immediately
   - Don't let pending items accumulate
   - Member experience depends on speed

5. **Use "View All" for Details**
   - Dashboard shows summary only
   - Click through for full information
   - Dive deep when needed
   - Return to dashboard for overview

---

## Final Thoughts

The admin dashboard is designed for efficiency—one page shows you everything that matters. No clutter, no distractions, just the essential metrics and recent activity. The card highlighting system draws your eye to what needs attention (pending items), while muted cards show stable metrics. It's your gym's pulse check, updated every time you refresh.

Whether you're starting your day, checking in between tasks, or doing end-of-month reporting, this dashboard gives you the big picture fast. The "Review Now" buttons are strategic shortcuts—see a problem, fix it immediately, no hunting through menus. Combined with the recent activity log, you have both real-time status (cards) and historical context (log). It's simple, focused, and built for admins who need answers fast.

