# Reservations Management Page Documentation
**File:** `public/php/admin/reservations.php`  
**Purpose:** View and manage all training session bookings  
**User Access:** Admins only (role-based authentication)

---

## What This Page Does

The reservations management page is your complete booking oversight center. View all training session reservations (confirmed, completed, cancelled), filter by trainer/date/status, search clients, and manage booking statuses. Switch between table view (detailed list) and calendar view (visual monthly grid) to monitor trainer schedules and client bookings. Think of it as mission control for all gym training sessions—from confirmation to completion.

### Who Can Access This Page
- **Admins only:** Must have `role = 'admin'`
- **Login required:** Redirects non-authenticated users
- **Action permissions:** Can update booking statuses (confirm, complete, cancel)

### What It Shows
- **All reservations:** Client bookings with trainers
- **Booking details:** Client info, trainer, class type, date/time, status
- **Statistics dashboard:** Total, upcoming, completed, cancelled counts
- **Calendar view:** Monthly grid showing all bookings
- **Filtering options:** By status, trainer, date range, search term
- **Bulk actions:** Mark multiple bookings as complete/cancelled

---

## The Page Experience

### **1. Page Header**

**Title:**
- "Reservations Management"
- Large, clear heading

**Subtitle:**
- "View and manage all training session bookings"
- Explains page purpose

---

### **2. Statistics Dashboard**

Four stat cards displayed at the top:

#### **Total Bookings Card** (Blue icon)
- **Icon:** 📅 Calendar check
- **Number:** Total reservation count
- **Label:** "Total Bookings"
- **Example:** "287"
- **Meaning:** All bookings regardless of status

#### **Upcoming Card** (Orange icon)
- **Icon:** 🕐 Clock
- **Number:** Confirmed bookings count
- **Label:** "Upcoming"
- **Example:** "42"
- **Meaning:** Active confirmed sessions scheduled

#### **Completed Card** (Green icon)
- **Icon:** ✅ Circle check
- **Number:** Completed bookings count
- **Label:** "Completed"
- **Example:** "198"
- **Meaning:** Sessions that were successfully held

#### **Cancelled Card** (Red icon)
- **Icon:** ❌ Circle X mark
- **Number:** Cancelled bookings count
- **Label:** "Cancelled"
- **Example:** "47"
- **Meaning:** Sessions that were cancelled

---

### **3. View Toggle**

**Two View Options:**

**Table View** (Default - Active on load)
- **Icon:** 📊 Table icon
- **Text:** "Table View"
- **Shows:** Detailed row-by-row list
- **Best for:** Filtering, searching, bulk actions

**Calendar View**
- **Icon:** 📅 Calendar icon
- **Text:** "Calendar View"
- **Shows:** Monthly grid with booking dots
- **Best for:** Visual schedule overview

**Toggle Behavior:**
- Click button to switch views
- Active button highlighted (blue/gold)
- Inactive button gray
- One active at a time

---

### **4. Toolbar Section**

#### **Search Box (Left)**

**What It Shows:**
- 🔍 Magnifying glass icon
- Input field: "Search by name, email, or trainer..."
- Full-width search bar

**What It Searches:**
- Client name (e.g., "John Smith")
- Client email (e.g., "john@email.com")
- Trainer name (e.g., "Coach Mike")
- Real-time filtering as you type

---

#### **Status Filter Dropdown**

**Options:**
- All Statuses (default)
- Confirmed
- Completed
- Cancelled

**Behavior:**
- Click to select
- Filters table instantly
- Works with other filters

---

#### **Trainer Filter Dropdown**

**Options:**
- All Trainers (default)
- [List of all trainers]
- Example: "Coach Mike", "Sarah Lee", etc.

**Behavior:**
- Shows bookings for selected trainer only
- Useful for checking specific trainer schedules

---

#### **Date Range Filters**

**Date From:**
- Input type: Date picker
- Label: (implied) "From date"
- Example: "11/01/2025"
- Filters bookings starting from this date

**Date To:**
- Input type: Date picker
- Label: (implied) "To date"
- Example: "11/30/2025"
- Filters bookings up to this date

**Combined Filtering:**
- All filters work together
- Example: "Coach Mike" + "Confirmed" + Nov 1-30 = Coach Mike's confirmed November bookings

---

### **5. Bulk Actions Bar**

**When It Appears:**
- Only visible when one or more bookings selected
- Slides in from top
- Highlighted background

**What It Shows:**

**Selected Count:**
- Text: "X selected"
- Example: "3 selected"
- Updates as you check/uncheck boxes

**Mark Complete Button** (Green)
- **Icon:** ✅ Check mark
- **Text:** "Mark Complete"
- **Action:** Sets all selected bookings to "completed" status
- **Use case:** Bulk update after sessions held

**Cancel Selected Button** (Red)
- **Icon:** ❌ X mark
- **Text:** "Cancel Selected"
- **Action:** Sets all selected bookings to "cancelled" status
- **Use case:** Mass cancellations (trainer sick, facility closed)

---

### **6. Table View**

#### **Table Header (Columns)**

1. **Checkbox Column** (40px width)
   - Select All checkbox in header
   - Click to select/deselect all visible bookings

2. **Client**
   - Client avatar (circular image)
   - Client full name
   - Client email
   - Two-line display with avatar

3. **Trainer**
   - Trainer name
   - Example: "Coach Mike"

4. **Class Type**
   - Type of training session
   - Examples: "MMA", "Boxing", "Muay Thai", "Gym"

5. **Date & Time**
   - Booking date (e.g., "Nov 10, 2025")
   - Session time (Morning/Afternoon/Evening)
   - Time range shown:
     - Morning: 7:00 AM - 11:00 AM
     - Afternoon: 1:00 PM - 5:00 PM
     - Evening: 6:00 PM - 10:00 PM

6. **Status** (120px width)
   - Color-coded status badge
   - Options: Confirmed, Completed, Cancelled

---

#### **Table Row Structure**

**Each Booking Row Shows:**

**Column 1: Checkbox**
- Individual selection checkbox
- Check to include in bulk actions
- Value: Booking ID

**Column 2: Client Info**
- **Avatar:** Circular profile image (or default icon if none)
- **Name:** Client full name (large, bold)
- **Email:** Client email address (smaller, gray)
- **Layout:** Horizontal, avatar on left

**Column 3: Trainer Name**
- Trainer assigned to session
- Plain text

**Column 4: Class Type**
- Training discipline
- Plain text

**Column 5: Date & Time**
- **Line 1:** Date formatted as "MMM DD, YYYY"
- **Line 2:** Session time (bold)
- **Line 3:** Time range (small, gray)
- **Example:**
  ```
  Nov 10, 2025
  Morning
  7:00 AM - 11:00 AM
  ```

**Column 6: Status Badge**
- **Confirmed:** Orange badge, "Confirmed"
- **Completed:** Green badge, "Completed"
- **Cancelled:** Red badge, "Cancelled"
- **Clickable:** (implied, may toggle status)

---

**Table States:**

**Empty State:**
- 📅❌ Calendar X mark icon (large, gray)
- Title: "No reservations found"
- Message: Appears when filters produce no results

**Populated State:**
- Shows all matching bookings
- Sorted by date descending (newest first)
- Scrollable table

---

### **7. Calendar View**

**Calendar Header:**

**Previous Month Button** (Left)
- **Icon:** ← Chevron left
- **Action:** Navigate to previous month

**Current Month Display** (Center)
- **Text:** "November 2025"
- **Format:** [Month Year]
- **Large, bold**

**Next Month Button** (Right)
- **Icon:** → Chevron right
- **Action:** Navigate to next month

---

**Calendar Grid:**

**Day Cells:**
- 7 columns (Sun-Sat)
- Rows for weeks of month
- Each cell shows:
  - Day number (e.g., "10")
  - Booking dots (if bookings exist)
  - Color-coded dots by status:
    - 🟠 Orange: Confirmed
    - 🟢 Green: Completed
    - 🔴 Red: Cancelled

**Example Cell (November 10):**
```
┌─────────┐
│   10    │
│ 🟠 🟠   │  (2 confirmed bookings)
│ 🔴      │  (1 cancelled booking)
└─────────┘
```

**Cell Interactions:**
- Click cell to view day's bookings
- Opens day bookings modal
- Shows detailed list for that date

---

### **8. Day Bookings Modal**

**When It Appears:**
- Click a day cell in calendar view
- Overlay darkens background
- Modal appears center-screen

**Modal Header:**

**Title:**
- "Bookings for [Date]"
- Example: "Bookings for November 10, 2025"
- Personalized to selected date

**Close Button:**
- ❌ X icon
- Top-right corner
- Closes modal

**Modal Body:**

Shows list of bookings for that day:

**Each Booking Entry:**
- **Client:** Name and avatar
- **Trainer:** Trainer name
- **Class Type:** MMA/Boxing/etc.
- **Session Time:** Morning/Afternoon/Evening
- **Status:** Color-coded badge
- **Actions:** (optional) Quick status update buttons

**Empty State:**
- "No bookings on this day"
- If selected date has no bookings

---

## How Features Work

### **1. Table View Filtering**

**Search Functionality:**
1. User types in search box
2. JavaScript filters rows in real-time
3. Matches against:
   - Client name (case-insensitive)
   - Client email
   - Trainer name
4. Filtered rows displayed instantly
5. No page reload

**Dropdown Filters:**
1. User selects status/trainer/date
2. Page reloads with URL parameters
3. PHP rebuilds query with filters
4. Filtered results displayed

**Combined Filtering Example:**
- Search: "john"
- Status: "Confirmed"
- Trainer: "Coach Mike"
- Date From: Nov 1
- Date To: Nov 30
- **Result:** John's confirmed bookings with Coach Mike in November

---

### **2. Bulk Actions**

**Selecting Bookings:**

**Select All:**
1. Click checkbox in table header
2. All visible bookings checked
3. Bulk actions bar appears
4. Count updates: "15 selected"

**Individual Selection:**
1. Click checkbox on specific rows
2. Bulk actions bar appears when first checked
3. Count updates with each selection
4. Can select/deselect individually

**Mark Complete:**
```
1. SELECT BOOKINGS
   ↓
   Check boxes for completed sessions
   ↓
2. CLICK "MARK COMPLETE"
   ↓
3. JAVASCRIPT SENDS API REQUEST
   ↓
   POST reservations.php?ajax
   Body: { action: 'bulk_update', ids: [42, 43, 44], status: 'completed' }
   ↓
4. SERVER UPDATES DATABASE
   ↓
   UPDATE user_reservations
   SET booking_status = 'completed'
   WHERE id IN (42, 43, 44)
   ↓
5. SERVER LOGS ACTION
   ↓
   INSERT INTO activity_log
   (admin_id, action, details)
   VALUES (..., 'reservation_bulk_completed', 'Bulk completion: 3 reservations')
   ↓
6. PAGE REFRESHES
   ↓
   Table reloads
   Badges turn green
   Stats update
```

**Cancel Selected:**
- Same flow as above
- Status set to 'cancelled'
- Badges turn red
- Logged separately

---

### **3. Calendar View**

**Rendering Calendar:**
1. JavaScript calculates days in current month
2. Creates grid of day cells
3. Fetches all bookings for month
4. Places colored dots on cells with bookings
5. Dot color matches booking status

**Navigating Months:**
1. Click "Next Month" button
2. JavaScript increments month
3. Recalculates days
4. Refetches bookings for new month
5. Renders updated calendar

**Viewing Day Bookings:**
1. User clicks cell (e.g., Nov 10)
2. JavaScript filters bookings for Nov 10
3. Builds modal HTML with that day's bookings
4. Displays modal with list
5. User can close modal to return to calendar

---

### **4. Individual Status Updates**

**Click Status Badge:**
1. User clicks "Confirmed" badge on a row
2. JavaScript triggers status update
3. Cycles through statuses:
   - Confirmed → Completed
   - Completed → Cancelled
   - Cancelled → Confirmed (optional, or disabled)
4. Badge color changes immediately
5. Database updated via AJAX
6. Activity logged

*(Note: Status toggle behavior may vary based on implementation)*

---

## Data Flow

### Page Load Process

```
1. ADMIN ACCESSES PAGE
   ↓
   Role check: Is admin?
   ↓
2. GET FILTER PARAMETERS
   ↓
   ?search=john&status=confirmed&trainer=3&date_from=2025-11-01
   ↓
3. BUILD DATABASE QUERY
   ↓
   SELECT ur.id, ur.class_type, ur.booking_date, ur.session_time,
          ur.booking_status, u.username, u.email, u.avatar,
          t.name as trainer_name, t.specialization
   FROM user_reservations ur
   JOIN users u ON ur.user_id = u.id
   JOIN trainers t ON ur.trainer_id = t.id
   WHERE 1=1
   AND u.username LIKE '%john%'
   AND ur.booking_status = 'confirmed'
   AND t.id = 3
   AND ur.booking_date >= '2025-11-01'
   ORDER BY ur.booking_date DESC
   ↓
4. FETCH STATISTICS
   ↓
   SELECT COUNT(*) as total,
          SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as upcoming,
          SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
          SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
   FROM user_reservations
   ↓
5. FETCH TRAINERS FOR FILTER
   ↓
   SELECT id, name FROM trainers WHERE deleted_at IS NULL
   ↓
6. RENDER PAGE
   ↓
   - Display stats cards with counts
   - Show toolbar with filters (pre-filled from URL)
   - Render table rows for each booking
   - Hide calendar view (inactive)
   ↓
7. JAVASCRIPT ENHANCES
   ↓
   - Attach event listeners to checkboxes
   - Enable search filtering
   - Prepare calendar rendering
   ↓
8. READY FOR ADMIN INTERACTION
```

---

## Common Admin Scenarios

### Scenario 1: Morning Schedule Check

**What Happens:**
1. Admin arrives at 7:00 AM
2. Opens Reservations Management
3. Sees stats: 15 bookings today (upcoming)
4. Clicks "Calendar View"
5. Today's cell shows 15 colored dots
6. Clicks today's cell
7. Modal opens with today's bookings:
   - 7:00 AM: John Smith - Boxing - Coach Mike
   - 9:00 AM: Jane Doe - MMA - Sarah Lee
   - 10:00 AM: Alex Chen - Muay Thai - Coach Mike
   - ...all 15 sessions listed
8. Prints list for front desk
9. Closes modal
10. Ready for day

---

### Scenario 2: Bulk Completion After Sessions

**What Happens:**
1. End of day: 5:00 PM
2. Admin opens Reservations Management
3. Filters: Date = Today, Status = Confirmed
4. Sees 15 confirmed bookings (all today's sessions)
5. Checks attendance sheet: All showed up except 2
6. Clicks "Select All" checkbox (all 15 selected)
7. Unchecks 2 no-shows
8. 13 selected
9. Clicks "Mark Complete"
10. Confirmation: "Mark 13 bookings complete?"
11. Confirms
12. Table refreshes
13. 13 badges turn green ("Completed")
14. Stats update: Completed +13, Upcoming -13
15. 2 remaining confirmed (for follow-up)
16. Day closed efficiently

---

### Scenario 3: Trainer Cancellation Emergency

**What Happens:**
1. Coach Mike calls sick at 8:00 AM
2. Admin opens Reservations Management
3. Trainer Filter: Selects "Coach Mike"
4. Date From: Today
5. Status: Confirmed
6. Sees Mike's 5 bookings today
7. Clicks "Select All"
8. All 5 checked
9. Clicks "Cancel Selected"
10. Confirmation: "Cancel 5 bookings?"
11. Confirms
12. System updates statuses to "Cancelled"
13. System sends emails to 5 clients (auto-notification)
14. Admin calls clients to reschedule
15. Crisis managed quickly

---

### Scenario 4: Finding Client's Booking History

**What Happens:**
1. Client calls: "What was my last session date?"
2. Admin searches "Maria Garcia"
3. Table filters to Maria's bookings
4. Sees history:
   - Nov 10: Muay Thai - Completed
   - Nov 5: Muay Thai - Completed
   - Oct 28: Muay Thai - Completed
   - Oct 20: Boxing - Cancelled
5. Tells Maria: "Your last session was Nov 10 (Muay Thai)"
6. Maria asks about cancelled Oct 20
7. Admin checks notes: "You called to cancel, no penalty"
8. Maria satisfied
9. Quick, informed support

---

### Scenario 5: Monthly Report Preparation

**What Happens:**
1. Admin needs November report
2. Opens Reservations Management
3. Calendar View
4. Navigates to November
5. Sees month overview:
   - Peak days: Nov 5, 12, 19 (many dots)
   - Slow days: Nov 1, 8, 24 (few dots)
6. Switches to Table View
7. Date From: Nov 1
8. Date To: Nov 30
9. Stats show:
   - Total: 142 bookings in November
   - Completed: 120
   - Cancelled: 18
   - Upcoming: 4 (end of month)
10. Records completion rate: 87% (120/138 non-upcoming)
11. Notes high cancellation rate (13%)
12. Investigates cancellation reasons
13. Report submitted

---

### Scenario 6: Trainer Schedule Comparison

**What Happens:**
1. Admin wants to balance trainer workload
2. Opens Reservations Management
3. Filters: Date range = This week, Status = Confirmed
4. Trainer Filter: "Coach Mike"
5. Counts: 18 bookings
6. Changes filter: "Sarah Lee"
7. Counts: 12 bookings
8. Changes filter: "Tom Chen"
9. Counts: 8 bookings
10. Analysis:
    - Mike overbooked (18)
    - Tom underbooked (8)
11. Redirects new bookings from Mike to Tom
12. Workload balanced

---

## Important Notes and Limitations

### Things to Know

1. **Admin Role Required**
   - Must have `role = 'admin'`
   - Non-admins cannot access
   - Trainers use separate reservations page

2. **Soft Deletes on Trainers**
   - Only shows bookings with active trainers (`deleted_at IS NULL`)
   - Deleted trainers' past bookings may not appear
   - Historical data preservation

3. **Session Times Fixed**
   - Morning: 7-11 AM
   - Afternoon: 1-5 PM
   - Evening: 6-10 PM
   - No custom time slots

4. **Bulk Actions Permanent**
   - No undo for bulk updates
   - Once marked complete/cancelled, irreversible from this page
   - Use caution with "Select All"

5. **Calendar View Read-Only**
   - Cannot update statuses from calendar
   - Must switch to table view for actions
   - Visualization only

6. **Filter Persistence**
   - Table filters persist via URL parameters
   - Refresh maintains current filters
   - Browser back/forward preserves state

7. **Auto-Refresh Not Enabled**
   - Page does not auto-update
   - Must manually refresh for new bookings
   - Multiple admins: refresh before bulk actions

### What This Page Doesn't Do

- **Doesn't create bookings** (members create their own)
- **Doesn't edit booking details** (cannot change date/time/trainer)
- **Doesn't send manual emails** (auto-notifications only)
- **Doesn't show payment info** (bookings free, tied to membership)
- **Doesn't reschedule** (cancel + member rebooks)
- **Doesn't show trainer availability** (use trainer schedules page)
- **Doesn't export data** (no CSV/Excel download)
- **Doesn't show client notes** (no notes field)
- **Doesn't track attendance** (manual check-in process)

---

## Navigation

### How Admins Arrive Here
- **Dashboard:** Click "Review Now →" on "Pending Reservations" card
- **Sidebar menu:** "Reservations" or "Bookings" link
- **Direct URL:** `fitxbrawl.com/public/php/admin/reservations.php`

### Where Admins Go Next
- **Trainers** (`trainers.php`) - Check trainer details
- **Users** (`users.php`) - View client membership status
- **Trainer Schedules** (`trainer_schedules.php`) - Manage availability
- **Dashboard** (`admin.php`) - Return to overview

---

## Tips for Admins

### Best Practices

1. **Check Calendar Daily**
   - Visual overview beats list scrolling
   - Spot scheduling conflicts quickly
   - Identify busy/slow days

2. **Use Bulk Actions Carefully**
   - Verify selections before confirming
   - Don't "Select All" unless certain
   - One wrong click cancels wrong bookings

3. **Filter Before Bulk Actions**
   - Narrow to exact bookings needed
   - Example: Today + Confirmed = completable sessions
   - Reduces accidental updates

4. **Search for Quick Lookups**
   - Client calls? Search their name
   - Faster than date filtering
   - Works across all dates

5. **Monitor Cancellation Patterns**
   - High cancellations = potential issues
   - Check if specific trainer/class type
   - Investigate and address root causes

6. **End-of-Day Routine**
   - Filter today's confirmed bookings
   - Mark completed in bulk
   - Follow up on no-shows
   - Clean daily queue

---

## Final Thoughts

The reservations management page balances visual overview (calendar) with detailed control (table). The dual-view approach lets you zoom out for patterns or zoom in for specifics. The bulk actions are a huge time-saver for daily completion updates—no clicking 20 individual rows, just select and done.

The filtering is robust (search + status + trainer + date range), giving you laser-focused views. Need this week's MMA sessions with Coach Mike? Filter and there they are. The stats dashboard keeps you informed at a glance—142 total, 42 upcoming, 198 completed tells a story without diving into details.

The calendar view shines for scheduling conflicts and workload distribution. See at a glance which days are packed (many dots) and which are light. The day modal bridges calendar and detail—click a day, see that day's sessions. It's efficient, flexible, and built for admins who manage dozens of bookings weekly.

