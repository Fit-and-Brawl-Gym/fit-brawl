# User Page Documentation: Member Dashboard (loggedin-index.php)

**Page Name**: Member Dashboard / Logged-In Homepage  
**Who Can Access**: Logged-in members only  
**Last Updated**: November 10, 2025

---

## What This Page Does

The member dashboard is your **personal home base** after logging in. It shows:

1. **Personalized Welcome** - Time-based greeting with your name
2. **Membership Status** - Active plan, expiry date, days remaining
3. **Upcoming Sessions** - Next 3 booked training sessions
4. **Weekly Progress** - How many sessions you've attended this week
5. **Quick Stats** - Weekly sessions, days left, favorite trainer
6. **Quick Actions** - Book sessions, view reservations, manage membership

---

## Page Sections

### 1. Welcome Header

**What You See**:
- **Time-based greeting**: "Good Morning", "Good Afternoon", or "Good Evening"
- **Your name**: Displays your username from account
- **Motivational subtitle**: "Ready to push your limits today?"

**How Greetings Work**:
```
Morning:   12:00 AM - 11:59 AM → "Good Morning"
Afternoon: 12:00 PM - 5:59 PM  → "Good Afternoon"
Evening:   6:00 PM - 11:59 PM  → "Good Evening"
```

**Technical**: Uses PHP `date('G')` to get current hour (0-23)

---

### 2. Quick Stats Bar (Active Members Only)

**Shows Three Key Metrics**:

| Stat | What It Shows | Example |
|------|---------------|---------|
| **Weekly Sessions** | Sessions completed this week / max allowed | "8/12 sessions" |
| **Days Left** | Days remaining in current membership | "23 days left" |
| **Favorite Trainer** | Most frequently booked trainer | "⭐ John Smith" |

**When Shown**:
- Only appears if you have an **active membership**
- Hidden if membership pending or expired
- Hidden if no membership at all

**Weekly Session Count**:
- Week runs Monday to Sunday
- Counts only "confirmed" or "completed" bookings
- Resets every Monday
- Max 12 sessions per week (assumption based on code)

---

### 3. Book a Session Card (Primary Action)

**What It Does**:
Large, prominent card encouraging you to book training

**Button Behavior** (Smart Routing):

| Your Status | Button Takes You To | Why |
|-------------|---------------------|-----|
| **Active Membership** | `reservations.php` | Book sessions directly |
| **Pending Request** | `membership-status.php` | Check application status |
| **No Membership** | `membership.php` | Purchase membership first |

**Visual Design**:
- Icon: Calendar with plus sign
- Title: "Book a Session"
- Subtitle: "Schedule your next training session"
- Yellow "Book Now" button with arrow

---

### 4. Upcoming Sessions Card

**Shows Next 3 Sessions**:

**Each Session Displays**:
- **Date Badge**: Day number and month (e.g., "15 Nov")
- **Class Type**: Boxing, MMA, Muay Thai, Gym Access, etc.
- **Session Time**: Morning, Afternoon, or Evening
- **Trainer Name**: Who will train you

**Time Slots**:
```
Morning:   7:00 AM - 11:00 AM
Afternoon: 1:00 PM - 5:00 PM  
Evening:   6:00 PM - 10:00 PM
```

**Sorting**:
- Ordered by date (earliest first)
- Then by time slot (Morning → Afternoon → Evening)
- Only shows "confirmed" bookings
- Ignores past dates

**If No Bookings**:
- Shows empty state with calendar icon
- Message: "No upcoming sessions"
- Button: "Book your first session"

**View All Link**:
- Appears if you have bookings
- Takes you to `reservations.php` for full list

---

### 5. Weekly Progress Card

**Shows Your Activity This Week**:

**Big Number Display**:
- Example: "8 / 12 sessions"
- Large number = sessions completed
- Small number = max allowed per week

**Progress Bar**:
- Visual bar filling from 0% to 100%
- Calculation: `(completed / 12) * 100`
- Max width: 100% (even if you book more than 12)

**Remaining Sessions**:
- Text: "4 sessions remaining this week"
- Calculation: `12 - completed`

**Motivational Messages**:

| Sessions Completed | Message | Icon |
|--------------------|---------|------|
| **10-12** | "You're on fire! Keep it up!" | 🔥 Fire |
| **6-9** | "Great progress this week!" | ⚡ Bolt |
| **1-5** | "Let's keep pushing!" | 🏋️ Dumbbell |
| **0** | No message shown | - |

**Technical Note**: Week calculation finds last Monday and counts forward 6 days to get Sunday range.

---

### 6. Membership Status Card

**Three Possible States**:

#### State 1: Active Membership ✅

**Shows**:
- Green badge: "✓ Active"
- Plan name: "Gladiator Plan", "Brawler Plan", etc.
- Class type: "All Classes", "Boxing Only", etc.
- Expiry date: "Dec 15, 2025"
- Days remaining: "23 days" (highlighted in yellow)

**Data Sources**:
- First checks `user_memberships` table
- If not found, checks `subscriptions` table
- Uses most recent approved membership

**Grace Period**:
- Membership valid for 3 days after expiry date
- Example: Expires Dec 15 → Still active until Dec 18
- After grace period, becomes inactive

---

#### State 2: Pending Request ⏳

**Shows**:
- Hourglass icon
- Message: "Your membership request is pending approval"
- Link: "Check Status" → `membership-status.php`

**When This Appears**:
- You submitted membership purchase
- Admin hasn't approved yet
- Request exists in database but not approved

---

#### State 3: No Membership 🎫

**Shows**:
- Ticket icon
- Message: "You don't have an active membership"
- Button: "Get Membership" → `membership.php`

**When This Appears**:
- New account, never purchased
- Previous membership expired (past grace period)
- Request was rejected/cancelled

---

## How Membership Detection Works

### Technical Process

**Step 1: Check user_memberships Table**
```
Query: Get latest membership for your user_id
Filter: request_status = 'approved'
Check: end_date + 3 days >= today
Result: Active if within grace period
```

**Step 2: Check subscriptions Table (Fallback)**
```
If no active membership found in step 1:
Query: Get latest subscription for your user_id  
Filter: status = 'approved'
Check: end_date + 3 days >= today
Result: Active if within grace period
```

**Step 3: Set Flags**
- `$hasActiveMembership` = true/false
- `$hasAnyRequest` = true/false (pending exists)
- `$activeMembership` = array of membership data or null

**Why Two Tables?**:
- `user_memberships`: New system for membership purchases
- `subscriptions`: Legacy/alternative membership system
- Fallback ensures compatibility

---

## Remember Me Login Recovery

### Auto-Login Feature

**What Happens**:
If you have a "Remember Me" token but no active session:

```
1. Page checks for remember_password token in session
   ↓
2. Queries remember_password table
   ↓
3. Verifies token hash matches
   ↓
4. If valid → Retrieves user data from users table
   ↓
5. Rebuilds session variables:
   - user_id
   - name (username)
   - email
   - role
   - avatar
   ↓
6. You're automatically logged back in
```

**Security**:
- Token is hashed (not stored as plain text)
- Uses `password_verify()` like password checking
- Token linked to specific user_id

---

## Avatar Display

### Profile Picture Logic

**Where Avatar Comes From**:
- Stored in `$_SESSION['avatar']` (filename)
- Files located in `/uploads/avatars/` folder

**Display Rules**:

| Avatar Value | Displays |
|--------------|----------|
| `default-avatar.png` | Default account icon SVG |
| Empty/null | Default account icon SVG |
| Custom filename | User's uploaded photo from `/uploads/avatars/` |

**Technical**:
```php
$avatarSrc = $hasCustomAvatar 
    ? "../../uploads/avatars/" . $avatar 
    : "../../images/account-icon.svg";
```

**Security**: Uses `htmlspecialchars()` to prevent XSS attacks in avatar filename

---

## Navigation Links

### Quick Access Buttons

**Depending on Status**:

1. **Book Now Button**:
   - Active: → `reservations.php`
   - Pending: → `membership-status.php`
   - None: → `membership.php`

2. **View All (Bookings)**: → `reservations.php`

3. **Check Status (Pending)**: → `membership-status.php`

4. **Get Membership**: → `membership.php`

### Header Menu

**Standard Navigation** (via header.php):
- Home (current page)
- Reservations
- Membership
- Profile
- Products
- Contact
- Logout

---

## Data Calculations

### Days Remaining in Membership

**How It's Calculated**:
```
1. Get membership end_date from database
2. Get current date (today)
3. Calculate difference: end_date - today
4. Result = number of days
```

**Technical**: Uses PHP `DateTime` objects and `diff()` method

**Example**:
- Today: Nov 10, 2025
- End Date: Dec 3, 2025
- Days Remaining: 23

**Display**: Shows in "Quick Stats Bar" and "Membership Status Card"

---

### Weekly Bookings Count

**What Counts**:
- Bookings from Monday (start of week) to Sunday (end)
- Only status: "confirmed" or "completed"
- Excludes: cancelled, pending, no-show

**How Week is Determined**:
```
1. Get current day of week (1=Monday, 7=Sunday)
2. Calculate days since last Monday
3. Week Start = Today - days_since_monday
4. Week End = Week Start + 6 days
```

**Example**:
- Today: Wednesday (day 3)
- Days since Monday: 2
- Week: Monday Nov 4 to Sunday Nov 10

**Query**:
```sql
SELECT COUNT(*) 
FROM user_reservations 
WHERE user_id = ? 
AND booking_date BETWEEN 'week_start' AND 'week_end'
AND booking_status IN ('confirmed', 'completed')
```

---

### Favorite Trainer

**How It's Determined**:
- Counts confirmed bookings per trainer
- Groups by trainer_id
- Selects trainer with most bookings
- Shows name and photo

**Example**:
- You booked John Smith: 8 times
- You booked Jane Doe: 3 times
- Favorite: John Smith (highest count)

**Query Logic**:
```sql
SELECT trainer.name, COUNT(*) as booking_count
FROM user_reservations
JOIN trainers ON user_reservations.trainer_id = trainers.id
WHERE user_id = ?
GROUP BY trainer_id
ORDER BY booking_count DESC
LIMIT 1
```

**Display**: Shows in "Quick Stats Bar" with ⭐ icon

---

## Security Features

### Access Control

**1. Login Required**
```php
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
```
- If no session → Redirect to login page
- Cannot access dashboard without authentication

**2. Email Verification**
```php
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}
```
- Double-check for email in session
- Failsafe if session incomplete

**3. Session Manager**
- Initializes secure session
- Handles timeouts (15 min idle, 10 hours absolute)
- Regenerates session IDs
- Prevents session hijacking

### Data Sanitization

**All User Data Escaped**:
- `htmlspecialchars()` on all output
- Prevents XSS (Cross-Site Scripting) attacks
- User names, plan names, trainer names all escaped

**Example**:
```php
<?= htmlspecialchars($userName) ?>
```

**Why Important**: If username contains `<script>alert('hack')</script>`, it displays as text instead of executing.

---

## Responsive Design

### Mobile Adaptations

**Small Screens** (< 768px):
- Cards stack vertically (single column)
- Quick stats bar wraps to multiple lines
- Booking previews simplified
- Larger touch targets

**Tablets** (768px - 1023px):
- Two-column grid for dashboard cards
- Stats bar stays horizontal
- Comfortable spacing

**Desktop** (1024px+):
- Three-column grid layout
- Full stats bar
- Hover effects on cards
- Spacious layout

---

## Empty States

### When You Have No Data

**No Upcoming Sessions**:
- Icon: Calendar with X
- Message: "No upcoming sessions"
- Button: "Book your first session"
- Links to appropriate booking page

**No Membership**:
- Icon: Ticket
- Message: "You don't have an active membership"
- Button: "Get Membership"
- Links to `membership.php`

**No Favorite Trainer** (not shown):
- Quick stat simply doesn't appear
- Only shows if you have booking history

**Purpose**: Guide users toward next action rather than showing empty space

---

## Performance Optimizations

### Database Queries

**Total Queries Run**: 5-6 queries
1. Membership check (user_memberships)
2. Subscription check (fallback)
3. Weekly bookings count
4. Upcoming bookings (limit 3)
5. Favorite trainer
6. (Optional) Remember Me token verification

**Query Optimization**:
- Uses prepared statements (prevents SQL injection)
- Uses LIMIT clauses (only fetch needed rows)
- Indexes on user_id for faster lookups
- LEFT JOIN for optional data (trainers)

### Caching

**Session Caching**:
- User data stored in session
- No need to fetch from DB on every page load
- Only membership status checked fresh

**CSS Versioning**:
```php
"loggedin-homepage.css?v=" . time()
```
- Adds timestamp to CSS URL
- Forces browser to reload CSS on changes
- Prevents old cached styles

---

## Known Limitations

### Current Issues

**1. No Real-Time Updates**
- Membership status not live
- Must refresh page to see changes
- If admin approves membership while you're on page, you won't see it

**2. Fixed Weekly Limit (12 Sessions)**
- Hardcoded max sessions per week
- Not configurable per membership plan
- Some plans might allow more/less

**3. Grace Period Not Configurable**
- Fixed at 3 days after expiry
- Not adjustable per plan
- Hardcoded in PHP

**4. No Session Type Breakdown**
- Weekly count shows total sessions
- Doesn't break down by type (Boxing, MMA, etc.)
- Could be useful analytics

**5. Favorite Trainer Doesn't Show Photo**
- Data is fetched (photo column)
- But only name displayed in quick stats
- Photo not utilized

**6. No Membership Auto-Renewal Warning**
- Doesn't warn if membership expiring soon
- No "Renew Now" prompt
- User must remember to renew

**7. Remember Me Token No Expiry**
- Tokens don't expire
- Could be security risk
- Recommendation: 30-day expiry

---

## Common User Scenarios

### Scenario 1: Active Member Checking Dashboard

```
1. Login successfully
   ↓
2. Lands on loggedin-index.php
   ↓
3. Sees: "Good Morning, John"
   ↓
4. Quick Stats: "8/12 sessions, 23 days left"
   ↓
5. Upcoming Sessions: 3 bookings listed
   ↓
6. Weekly Progress: Progress bar at 66%
   ↓
7. Membership: "Active - Gladiator Plan"
   ↓
8. Clicks "Book Now" → Goes to reservations.php
```

---

### Scenario 2: New Member (Pending Approval)

```
1. Signed up and purchased membership
   ↓
2. Admin hasn't approved yet
   ↓
3. Logs in → Sees dashboard
   ↓
4. No quick stats (not active yet)
   ↓
5. No upcoming sessions (can't book yet)
   ↓
6. Weekly Progress: 0/12
   ↓
7. Membership: "Pending approval"
   ↓
8. Clicks "Check Status" → membership-status.php
```

---

### Scenario 3: Expired Membership (Within Grace Period)

```
1. Membership expired 2 days ago
   ↓
2. Still within 3-day grace period
   ↓
3. Logs in → Sees dashboard
   ↓
4. Quick Stats: Shows but "0 days left"
   ↓
5. Membership Status: Still shows "Active"
   ↓
6. Can still book sessions
   ↓
7. After day 3: Becomes inactive
```

---

### Scenario 4: No Membership

```
1. New account or expired membership
   ↓
2. Logs in → Sees dashboard
   ↓
3. No quick stats shown
   ↓
4. Upcoming Sessions: "No upcoming sessions"
   ↓
5. Weekly Progress: 0/12
   ↓
6. Membership: "You don't have an active membership"
   ↓
7. Clicks "Get Membership" → membership.php
```

---

### Scenario 5: Remember Me Auto-Login

```
1. Previously checked "Remember Me" on login
   ↓
2. Close browser completely
   ↓
3. Open browser days later
   ↓
4. Visit site → Session expired
   ↓
5. Page checks remember_password token
   ↓
6. Token valid → Auto-login
   ↓
7. Session rebuilt with user data
   ↓
8. Sees dashboard without manual login
```

---

## Best Practices for Users

### Dashboard Tips

**✓ Do This**:
- Check dashboard daily for upcoming sessions
- Monitor days remaining in membership
- Renew before expiry (not during grace period)
- Track weekly progress to stay motivated
- Book sessions in advance

**✗ Avoid This**:
- Waiting until last day to renew
- Ignoring "pending" status for too long
- Booking without checking trainer availability
- Forgetting to log out on shared devices

---

## Future Enhancements

### Planned Improvements

**1. Membership Renewal Reminder**
- Alert when < 7 days remaining
- "Renew Now" button in dashboard
- Email reminder notifications

**2. Achievement Badges**
- Weekly streak counter
- Total sessions milestone badges
- "Top Performer" recognition

**3. Class Schedule Preview**
- Next week's available classes
- Direct booking from dashboard
- Real-time availability

**4. Progress Charts**
- Visual graph of attendance over time
- Comparison to previous weeks
- Goal setting and tracking

**5. Trainer Profiles**
- Click favorite trainer to see profile
- View trainer's schedule
- Book directly with preferred trainer

**6. Quick Actions Widget**
- Cancel upcoming session
- Reschedule booking
- Contact trainer/support

**7. Personalized Recommendations**
- Suggest classes based on history
- Optimal training days
- Recovery time suggestions

---

## Summary

### What This Dashboard Does Well

**✓ Personalization**:
- Time-based greetings
- Shows your name
- Customized stats
- Favorite trainer recognition

**✓ Clear Status Indicators**:
- Membership active/pending/none
- Days remaining prominently shown
- Weekly progress visualization
- Upcoming sessions preview

**✓ Smart Navigation**:
- "Book Now" adapts to your status
- Empty states guide next actions
- Quick links to relevant pages

**✓ Motivation**:
- Progress tracking
- Encouraging messages
- Visual feedback (progress bars)
- Achievement recognition (sessions completed)

### What Could Be Better

**⚠️ Missing Features**:
- No renewal reminders
- No real-time updates
- No detailed analytics
- No session type breakdown
- No achievement system

**⚠️ Technical Improvements**:
- Add WebSocket for live updates
- Configurable weekly limits
- Dynamic grace periods
- Token expiration
- Better error handling

### Quick User Guide

1. **Check your stats daily** - Stay informed about membership
2. **Book sessions in advance** - Don't wait until last minute
3. **Monitor days remaining** - Renew before expiry
4. **Track weekly progress** - Stay motivated
5. **Use "Book Now" button** - It knows where to send you

---

**Page Status**: ✅ Fully functional  
**Best For**: Daily check-in, booking sessions, tracking progress  
**Update Frequency**: Real-time on page load  
**Mobile Friendly**: ✅ Yes

**Documentation Version**: 1.0  
**Last Updated**: November 10, 2025
