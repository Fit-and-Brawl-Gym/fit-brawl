# Reservations Page Documentation
**File:** `public/php/reservations.php`  
**Purpose:** Book training sessions with professional trainers  
**User Access:** Members only (requires active membership or grace period)

---

## What This Page Does

The reservations page is your personal booking system for scheduling training sessions at FitXBrawl gym. Think of it like booking a restaurant table, but instead of dining, you're reserving your spot with a trainer for Boxing, MMA, Muay Thai, or gym training. The page guides you through a 5-step wizard that makes booking sessions simple and organized.

### Who Can Use This Page
- **Active members:** Full access to book sessions included in their membership
- **Grace period members:** Members whose membership expired within the last 3 days can still book
- **Non-members:** Redirected to membership page (must purchase membership first)

---

## The Page Experience

### For Members Without Active Membership

If you visit this page without an active membership, you'll see a simple call-to-action screen:

**No Membership CTA Display:**
- A ticket icon (🎫)
- Headline: "Get Started with a Membership"
- Explanation: "To book training sessions, you need an active membership plan"
- "View Membership Plans" button that redirects to the membership page

This prevents confusion and directs you to purchase a membership before attempting to book.

---

### For Active Members

When you have an active membership, the page transforms into a comprehensive booking system. Here's what you'll see:

#### **1. Page Header Section**

**Title and Subtitle:**
- Bold headline: "Book Your Training Session"
- Subtitle: "Reserve your spot with our expert trainers"

**Membership Status Bar:**
The status bar dynamically changes based on your membership situation:

**Active Membership (Normal):**
- ✅ Green checkmark icon
- Shows: "Membership Active until [Date] ([Plan Name] Plan)"
- Example: "Membership Active until December 31, 2025 (Gladiator Plan)"

**Expiring Soon (1-7 days before expiration):**
- ⚠️ Warning triangle icon
- Yellow/orange warning color
- Shows: "Membership Expiring Soon"
- Details: "Expires on [Date] ([X] days left) • Book until [Grace End Date] • Visit gym to renew or wait for expiration date to renew online"

**Expired (Within Grace Period):**
- ❌ Red X icon
- Red critical color
- Shows: "Membership Expired"
- Details: "Expired on [Date] ([X] days ago) • Book until [Grace End Date] ([X] days left)"
- Includes yellow "Renew Membership" button that links to membership page

The grace period is 3 days, meaning even if your membership expires, you can still book sessions for 3 more days.

#### **2. Statistics Cards (Top Section)**

Three cards display your booking information at a glance:

**Weekly Bookings Card:**
- 📅 Calendar icon
- Shows: "X/12" format (e.g., "7/12")
- Counter: How many sessions you've booked this week
- Limit: Maximum 12 bookings per week
- Subtext: "X bookings remaining this week" or "This week's limit reached (12 max)" in orange

**Upcoming Class Card:**
- 🔔 Bell icon
- Shows: Your next scheduled class type (e.g., "Boxing", "MMA")
- Subtext: Date and time of upcoming session (e.g., "Nov 15, 2025 - Morning")
- Shows "-" and "No upcoming sessions" if nothing booked

**Trainer Card:**
- 👤 User icon
- Shows: Name of trainer for your upcoming session
- Subtext: Additional trainer info
- Shows "-" if no upcoming sessions

These cards update in real-time as you book or cancel sessions.

#### **3. The 5-Step Booking Wizard**

The core of the page is a step-by-step wizard that walks you through booking a session:

---

### **Step 1: Select Date**

**What You See:**
- Large monthly calendar view
- Current month displayed (e.g., "NOVEMBER")
- Month navigation arrows (left/right)
- Dropdown menu to jump to any month
- Calendar shows full month grid with Sunday-Saturday columns

**How the Calendar Works:**

**Color-Coded Days:**
- **Gray/Dimmed:** Past dates (cannot select)
- **Light background:** Today's date
- **White/Normal:** Future available dates
- **Disabled:** Dates beyond your membership expiration (including grace period)

**Interactive Elements:**
- Click any available future date to select it
- Click left/right arrows to change months
- Click month name to open dropdown and jump to any month
- Selected date highlights in gold/yellow

**Date Restrictions:**
1. **Cannot book past dates** - Only today and future dates are clickable
2. **Cannot book beyond grace period** - If membership expires Dec 15, you can only book until Dec 18 (3-day grace)
3. **Weekly limit check** - If you've already booked 12 sessions in a week, dates in that week appear disabled

**Weekly Limit Logic:**
The system uses a Sunday-to-Saturday week cycle. If you've booked 12 sessions in the week of Nov 10-16, all dates in that week become unselectable. You'll see a warning:

> **Weekly Booking Limit Reached**
> 
> You've already booked 12 sessions for the week of Nov 10 - Nov 16 (maximum 12 per week). Please select a date from a different week.

**Navigation:**
- "Next" button appears at top right after selecting a date
- Click "Next" to proceed to Step 2

---

### **Step 2: Select Session Time**

**What You See:**
Three large session blocks, each showing:
- Icon (sun, cloud-sun, or moon)
- Session name
- Time range
- Helpful note

**Available Sessions:**

**Morning Session:**
- ☀️ Sun icon
- Time: 7:00 AM - 11:00 AM
- Note: "Arrive anytime during this window"
- Best for: Early risers and before-work training

**Afternoon Session:**
- ☁️ Cloud-sun icon
- Time: 1:00 PM - 5:00 PM
- Note: "Arrive anytime during this window"
- Best for: Lunch breaks and mid-day training

**Evening Session:**
- 🌙 Moon icon
- Time: 6:00 PM - 10:00 PM
- Note: "Arrive anytime during this window"
- Best for: After-work and night owls

**How It Works:**
- Click on any session block to select it
- Selected session highlights with gold border
- Note: You choose the time window, not exact arrival time
- You can arrive anytime during the 4-hour window

**Navigation:**
- "Back" button now appears (returns to Step 1)
- "Next" button activates after selecting session

---

### **Step 3: Select Class Type**

**What You See:**
Cards showing only the class types included in your membership plan.

**Possible Class Types:**

**Gym:**
- 🏋️ Dumbbell icon
- Description: "Strength training and fitness conditioning"
- Available in: Resolution, Gladiator plans

**Boxing:**
- 🥊 Fist icon
- Description: "Improve technique, footwork, and conditioning"
- Available in: Champion, Gladiator plans

**MMA:**
- 🛡️ Shield icon
- Description: "Mixed martial arts training and sparring"
- Available in: Gladiator, Clash plans

**Muay Thai:**
- 👊 Back-fist icon
- Description: "Master the art of eight limbs"
- Available in: Brawler, Gladiator plans

**Smart Filtering:**
The page automatically shows only classes included in your membership. For example:
- **Resolution members** see only "Gym"
- **Gladiator members** see all four class types
- **Champion members** see only "Boxing"

**How It Works:**
- Click on any available class card
- Card highlights with gold border when selected
- If no classes available, shows: "No class types available in your membership"

**Navigation:**
- "Back" button returns to Step 2
- "Next" button activates after selecting class type

---

### **Step 4: Select Trainer**

**What You See:**
- Facility capacity information at the top
- Grid of available trainer cards

**Facility Capacity Info:**
Shows one of these messages:
- ℹ️ "Checking trainer availability..." (while loading)
- ✅ "X trainers available for [Class Type] on [Date] - [Session]"
- ⚠️ "No trainers available for this time slot" (if fully booked)

**Trainer Cards Display:**
Each trainer card shows:
- Profile photo or default avatar
- Trainer name
- Specialization (e.g., "Boxing Specialist")
- Current bookings for selected slot
- Chevron arrow for selection

**Availability Status:**
- **Available trainers:** Normal appearance, clickable
- **Fully booked trainers:** Grayed out, shows "Fully Booked" badge
- **Unavailable trainers:** Hidden (on day-off, admin-blocked, or wrong specialization)

**Smart Filtering:**
The system automatically filters trainers:

1. **Specialization Match:** Only shows trainers who specialize in your selected class type
   - If you selected "Boxing", only boxing trainers appear
   - If you selected "MMA", only MMA trainers appear

2. **Day-Off Check:** Trainers who have this day as their day-off show as "Unavailable"

3. **Admin Blocks:** Trainers blocked by admin for this specific date/session show as "Unavailable"

4. **Capacity Check:** Trainers who already have maximum bookings show as "Fully Booked"

**How It Works:**
- Click on any available trainer card
- Card highlights with gold border
- "Next" button activates

**If No Trainers Available:**
Shows message: "No trainers available for this time slot. Please select a different date or session."
- Cannot proceed to next step
- Must go back and change date or session

**Navigation:**
- "Back" button returns to Step 3
- "Next" button activates after selecting trainer

---

### **Step 5: Confirm Booking**

**What You See:**
A summary card showing all your selections:

**Booking Summary:**
- 📅 Date: "November 15, 2025"
- 🕐 Session: "Morning (7:00 AM - 11:00 AM)"
- 🏋️ Class Type: "Boxing"
- 👤 Trainer: "John Doe"

**Confirm Booking Button:**
Large green button with checkmark icon: "Confirm Booking"

**How It Works:**
1. Review all details carefully
2. Click "Confirm Booking" button
3. System validates the booking one final time:
   - Checks weekly limit again
   - Verifies trainer still available
   - Confirms membership still valid
   - Validates no duplicate booking
4. If successful: Creates booking in database
5. Shows success toast notification
6. Resets wizard to Step 1
7. Updates "My Bookings" section immediately

**Validation Checks During Confirmation:**
- **Weekly limit:** Ensures you haven't exceeded 12 bookings for that week
- **Trainer availability:** Confirms trainer isn't now fully booked
- **Membership validity:** Checks membership hasn't expired during booking process
- **Duplicate prevention:** Ensures you don't have another booking for same date/session
- **Specialization match:** Final check that trainer teaches the selected class
- **Admin blocks:** Verifies no last-minute admin restrictions

**Navigation:**
- "Back" button returns to Step 4
- After successful booking, wizard resets to Step 1

---

#### **4. My Bookings Section**

Below the wizard is a comprehensive list of all your bookings:

**Section Controls:**

**Filter Dropdown:**
- Icon: 🔍 Filter icon
- Label: "Filter by Class:"
- Options: "All Classes", plus each class in your membership
- Filters bookings by class type in real-time

**Tab Navigation:**
Three tabs to organize bookings:

1. **Upcoming Tab** (Default)
   - Shows all future bookings
   - Badge count: Number of upcoming sessions
   - Status: "confirmed" bookings

2. **Past Tab**
   - Shows completed or past-date bookings
   - Badge count: Number of past sessions
   - Status: "completed" bookings

3. **Cancelled Tab**
   - Shows all cancelled bookings
   - Badge count: Number of cancelled sessions
   - Status: "cancelled" bookings

**Booking Cards:**
Each booking displays as a card with:
- **Class type badge** (colored, e.g., "BOXING" in gold)
- **Date and time** (e.g., "November 15, 2025 - Morning")
- **Trainer name** with profile photo
- **Status indicator** (Confirmed, Completed, Cancelled)
- **Action buttons** (for upcoming bookings only)

**Available Actions (Upcoming Bookings):**
- **Cancel Button:** Red button with X icon - cancels the booking
  - Shows confirmation dialog: "Are you sure you want to cancel this booking?"
  - If confirmed, changes status to "cancelled"
  - Frees up the slot for other members
  - Updates weekly count

**Empty States:**
- No upcoming bookings: "No upcoming sessions"
- No past bookings: "No past sessions"
- No cancelled bookings: "No cancelled bookings"

---

## How the Booking System Works

### The Booking Flow (Summary)

```
1. SELECT DATE
   ↓
   Calendar → Choose future date within membership period
   ↓
2. SELECT SESSION
   ↓
   Morning/Afternoon/Evening → Choose time window
   ↓
3. SELECT CLASS TYPE
   ↓
   Gym/Boxing/MMA/Muay Thai → Choose training discipline
   ↓
4. SELECT TRAINER
   ↓
   Trainer Grid → Choose available trainer who specializes in selected class
   ↓
5. CONFIRM BOOKING
   ↓
   Review Summary → Submit booking
   ↓
   ✅ SUCCESS → Booking created, notifications sent, wizard resets
```

### Weekly Limit System

**How Weeks Are Calculated:**
- Week runs Sunday through Saturday
- System counts bookings in each 7-day window
- Maximum: 12 bookings per week
- Counts both confirmed and completed bookings
- Does NOT count cancelled bookings

**Example:**
- Week of Nov 10-16 (Sunday to Saturday)
- You've booked 8 sessions already
- You can book 4 more sessions in this week
- If you try to book a 13th session, system blocks it

**Per-Week Validation:**
When you select a date, the system:
1. Determines which week that date falls in
2. Counts your existing bookings in that week
3. Displays warning if limit reached
4. Disables "Next" button if week is full

### Grace Period Rules

**3-Day Buffer:**
After your membership expires, you have 3 extra days to:
- View the reservations page
- Book training sessions
- Attend already-booked sessions

**Example:**
- Membership expires: December 15, 2025
- Grace period extends to: December 18, 2025
- You can book sessions dated up to December 18
- On December 19, you lose access

**Visual Indicators During Grace:**
- Status bar turns red with "Expired" message
- Shows days remaining in grace period
- "Renew Membership" button appears
- Calendar disables dates beyond grace end

### Trainer Availability Logic

**How Trainers Appear in Step 4:**

The system performs multiple checks:

1. **Specialization Filter:**
   - Only shows trainers whose specialization matches selected class type
   - Boxing class → Only boxing trainers
   - MMA class → Only MMA trainers

2. **Day-Off Filter:**
   - Checks trainer_day_offs table
   - If trainer has selected day as day-off, they're disabled
   - Each trainer sets their own day-off schedule

3. **Admin Block Filter:**
   - Checks trainer_availability_blocks table
   - If admin blocked trainer for specific date/session, they're disabled
   - Can be "All Day" block or specific session block

4. **Capacity Check:**
   - Counts existing bookings for trainer on that date/session
   - If at maximum capacity (fully booked), shows "Fully Booked" badge
   - Card grayed out, not clickable

5. **Soft Delete Check:**
   - Only shows active trainers (deleted_at IS NULL)
   - Removed trainers never appear

**Result:**
Only trainers who pass ALL checks appear as available options.

---

## Interactive Features

### Real-Time Updates

**Statistics Cards:**
- Update immediately after booking or cancellation
- Weekly count increments/decrements
- Upcoming session details refresh
- No page reload needed

**Booking Lists:**
- New bookings appear instantly in "Upcoming" tab
- Cancelled bookings move to "Cancelled" tab
- Past-date bookings automatically move to "Past" tab
- Filter and tab changes happen instantly

### Toast Notifications

**Success Toasts (Green):**
- ✅ "Booking confirmed successfully!"
- ✅ "Booking cancelled"

**Error Toasts (Red):**
- ❌ "Failed to create booking. Please try again."
- ❌ "Weekly booking limit reached for this date"
- ❌ "Trainer is no longer available for this session"

**Warning Toasts (Orange):**
- ⚠️ "Membership expires soon. Book now!"

**Info Toasts (Blue):**
- ℹ️ "Checking availability..."

Toasts appear in top-right corner, auto-dismiss after 4 seconds, and have close button.

### Responsive Calendar

**Desktop View:**
- Full month grid (7 columns × 5-6 rows)
- Large clickable date cells
- Easy month navigation

**Mobile View:**
- Compact calendar layout
- Touch-friendly date cells
- Swipe gestures for month navigation
- Dropdown month selector easier to use

### Wizard Navigation

**Step Progress:**
- Each step numbered (1-5)
- Current step highlighted
- Previous steps accessible via "Back" button
- Cannot skip ahead without completing current step

**Button States:**
- "Next" button disabled until selection made
- "Back" button appears from Step 2 onward
- "Confirm Booking" only appears on Step 5

---

## Common User Scenarios

### Scenario 1: First-Time Booking

**What Happens:**
1. Member visits reservations.php
2. Sees active membership status in green
3. Stats show "0/12" weekly bookings
4. Clicks on next Monday in calendar
5. Selects "Morning" session
6. Chooses "Boxing" class type
7. Sees 3 available boxing trainers
8. Selects favorite trainer
9. Reviews summary, clicks "Confirm Booking"
10. Success toast appears
11. Wizard resets to Step 1
12. Stats update to "1/12"
13. New booking appears in "Upcoming" tab

### Scenario 2: Approaching Weekly Limit

**What Happens:**
1. Member has already booked 11 sessions this week
2. Stats show "11/12" with "1 booking remaining this week"
3. Selects date in current week
4. Completes booking wizard
5. Successfully books 12th session
6. Stats update to "12/12"
7. Stats subtext changes to "This week's limit reached (12 max)" in orange
8. If member tries to select another date in same week:
   - Date appears disabled
   - Warning banner shows: "Weekly Booking Limit Reached"
   - Cannot proceed with booking

### Scenario 3: Membership Expiring Soon

**What Happens:**
1. Member's plan expires in 5 days
2. Status bar shows orange warning
3. Message: "Membership Expiring Soon - Expires on Dec 15, 2025 (5 days left)"
4. Member books session for Dec 17 (within grace period)
5. Booking succeeds
6. Calendar shows dates after Dec 18 as disabled (grace period ends)
7. Member sees "Visit gym to renew" reminder

### Scenario 4: Grace Period Member

**What Happens:**
1. Membership expired 2 days ago
2. Status bar shows red critical alert
3. Message: "Membership Expired - Expired on Dec 15 (2 days ago) • Book until Dec 18 (1 day left)"
4. Yellow "Renew Membership" button appears
5. Member can still book sessions until Dec 18
6. Calendar only shows dates up to Dec 18
7. After Dec 18, member redirected to membership page

### Scenario 5: No Trainers Available

**What Happens:**
1. Member selects Saturday, Evening session, MMA
2. Proceeds to Step 4
3. System checks: All MMA trainers are either:
   - On day-off (Saturday)
   - Fully booked
   - Admin-blocked
4. Message appears: "No trainers available for this time slot"
5. Member clicks "Back"
6. Changes to Afternoon session
7. Now sees 2 available trainers
8. Completes booking successfully

### Scenario 6: Cancelling a Booking

**What Happens:**
1. Member views "Upcoming" tab
2. Sees booking for next Tuesday
3. Clicks red "Cancel" button
4. Confirmation dialog: "Are you sure you want to cancel this booking?"
5. Clicks "Yes"
6. Booking status changes to "cancelled"
7. Moves from "Upcoming" to "Cancelled" tab
8. Weekly count decreases (e.g., from "8/12" to "7/12")
9. Success toast: "Booking cancelled"
10. Slot becomes available for other members

---

## Key Features Summary

| Feature | Description | Benefit |
|---------|-------------|---------|
| **5-Step Wizard** | Guided booking process | Prevents confusion, ensures all info collected |
| **Weekly Limits** | Maximum 12 bookings per week | Fair access, prevents overuse |
| **Grace Period** | 3-day buffer after expiration | Flexibility for late renewals |
| **Smart Filtering** | Only shows relevant classes/trainers | Streamlined experience |
| **Real-Time Stats** | Live booking count and upcoming session | Clear visibility of status |
| **Availability Check** | Validates trainer, capacity, day-offs | Prevents booking conflicts |
| **Mobile Responsive** | Adapts to all screen sizes | Book anywhere, anytime |
| **Toast Notifications** | Instant feedback on actions | Clear success/error communication |
| **Booking History** | Upcoming, Past, Cancelled tabs | Complete booking management |

---

## What Makes This Page Special

### 1. **Intelligent Validation**
The system performs 10+ validation checks before confirming a booking:
- Membership active or in grace period
- Weekly limit not exceeded
- Date not in the past
- Trainer specializes in selected class
- Trainer not on day-off
- Trainer not admin-blocked
- Trainer has capacity available
- No duplicate booking for same date/session
- Booking date within membership validity
- Class type included in membership plan

This prevents errors and ensures smooth bookings.

### 2. **Fair Access System**
The 12-per-week limit ensures:
- All members get fair access to trainers
- Prevents monopolization by power users
- Spreads training load across weeks
- Encourages balanced training schedule

### 3. **Graceful Degradation**
The page handles edge cases elegantly:
- Expired membership → Clear renewal path
- No trainers available → Suggests alternatives
- Weekly limit reached → Shows other weeks
- All sessions full → Encourages different dates

### 4. **Trainer Respect**
The system respects trainer preferences:
- Honors day-off settings
- Shows admin blocks (personal emergencies)
- Prevents overbooking
- Matches specializations only

---

## Important Notes and Limitations

### Things to Know

1. **Weekly Cycle**
   - Weeks run Sunday-Saturday (not Monday-Sunday)
   - 12-booking limit applies to each 7-day cycle
   - Cancelled bookings don't count toward limit
   - System recalculates limit for each new week

2. **Time Windows, Not Exact Times**
   - You select Morning/Afternoon/Evening blocks
   - You can arrive anytime within the 4-hour window
   - Exact scheduling happens when you arrive at gym
   - Flexibility built-in for member convenience

3. **Grace Period Restrictions**
   - 3 days after membership expiration
   - Can book sessions dated within grace period
   - Cannot book sessions beyond grace end date
   - Some features may be limited during grace

4. **Trainer Assignment**
   - You select specific trainer when booking
   - Trainer cannot be changed after booking
   - Must cancel and rebook to change trainer
   - Trainer specialization must match class type

5. **Cancellation Rules**
   - Can only cancel upcoming bookings
   - Cannot cancel past or completed sessions
   - Cancellation is immediate (no confirmation delay)
   - Cancelled slots become available to others instantly

6. **Session Definitions**
   - Morning: 7:00 AM - 11:00 AM
   - Afternoon: 1:00 PM - 5:00 PM
   - Evening: 6:00 PM - 10:00 PM
   - No sessions outside these times

### What This Page Doesn't Do

- **Doesn't show trainer photos** (uses default avatars unless uploaded)
- **Doesn't allow booking multiple sessions at once** (one at a time only)
- **Doesn't send email confirmations** (relies on in-app notifications)
- **Doesn't allow editing bookings** (must cancel and rebook)
- **Doesn't show trainer ratings/reviews** (coming in future version)
- **Doesn't handle walk-in appointments** (online bookings only)
- **Doesn't reschedule automatically** (member must manually rebook)

---

## Navigation Flow

### How Users Arrive Here
- Click "Reservations" or "Book Session" in main navigation
- From dashboard "Book a Session" button
- From membership page after purchase
- Direct URL: `fitxbrawl.com/public/php/reservations.php`
- From email reminder links

### Where Users Go Next
From this page, users typically:
- **Stay on page** - Complete booking, view bookings
- **Dashboard** - After successful booking, return to dashboard
- **Membership page** - If in grace period, click "Renew Membership"
- **User profile** - Check membership details
- **Logout** - After booking sessions

---

## Final Thoughts

The reservations page is FitXBrawl's scheduling engine—connecting members with trainers in an organized, fair, and user-friendly way. Every detail, from the 5-step wizard to the weekly limit system to the grace period handling, is designed to provide structure while maintaining flexibility.

Whether you're booking your first session or your hundredth, the page adapts to your situation, guides you through the process, and ensures you get the training you need with the right trainer at the right time. It's a perfect example of how thoughtful system design creates a seamless user experience.