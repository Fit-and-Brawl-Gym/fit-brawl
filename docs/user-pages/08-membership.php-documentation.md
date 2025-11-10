# Membership Plans Page Documentation
**File:** `public/php/membership.php`  
**Purpose:** Browse and select gym membership plans and single-day training passes  
**User Access:** Members and non-members (content changes based on membership status)

---

## What This Page Does

The membership page is FitXBrawl's digital storefront for all membership plans and training services. Think of it as a catalog where users can explore different membership tiers, compare features side-by-side, and purchase single-day training sessions. The page dynamically adjusts what it shows based on whether you already have an active membership.

### Who Can Use This Page
- **Non-members:** See all membership plans and can purchase memberships or single-day passes
- **Active members:** Shown their current membership status and can only purchase single-day training passes
- **Grace period members:** Members whose membership expired within the last 3 days can see all plans again

---

## The Page Experience

### For Non-Members or Grace Period Members

When you don't have an active membership, the page opens up all its options to you. Here's what you'll see:

#### 1. **Hero Section**
The page greets you with a bold headline: "CHOOSE YOUR JOURNEY!" This immediately sets the tone and lets you know you're about to pick a fitness path. A subtitle explains that becoming a member unlocks your full potential.

#### 2. **Membership Plans Carousel**
The centerpiece of the page is an interactive carousel showcasing five different membership plans. The carousel is designed to display three cards at once, with the middle card (Gladiator plan) taking center stage. Here are your options:

**RESOLUTION Plan** - 2,200 PHP/month
- Focus: Gym access only
- What you get:
  - Full access to gym equipment
  - Face recognition entry system
  - Shower facilities
  - Locker access
- Savings: 1,400 PHP compared to daily rates
- Best for: People who want traditional gym workouts without combat training

**BRAWLER Plan** - 11,500 PHP/month
- Focus: Muay Thai training
- What you get:
  - Professional Muay Thai coaching
  - MMA area access
  - Free orientation and fitness assessment
  - Shower and locker facilities
- Savings: 3,500 PHP
- Best for: Traditional Muay Thai enthusiasts

**GLADIATOR Plan** - 14,500 PHP/month (POPULAR CHOICE!)
- Focus: Complete combat training package
- What you get:
  - Boxing training with pro coaches
  - MMA training (includes Brazilian Jiu-Jitsu and Wrestling)
  - Full boxing and MMA area access
  - Gym equipment access
  - Jakuzzi access (exclusive to this plan!)
  - Nutrition consultation
  - Shower and locker facilities
- Best for: Serious fighters or those wanting the complete experience
- Why it's popular: Most comprehensive package with unique amenities like jakuzzi and nutrition guidance

**CHAMPION Plan** - 7,000 PHP/month
- Focus: Boxing specialization
- What you get:
  - Professional boxing coaches
  - MMA area access
  - Free orientation and fitness assessment
  - Shower and locker facilities
- Savings: 3,500 PHP
- Best for: Boxing purists who want focused training

**CLASH Plan** - 13,500 PHP/month
- Focus: MMA training
- What you get:
  - Professional MMA coaching
  - MMA area access
  - Free orientation and fitness assessment
  - Shower and locker facilities
- Savings: 4,500 PHP (highest savings!)
- Best for: MMA enthusiasts and mixed martial artists

**How the Carousel Works:**
- The Gladiator plan starts in the center (it's the most popular)
- Click the left/right arrow buttons to browse other plans
- On mobile devices, the carousel switches to a vertical stack for easier scrolling
- Each card has a "SELECT PLAN" button that takes you directly to the purchase page

#### 3. **Plan Comparison Table**
Below the carousel is a detailed comparison table that shows all five plans side-by-side. This helps you see exactly what's included in each plan:

**Features Compared:**
- ✅ Gym Equipment Access (Resolution and Gladiator only)
- ✅ Face Recognition Access (Resolution and Gladiator only)
- ✅ Shower Access (all plans)
- ✅ Locker Access (all plans)
- ✅ MMA Area Access (all except Resolution)
- ✅ Boxing Training (Champion only)
- ✅ Muay Thai Training (Brawler only)
- ✅ MMA Training (Gladiator and Clash)
- ✅ Brazilian Jiu-Jitsu (Gladiator only)
- ✅ Wrestling Training (Gladiator only)
- ✅ Professional Coaches (all combat training plans)
- ✅ Orientation & Assessment (all combat training plans)
- ✅ Nutrition Consultation (Gladiator only)

Each feature row clearly shows checkmarks (✅) or X marks (❌) so you can quickly compare what's included. At the bottom of each column is another "SELECT PLAN" button for quick purchasing.

#### 4. **Single-Day Training Passes** ("Train for a Day")
If you're not ready to commit to a monthly membership, you can purchase single-day training sessions:

**Training: Boxing** - 350 PHP
- Full-day access to the boxing area
- Focus on footwork, defense, and power punching
- Perfect for skill improvement and pad work
- Includes personalized fight strategies

**Training: Muay Thai** - 400 PHP
- Full-day access to MMA area
- Deep dive into clinch work, teeps, and low kicks
- Master traditional Muay Thai techniques
- Intense conditioning workout

**Training: MMA** - 500 PHP
- 75-minute comprehensive session
- Combines striking (boxing and Muay Thai)
- Includes wrestling and Brazilian Jiu-Jitsu
- Perfect for competitive fighters
- Most intense and varied workout

Each training pass is displayed as a card showing the price, service name, and detailed benefits. Click on any card or its "SELECT SERVICE" button to open a modal with more information.

#### 5. **Service Selection Modal**
When you click on a training pass, a popup window appears with three options:
- **Proceed to Transaction:** Takes you to the payment page
- **Inquire:** Redirects to the contact page with the service pre-filled
- **Cancel:** Closes the modal and returns you to the page

This gives you flexibility—you can purchase immediately or ask questions first.

#### 6. **Contact Call-to-Action**
At the bottom of the page is a simple question: "Still unsure?" with a bright button that says "CONTACT US NOW!" This provides an easy way to reach out if you have questions or need guidance in choosing a plan.

---

### For Active Members

If you already have an active membership, the page looks completely different. Here's what changes:

#### **Active Membership Notice**
Instead of seeing all the membership plans, you'll see a large, gold-bordered notification box at the top displaying:
- A weightlifting emoji (🏋️)
- "Active Membership" headline in gold
- Your current plan name (e.g., "GLADIATOR")
- Expiration date (e.g., "Valid until: December 15, 2024")

#### **Important Information Box**
Below your membership details is a helpful notice explaining that you can't change plans online. It says:

> **Want to Change or Upgrade Your Plan?**
> Please visit our gym in person to change or upgrade your membership plan. Our staff will be happy to assist you!

This prevents confusion and directs you to the proper channel for membership changes.

#### **Quick Action Buttons**
Two prominent buttons help you navigate:
- **Go to Dashboard:** Returns you to your member homepage
- **Book a Session:** Takes you to the reservations page to schedule training

#### **Single-Day Passes Section**
You'll still see the "Train for a Day" section, but with an important difference:

**Disabled Training Cards**
All three training pass cards appear grayed out with a notice explaining:

> You Already Have an Active Membership
> 
> Single day passes are only available for non-members. As an active member, you already have access to the facilities included in your membership plan.

The "SELECT SERVICE" buttons change to "DISABLED" and won't respond to clicks. This prevents members from accidentally purchasing services they already have access to.

---

## How the System Determines What to Show

The page uses smart logic to decide what content to display:

### Membership Status Check
When the page loads, it checks three things:
1. **Are you logged in?** (Checks your session)
2. **Do you have a membership?** (Queries the `user_memberships` database table)
3. **Is it currently active?** (Checks if today's date is before the expiration date plus 3 days)

### The Grace Period Feature
FitXBrawl gives members a 3-day grace period after their membership expires. During this time:
- You're treated as a non-member for the membership page (can see all plans)
- You can still access most member features
- You can purchase a new membership plan

This grace period is a thoughtful touch that prevents immediate loss of access if your payment is slightly delayed.

### Active vs. Non-Active Display Logic
```
IF user is logged in AND has active membership (not in grace period):
    → Show active membership notice
    → Hide membership plan carousel
    → Hide comparison table
    → Disable single-day training passes
ELSE:
    → Show full membership carousel
    → Show comparison table
    → Enable single-day training passes
```

---

## Interactive Features

### Carousel Navigation
The membership plans carousel has several interactive elements:

**Desktop View:**
- Shows 3 cards at once (overlapping design)
- Left/Right arrow buttons to slide through plans
- Center card is always larger and more prominent
- Arrows disable when you reach the first or last plan

**Mobile View (767px or smaller):**
- Switches to vertical stack layout
- All cards displayed at once (no carousel)
- Arrow buttons hidden
- Easier scrolling on smaller screens

**Keyboard Support:**
- Press left arrow key to go to previous plan
- Press right arrow key to go to next plan
- Press Escape to close any open modals

### Plan Selection Process
When you click "SELECT PLAN" on any membership:

1. The page collects information about the plan you selected
2. It determines the plan type (e.g., "gladiator", "champion")
3. It identifies the category ("member" or "non-member")
4. It redirects you to `transaction.php` with these details in the URL
5. The transaction page then handles payment and registration

Example redirect:
```
transaction.php?plan=gladiator&category=member&billing=monthly
```

### Service Modal Interaction
For single-day training passes:

1. **Opening the modal:** Click anywhere on a service card or its "SELECT SERVICE" button
2. **Modal displays:** Price, service name, and detailed benefits
3. **Three action buttons appear:**
   - Purchase button (green) → Goes to payment page
   - Inquire button (blue) → Goes to contact form
   - Cancel button (red) → Closes modal
4. **Multiple ways to close:** Click Cancel, click X button, click outside the modal, or press Escape key

---

## Behind the Scenes: Technical Details

### Database Queries
The page checks your membership status by querying the `user_memberships` table:
- Looks for your user ID
- Checks if `request_status` is 'approved'
- Checks if `membership_status` is 'active'
- Verifies the end date is within the grace period (today or up to 3 days ago)

### Session Management
The page relies heavily on session data:
- `$_SESSION['user_id']` - Your unique identifier
- `$_SESSION['email']` - Confirms you're logged in
- `$_SESSION['avatar']` - Displays your profile picture in the header
- `$_SESSION['plan_error']` - Shows error messages (then clears them)

### Security Measures
- All user input is sanitized with `htmlspecialchars()` to prevent code injection
- Sessions are managed through a dedicated SessionManager class
- Non-logged-in users are redirected to the login page
- Database queries use prepared statements to prevent SQL injection

### Responsive Design
The page adapts to different screen sizes:
- **Desktop (> 767px):** Overlapping carousel with 3 visible cards
- **Tablet (768px - 1024px):** Adjusted card sizing
- **Mobile (≤ 767px):** Vertical stack, no carousel
- **Comparison table:** Scrollable horizontally on smaller screens

---

## Common User Scenarios

### Scenario 1: New User Browsing Plans
**What happens:**
1. User visits membership.php
2. System detects no active membership
3. Full carousel displays with Gladiator in center
4. User browses plans using arrow buttons
5. User compares features in the table below
6. User clicks "SELECT PLAN" on Champion
7. Redirected to transaction page to complete purchase

### Scenario 2: Active Member Trying to Change Plan
**What happens:**
1. Member visits membership.php
2. System detects active Gladiator membership
3. Large notification appears showing current plan
4. All membership plans are hidden
5. Notice explains to visit gym in person for changes
6. Member clicks "Go to Dashboard" to return home

### Scenario 3: Non-Member Purchasing Single-Day Pass
**What happens:**
1. User scrolls to "Train for a Day" section
2. User clicks on "Training: MMA" card
3. Modal pops up showing 500 PHP price and benefits
4. User clicks "Proceed to Transaction"
5. Redirected to service transaction page
6. Payment processed for single day access

### Scenario 4: Grace Period Member
**What happens:**
1. Member's plan expired 2 days ago
2. Visits membership.php
3. System calculates: expired date + 3 days = still within grace
4. Full membership carousel appears (can purchase new plan)
5. Training passes are also enabled
6. Member can renew or select different plan

### Scenario 5: Mobile User Shopping Plans
**What happens:**
1. User opens page on phone (width < 767px)
2. Carousel automatically switches to vertical stack
3. All 5 plans visible at once
4. User scrolls down to see each plan
5. Comparison table scrolls horizontally
6. User taps "SELECT PLAN" on desired option
7. Seamless transition to transaction page

---

## Key Features Summary

| Feature | Description | Benefit |
|---------|-------------|---------|
| **Dynamic Content** | Page changes based on membership status | Personalized experience for each user |
| **Interactive Carousel** | Browse plans with smooth animations | Engaging way to explore options |
| **Comparison Table** | Side-by-side feature comparison | Make informed decisions quickly |
| **Grace Period** | 3-day buffer after expiration | Prevents sudden loss of access |
| **Single-Day Passes** | No commitment required | Try before you buy |
| **Mobile Responsive** | Adapts to all screen sizes | Works perfectly on any device |
| **Modal Dialogs** | Clean popup for service selection | Focused decision-making |
| **Direct Navigation** | Quick buttons to dashboard/reservations | Efficient user flow |

---

## What Makes This Page Special

### 1. **Smart Status Detection**
Unlike static pages, this one knows who you are and what you need. It doesn't waste your time showing plans you can't purchase or services you already have.

### 2. **Visual Hierarchy**
The Gladiator plan is centered and marked as "POPULAR CHOICE" because data shows it's the most purchased. This guides new users toward a proven option while still allowing full exploration.

### 3. **Multiple Purchase Paths**
You can select a plan from:
- The carousel cards
- The comparison table
- Even the pricing section
All roads lead to the same transaction page, but with flexibility in how you get there.

### 4. **Prevention Over Correction**
Active members physically can't purchase single-day passes (buttons are disabled). This prevents accidental purchases and support tickets rather than dealing with refunds later.

### 5. **Graceful Degradation**
On older browsers or slower connections, the page still works—it just displays plans vertically instead of in a fancy carousel. Function over flash.

---

## Important Notes and Limitations

### Things to Know

1. **In-Person Plan Changes**
   - Current members cannot upgrade, downgrade, or change plans online
   - Must visit the gym physically for any membership modifications
   - This is intentional to allow staff to discuss options and handle prorating

2. **Grace Period Restrictions**
   - 3-day grace period only applies to viewing plans and single-day passes
   - Some other features (like reservations) may have different grace period rules
   - After 3 days, membership is considered fully expired

3. **Plan Pricing**
   - All prices shown are monthly rates
   - "Savings" shown compare monthly membership to equivalent daily rates
   - No billing frequency options on this page (monthly is default)

4. **Single-Day Pass Limitations**
   - Only available to non-members
   - Active members see disabled buttons
   - Each pass is for one full day of training
   - 75-minute session for MMA only

5. **Face Recognition Access**
   - Only Resolution and Gladiator plans include face recognition entry
   - Other plans use standard check-in procedures
   - Face recognition setup happens during onboarding

6. **Comparison Table**
   - Some features may appear in comparison table but not in plan cards
   - Table is comprehensive; cards show highlights only
   - Brazilian Jiu-Jitsu and Wrestling are part of MMA training

### What This Page Doesn't Do

- **Doesn't process payments** (redirects to transaction pages)
- **Doesn't show membership history** (use dashboard for that)
- **Doesn't allow plan customization** (packages are fixed)
- **Doesn't display promotional discounts** (standard pricing only)
- **Doesn't handle membership cancellations** (must contact admin)
- **Doesn't show trainer assignments** (happens after purchase)

---

## Navigation Flow

### How Users Arrive Here
- Click "Membership" in main navigation
- From homepage "Get Started" button
- Direct URL: `fitxbrawl.com/public/php/membership.php`
- From dashboard "Manage Membership" link

### Where Users Go Next
From this page, users typically navigate to:
- **Transaction.php** - After selecting a membership plan
- **Transaction_service.php** - After selecting a single-day pass
- **Contact.php** - If they have questions (via "Inquire" or "Contact Us")
- **Loggedin-index.php** - If they're active members going to dashboard
- **Reservations.php** - If they're ready to book a session

---

## Final Thoughts

The membership page is designed to be FitXBrawl's primary conversion tool—turning interested visitors into paying members. Every element, from the centered Gladiator plan to the disabled buttons for active members, serves a purpose in guiding users toward the right decision for their fitness journey.

Whether you're a first-time visitor exploring options or an active member checking your status, the page adapts to provide exactly what you need without overwhelming you with irrelevant information. It's a perfect example of thoughtful user experience design in action.