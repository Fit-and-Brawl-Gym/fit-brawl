# Contact Page Documentation
**File:** `public/php/contact.php`  
**Purpose:** Send messages and inquiries to gym management  
**User Access:** Public (available to logged-in and non-logged-in users)

---

## What This Page Does

The contact page is FitXBrawl's communication hub where anyone can send messages, questions, or feedback directly to gym management. Think of it as a digital suggestion box or customer service desk—whether you're a member with a question, a visitor inquiring about memberships, or someone with feedback, this is your direct line to the gym.

### Who Can Use This Page
- **Everyone:** Both logged-in members and non-logged-in visitors
- **No restrictions:** One of the few pages that doesn't require login
- **Universal access:** Allows potential members to ask questions before signing up

---

## The Page Experience

### **1. Page Header Section**

The page opens with a welcoming message:

**Headline:**
- "Get In Touch" (with "Touch" highlighted in gold)

**Subtitle:**
- "Have questions or feedback? We'd love to hear from you."
- "Send us a message and we'll respond as soon as possible."

This friendly tone invites communication and sets expectations (responses aren't instant but will come soon).

---

### **2. Contact Information Cards**

Before the form, three informational cards display gym contact details:

#### **Visit Us Card**

**What It Shows:**
- 📍 Location icon (map marker)
- Title: "Visit Us"
- Full address:
  - 1832 Oroquieta Rd, Santa Cruz, Manila
  - 1008 Metro Manila
- Operating hours: **Mon-Sun: 7AM - 12NN**

**Purpose:**
Know where the gym is located and when you can visit in person.

#### **Call Us Card**

**What It Shows:**
- 📞 Phone icon
- Title: "Call Us"
- Phone number: +63 912 345 6789

**Purpose:**
Direct phone contact for immediate questions or emergencies.

#### **Email Us Card**

**What It Shows:**
- ✉️ Envelope icon
- Title: "Email Us"
- Email address: fitxbrawl.gym@gmail.com

**Purpose:**
Alternative contact method for those who prefer email over the web form.

**Design:**
All three cards have:
- Icon at the left
- Information on the right
- Clean, card-based layout
- Easy to scan at a glance

---

### **3. Contact Form Section**

The main feature of the page—a form for sending messages:

#### **Form Header**

**Title:**
- "Send Us a Message"

**Personalization:**
- **For logged-in users:** "Welcome back, **[Your Name]**!"
- **For non-logged-in users:** "Fill out the form below and we'll get back to you shortly."

This personalization acknowledges returning members while remaining welcoming to new visitors.

---

### **4. Form Layout (Changes Based on Login Status)**

The form adapts based on whether you're logged in:

#### **For Non-Logged-In Users (4 Required Fields)**

**First Name Field:**
- 👤 User icon
- Label: "First Name *"
- Placeholder: "Enter your first name"
- Required field (asterisk indicates required)
- Validation: Letters and spaces only

**Last Name Field:**
- 👤 User icon
- Label: "Last Name *"
- Placeholder: "Enter your last name"
- Required field
- Validation: Letters and spaces only

**Email Address Field:**
- ✉️ Envelope icon
- Label: "Email Address *"
- Placeholder: "your.email@example.com"
- Required field
- Validation: Valid email format

**Phone Number Field:**
- 📞 Phone icon
- Label: "Phone Number *"
- Placeholder: "09123456789"
- Required field
- Validation: 10-15 digits only

**Your Message Field:**
- 💬 Comment icon
- Label: "Your Message *"
- Large text area (6 rows)
- Placeholder: "Tell us what's on your mind..."
- Required field
- No character limit

**Layout:**
- First Name and Last Name side-by-side (desktop)
- Email and Phone Number side-by-side (desktop)
- Message full-width
- Stack vertically on mobile

---

#### **For Logged-In Users (2 Required Fields)**

**Smart Pre-Population:**
- **Name:** Automatically filled from your account (no field shown)
- **Email:** Automatically filled from your account (no field shown)
- Form only shows fields you need to fill

**Phone Number Field:**
- 📞 Phone icon
- Label: "Phone Number *"
- Placeholder: "09123456789"
- Required field
- Validation: 10-15 digits only
- Full-width layout

**Your Message Field:**
- 💬 Comment icon
- Label: "Your Message *"
- Large text area (6 rows)
- Placeholder: "Tell us what's on your mind..."
- Required field
- Full-width layout

**Why This Matters:**
Logged-in users save time—the system already knows your name and email, so you only fill out what's necessary (phone and message). Faster, easier, more convenient.

---

### **5. Form Submission Button**

**Submit Button:**
- ✈️ Paper plane icon (suggests "sending")
- Text: "Send Message"
- Large, prominent green button
- Located at bottom of form

**Visual State:**
- Normal: Green background, white text
- Hover: Slightly darker green (feedback)
- Click: Submits form immediately

---

### **6. Success/Error Messages**

After submitting the form, an alert banner appears:

#### **Success Message**

**What It Shows:**
- ✅ Green checkmark icon
- Green background banner
- Text: "Your message has been sent successfully! We'll get back to you soon."

**What Happens:**
- Form fields clear (except name/email for logged-in users)
- Message confirms successful submission
- Sets expectation for response time
- You can send another message if needed

#### **Error Messages**

**What It Shows:**
- ❌ Exclamation icon
- Red background banner
- Text varies based on error:
  - "Something went wrong. Please try again later." (general error)
  - Individual field errors shown below each field

**Field-Specific Errors:**
- **First Name:** "First name is required" or "Only letters and white space allowed"
- **Last Name:** "Last name is required" or "Only letters and white space allowed"
- **Email:** "Email is required" or "Invalid email format"
- **Phone:** "Phone number is required" or "Invalid phone number format"
- **Message:** "Message is required"

**Visual Indicators:**
- Error messages in red text below field
- Field borders turn red
- Icon changes to error state

---

## How the Form Works

### For Non-Logged-In Users

**The Process:**
1. Visitor arrives at contact page
2. Sees full form (4 fields + message)
3. Fills in first name, last name, email, phone, and message
4. Clicks "Send Message"
5. Form validates all fields
6. If valid: Submits to database
7. Success message appears
8. Form clears, ready for another submission

**Validation Steps:**
1. **Client-Side (Browser):**
   - HTML5 validation (required fields, email format)
   - Immediate feedback on empty required fields

2. **Server-Side (After Submission):**
   - First/Last name: Not empty, letters/spaces only
   - Email: Not empty, valid email format
   - Phone: Not empty, 10-15 digits
   - Message: Not empty
   - All validation redone on server for security

---

### For Logged-In Users

**The Process:**
1. Member arrives at contact page
2. Sees personalized welcome: "Welcome back, John!"
3. Sees simplified form (only phone + message)
4. Name and email auto-filled from session
5. Fills in phone number and message
6. Clicks "Send Message"
7. Form validates phone and message
8. If valid: Submits to database with session name/email
9. Success message appears
10. Phone and message clear, ready for another submission

**Smart Data Usage:**
- First name extracted from session username (split at first space)
- Last name extracted from remaining username
- Email pulled from session email
- No need to ask for information already known

---

## Form Validation System

### Client-Side Validation (Instant Feedback)

**HTML5 Built-In:**
- Required attribute on all fields (asterisk indicates required)
- Email type on email field (validates format)
- Tel type on phone field (numeric keyboard on mobile)

**User Experience:**
- Browser prevents submission if required fields empty
- Email field shows error if format invalid
- Immediate feedback before server interaction

---

### Server-Side Validation (Security Layer)

**Name Validation:**
- Checks: Not empty, letters and spaces only
- Pattern: `/^[a-zA-Z-' ]*$/`
- Allows: Letters, spaces, hyphens, apostrophes
- Blocks: Numbers, special characters (except - and ')
- Error: "Only letters and white space allowed"

**Email Validation:**
- Checks: Not empty, valid email format
- Uses PHP `filter_var()` with `FILTER_VALIDATE_EMAIL`
- Example valid: user@example.com
- Example invalid: userexample.com, @example.com
- Error: "Invalid email format"

**Phone Validation:**
- Checks: Not empty, 10-15 digits
- Pattern: `/^[0-9]{10,15}$/`
- Allows: Only numbers, 10-15 characters
- Blocks: Letters, spaces, dashes, parentheses
- Example valid: 09123456789, 639123456789
- Example invalid: 0912-345-6789, +63 912 345 6789
- Error: "Invalid phone number format"

**Message Validation:**
- Checks: Not empty
- No length limit
- No pattern restriction
- Allows: Any text
- Error: "Message is required"

**Why Double Validation?**
1. **Client-side:** Fast feedback, better UX
2. **Server-side:** Security (can't bypass client validation)
3. **Both together:** Best of both worlds

---

## Data Flow

### What Happens When You Submit

```
1. USER FILLS FORM
   ↓
   - Enters required information
   - Clicks "Send Message"
   ↓
2. CLIENT-SIDE CHECK
   ↓
   - Browser validates required fields
   - Checks email format
   - If invalid: Shows browser error, stops submission
   ↓
3. SERVER RECEIVES DATA
   ↓
   - PHP receives POST request
   - Retrieves form data
   - If logged in: Uses session name/email
   - If not logged in: Uses form name/email
   ↓
4. SERVER-SIDE VALIDATION
   ↓
   - Sanitizes all inputs (removes dangerous characters)
   - Validates each field (name, email, phone, message)
   - If any error: Reloads form with error messages
   ↓
5. DATABASE INSERTION
   ↓
   - Prepares SQL query with placeholders
   - Binds parameters (prevents SQL injection)
   - Inserts: first_name, last_name, email, phone, message, timestamp
   - Executes query
   ↓
6. RESPONSE TO USER
   ↓
   - If success: Green success message, clears form
   - If failure: Red error message, preserves form data
   ↓
7. DATABASE RECORD CREATED
   ↓
   - Stored in 'contact' table
   - Includes date_submitted (timestamp)
   - Ready for admin to view and respond
```

---

## Smart Features

### Session-Based Pre-Population

**For Logged-In Users:**
- System reads `$_SESSION['name']` for username
- Splits username into first and last name
  - "John Doe" → first: "John", last: "Doe"
  - "SingleName" → first: "SingleName", last: ""
- Reads `$_SESSION['email']` for email
- Automatically fills these in database submission
- User sees simplified form (less typing, faster submission)

### Input Sanitization

**Every Input Cleaned:**
- `trim()`: Removes leading/trailing whitespace
- `stripslashes()`: Removes backslashes
- `htmlspecialchars()`: Converts special characters to HTML entities

**Why This Matters:**
- Prevents XSS (cross-site scripting) attacks
- Prevents code injection
- Protects database integrity
- Security best practice

### Form State Preservation

**On Validation Error:**
- Form remembers what you typed
- Only shows error fields highlighted
- No need to re-enter valid data
- Better user experience

**On Success:**
- Form clears to allow new submission
- Success message confirms completion
- Ready for another message if needed

---

## Common User Scenarios

### Scenario 1: Non-Member Inquiring About Membership

**What Happens:**
1. Visitor arrives at contact page
2. Not logged in, sees full form
3. Fills in:
   - First Name: "Jane"
   - Last Name: "Smith"
   - Email: "jane.smith@email.com"
   - Phone: "09123456789"
   - Message: "I'm interested in the Gladiator membership plan. Do you offer trial sessions?"
4. Clicks "Send Message"
5. Form validates (all fields correct)
6. Submits to database
7. Success message: "Your message has been sent successfully!"
8. Form clears
9. Gym admin sees inquiry and responds via email

### Scenario 2: Member With Feedback

**What Happens:**
1. Member logged in as "John Doe"
2. Arrives at contact page
3. Sees: "Welcome back, John Doe!"
4. Sees simplified form (only phone + message)
5. Fills in:
   - Phone: "09187654321"
   - Message: "The new boxing equipment is fantastic! Thank you for the upgrade."
6. Clicks "Send Message"
7. System automatically uses:
   - First Name: "John" (from session)
   - Last Name: "Doe" (from session)
   - Email: "john.doe@email.com" (from session)
8. Form validates (phone and message correct)
9. Submits to database
10. Success message appears
11. Phone and message clear, ready for another submission

### Scenario 3: Validation Error - Invalid Email

**What Happens:**
1. Visitor fills form:
   - First Name: "Mike"
   - Last Name: "Johnson"
   - Email: "mikejohnson" (missing @ and domain)
   - Phone: "09123456789"
   - Message: "When do you close on weekends?"
2. Clicks "Send Message"
3. Server validates email
4. Detects invalid format
5. Form reloads with error
6. Email field shows red border
7. Error message below: "Invalid email format"
8. Other fields preserve data (name: Mike Johnson, phone, message still filled)
9. Visitor corrects email to "mike.johnson@email.com"
10. Submits again, now successful

### Scenario 4: Validation Error - Invalid Phone

**What Happens:**
1. Visitor fills form correctly except:
   - Phone: "0912-345-6789" (contains dashes)
2. Clicks "Send Message"
3. Server validates phone
4. Detects invalid format (should be digits only)
5. Form reloads with error
6. Phone field shows red border
7. Error message: "Invalid phone number format"
8. Other data preserved
9. Visitor removes dashes: "09123456789"
10. Submits again, now successful

### Scenario 5: Empty Required Fields

**What Happens:**
1. Visitor enters only message, leaves name/email/phone empty
2. Clicks "Send Message"
3. Browser validation catches empty required fields
4. Browser shows tooltip: "Please fill out this field"
5. Form doesn't submit to server
6. Visitor fills missing fields
7. Submits again, now goes through

### Scenario 6: Equipment Inquiry (Coming from Products Page)

**What Happens:**
1. Member browsing products page
2. Wants to ask about specific product
3. Clicks "Inquire" button (redirects to contact with service pre-filled)
4. Contact form opens with message field potentially pre-populated
5. Member adds phone number
6. Submits inquiry
7. Gym receives product-specific question

---

## Key Features Summary

| Feature | Description | Benefit |
|---------|-------------|---------|
| **Public Access** | No login required | Anyone can contact gym |
| **Smart Pre-Population** | Logged-in users skip name/email | Faster for members |
| **Contact Info Cards** | Phone, email, address displayed | Multiple contact methods |
| **Double Validation** | Client-side + server-side | Security + UX |
| **Form State Preservation** | Remembers data on errors | No re-typing needed |
| **Success/Error Feedback** | Clear messages after submission | Know status immediately |
| **Input Sanitization** | Cleans all user input | Prevents security issues |
| **Responsive Layout** | Adapts to screen size | Works on all devices |
| **Database Storage** | Messages saved for admin review | No lost inquiries |

---

## What Makes This Page Special

### 1. **Universal Accessibility**
Unlike most pages that require login, the contact page is open to everyone. This allows potential members to ask questions before signing up, and allows visitors to inquire without commitment.

### 2. **Smart Form Adaptation**
The form literally changes based on who's using it:
- Non-logged-in users: Full form (4 fields)
- Logged-in users: Simplified form (2 fields)

This isn't just hiding fields—it's intelligently using available data to reduce friction.

### 3. **Multiple Contact Channels**
The page doesn't force you to use the web form. It prominently displays:
- Physical address (visit in person)
- Phone number (call directly)
- Email address (send traditional email)
- Web form (submit through site)

Choose whatever method suits your preference.

### 4. **Graceful Error Handling**
When validation fails, the page doesn't just say "error"—it:
- Shows exactly which field has the problem
- Explains what's wrong ("Invalid email format")
- Preserves all your correct data
- Highlights only problem fields in red
- Lets you fix and resubmit easily

### 5. **Security Without Friction**
The page is secure (double validation, input sanitization, SQL injection prevention) but users barely notice. Security happens invisibly in the background while the form remains easy to use.

---

## Important Notes and Limitations

### Things to Know

1. **No Real-Time Chat**
   - This is an asynchronous contact form
   - Messages go to database, not instant chat
   - Response time: "as soon as possible" (not immediate)
   - For urgent matters, call phone number

2. **Phone Number Format**
   - Must be 10-15 digits only
   - No dashes, spaces, parentheses, or + symbol
   - Examples: 09123456789, 639123456789
   - Invalid: 0912-345-6789, +63 912 345 6789

3. **Name Splitting for Logged-In Users**
   - System splits username at first space
   - "John Doe Smith" → First: "John", Last: "Doe Smith"
   - Single names work but have empty last name
   - May not match legal name perfectly

4. **No Attachments**
   - Cannot upload files or images
   - Text messages only
   - For files, email directly to gym address

5. **No Message Tracking**
   - No ticket number or tracking system
   - Cannot check status of your message
   - No notification when admin responds
   - Response via email or phone callback

6. **Operating Hours**
   - Displayed as Mon-Sun: 7AM - 12NN
   - This is visit hours, not response hours
   - Messages can be sent 24/7
   - Responses during business hours only

### What This Page Doesn't Do

- **Doesn't provide instant responses** (not live chat)
- **Doesn't track message status** (no ticket system)
- **Doesn't send confirmation emails** (only success message on page)
- **Doesn't allow file uploads** (text only)
- **Doesn't show message history** (no archive of sent messages)
- **Doesn't guarantee response time** (just "as soon as possible")
- **Doesn't route to specific departments** (all messages go to general inbox)
- **Doesn't auto-respond** (no automated acknowledgment emails)

---

## Navigation Flow

### How Users Arrive Here
- Click "Contact" in main navigation menu
- From membership page "Contact Us" button
- From products/equipment page "Inquire" buttons
- From homepage "Get In Touch" link
- Direct URL: `fitxbrawl.com/public/php/contact.php`
- From any page footer contact link

### Where Users Go Next
From this page, users typically:
- **Stay on page** - After successful submission (to send another message)
- **Homepage** - After getting contact info
- **Membership page** - After inquiring about plans
- **Login page** - If non-member wants to sign up
- **Close browser** - After sending message

---

## Mobile Experience

### Responsive Behavior

**Small Screens (Phones):**
- Contact info cards stack vertically
- Form fields full-width (no side-by-side)
- Larger text for readability
- Touch-friendly input fields
- Phone keyboard appears for phone field
- Email keyboard appears for email field

**Medium Screens (Tablets):**
- Contact info cards may stack or show 2 per row
- Form fields may show side-by-side
- Comfortable spacing

**Large Screens (Desktop):**
- Contact info cards in single row (3 across)
- Form fields side-by-side (First/Last, Email/Phone)
- Optimal reading width
- Hover effects on submit button

### Touch Optimization

- Large input fields (easy to tap)
- Comfortable spacing between fields
- Large submit button (easy to tap)
- Appropriate keyboard types:
  - Email field → Email keyboard
  - Phone field → Numeric keyboard
  - Message field → Standard keyboard

---

## Final Thoughts

The contact page is FitXBrawl's open door to communication. Whether you're a potential member with questions, a current member with feedback, or anyone with an inquiry, this page makes it easy to reach out. The smart design—adapting to logged-in vs. non-logged-in users—shows thoughtful attention to user experience, while the multiple contact methods (form, phone, email, address) respect different communication preferences.

It's a perfect example of form design done right: secure but not cumbersome, validated but forgiving of mistakes, simple but not simplistic. The page doesn't try to replace human interaction (no chatbot, no automated responses)—it facilitates it by getting your message into the right hands as efficiently as possible.