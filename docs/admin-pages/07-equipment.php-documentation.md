# Equipment Management Page Documentation
**File:** `public/php/admin/equipment.php`  
**Purpose:** Manage gym equipment inventory and status  
**User Access:** Admins only (role-based authentication)

---

## What This Page Does

The equipment management page is your complete gym inventory control center. View all gym equipment (treadmills, weights, mats, etc.), filter by category, track equipment status (Available, Maintenance, Out of Order), add new equipment with images, edit details, and delete items. Think of it as your equipment catalog and maintenance tracker—essential for keeping members informed about available resources and tracking repair needs.

### Who Can Access This Page
- **Admins only:** Must have `role = 'admin'`
- **Login required:** Redirects non-authenticated users
- **Full CRUD access:** Create, Read, Update, Delete equipment

### What It Shows
- **All equipment:** Complete gym inventory
- **Equipment details:** Name, category, status, description, image
- **Status tracking:** Available, Maintenance, Out of Order
- **Category organization:** Cardio, Flexibility, Core, Strength, Functional
- **Filtering options:** By category, search by name
- **Visual cards:** Equipment grid with icons and images

---

## The Page Experience

### **1. Page Header**

**Title:**
- "Equipment Management"
- Large, clear heading

**Subtitle:**
- "Manage gym equipment inventory and status"
- Explains page purpose

---

### **2. Toolbar Section**

#### **Search Box (Left)**

**What It Shows:**
- 🔍 Magnifying glass icon
- Input field: "Search equipment..."
- Full-width search bar

**What It Searches:**
- Equipment name
- Real-time filtering as you type
- Case-insensitive
- **Example:** Type "treadmill" → Shows all treadmills

---

#### **Category Filter Dropdown (Center)**

**Options:**
- All Categories (default)
- Cardio
- Flexibility
- Core
- Strength Training
- Functional Training

**Behavior:**
- Click to select category
- Filters equipment grid instantly
- Works with search (combined filters)
- No page reload

---

#### **Add New Equipment Button (Right)**

**What It Shows:**
- **Icon:** ➕ Plus icon
- **Text:** "Add New Equipment"
- **Color:** Primary (blue/gold)
- **Action:** Opens side panel form

---

### **3. Category Tabs**

**Visual Tab Bar:**

**Six Tabs:**

1. **All** (Default - Active on load)
   - Shows all equipment
   - No category filter
   - Total inventory

2. **🏃 Cardio**
   - Cardio equipment only
   - Examples: Treadmills, bikes, ellipticals
   - Icon: Running person

3. **🧘 Flexibility**
   - Flexibility equipment only
   - Examples: Mats, foam rollers, bands
   - Icon: Person stretching

4. **💪 Core**
   - Core training equipment
   - Examples: Ab benches, medicine balls
   - Icon: Flexed bicep

5. **🏋️ Strength Training**
   - Strength equipment
   - Examples: Barbells, dumbbells, machines
   - Icon: Weight lifter

6. **🦾 Functional Training**
   - Functional training equipment
   - Examples: Kettlebells, battle ropes, TRX
   - Icon: Mechanical arm

**Tab Behavior:**
- Click tab to filter by category
- Active tab highlighted
- Inactive tabs gray
- Updates category dropdown to match
- Instant filtering (no reload)
- Works with search filter

---

### **4. Equipment Grid**

**Grid Layout:**
- Responsive grid (2-4 cards per row depending on screen size)
- Visual card-based layout
- Category icons and status badges

**Empty State:**
- 🔩 Dumbbell icon (large, gray)
- Title: "No Equipment Found"
- Message: "Start by adding your first equipment item"
- **Add Equipment Button:** Shortcut to open side panel

---

### **Equipment Card Structure**

Each equipment item displayed as visual card:

#### **Card Header**

**Equipment Icon (Left):**
- Large emoji icon based on category:
  - Cardio: 🏃
  - Flexibility: 🧘
  - Core: 💪
  - Strength Training: 🏋️
  - Functional Training: 🦾
  - Default: 🔧 (if category not recognized)

**Status Badge (Right):**
- Color-coded pill badge:
  - **Available:** Green badge, "Available"
  - **Maintenance:** Orange badge, "Maintenance"
  - **Out of Order:** Red badge, "Out of Order"
- **Position:** Floating top-right corner

---

#### **Card Body**

**Equipment Name:**
- Large, bold heading
- **Example:** "Treadmill Pro X500"
- Main identifier

**Category Label:**
- Smaller text below name
- **Example:** "Cardio"
- Gray/muted color

**Description** (if exists):
- Brief text snippet
- **Example:** "High-performance treadmill with incline control and heart rate monitor"
- Shows if description provided
- Helps identify specific equipment

**Equipment Image** (if uploaded):
- Visual representation of equipment
- Displayed above or within card body
- Helps with identification
- Falls back to icon if no image

---

#### **Card Footer (Actions)**

**Two Buttons:**

1. **Edit Button** (Primary)
   - **Icon:** ✏️ Pen icon
   - **Text:** "Edit"
   - **Action:** Opens side panel with equipment details pre-filled

2. **Delete Button** (Danger)
   - **Icon:** 🗑️ Trash icon
   - **Text:** "Delete"
   - **Action:** Opens delete confirmation modal

---

### **5. Add/Edit Side Panel**

**When It Appears:**
- Click "Add New Equipment" button (empty form)
- Click "Edit" on equipment card (pre-filled form)
- Slides in from right side of screen
- Overlay darkens main content

**Click Outside to Close:**
- Click darkened overlay
- Closes panel without saving

---

#### **Panel Header**

**Title:**
- **Add Mode:** "Add New Equipment"
- **Edit Mode:** "Edit Equipment"
- Changes based on context

**Close Button:**
- ❌ X icon
- Top-right corner
- Closes panel without saving

---

#### **Panel Body (Form)**

**Hidden Fields:**
- Equipment ID (for edits)
- Existing image path (preserve if not changing)

---

**Form Fields:**

**1. Equipment Image Upload**

**Image Preview Box:**
- Large preview area
- **Default state:**
  - 🖼️ Image icon
  - Text: "Click to upload image"
  - Gray background
- **After upload:**
  - Shows uploaded image preview
  - Background image fills box

**Choose Image Button:**
- **Icon:** ⬆️ Upload icon
- **Text:** "Choose Image"
- **Action:** Opens file picker
- **Accepts:** Image files only (JPG, PNG, etc.)

**How It Works:**
1. Click "Choose Image"
2. File picker opens
3. Select image from computer
4. Image preview appears in box
5. Image uploaded when form saved

---

**2. Equipment Name** (Required)

- **Label:** "Equipment Name *"
- **Type:** Text input
- **Placeholder:** "e.g., Treadmill Pro X500"
- **Required:** Red asterisk (*)
- **Example:** "Rowing Machine Elite"

---

**3. Category** (Required)

- **Label:** "Category *"
- **Type:** Dropdown select
- **Options:**
  - (Select category) - Placeholder
  - Cardio
  - Flexibility
  - Core
  - Strength Training
  - Functional Training
- **Required:** Must select one

---

**4. Status** (Required)

- **Label:** "Status *"
- **Type:** Dropdown select
- **Options:**
  - Available (default)
  - Maintenance
  - Out of Order
- **Required:** Must select one
- **Pre-selected:** "Available" for new equipment

---

**5. Description** (Optional)

- **Label:** "Description"
- **Type:** Textarea (4 rows)
- **Placeholder:** "Optional description..."
- **Purpose:** Additional details about equipment
- **Example:** "Adjustable resistance, LCD display, heart rate monitor included"

---

#### **Panel Footer (Form Actions)**

**Cancel Button** (Gray, left)
- **Text:** "Cancel"
- **Action:** Closes panel without saving
- **Discards changes**

**Save Equipment Button** (Primary, right)
- **Icon:** 💾 Floppy disk icon
- **Text:** "Save Equipment"
- **Action:** Submits form, saves to database
- **Adds new equipment OR updates existing**

---

### **6. Delete Confirmation Modal**

**When It Appears:**
- Click "Delete" button on equipment card
- Overlay darkens background
- Modal appears center-screen

**Modal Content:**

**Title:**
- "Delete Equipment"
- Red/warning color

**Message:**
- "Are you sure you want to delete **[Equipment Name]**?"
- **Example:** "Are you sure you want to delete Treadmill Pro X500? This action cannot be undone."
- Emphasizes permanent deletion

**Buttons:**
- **Cancel** (Gray, left)
  - Closes modal
  - No action taken
  
- **Delete** (Red/Danger, right)
  - Confirms deletion
  - Permanently deletes equipment
  - Closes modal
  - Refreshes page

---

## How Features Work

### **1. Category Filtering**

**Tab Click:**
1. User clicks "🏃 Cardio" tab
2. JavaScript sets category filter to "Cardio"
3. Updates dropdown to "Cardio"
4. Calls `filterEquipment()` function
5. Filters cards: `card.dataset.category === 'Cardio'`
6. Hides non-Cardio cards (`display: none`)
7. Shows only Cardio cards
8. Instant filtering, no reload

**Dropdown Select:**
- Same functionality as tab click
- Alternative interaction method
- Syncs with tabs

---

### **2. Search Functionality**

**Search Input:**
1. User types "treadmill" in search box
2. JavaScript captures input event
3. Calls `filterEquipment()` function
4. Filters cards by equipment name:
   ```javascript
   const name = card.querySelector('.equipment-name').textContent.toLowerCase();
   const matchesSearch = name.includes('treadmill');
   ```
5. Hides non-matching cards
6. Shows only treadmills
7. Real-time filtering as you type

**Combined Search + Category:**
- Search: "pro"
- Category: "Cardio"
- **Result:** Cardio equipment with "pro" in name
- Both filters applied simultaneously

---

### **3. Add New Equipment**

**Add Flow:**
```
1. ADMIN CLICKS "ADD NEW EQUIPMENT"
   ↓
2. SIDE PANEL OPENS
   ↓
   Panel title: "Add New Equipment"
   Form: Empty fields
   Status: Pre-selected "Available"
   ↓
3. ADMIN FILLS FORM
   ↓
   - Clicks "Choose Image", selects treadmill photo
   - Image preview appears
   - Name: "Treadmill Pro X500"
   - Category: "Cardio"
   - Status: "Available"
   - Description: "High-performance treadmill with incline control"
   ↓
4. ADMIN CLICKS "SAVE EQUIPMENT"
   ↓
5. JAVASCRIPT SUBMITS FORM
   ↓
   POST api/admin_equipment_api.php
   Body: FormData (multipart for image upload)
   {
     name: "Treadmill Pro X500",
     category: "Cardio",
     status: "Available",
     description: "High-performance...",
     image: [File object]
   }
   ↓
6. SERVER PROCESSES REQUEST
   ↓
   - Validates inputs (name, category, status required)
   - Uploads image to uploads/equipment/
   - Generates filename: equipment_[timestamp]_[random].jpg
   - Inserts into database:
     INSERT INTO equipment 
     (name, category, status, description, image_path)
     VALUES (...)
   ↓
7. SERVER RETURNS SUCCESS
   ↓
   { "success": true, "message": "Equipment added" }
   ↓
8. JAVASCRIPT CLOSES PANEL
   ↓
9. PAGE REFRESHES
   ↓
   - New equipment card appears in grid
   - Positioned by category
   - Shows uploaded image
   - Status badge "Available" (green)
```

---

### **4. Edit Equipment**

**Edit Flow:**
```
1. ADMIN CLICKS "EDIT" ON EQUIPMENT CARD
   ↓
2. JAVASCRIPT OPENS SIDE PANEL
   ↓
   Panel title: "Edit Equipment"
   Form: Pre-filled with current values
   ↓
3. PANEL SHOWS EXISTING DATA
   ↓
   - Equipment ID: (hidden field, e.g., 42)
   - Image preview: Shows current equipment image
   - Name: "Treadmill Pro X500"
   - Category: "Cardio" (selected)
   - Status: "Available" (selected)
   - Description: "High-performance..."
   ↓
4. ADMIN MODIFIES DATA
   ↓
   Example: Changes status to "Maintenance"
   (Treadmill needs repair)
   ↓
5. ADMIN CLICKS "SAVE EQUIPMENT"
   ↓
6. JAVASCRIPT SUBMITS FORM
   ↓
   POST api/admin_equipment_api.php
   Body: FormData
   {
     id: 42,
     name: "Treadmill Pro X500",
     category: "Cardio",
     status: "Maintenance",  // Changed
     description: "High-performance...",
     existing_image: "equipment_1699999999_abc123.jpg"
     // No new image uploaded, preserves existing
   }
   ↓
7. SERVER UPDATES DATABASE
   ↓
   UPDATE equipment
   SET status = 'Maintenance', ...
   WHERE id = 42
   ↓
8. SERVER RETURNS SUCCESS
   ↓
   { "success": true, "message": "Equipment updated" }
   ↓
9. PAGE REFRESHES
   ↓
   - Equipment card updates
   - Status badge changes: Green → Orange
   - Text: "Available" → "Maintenance"
   - Members see equipment unavailable
```

---

### **5. Delete Equipment**

**Delete Flow:**
```
1. ADMIN CLICKS "DELETE" ON EQUIPMENT CARD
   ↓
2. JAVASCRIPT OPENS MODAL
   ↓
   Title: "Delete Equipment"
   Message: "Delete Treadmill Pro X500? Cannot be undone."
   ↓
3. ADMIN CLICKS "DELETE" (CONFIRM)
   ↓
4. JAVASCRIPT SENDS REQUEST
   ↓
   DELETE api/admin_equipment_api.php?id=42
   ↓
5. SERVER DELETES FROM DATABASE
   ↓
   DELETE FROM equipment WHERE id = 42
   ↓
6. SERVER DELETES IMAGE FILE
   ↓
   unlink(uploads/equipment/equipment_1699999999_abc123.jpg)
   ↓
7. SERVER RETURNS SUCCESS
   ↓
   { "success": true, "message": "Equipment deleted" }
   ↓
8. PAGE REFRESHES
   ↓
   - Equipment card removed from grid
   - No longer visible to admins or members
   - Permanent deletion
```

---

### **6. Image Upload**

**Upload Process:**
1. Click "Choose Image" button
2. File picker opens
3. Select image file (e.g., treadmill.jpg)
4. JavaScript reads file via FileReader
5. Generates base64 data URL
6. Sets as background image of preview box
7. Preview shows selected image
8. On form submit:
   - Image file included in FormData
   - Uploaded to server
   - Saved to uploads/equipment/ directory
   - Database stores image filename
   - Card displays image

**Image Display:**
- Equipment cards show uploaded image
- Falls back to category icon if no image
- Image helps members identify equipment

---

## Data Flow

### Page Load Process

```
1. ADMIN ACCESSES PAGE
   ↓
   Role check: Is admin?
   ↓
2. FETCH EQUIPMENT FROM DATABASE
   ↓
   SELECT * FROM equipment
   ORDER BY category, name
   ↓
3. NORMALIZE DATA
   ↓
   Ensure all expected keys exist (id, name, category, status, description)
   Set defaults if missing
   ↓
4. RENDER PAGE STRUCTURE
   ↓
   - Display header
   - Show toolbar (search, filter, add button)
   - Show category tabs
   - Display equipment grid
   ↓
5. RENDER EQUIPMENT CARDS
   ↓
   Loop through equipment:
     - Create card HTML
     - Add category icon
     - Add status badge
     - Add image (if exists)
     - Add name, category, description
     - Add action buttons
   ↓
6. JAVASCRIPT ENHANCES
   ↓
   - Attach event listeners to tabs
   - Attach search input listener
   - Attach category filter listener
   - Prepare side panel
   ↓
7. READY FOR ADMIN INTERACTION
```

---

## Common Admin Scenarios

### Scenario 1: Adding New Treadmill

**What Happens:**
1. Gym receives new treadmill delivery
2. Admin opens Equipment Management
3. Clicks "Add New Equipment"
4. Side panel opens
5. Fills form:
   - Uploads treadmill photo
   - Name: "Treadmill Pro X500"
   - Category: "Cardio"
   - Status: "Available"
   - Description: "Max speed 12 mph, incline up to 15%, heart rate monitor"
6. Clicks "Save Equipment"
7. Panel closes
8. New treadmill card appears in grid
9. Members can now see equipment in public equipment page
10. Inventory updated

---

### Scenario 2: Marking Equipment for Maintenance

**What Happens:**
1. Rowing machine breaks down
2. Member reports issue to front desk
3. Admin opens Equipment Management
4. Searches "rowing"
5. Finds "Rowing Machine Elite" card
6. Current status: "Available" (green)
7. Clicks "Edit"
8. Side panel opens with current details
9. Changes Status dropdown: "Available" → "Maintenance"
10. Clicks "Save Equipment"
11. Card updates, badge turns orange: "Maintenance"
12. Members see equipment unavailable on public page
13. Repair scheduled
14. When fixed, admin changes back to "Available"

---

### Scenario 3: Organizing Equipment by Category

**What Happens:**
1. Admin needs to inventory strength equipment
2. Opens Equipment Management
3. Clicks "🏋️ Strength Training" tab
4. Grid filters to show only strength equipment
5. Sees all barbells, dumbbells, machines
6. Counts: 8 items total
7. Notes: 2 in "Maintenance", 6 "Available"
8. Identifies maintenance needs
9. Orders replacement parts
10. Category filtering for focused inventory

---

### Scenario 4: Finding Specific Equipment

**What Happens:**
1. Member asks: "Do you have kettlebells?"
2. Admin searches "kettle"
3. Grid filters instantly
4. Shows 2 results:
   - "Kettlebell Set 10-50 lbs" - Available
   - "Kettlebell Competition Grade" - Maintenance
5. Tells member: "Yes, we have kettlebells. One set available now, another under repair."
6. Quick, accurate response
7. Member satisfied

---

### Scenario 5: Removing Old Equipment

**What Happens:**
1. Old treadmill beyond repair, being discarded
2. Admin opens Equipment Management
3. Finds "Treadmill Classic 2010" card
4. Status: "Out of Order" (red)
5. Clicks "Delete"
6. Modal: "Delete Treadmill Classic 2010? Cannot be undone."
7. Confirms deletion
8. Equipment removed from database
9. Image file deleted from server
10. Card disappears from grid
11. Inventory clean and current

---

### Scenario 6: Bulk Category Review

**What Happens:**
1. Admin prepares monthly report
2. Opens Equipment Management
3. Clicks "All" tab (total inventory)
4. Counts total cards: 42 equipment items
5. Clicks "🏃 Cardio" tab: 12 items
6. Clicks "🏋️ Strength Training" tab: 18 items
7. Clicks "🧘 Flexibility" tab: 6 items
8. Clicks "💪 Core" tab: 4 items
9. Clicks "🦾 Functional Training" tab: 2 items
10. Records breakdown:
    - Strength: 43% (largest category)
    - Cardio: 29%
    - Flexibility: 14%
    - Core: 10%
    - Functional: 5% (need more)
11. Recommends purchasing functional training equipment
12. Data-driven budget planning

---

## Important Notes and Limitations

### Things to Know

1. **Admin Role Required**
   - Must have `role = 'admin'`
   - Members can VIEW equipment on public page
   - Only admins can ADD/EDIT/DELETE

2. **Image Uploads Optional**
   - Equipment can be added without image
   - Falls back to category icon
   - Images helpful for identification

3. **Three Status Options**
   - **Available:** Members can use
   - **Maintenance:** Temporarily unavailable (repairs)
   - **Out of Order:** Not functioning (consider replacing)

4. **Five Categories Fixed**
   - Cardio, Flexibility, Core, Strength Training, Functional Training
   - Cannot add custom categories (hardcoded)
   - All equipment must fit one category

5. **Delete is Permanent**
   - No soft delete
   - Cannot undo
   - Image file also deleted
   - Use with caution

6. **No Quantity Tracking**
   - Each card represents one equipment item
   - If 5 identical treadmills: Create 5 separate entries
   - Or use name: "Treadmill Pro X500 - Unit 1", "Unit 2", etc.

7. **Status Changes Affect Public Page**
   - Members see equipment status on public equipment page
   - "Maintenance" or "Out of Order" items shown as unavailable
   - Keeps members informed

### What This Page Doesn't Do

- **Doesn't track usage** (no booking/reservation system for equipment)
- **Doesn't show maintenance history** (no repair logs)
- **Doesn't track quantity** (one card = one item, not inventory count)
- **Doesn't have serial numbers** (manual description field only)
- **Doesn't schedule maintenance** (status update only, no calendar)
- **Doesn't show equipment location** (no floor map/zone tracking)
- **Doesn't allow member reporting** (admin-only status changes)
- **Doesn't integrate with purchases** (no purchase date/warranty tracking)
- **Doesn't export data** (no CSV/Excel download)
- **Doesn't have bulk actions** (one-by-one edits only)

---

## Navigation

### How Admins Arrive Here
- **Sidebar menu:** "Equipment" link
- **Dashboard:** (if equipment widget exists)
- **Direct URL:** `fitxbrawl.com/public/php/admin/equipment.php`

### Where Admins Go Next
- **Public Equipment Page:** View member-facing display
- **Products** (`products.php`) - Manage gym products for sale
- **Dashboard** (`admin.php`) - Return to overview

---

## Tips for Admins

### Best Practices

1. **Upload Images for Clarity**
   - Images help members identify equipment
   - Take photos of actual gym equipment
   - Better than icons alone

2. **Use Descriptive Names**
   - "Treadmill Pro X500" better than "Treadmill 1"
   - Include brand/model if known
   - Helps differentiate similar items

3. **Update Status Promptly**
   - Equipment breaks? Set to "Maintenance" immediately
   - Repair complete? Change back to "Available"
   - Keeps members informed, reduces frustration

4. **Add Helpful Descriptions**
   - "Max weight 300 lbs, adjustable seat"
   - Helps members choose right equipment
   - Include special features

5. **Regular Inventory Checks**
   - Monthly review of all equipment
   - Check status accuracy
   - Remove items no longer in gym

6. **Archive, Don't Delete (Usually)**
   - Set to "Out of Order" first
   - Only delete when physically removed from gym
   - Preserves history

7. **Use Categories Consistently**
   - Dumbbells → "Strength Training"
   - Resistance bands → "Functional Training"
   - Consistent categorization helps filtering

---

## Final Thoughts

The equipment management page is your gym inventory system—simple, visual, and effective. The card-based grid makes scanning equipment easy, with category icons and color-coded status badges providing instant visual feedback. The dual filtering (tabs + dropdown + search) gives you multiple ways to find what you need fast.

The side panel form is clean and focused: upload image, enter name, select category and status, add description, save—done. The image preview is a nice touch, letting you confirm the right photo before saving. The status system (Available/Maintenance/Out of Order) bridges admin operations and member communication—change it here, members see it on the public page.

The category organization (Cardio, Flexibility, Core, Strength, Functional) maps well to typical gym layouts and training disciplines. The emoji icons add personality without clutter. The delete modal prevents accidental removal of equipment.

It's not a full asset management system—no usage tracking, maintenance scheduling, or warranty tracking—but for a gym's equipment catalog, it hits the essentials: what do we have, what category, what status, what does it look like. Fast to add, easy to update, clear to view.

