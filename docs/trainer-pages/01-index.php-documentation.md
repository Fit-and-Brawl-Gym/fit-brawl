# Trainer Dashboard Documentation
**File:** `public/php/trainer/index.php`  
**Purpose:** Main dashboard for trainers to view upcoming bookings  
**User Access:** Trainers only (role-based authentication)

---

## What This Page Does

The trainer dashboard is your command center as a FitXBrawl trainer. It displays your upcoming training sessions at a glance, showing who you're training, when, and what type of class. Think of it as your daily schedule overview—open this page first thing each day to see which members you'll be working with.

### Who Can Access This Page
- **Trainers only:** Must have `role = 'trainer'` in session
- **Login required:** Not logged in? Redirects to login page
- **Non-trainers blocked:** Members/admins redirected to login

### What It Shows
- **Welcome hero section:** Motivational branding
- **Upcoming bookings:** Next 5 confirmed training sessions
- **Client details:** Member names and contact info
- **Session details:** Date, time, and class type
- **Quick navigation:** "View Schedule" button to full calendar

---

## The Page Experience

### **1. Hero Section**

**Visual Elements:**
- Full-width hero background
- Decorative lines above and below title

**Main Heading:**
```
BUILD A BODY THAT'S
BUILT FOR BATTLE
```
- "BODY" highlighted in yellow/gold
- "BATTLE" highlighted in yellow/gold
- Bold, motivational fitness messaging

**Subheading:**
- "Ready to train champions?"
- Underlined for emphasis
- Encouraging call-to-action

**Action Button:**
- "View Schedule" button
- Links to `schedule.php` (full calendar view)
- Primary call-to-action
- Gold/yellow color scheme

---

### **2. Upcoming Schedule Section**

**Section Header:**
- 📅 Calendar check icon
- Title: "Upcoming Schedule"
- Clean, professional layout

---

### **Booking Cards Display**

Each upcoming booking appears as a card with:

#### **Card Header (Date & Time)**

**Date Display:**
- 📅 Calendar icon
- Format: "Nov 10, 2025"
- Month abbreviation + day + year

**Time Display:**
- 🕐 Clock icon
- Format: "2:00 PM"
- 12-hour format with AM/PM

**Layout:**
- Date on left, time on right
- Horizontal layout
- Clear visual separation

---

#### **Card Body (Client & Class Info)**

**Client Information:**
- 👤 User icon
- Label: "Client:"
- Member's full name
- Example: "Client: John Smith"

**Class Type:**
- 🏋️ Dumbbell icon
- Label: "Class:"
- Class name from reservation
- Example: "Class: Boxing Fundamentals"
- Shows "General Training" if no specific class type

---

### **No Upcoming Bookings State**

**When You Have No Bookings:**

**What It Shows:**
- 📅❌ Calendar times icon (crossed calendar)
- Message: "No upcoming bookings scheduled"
- Centered display
- Neutral gray color scheme

**What It Means:**
- No confirmed reservations in next 5 sessions
- Your schedule is clear
- Members can book sessions with you
- Check back later for new bookings

---

## How the Schedule Works

### **Upcoming Bookings Logic**

**What Counts as "Upcoming":**
1. **Today or future dates:** `reservation_date >= CURDATE()`
2. **Confirmed bookings only:** `booking_status = 'confirmed'`
3. **Your sessions only:** `trainer_id = [your trainer ID]`
4. **Next 5 sessions:** `LIMIT 5`
5. **Chronological order:** Sorted by date, then time (earliest first)

**What Doesn't Show:**
- Past bookings (yesterday and before)
- Cancelled bookings
- Pending/unconfirmed bookings
- Sessions assigned to other trainers
- Bookings beyond the next 5

---

### **Trainer Identification**

**How the System Knows You're a Trainer:**

**Step 1: Session Role Check**
- Checks `$_SESSION['role']`
- Must equal `'trainer'`
- Set during login

**Step 2: Match Trainer Record**
- Reads your username from `$_SESSION['name']`
- Queries `trainers` table
- Matches by `name` column (case-insensitive)
- Retrieves your `trainer_id`

**Step 3: Fetch Your Bookings**
- Uses `trainer_id` from step 2
- Queries `reservations` table
- Filters by your ID
- Returns only your sessions

---

## Data Flow

### Page Load Process

```
1. USER ACCESSES PAGE
   ↓
   Session check: Is logged in?
   ↓
2. ROLE VERIFICATION
   ↓
   If role ≠ 'trainer':
      → Redirect to login.php
   If role = 'trainer':
      → Continue
   ↓
3. GET TRAINER ID
   ↓
   - Read $_SESSION['name']
   - Query: SELECT id FROM trainers WHERE name = '[username]'
   - Store trainer_id
   ↓
4. FETCH UPCOMING BOOKINGS
   ↓
   Query:
   SELECT r.*, u.name, u.email, ct.class_name
   FROM reservations r
   JOIN users u ON r.user_id = u.id
   JOIN class_types ct ON r.class_type_id = ct.id
   WHERE r.trainer_id = [trainer_id]
   AND r.reservation_date >= TODAY
   AND r.booking_status = 'confirmed'
   ORDER BY date ASC, time ASC
   LIMIT 5
   ↓
5. RENDER PAGE
   ↓
   - Display hero section
   - Loop through bookings
   - Create card for each booking
   - Show date, time, client, class
   - If no bookings: Show "No upcoming" message
```

---

## Common Trainer Scenarios

### Scenario 1: Trainer Starting Their Day

**What Happens:**
1. Coach Mike logs in at 7:00 AM
2. System checks role: `trainer` ✓
3. Lands on trainer dashboard (index.php)
4. Sees hero: "BUILD A BODY THAT'S BUILT FOR BATTLE"
5. Scrolls to "Upcoming Schedule"
6. Sees 5 booking cards:
   - **Card 1:** Nov 10, 2025 at 8:00 AM - Client: Sarah Johnson - Boxing Fundamentals
   - **Card 2:** Nov 10, 2025 at 10:00 AM - Client: Mark Davis - Strength Training
   - **Card 3:** Nov 10, 2025 at 2:00 PM - Client: Lisa Chen - Muay Thai
   - **Card 4:** Nov 11, 2025 at 9:00 AM - Client: Alex Brown - General Training
   - **Card 5:** Nov 11, 2025 at 3:00 PM - Client: Emma Wilson - Boxing Sparring
7. Mike knows today's schedule: 3 sessions (8am, 10am, 2pm)
8. Prepares equipment for first client (Sarah - Boxing)
9. Ready to train!

---

### Scenario 2: New Trainer with No Bookings

**What Happens:**
1. Coach Jessica just hired yesterday
2. Admin created her trainer account
3. Logs in for first time
4. Lands on trainer dashboard
5. Sees hero section
6. Scrolls to "Upcoming Schedule"
7. Sees: 📅❌ "No upcoming bookings scheduled"
8. Understands no sessions booked yet
9. Clicks "View Schedule" to see availability
10. Checks schedule.php for open time slots
11. Waits for members to book sessions

---

### Scenario 3: Busy Trainer Checking Next Week

**What Happens:**
1. Coach David has many bookings
2. Opens dashboard
3. Sees 5 upcoming sessions (next 5 only)
4. All 5 are for today and tomorrow
5. Wants to see next week's schedule
6. Clicks "View Schedule" button
7. Redirected to schedule.php
8. Full calendar shows all bookings (this week + next week)
9. Can see long-term schedule
10. Returns to dashboard for quick daily view

---

### Scenario 4: Member Tries to Access Trainer Dashboard

**What Happens:**
1. Regular member "John" logs in
2. Session role: `member` (not `trainer`)
3. John types URL: `fitxbrawl.com/public/php/trainer/index.php`
4. Page loads, checks role
5. Role ≠ 'trainer' → Redirect triggered
6. John redirected to `login.php`
7. Access denied (unauthorized)
8. John returns to member dashboard

---

### Scenario 5: Checking Upcoming After Cancellation

**What Happens:**
1. Coach Maria had 5 bookings showing
2. Member cancels 2:00 PM session
3. Maria refreshes dashboard
4. Now shows only 4 booking cards
5. Cancelled session removed automatically
6. Next booking (previously #6) now appears as #5
7. Always shows next 5 confirmed bookings
8. Maria adjusts her afternoon plans

---

## Important Notes and Limitations

### Things to Know

1. **Trainer Role Required**
   - Must have `role = 'trainer'` in database
   - Set during account creation by admin
   - Cannot self-assign trainer role
   - Non-trainers cannot access

2. **Shows Only Next 5 Sessions**
   - Limited to 5 upcoming bookings
   - Not a full calendar view
   - For full schedule, use schedule.php
   - Updates automatically as sessions pass

3. **Confirmed Bookings Only**
   - Only shows `booking_status = 'confirmed'`
   - Pending bookings not displayed
   - Cancelled bookings removed instantly
   - No past bookings shown

4. **Today and Future Only**
   - Past dates automatically hidden
   - Yesterday's sessions gone
   - Updates at midnight (date changes)
   - Always current

5. **Trainer Name Matching**
   - Your `trainers.name` must match `users.name`
   - Case-insensitive matching
   - If names don't match: No bookings show
   - Contact admin if mismatch

6. **Manual Refresh Required**
   - Page doesn't auto-refresh
   - New bookings require page reload
   - Cancellations require refresh
   - No real-time updates

### What This Page Doesn't Do

- **Doesn't show full calendar** (schedule.php does that)
- **Doesn't allow booking changes** (read-only view)
- **Doesn't show member contact info** (only names)
- **Doesn't show past sessions** (history not available)
- **Doesn't show pending bookings** (confirmed only)
- **Doesn't send notifications** (separate email system)
- **Doesn't allow scheduling** (members book via reservations.php)
- **Doesn't show payment info** (not trainer's concern)
- **Doesn't show trainer profile** (profile.php does that)
- **Doesn't display feedback** (feedback.php does that)

---

## Navigation

### How Trainers Arrive Here
- **After login:** Automatic redirect to trainer dashboard
- **From schedule page:** "Home" link in navigation
- **From profile page:** "Home" link in navigation
- **From feedback page:** "Home" link in navigation
- **Direct URL:** `fitxbrawl.com/public/php/trainer/index.php`

### Where Trainers Go Next
- **Schedule page** (`schedule.php`) - Full calendar view with all bookings
- **Profile page** (`profile.php`) - Edit trainer bio, specialization, photo
- **Feedback page** (`feedback.php`) - View member reviews and ratings
- **Logout** - End session, return to public site

---

## Visual Design

### Card Layout

**Booking Card Structure:**
```
┌─────────────────────────────────────┐
│ 📅 Nov 10, 2025    🕐 2:00 PM      │ ← Header (date/time)
├─────────────────────────────────────┤
│ 👤 Client: Sarah Johnson            │ ← Body (client)
│ 🏋️ Class: Boxing Fundamentals      │ ← Body (class)
└─────────────────────────────────────┘
```

**Color Scheme:**
- White card background
- Gold/yellow accents for icons
- Dark text for readability
- Subtle shadows for depth

**Responsive Design:**
- Desktop: Cards in grid (2-3 columns)
- Tablet: Cards in 2 columns
- Mobile: Cards stacked vertically (1 column)

---

## Technical Details (Simplified)

### Database Queries

**Query 1: Get Trainer ID**
```sql
SELECT id, name, specialization 
FROM trainers 
WHERE name = '[your username]' 
OR LOWER(name) = LOWER('[your username]')
```

**Query 2: Get Upcoming Bookings**
```sql
SELECT r.*, u.name, u.email, ct.class_name
FROM reservations r
JOIN users u ON r.user_id = u.id
LEFT JOIN class_types ct ON r.class_type_id = ct.id
WHERE r.trainer_id = [your trainer ID]
AND r.reservation_date >= CURDATE()
AND r.booking_status = 'confirmed'
ORDER BY r.reservation_date ASC, r.start_time ASC
LIMIT 5
```

**Table Relationships:**
- `reservations` → Contains booking records
- `users` → Member information
- `class_types` → Class names (Boxing, Muay Thai, etc.)
- `trainers` → Trainer profiles

---

### Session Variables Used

**Authentication:**
- `$_SESSION['role']` - Must be `'trainer'`
- `$_SESSION['user_id']` - Your user account ID
- `$_SESSION['name']` - Your username (matched with trainers table)

**Display:**
- `$_SESSION['avatar']` - Profile picture filename
- Used in header navigation
- Shows custom avatar or default icon

---

## Security Features

### 1. **Role-Based Access Control**
- Checks `role = 'trainer'` before page loads
- Non-trainers redirected immediately
- No data exposed to unauthorized users

### 2. **Session Validation**
- `SessionManager::isLoggedIn()` check
- Prevents access without authentication
- Session timeout protection

### 3. **Data Sanitization**
- `htmlspecialchars()` on all displayed data
- Prevents XSS attacks
- Safe output of member names, class types

### 4. **Prepared Statements**
- All database queries use parameterized statements
- Prevents SQL injection
- Secure data retrieval

---

## Tips for Trainers

### Best Practices

1. **Check Dashboard Daily**
   - Open first thing each morning
   - Review upcoming sessions
   - Prepare equipment based on class types

2. **Note Client Names**
   - Memorize upcoming clients
   - Personalize training experience
   - Build rapport before session

3. **Plan Class Content**
   - See class types in advance
   - Prepare specialized drills (Boxing vs Muay Thai)
   - Adjust difficulty based on client

4. **Use "View Schedule" for Planning**
   - Dashboard shows next 5 only
   - Click button for full weekly/monthly view
   - Plan ahead for busy periods

5. **Refresh Before Each Session**
   - Check for cancellations
   - Verify client didn't reschedule
   - Ensure booking still confirmed

---

## Final Thoughts

The trainer dashboard is designed for speed and clarity. You don't need to navigate through complex menus or search for bookings—land on the page, scroll down, and immediately see who you're training in the next few days. The "next 5 bookings" approach keeps the interface clean while providing enough information for daily planning.

The integration with the full schedule page (`schedule.php`) gives you flexibility: quick daily view here, detailed calendar view there. Whether you're a new trainer with one booking or a veteran with 30+ sessions per week, this dashboard gives you instant context the moment you log in. It's your morning briefing in web page form—simple, focused, and built for trainers who need information fast.

