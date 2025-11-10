# Trainer Schedule Page Documentation
**File:** `public/php/trainer/schedule.php`  
**Purpose:** Full training schedule with upcoming, past, and cancelled bookings  
**User Access:** Trainers only (role-based authentication)

---

## What This Page Does

The trainer schedule page is your complete booking management view. Unlike the dashboard which shows only the next 5 sessions, this page displays ALL your bookings organized by status: upcoming (future sessions), past (completed sessions), and cancelled (member-cancelled bookings). Think of it as your master calendar—filter by class type, switch between tabs, and see your complete training history at a glance.

### Who Can Access This Page
- **Trainers only:** Must have `role = 'trainer'`
- **Login required:** Redirects non-authenticated users
- **Active trainers only:** Must have `status = 'Active'` in trainers table

### What It Shows
- **Upcoming bookings:** All confirmed future sessions
- **Past bookings:** Completed training sessions
- **Cancelled bookings:** Member cancellations
- **Class filter:** Filter by class type (Boxing, Muay Thai, etc.)
- **Booking counts:** Number of bookings in each category
- **Trainer info:** Your name and specialization

---

## The Page Experience

### **1. Page Header**

**Title:**
- "TRAINING SCHEDULE"
- Bold, all-caps styling
- Centered alignment

**Trainer Information Bar:**
- ✅ User check icon
- Text: "Trainer: **[Your Name]** | Specialization: **[Your Specialization]**"
- Example: "Trainer: **Mike Johnson** | Specialization: **Boxing & Muay Thai**"
- Confirms you're viewing your own schedule

---

### **2. My Bookings Section**

#### **Section Header**

**Title:**
- 📅 Calendar icon
- "My Bookings"
- Section identifier

**Controls (Right Side):**

**Filter Dropdown:**
- 🔍 Filter icon
- Label: "Filter by Class:"
- Dropdown menu with options:
  - **All Classes** (default - shows everything)
  - **Boxing** (if you have boxing bookings)
  - **Muay Thai** (if you have Muay Thai bookings)
  - **MMA** (if you have MMA bookings)
  - **General Training** (general sessions)
  - *(Only shows class types you actually have bookings for)*

**Tab Buttons:**
- **Upcoming** tab (active by default)
  - Badge shows count: e.g., "Upcoming 12"
  - Blue/gold active indicator
  
- **Past** tab
  - Badge shows count: e.g., "Past 48"
  - Gray when inactive
  
- **Cancelled** tab
  - Badge shows count: e.g., "Cancelled 3"
  - Gray when inactive

---

### **3. Booking Cards Display**

Each booking appears as a card with four rows of information:

#### **Card Structure**

**Row 1: Client**
- 👤 User icon
- Label: "Client:"
- Value: Member's username
- Example: "Client: JohnDoe2024"

**Row 2: Date**
- 📅 Calendar icon
- Label: "Date:"
- Value: Formatted date
- Example: "Date: Nov 10, 2025"

**Row 3: Session Time**
- 🕐 Clock icon
- Label: "Session:"
- Value: Time slot + time range
- Examples:
  - "Morning (7-11 AM)"
  - "Afternoon (1-5 PM)"
  - "Evening (6-10 PM)"

**Row 4: Class Type**
- 🏋️ Dumbbell icon
- Label: "Class:"
- Value: Class name
- Examples:
  - "Boxing"
  - "Muay Thai"
  - "MMA"
  - "General Training" (if no specific class)

---

### **4. Tab Content Areas**

#### **Upcoming Bookings Tab** (Default View)

**What It Shows:**
- Section title: "Upcoming Bookings"
- All confirmed future bookings
- Sorted chronologically (earliest first)
- Includes today and all future dates

**Sorting Logic:**
1. By date (ascending - soonest first)
2. By session time within same date:
   - Morning sessions first
   - Then Afternoon sessions
   - Then Evening sessions

**Empty State:**
- 📅❌ Calendar times icon
- Message: "No upcoming bookings."
- Appears when no future sessions scheduled

---

#### **Past Bookings Tab**

**What It Shows:**
- Section title: "Past Bookings"
- All completed training sessions
- Sessions with `booking_status = 'completed'`
- Historical record of your training sessions

**Empty State:**
- Message: "No past bookings."
- Appears for new trainers

---

#### **Cancelled Bookings Tab**

**What It Shows:**
- Section title: "Cancelled Bookings"
- Sessions cancelled by members
- Status: `booking_status = 'cancelled'`
- Useful for tracking no-shows or schedule changes

**Empty State:**
- Message: "No cancelled bookings."
- Appears when no cancellations

---

## How Features Work

### **1. Class Filter Dropdown**

**How It Works:**
1. Dropdown populated from your actual bookings
2. System queries: "What class types has this trainer been booked for?"
3. Only shows class types you have bookings for
4. Select a class type to filter

**Filter Behavior:**
- **All Classes:** Shows all bookings (no filter)
- **Specific Class:** Shows only that class type
- Applies across all tabs (Upcoming, Past, Cancelled)
- Real-time filtering (instant update)

**Example:**
- You select "Boxing"
- Only boxing-related bookings appear
- Muay Thai, MMA sessions hidden
- Tab counts don't change (filter is visual only)

---

### **2. Tab Switching**

**How Tabs Work:**
1. Click a tab button
2. Previous tab content hides
3. Selected tab content displays
4. Tab button gets active styling (blue/gold)

**Tab Counts:**
- Numbers update when page loads
- Show total bookings in each category
- Don't change when filtering (show true totals)
- Help plan workload at a glance

**Example Tab Counts:**
- Upcoming: 15 (next 15 sessions)
- Past: 120 (completed 120 sessions)
- Cancelled: 5 (5 cancellations)

---

### **3. Session Time Ranges**

**Three Time Slots:**

| Session Time | Time Range | Typical Use |
|--------------|------------|-------------|
| **Morning** | 7-11 AM | Early risers, before-work sessions |
| **Afternoon** | 1-5 PM | Lunch breaks, flexible schedules |
| **Evening** | 6-10 PM | After-work sessions, most popular |

**Display Format:**
- Shows both slot name AND time range
- Example: "Morning (7-11 AM)"
- Helps you plan daily schedule

**Sorting:**
- Cards within same day sorted by time slot order
- Morning → Afternoon → Evening
- Chronological flow

---

## Data Flow

### Page Load Process

```
1. USER ACCESSES PAGE
   ↓
   Role check: Is trainer?
   ↓
2. GET TRAINER INFORMATION
   ↓
   Query trainers table:
   - Match by email ($_SESSION['email'])
   - Filter: status = 'Active'
   - Get: trainer_id, name, specialization
   ↓
3. FETCH BOOKINGS BY STATUS
   ↓
   Query A: Upcoming Bookings
   - WHERE trainer_id = [yours]
   - AND booking_status = 'confirmed'
   - AND booking_date >= TODAY
   - ORDER BY date ASC, session_time
   
   Query B: Past Bookings
   - WHERE trainer_id = [yours]
   - AND booking_status = 'completed'
   - ORDER BY date DESC
   
   Query C: Cancelled Bookings
   - WHERE trainer_id = [yours]
   - AND booking_status = 'cancelled'
   - ORDER BY date DESC
   ↓
4. FETCH DISTINCT CLASS TYPES
   ↓
   Query: SELECT DISTINCT class_type
   FROM user_reservations
   WHERE trainer_id = [yours]
   - Populates filter dropdown
   ↓
5. RENDER PAGE
   ↓
   - Display header with your name/specialization
   - Show filter dropdown (with your class types)
   - Show tab buttons with counts
   - Render booking cards in each tab
   - Attach JavaScript for tab switching
```

---

## Common Trainer Scenarios

### Scenario 1: Viewing Full Week Schedule

**What Happens:**
1. Coach Sarah opens schedule page
2. Sees "Upcoming" tab (active by default)
3. Tab shows: "Upcoming 18"
4. Scrolls through upcoming booking cards
5. Sees this week's sessions:
   - Monday 8 AM: Client Alex - Boxing
   - Monday 2 PM: Client Maria - Muay Thai
   - Tuesday 7 AM: Client John - MMA
   - Wednesday 6 PM: Client Lisa - Boxing
   - ... (and so on)
6. All 18 future sessions visible
7. Can plan entire week from one view

---

### Scenario 2: Filtering by Class Type

**What Happens:**
1. Coach Mike handles multiple class types
2. Has bookings for: Boxing (8), Muay Thai (5), MMA (3)
3. Opens schedule page
4. Sees "Upcoming 16" tab
5. Wants to see only Boxing sessions
6. Clicks "Filter by Class" dropdown
7. Selects "Boxing"
8. Page updates instantly
9. Now shows only 8 boxing booking cards
10. Muay Thai and MMA cards hidden
11. Mike prepares boxing-specific equipment
12. Clicks "All Classes" to see full schedule again

---

### Scenario 3: Checking Past Training History

**What Happens:**
1. Coach Jessica wants to see how many sessions completed
2. Opens schedule page
3. Clicks "Past" tab
4. Tab shows: "Past 87"
5. Sees all 87 completed sessions
6. Scrolls through history:
   - Last month: 20 sessions
   - Two months ago: 18 sessions
   - Three months ago: 22 sessions
7. Notes consistent training volume
8. Can reference past clients if they return

---

### Scenario 4: Reviewing Cancellations

**What Happens:**
1. Coach David notices some no-shows lately
2. Opens schedule page
3. Clicks "Cancelled" tab
4. Tab shows: "Cancelled 7"
5. Sees 7 cancelled bookings
6. Reviews cancellation patterns:
   - 3 cancellations from same member (frequent canceller)
   - 4 cancellations on rainy days (weather-related)
7. Can discuss attendance with frequent canceller
8. Understands cancellation trends

---

### Scenario 5: Morning Routine Check

**What Happens:**
1. Coach Maria logs in at 6:00 AM
2. Opens schedule page
3. Sees "Upcoming 12" bookings
4. Checks today's sessions
5. First card: "Morning (7-11 AM) - Client: Tom - Boxing"
6. Second card: "Afternoon (1-5 PM) - Client: Sarah - Muay Thai"
7. Prepares boxing gear for morning session
8. Plans Muay Thai drills for afternoon
9. Ready for day

---

### Scenario 6: No Bookings State (New Trainer)

**What Happens:**
1. New trainer "Alex" just hired
2. Admin created account yesterday
3. Alex logs in, opens schedule page
4. Sees header: "Trainer: Alex Rodriguez | Specialization: MMA"
5. Clicks "Upcoming" tab
6. Sees: "No upcoming bookings."
7. Clicks "Past" tab
8. Sees: "No past bookings."
9. Clicks "Cancelled" tab
10. Sees: "No cancelled bookings."
11. Filter dropdown shows only "All Classes" (no class types yet)
12. Alex waits for members to book sessions

---

## Important Notes and Limitations

### Things to Know

1. **Shows All Bookings (No Limit)**
   - Unlike dashboard (5 bookings max)
   - Schedule shows unlimited bookings
   - All upcoming, past, and cancelled sessions
   - Complete historical view

2. **Active Trainers Only**
   - Must have `status = 'Active'` in database
   - Inactive trainers redirected to login
   - Admin controls active/inactive status

3. **Confirmed Bookings Only (Upcoming Tab)**
   - `booking_status = 'confirmed'` required
   - Pending bookings not shown
   - Only bookings members actually reserved

4. **Manual Refresh Required**
   - New bookings require page reload
   - Cancellations require refresh
   - No real-time updates
   - Press F5 to see latest data

5. **Filter Dropdown Dynamic**
   - Shows only your class types
   - If you teach only Boxing → dropdown shows only "All Classes" and "Boxing"
   - If you teach 4 types → dropdown shows all 4
   - Reflects your actual booking history

6. **Session Times (Not Exact)**
   - Shows time ranges (7-11 AM, 1-5 PM, 6-10 PM)
   - Not exact appointment times
   - Members book by slot, not specific hour
   - Coordinate exact time with member directly

### What This Page Doesn't Do

- **Doesn't allow booking changes** (members manage via their account)
- **Doesn't show member contact** (only username, not email/phone)
- **Doesn't allow marking complete** (system auto-completes based on date)
- **Doesn't show payment info** (not trainer's concern)
- **Doesn't send notifications** (email system handles that)
- **Doesn't allow scheduling** (members book from their end)
- **Doesn't show availability** (only booked sessions, not open slots)
- **Doesn't export/print** (no export feature)
- **Doesn't show notes** (no session notes field)
- **Doesn't allow cancellation** (members cancel their own bookings)

---

## Navigation

### How Trainers Arrive Here
- **From dashboard:** Click "View Schedule" button
- **From navigation menu:** "Schedule" link in header
- **From profile page:** "Schedule" link in nav
- **From feedback page:** "Schedule" link in nav
- **Direct URL:** `fitxbrawl.com/public/php/trainer/schedule.php`

### Where Trainers Go Next
- **Dashboard** (`index.php`) - Return to quick daily view
- **Profile** (`profile.php`) - Edit trainer information
- **Feedback** (`feedback.php`) - View member reviews
- **Logout** - End session

---

## Visual Design

### Card Layout

```
┌─────────────────────────────────────────────┐
│ 👤 Client: JohnDoe2024                     │
│ 📅 Date: Nov 10, 2025                      │
│ 🕐 Session: Morning (7-11 AM)              │
│ 🏋️ Class: Boxing                           │
└─────────────────────────────────────────────┘
```

### Tab Styling

**Active Tab:**
- Blue/gold background
- White text
- Count badge highlighted
- Bottom border accent

**Inactive Tab:**
- Gray background
- Dark text
- Count badge visible
- No border

### Responsive Design

**Desktop:**
- Cards in grid (2-3 columns)
- Filter and tabs side-by-side
- Spacious layout

**Tablet:**
- Cards in 2 columns
- Filter and tabs stacked
- Compact spacing

**Mobile:**
- Cards stacked (1 column)
- Filter full-width
- Tabs scrollable if many
- Touch-friendly buttons

---

## Technical Details (Simplified)

### Database Tables

**Primary Table:** `user_reservations`

**Columns Used:**
- `id` - Booking ID
- `trainer_id` - Your trainer ID (foreign key)
- `user_id` - Member who booked (foreign key)
- `booking_date` - Session date
- `session_time` - Morning/Afternoon/Evening
- `class_type` - Boxing, Muay Thai, MMA, etc.
- `booking_status` - confirmed/completed/cancelled

**Joins:**
- `users` table → Get member username and email

---

### Queries

**Upcoming Bookings:**
```sql
SELECT ur.*, u.username, u.email
FROM user_reservations ur
JOIN users u ON ur.user_id = u.id
WHERE ur.trainer_id = [your ID]
AND ur.booking_status = 'confirmed'
AND ur.booking_date >= CURDATE()
ORDER BY ur.booking_date ASC,
         FIELD(ur.session_time, 'Morning', 'Afternoon', 'Evening')
```

**Past Bookings:**
```sql
SELECT ur.*, u.username, u.email
FROM user_reservations ur
JOIN users u ON ur.user_id = u.id
WHERE ur.trainer_id = [your ID]
AND ur.booking_status = 'completed'
ORDER BY ur.booking_date DESC
```

**Cancelled Bookings:**
```sql
SELECT ur.*, u.username, u.email
FROM user_reservations ur
JOIN users u ON ur.user_id = u.id
WHERE ur.trainer_id = [your ID]
AND ur.booking_status = 'cancelled'
ORDER BY ur.booking_date DESC
```

---

### JavaScript Functionality

**Tab Switching:**
```javascript
tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active from all tabs
        tabBtns.forEach(b => b.classList.remove('active'));
        // Add active to clicked tab
        btn.classList.add('active');
        
        // Hide all tab content
        lists.forEach(l => l.classList.remove('active'));
        // Show selected tab content
        document.getElementById(tab + 'Bookings').classList.add('active');
    });
});
```

**Filter Dropdown:**
- External JavaScript file: `schedule.js`
- Filters cards by class type
- Uses `data-class` attribute on cards
- Real-time filtering (no page reload)

---

## Security Features

### 1. **Role-Based Access**
- Checks `role = 'trainer'` before loading
- Non-trainers redirected to login
- Protects trainer-specific data

### 2. **Session Validation**
- `SessionManager::isLoggedIn()` check
- Prevents unauthorized access
- Session timeout protection

### 3. **Data Sanitization**
- `htmlspecialchars()` on all output
- Prevents XSS attacks
- Safe display of usernames, class names

### 4. **Prepared Statements**
- Parameterized queries
- Prevents SQL injection
- Secure data retrieval

### 5. **Email-Based Matching**
- Trainer identified by email (unique)
- Prevents ID spoofing
- Ensures you see only your bookings

---

## Tips for Trainers

### Best Practices

1. **Check Schedule Daily**
   - Open first thing each morning
   - Refresh before each session (check for cancellations)
   - Plan based on upcoming sessions

2. **Use Class Filter**
   - Filter by class type when preparing equipment
   - Boxing day? Filter to see all boxing sessions
   - Helps focus preparation

3. **Review Past Tab Monthly**
   - Track training volume (how many sessions/month)
   - Identify frequent clients (repeat bookings)
   - Measure productivity

4. **Monitor Cancelled Tab**
   - Identify frequent cancellers
   - Spot patterns (weather, day of week)
   - Address attendance issues with members

5. **Combine with Dashboard**
   - Dashboard for quick daily view (next 5)
   - Schedule for full weekly/monthly planning
   - Use both tools together

---

## Final Thoughts

The trainer schedule page is your complete booking command center. While the dashboard gives you a quick snapshot of upcoming sessions, this page provides the full picture: past performance, future commitments, and cancellation trends. The tab system keeps information organized without overwhelming you, while the class filter lets you focus on specific training types when needed.

Whether you're a new trainer with a handful of bookings or a veteran managing 30+ sessions per week, this page scales to your needs. The booking counts give instant workload awareness, the filter helps with preparation, and the historical tabs let you track your training career over time. It's not just a schedule—it's your training portfolio, organized and accessible with one click.

