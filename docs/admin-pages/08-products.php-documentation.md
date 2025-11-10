# Products Management Page (`products.php`) - Complete Documentation

## Table of Contents
1. [Page Purpose](#page-purpose)
2. [Key Features Overview](#key-features-overview)
3. [The Complete Page Experience](#the-complete-page-experience)
4. [How Each Feature Works](#how-each-feature-works)
5. [Data Flow & Backend Integration](#data-flow--backend-integration)
6. [Common Scenarios & Workflows](#common-scenarios--workflows)
7. [Tips for Effective Product Management](#tips-for-effective-product-management)

---

## Page Purpose

The **Products Management** page serves as FitXBrawl's **gym store inventory system**, allowing administrators to manage all products sold at the gym—from supplements and protein shakes to boxing gloves and gym accessories. This page combines **inventory tracking**, **automatic stock status management**, and **bulk operations** in one unified interface.

Think of this page as your **gym store command center**. Whether you're restocking supplements after a busy week, adding new merchandise for an upcoming promotion, or managing low stock alerts for popular items, this is where you keep the gym store running smoothly.

### What Makes This Page Special?

1. **Automatic Stock Status System**: The page automatically categorizes products based on quantity—no manual status updates needed. Zero stock? Automatically marked "Out of Stock". Between 1-10 items? Automatically shows "Low Stock" with a warning badge. Above 10? "In Stock" and ready to sell.

2. **Visual Inventory Management**: See product images, stock levels, and status at a glance with color-coded badges (green for in stock, yellow for low stock, red for out of stock).

3. **Bulk Operations**: Select multiple products and delete them all at once—perfect for clearing out discontinued items or seasonal products after promotions end.

4. **Side Panel Workflow**: Add or edit products without leaving the main page. The side panel slides in smoothly, you make your changes, and you're back to the main inventory view instantly.

5. **Category Organization**: Products are grouped into five practical categories (Supplements, Hydration & Drinks, Snacks, Accessories, Boxing & Muay Thai Products), making it easy to filter and find specific items.

### Who Uses This Page?

- **Gym Managers**: Add new products for upcoming promotions, monitor inventory levels, and ensure popular items stay in stock
- **Front Desk Staff**: Check stock availability when members ask about specific products, update quantities after sales
- **Inventory Coordinators**: Conduct monthly stock reviews, identify products needing reorder, manage seasonal merchandise

---

## Key Features Overview

### 1. Product Categories (5 Types)
The page organizes all gym store products into **five distinct categories**:

- **Supplements**: Protein powders, creatine, BCAAs, pre-workout formulas, vitamins
- **Hydration & Drinks**: Sports drinks, electrolyte solutions, bottled water, energy drinks
- **Snacks**: Protein bars, energy bars, nuts, healthy snack options
- **Accessories**: Gym bags, water bottles, towels, resistance bands, workout gloves
- **Boxing & Muay Thai Products**: Boxing gloves, hand wraps, shin guards, punching mitts, mouthguards

Each category helps organize inventory and allows members to browse related products when shopping at the gym store.

### 2. Automatic Stock Status System
The page features an **intelligent auto-status system** that eliminates manual status updates:

- **Out of Stock** (Red Badge): Stock quantity = 0 (product unavailable for purchase)
- **Low Stock** (Yellow Badge): Stock quantity = 1-10 items (warning to reorder soon)
- **In Stock** (Green Badge): Stock quantity = 11+ items (healthy inventory level)

When you update a product's stock quantity, the system **automatically recalculates and updates the status badge**—no need to manually change status fields. This prevents human error and ensures accurate availability information.

### 3. Search & Filter Toolbar
The page provides **three powerful filtering options**:

**Search Box**: Type product names to quickly find specific items (e.g., "protein", "gloves", "water bottle")

**Category Filter**: Dropdown menu to view only products from specific categories:
- All Categories (default view showing everything)
- Supplements
- Hydration & Drinks
- Snacks
- Accessories
- Boxing & Muay Thai Products

**Status Filter**: Dropdown menu to view products by availability:
- All Status (default view showing everything)
- In Stock (green badges, 11+ items)
- Low Stock (yellow badges, 1-10 items)
- Out of Stock (red badges, 0 items)

These filters work independently or can be combined (e.g., show only "Low Stock" items in the "Supplements" category).

### 4. Products Table View
The main table displays **all product information** in an organized format:

**Columns**:
- **Checkbox**: Select individual products for bulk delete operations
- **Image**: Product photo thumbnail (uploaded by admin)
- **Product Name**: Item name as displayed to members
- **Category**: Which product group it belongs to
- **Stock**: Current quantity number (determines auto-status)
- **Status**: Color-coded badge (Out of Stock/Low Stock/In Stock)
- **Actions**: Edit button (pencil icon) and Delete button (trash icon)

**Header Row**:
- **Select All** checkbox: Toggle selection for all visible products at once
- Column headers for easy identification

### 5. Bulk Delete Feature
Select **multiple products** and delete them all in one action:

- **Individual Checkboxes**: Click boxes next to products you want to delete
- **Select All**: Check the header checkbox to select all visible products at once
- **Bulk Delete Button**: Appears dynamically when products are selected, showing count (e.g., "Delete Selected (5)")
- **Confirmation Modal**: Prevents accidental bulk deletions with "Are you sure?" prompt
- **Auto-Refresh**: Page reloads automatically after successful bulk deletion

Perfect for clearing out discontinued product lines, seasonal items after promotions, or duplicate entries.

### 6. Side Panel Add/Edit Form
A **slide-in panel** on the right side of the screen allows adding new products or editing existing ones:

**Form Fields**:
- **Product Image Upload**: Click the image preview box to select a photo (shows live preview before saving)
- **Product Name**: Text input for the item name (required field)
- **Category**: Dropdown menu to select from 5 categories (required field)
- **Stock Quantity**: Number input for current inventory count (minimum 0, required field)
- **Auto-Status Hint**: Text reminder showing "Status will auto-update: 0=Out of Stock, 1-10=Low Stock, 11+=In Stock"

**Action Buttons**:
- **Cancel**: Close the panel without saving changes
- **Save Product**: Submit the form and add/update the product

**Panel Behavior**:
- Opens via "Add New Product" button in toolbar (for new products)
- Opens via "Edit" button in product row (for existing products, pre-fills all fields)
- Closes automatically after successful save
- Shows live image preview when selecting photos

### 7. Delete Confirmation Modal
A **modal dialog** appears whenever deleting products (single or bulk):

- **Warning Message**: "Are you sure you want to delete [product name]?" or "Are you sure you want to delete [X] product(s)?"
- **Caution Notice**: "This action cannot be undone"
- **Cancel Button**: Close modal without deleting anything
- **Confirm Delete Button**: Proceed with deletion (styled in red for emphasis)

Prevents accidental deletions and gives admins a final chance to reconsider.

---

## The Complete Page Experience

### First Impression: Landing on the Products Page

When you first load the page, you see:

**At the top**: 
- Page title "Product Management" 
- Subtitle "Manage your gym store inventory"

**Toolbar area** (light gray background):
- Search box on the left: "Search products..."
- Two dropdown filters: "All Categories" and "All Status"
- Green "Add New Product" button on the right
- Bulk delete button (hidden until products are selected)

**Main table** (white background with subtle borders):
- Header row with "Select All" checkbox and column labels
- Product rows showing images, names, categories, stock numbers, colored status badges
- Edit and Delete action buttons on each row
- Empty state if no products exist: "No Products Found - Click 'Add New Product' to get started"

**Visual indicators**:
- Green badges = In Stock (healthy inventory)
- Yellow badges = Low Stock (needs attention)
- Red badges = Out of Stock (urgent restock needed)

**Overall impression**: Clean, organized inventory dashboard that clearly shows stock health at a glance through color coding.

### Adding Your First Product

**Step 1: Open the Add Panel**
Click the green "Add New Product" button in the top-right toolbar.

**What happens**: A panel slides in from the right side of the screen, overlaying about 30% of the page width. The panel header reads "Add New Product".

**Step 2: Upload Product Image**
Click the large image preview box (shows camera icon and "Click to upload image" text).

**What happens**: Your computer's file picker opens. Select a JPG, PNG, or GIF image.

**Result**: The selected image appears as a live preview in the box (before even saving the form).

**Step 3: Enter Product Details**
- Type the product name (e.g., "Optimum Nutrition Gold Standard Whey")
- Select a category from the dropdown (e.g., "Supplements")
- Enter the current stock quantity (e.g., 25)

**Notice**: Below the stock field, you see a hint: "Status will auto-update: 0=Out of Stock, 1-10=Low Stock, 11+=In Stock"

**Step 4: Save the Product**
Click the green "Save Product" button at the bottom of the panel.

**What happens**: 
1. Form data submits to the backend (including the uploaded image)
2. Success message appears (if successful)
3. Panel automatically closes
4. Page refreshes to show the new product in the table

**Result**: Your new product appears in the table with:
- Uploaded image thumbnail
- Product name "Optimum Nutrition Gold Standard Whey"
- Category "Supplements"
- Stock "25"
- Status badge "In Stock" (green, because 25 > 10)
- Edit and Delete buttons ready for future changes

### Editing an Existing Product

**Scenario**: A member just bought 20 protein bars, and now you have only 8 left (low stock territory).

**Step 1: Find the Product**
Use the search box to quickly find "Protein Bars" or scroll through the Snacks category.

**Step 2: Click Edit**
Click the blue pencil icon in the Actions column for "Protein Bars".

**What happens**: The side panel slides in from the right, but this time:
- Panel header reads "Edit Product" (not "Add New Product")
- All fields are **pre-filled** with current data:
  - Image preview shows the existing product photo
  - Product Name field shows "Protein Bars"
  - Category dropdown shows "Snacks" (pre-selected)
  - Stock Quantity shows "28" (the old quantity)

**Step 3: Update Stock Quantity**
Clear the stock field and type "8" (the new quantity after sales).

**Step 4: Save Changes**
Click the green "Save Product" button.

**What happens**:
1. Updated data submits to backend
2. Page refreshes automatically
3. Panel closes

**Result**: The product row now shows:
- Stock: 8
- Status: "Low Stock" (yellow badge, because 8 is between 1-10)

**Notice**: You didn't manually change the status—the system automatically updated it from "In Stock" to "Low Stock" based on the quantity change.

### Using Filters to Find Products

**Scenario**: You want to see only supplements that are running low on stock.

**Step 1: Set Category Filter**
Click the "All Categories" dropdown → Select "Supplements"

**What happens**: The page reloads, showing only products where category = "Supplements". All other categories disappear from view.

**Step 2: Set Status Filter**
Click the "All Status" dropdown → Select "Low Stock"

**What happens**: The page reloads again, now showing **only** products where:
- Category = "Supplements" AND
- Status = "Low Stock" (1-10 items)

**Result**: You see a focused list of supplements needing reorder—perfect for weekly inventory checks.

**Step 3: Clear Filters**
To see everything again, reset both dropdowns to "All Categories" and "All Status".

### Bulk Deleting Old Products

**Scenario**: A protein powder brand discontinued three flavors, and you need to remove them from the system.

**Step 1: Search for Products**
Type "discontinued" or the brand name in the search box to find all related items.

**Step 2: Select Products for Deletion**
Click the checkbox next to each discontinued product (e.g., "Chocolate Mint Whey", "Strawberry Banana Whey", "Cookies & Cream Whey").

**What happens**: As you check each box:
- The "Bulk Delete" button appears in the toolbar
- The button text updates to show count: "Delete Selected (1)", then "(2)", then "(3)"

**Alternative**: Click the "Select All" checkbox in the table header to select all visible products at once.

**Step 3: Click Bulk Delete Button**
Click the red "Delete Selected (3)" button that appeared in the toolbar.

**What happens**: A confirmation modal pops up showing:
- Message: "Are you sure you want to delete 3 product(s)?"
- Warning: "This action cannot be undone"
- Two buttons: "Cancel" (gray) and "Confirm Delete" (red)

**Step 4: Confirm Deletion**
Click the red "Confirm Delete" button.

**What happens**:
1. All three selected products delete from the database
2. Success notification appears
3. Modal closes
4. Page refreshes automatically

**Result**: The three discontinued products are gone from the inventory table. The bulk delete button disappears (no products selected anymore).

### Handling Out of Stock Products

**Scenario**: The last boxing glove pair just sold, and now stock = 0.

**Manual Workflow**:
1. Find "Boxing Gloves" in the table (or search for it)
2. Click the Edit button
3. Change stock from "1" to "0"
4. Click Save Product

**What happens after save**:
- Stock column shows "0"
- Status badge automatically changes from "Low Stock" (yellow) to "Out of Stock" (red)
- Members browsing the gym store will see this product marked as unavailable

**Restocking Process**:
1. When new gloves arrive (e.g., 15 pairs), edit the product again
2. Change stock from "0" to "15"
3. Save changes
4. Status automatically updates to "In Stock" (green badge)
5. Product becomes available for purchase again

**Monitoring Out of Stock Items**:
- Use the Status filter → "Out of Stock" to see all unavailable products
- Create a weekly checklist: Check this filter, place orders for items shown
- Priority restock: Items with high demand (check sales reports separately)

---

## How Each Feature Works

### The Auto-Status System (Technical Breakdown)

**How it works behind the scenes**:

1. **Stock Entry**: Admin enters a stock quantity (e.g., 5) in the side panel form
2. **Form Submission**: JavaScript sends the quantity to `api/admin_products_api.php` via POST request
3. **Backend Calculation**: PHP script processes the quantity:
   ```php
   if ($stock == 0) {
       $status = 'out';
   } elseif ($stock >= 1 && $stock <= 10) {
       $status = 'low';
   } else { // $stock >= 11
       $status = 'in';
   }
   ```
4. **Database Update**: Both `stock` and `status` columns update in the `products` table
5. **Page Reload**: JavaScript reloads the page after success
6. **Badge Display**: PHP renders the table row with the appropriate badge:
   - `status = 'out'` → Red badge with "Out of Stock"
   - `status = 'low'` → Yellow badge with "Low Stock"
   - `status = 'in'` → Green badge with "In Stock"

**Why this matters**:
- **No manual errors**: Admins can't accidentally mark a product "In Stock" when quantity = 0
- **Consistency**: All products follow the same stock thresholds (0, 1-10, 11+)
- **Real-time updates**: Status always reflects current quantity accurately

**The thresholds explained**:
- **0 items = Out of Stock**: Makes sense—you literally have nothing to sell
- **1-10 items = Low Stock**: Warning zone—still available but needs reorder soon
- **11+ items = In Stock**: Healthy inventory level that won't run out immediately

These thresholds can be adjusted by editing the backend PHP logic if needed (e.g., if your gym prefers 1-20 as "low stock").

### Search Functionality (How It Works)

**What you type**: "protein"

**What happens**:

1. **Form Submission**: The search form submits to `products.php` with parameter `?search=protein`
2. **Backend Query**: PHP modifies the database query:
   ```sql
   SELECT * FROM products 
   WHERE name LIKE '%protein%' 
   ORDER BY name ASC
   ```
3. **Result Filtering**: Database returns only products with "protein" anywhere in the name
4. **Page Render**: PHP loops through results and builds the table showing only matching products

**Search examples**:
- Search "boxing" → Shows "Boxing Gloves", "Boxing Hand Wraps", "Boxing Mitts"
- Search "bar" → Shows "Protein Bars", "Energy Bars", "Granola Bars"
- Search "water" → Shows "Bottled Water", "Electrolyte Water", "Coconut Water"

**Case insensitive**: "PROTEIN", "protein", "Protein" all return the same results.

**Partial matching**: "glov" will find "Boxing Gloves", "Training Gloves", "Workout Gloves".

**Clear search**: Delete text from search box and press enter (or click search icon) to return to full product list.

### Category & Status Filters (Combined Filtering)

**How they work together**:

Both filters use **URL parameters** to refine the product list:
- Category filter: `?category=supplements`
- Status filter: `?status=low`
- Combined: `?category=supplements&status=low`

**Backend logic**:

1. **Get filter values** from URL parameters:
   ```php
   $category = $_GET['category'] ?? 'all';
   $status = $_GET['status'] ?? 'all';
   ```

2. **Build dynamic SQL query**:
   ```php
   $sql = "SELECT * FROM products WHERE 1=1";
   
   if ($category != 'all') {
       $sql .= " AND category = '$category'";
   }
   
   if ($status != 'all') {
       $sql .= " AND status = '$status'";
   }
   
   $sql .= " ORDER BY name ASC";
   ```

3. **Execute query** and return only products matching both conditions.

**Practical example**:

**Filter 1**: Category = "Supplements" (shows 50 supplement products)

**Filter 2**: Status = "Low Stock" (narrows to 8 low-stock supplements)

**Result**: Table shows only the 8 supplement products with 1-10 items in stock.

**Reset filters**: Change dropdowns back to "All Categories" and "All Status" to see everything again.

### Side Panel Form (Add vs Edit Mode)

The side panel behaves differently depending on how it's triggered:

#### Add Mode (New Product)

**Trigger**: Click "Add New Product" button

**JavaScript function**: `openSidePanel()`

**Panel state**:
- Title: "Add New Product"
- All fields empty (form reset)
- Product ID field: empty string (tells backend this is a new product)
- Image preview: Shows camera icon and "Click to upload" text
- Save button label: "Save Product"

**Form submission**:
- POST request to `api/admin_products_api.php`
- Backend detects empty ID → runs INSERT query
- New product added to database

#### Edit Mode (Existing Product)

**Trigger**: Click blue pencil icon (Edit button) in product row

**JavaScript function**: `editProduct(product)` receives full product data as parameter

**Panel state**:
- Title: "Edit Product"
- Product ID field: filled with existing product ID (e.g., "42")
- Product Name field: pre-filled (e.g., "Whey Protein")
- Category dropdown: pre-selected (e.g., "Supplements")
- Stock field: pre-filled (e.g., "18")
- Image preview: Shows existing product image (if available)
- Save button label: "Save Product" (same as add mode)

**Form submission**:
- POST request to `api/admin_products_api.php`
- Backend detects product ID → runs UPDATE query
- Existing product modified in database

**Key difference**: The product ID field determines whether backend performs INSERT (add) or UPDATE (edit).

### Image Upload & Preview System

**Step-by-step process**:

1. **Click image preview box**: Opens file picker dialog
2. **Select image file**: Choose JPG, PNG, or GIF from computer
3. **JavaScript FileReader API**: Reads the file and creates a temporary preview URL
   ```javascript
   const reader = new FileReader();
   reader.onload = function(e) {
       preview.style.backgroundImage = `url('${e.target.result}')`;
   };
   reader.readAsDataURL(file);
   ```
4. **Preview display**: Image appears in the box immediately (before form submission)
5. **Form submission**: Image file included in FormData object
6. **Backend processing**: PHP saves image to `uploads/products/` folder with unique filename
7. **Database storage**: Only the filename (not the full image data) saves to `products.image_path` column

**Image handling rules**:

- **Allowed formats**: JPG, JPEG, PNG, GIF
- **Storage location**: `c:\xampp\htdocs\fit-brawl\uploads\products\`
- **Filename handling**: Backend generates unique names to prevent overwrite (e.g., `product_123_1234567890.jpg`)
- **Display path**: Page uses environment variable `UPLOADS_PATH` to build full image URL

**Edit mode image handling**:

- **Existing image**: Shows current product photo in preview box
- **Change image**: Click preview box → Select new file → Replaces old image
- **Keep image**: Don't click preview box → Existing image path preserved

**Fallback for missing images**:
- If product has no image, preview box shows camera icon and "Click to upload" text
- In table view, missing images show placeholder icon instead of broken image

### Bulk Delete Workflow (Advanced)

**The complete flow**:

**Step 1: Checkbox Selection**
- User clicks individual checkboxes next to products (or "Select All" in header)
- JavaScript event listener detects each change:
  ```javascript
  document.querySelectorAll('.row-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', updateBulkDelete);
  });
  ```

**Step 2: Update Bulk Delete Button**
- `updateBulkDelete()` function counts checked boxes:
  ```javascript
  const checkboxes = document.querySelectorAll('.row-checkbox:checked');
  const count = checkboxes.length;
  ```
- If count > 0: Show bulk delete button with count text
- If count = 0: Hide bulk delete button

**Step 3: Click Bulk Delete**
- User clicks "Delete Selected (X)" button
- `bulkDelete()` function:
  - Collects all checked product IDs into array
  - Shows delete confirmation modal
  - Displays message: "Are you sure you want to delete X product(s)?"

**Step 4: Confirm Deletion**
- User clicks "Confirm Delete" in modal
- `confirmDelete()` function:
  - Sends DELETE request to `api/admin_products_api.php?bulk=1`
  - Request body: `{ ids: [42, 73, 91] }` (array of selected IDs)

**Step 5: Backend Processing**
- PHP receives bulk delete request
- Loops through ID array and deletes each product:
  ```php
  foreach ($ids as $id) {
      mysqli_query($conn, "DELETE FROM products WHERE id = $id");
  }
  ```
- Returns success response

**Step 6: Page Refresh**
- JavaScript reloads page automatically
- Deleted products no longer appear in table
- Checkboxes reset (none selected)
- Bulk delete button hides again

**Safety features**:
- Confirmation modal prevents accidental clicks
- "This action cannot be undone" warning clearly shown
- Cancel button available at every step
- Backend validates product IDs before deletion

### The Delete Modal (Single & Bulk Delete)

**Two delete scenarios handled by one modal**:

#### Single Product Delete

**Trigger**: Click red trash icon in product row

**JavaScript**: `deleteProduct(id, name)` function
- Sets `deleteId` variable to the product ID
- Clears `deleteIds` array
- Sets modal message: "Are you sure you want to delete '[Product Name]'?"
- Shows modal

**Confirmation**:
- Click "Confirm Delete"
- `confirmDelete()` sends DELETE request with single ID: `?id=42`
- Backend deletes one product

#### Bulk Delete

**Trigger**: Click "Delete Selected (X)" button

**JavaScript**: `bulkDelete()` function
- Clears `deleteId` variable
- Sets `deleteIds` array with all selected IDs: `[42, 73, 91]`
- Sets modal message: "Are you sure you want to delete 3 product(s)?"
- Shows modal

**Confirmation**:
- Click "Confirm Delete"
- `confirmDelete()` sends DELETE request with ID array: `?bulk=1` and body `{ ids: [...] }`
- Backend loops through and deletes all products

**Modal appearance**:
- Semi-transparent dark overlay covers page
- White modal box centered on screen
- Red warning icon at top
- Delete message (varies based on single vs bulk)
- Warning text: "This action cannot be undone"
- Gray "Cancel" button (closes modal, does nothing)
- Red "Confirm Delete" button (executes deletion)

**Close options**:
- Click "Cancel" button
- Click outside modal (on dark overlay)
- JavaScript `closeDeleteModal()` removes `active` class

---

## Data Flow & Backend Integration

### Database Structure

**Products table schema**:

```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    stock INT DEFAULT 0,
    status ENUM('in', 'low', 'out') DEFAULT 'out',
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Column explanations**:
- **id**: Unique identifier for each product (auto-increments)
- **name**: Product display name (e.g., "Optimum Nutrition Gold Standard Whey")
- **category**: One of 5 categories (Supplements, Hydration & Drinks, Snacks, Accessories, Boxing & Muay Thai Products)
- **stock**: Current quantity number (0-999+)
- **status**: Auto-calculated from stock ('in' = 11+, 'low' = 1-10, 'out' = 0)
- **image_path**: Filename stored in uploads/products/ (e.g., "product_42_1234567890.jpg")
- **created_at**: Timestamp when product was first added
- **updated_at**: Timestamp when product was last modified

### API Endpoints

#### 1. Get Products List (Read)

**URL**: `products.php?api=true`

**Method**: GET

**Purpose**: Fetch all products or filtered subset

**Query parameters** (optional):
- `search`: Filter by product name
- `category`: Filter by category
- `status`: Filter by stock status

**Response format**:
```json
[
    {
        "id": 42,
        "name": "Whey Protein Powder",
        "category": "Supplements",
        "stock": 15,
        "status": "in",
        "image_path": "product_42_1234567890.jpg"
    },
    {
        "id": 73,
        "name": "Boxing Gloves",
        "category": "Boxing & Muay Thai Products",
        "stock": 3,
        "status": "low",
        "image_path": "product_73_0987654321.jpg"
    }
]
```

**Usage**: JavaScript loads this data to render the products table dynamically.

#### 2. Add/Edit Product (Create/Update)

**URL**: `api/admin_products_api.php`

**Method**: POST

**Content-Type**: `multipart/form-data` (supports file uploads)

**Form data**:
- `id`: Product ID (empty for add, filled for edit)
- `name`: Product name
- `category`: Selected category
- `stock`: Stock quantity
- `product_image`: Uploaded image file (optional)
- `existing_image`: Current image path (edit mode only)

**Backend logic**:
1. Check if product ID exists
2. If empty → **INSERT** new product
3. If filled → **UPDATE** existing product
4. Handle image upload if file provided
5. Calculate status from stock quantity
6. Save to database

**Response format**:
```json
{
    "success": true,
    "message": "Product saved successfully"
}
```

Or on error:
```json
{
    "success": false,
    "message": "Product name is required"
}
```

#### 3. Delete Product (Single Delete)

**URL**: `api/admin_products_api.php?id=42`

**Method**: DELETE

**Purpose**: Remove one product by ID

**Backend logic**:
1. Validate product ID exists
2. Delete product image file from `uploads/products/`
3. Delete product record from database
4. Return success/error response

**Response**:
```json
{
    "success": true,
    "message": "Product deleted successfully"
}
```

#### 4. Bulk Delete Products

**URL**: `api/admin_products_api.php?bulk=1`

**Method**: DELETE

**Content-Type**: `application/json`

**Request body**:
```json
{
    "ids": [42, 73, 91]
}
```

**Backend logic**:
1. Parse JSON body to get ID array
2. Loop through each ID:
   - Delete product image file
   - Delete product record
3. Return count of deleted products

**Response**:
```json
{
    "success": true,
    "message": "3 products deleted successfully"
}
```

### JavaScript Files & Responsibilities

#### `products.js` (278 lines)

**Key functions**:

1. **openSidePanel()**: Opens side panel in "add" mode (empty form)
2. **editProduct(product)**: Opens side panel in "edit" mode (pre-filled form)
3. **closeSidePanel()**: Hides side panel
4. **previewImage(event)**: Shows live preview of selected image
5. **resetImagePreview()**: Clears preview back to placeholder
6. **saveProduct(formData)**: Submits add/edit form via AJAX
7. **deleteProduct(id, name)**: Opens delete modal for single product
8. **bulkDelete()**: Opens delete modal for multiple selected products
9. **confirmDelete()**: Executes actual delete API call
10. **closeDeleteModal()**: Hides delete confirmation modal
11. **toggleSelectAll()**: Checks/unchecks all product checkboxes
12. **updateBulkDelete()**: Shows/hides bulk delete button based on selections

**Event listeners**:
- Form submit → Intercepts and sends AJAX request
- Checkbox change → Updates bulk delete button visibility
- Image input change → Triggers live preview

#### `sidebar.js` (Shared utility)

**Purpose**: Generic sidebar panel behavior (open/close animations, overlay clicks)

**Used by**: Multiple admin pages (products, equipment, etc.)

### Image Upload Process (Detailed)

**Frontend process**:

1. User clicks image preview box
2. Hidden file input triggers: `<input type="file" name="product_image">`
3. User selects image file from computer
4. JavaScript FileReader reads file as Data URL
5. Preview box background updates with image: `backgroundImage: url(data:image/jpeg;base64,...)`
6. User fills other form fields
7. Click "Save Product"
8. FormData object created with all fields including file
9. AJAX POST request sends FormData to backend

**Backend process** (PHP):

```php
// 1. Check if file was uploaded
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    
    // 2. Validate file type
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['product_image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        die('Invalid file type');
    }
    
    // 3. Generate unique filename
    $new_filename = 'product_' . $product_id . '_' . time() . '.' . $ext;
    
    // 4. Set upload path
    $upload_path = '../../../uploads/products/' . $new_filename;
    
    // 5. Move uploaded file
    if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
        $image_path = $new_filename; // Save to database
    }
}
```

**Storage location**: `c:\xampp\htdocs\fit-brawl\uploads\products\`

**Database storage**: Only filename saved (e.g., `product_42_1705850400.jpg`), not full path

**Display path**: Frontend builds full URL using `UPLOADS_PATH` variable + filename

**File cleanup**: When product deleted, both database record AND image file removed

---

## Common Scenarios & Workflows

### Scenario 1: Weekly Inventory Restock

**Context**: Every Monday, the gym manager reviews stock and places orders for low-inventory items.

**Step 1: Filter for Low Stock Items**
- Navigate to Products page
- Set Status filter to "Low Stock"
- Result: See all products with 1-10 items remaining

**Step 2: Review Each Category**
- Set Category filter to "Supplements" → See low-stock supplements
- Note products needing reorder (e.g., "Whey Protein: 3 units")
- Change Category to "Hydration & Drinks" → Review low-stock drinks
- Repeat for all categories

**Step 3: Create Restock List**
- Write down or export products with quantities below 5 units (urgent)
- Note products with 6-10 units (order soon)
- Place supplier orders based on this list

**Step 4: Update Stock After Delivery**
- When shipment arrives (e.g., 50 new whey protein containers)
- Find "Whey Protein" in table (was 3 units, status "Low Stock")
- Click Edit button
- Change stock from "3" to "53" (3 old + 50 new)
- Save changes
- Status automatically updates to "In Stock" (green badge)

**Result**: Inventory replenished, stock levels healthy, no out-of-stock products.

### Scenario 2: Adding New Seasonal Products

**Context**: Summer is approaching, and the gym wants to stock refreshing hydration products.

**Step 1: Add First Product (Coconut Water)**
- Click "Add New Product" button
- Side panel opens in add mode
- Click image upload box → Select coconut water product photo
- Fill form:
  - Product Name: "Pure Coconut Water"
  - Category: "Hydration & Drinks"
  - Stock: 24
- Click "Save Product"

**What happens**: Product added with "In Stock" status (24 > 10)

**Step 2: Add Second Product (Watermelon Energy Drink)**
- Click "Add New Product" again
- Upload watermelon drink image
- Fill form:
  - Product Name: "Watermelon Energy Boost"
  - Category: "Hydration & Drinks"
  - Stock: 18
- Save product

**Step 3: Verify New Products**
- Set Category filter to "Hydration & Drinks"
- Scroll through list
- Confirm both new products appear with green "In Stock" badges

**Result**: Summer product line successfully added, ready for member purchases.

### Scenario 3: Handling a Product Recall

**Context**: A supplement brand issued a recall for a specific batch, and you need to remove it immediately.

**Step 1: Find the Product**
- Use search box: Type "recalled brand name"
- Product appears in search results

**Step 2: Delete the Product**
- Click red trash icon in Actions column
- Delete modal appears: "Are you sure you want to delete '[Product Name]'?"
- Read warning: "This action cannot be undone"
- Click "Confirm Delete" (red button)

**What happens**:
- Product deleted from database
- Product image removed from uploads folder
- Page refreshes
- Product no longer visible in table

**Step 3: Verify Removal**
- Search for product again → "No products found"
- Check gym store (member-facing pages) → Product no longer available for purchase

**Result**: Recalled product immediately removed from inventory and sales system.

### Scenario 4: Managing Bulk Deletions (End of Season)

**Context**: The gym ran a boxing promotion with special merchandise. Promotion ended, and discontinued items need removal.

**Step 1: Filter for Promotional Products**
- Set Category to "Boxing & Muay Thai Products"
- Use search box to find promotional items (e.g., "Limited Edition")

**Step 2: Select All Promotional Items**
- Click checkbox next to each discontinued product:
  - "Limited Edition Red Gloves"
  - "Limited Edition Hand Wraps"
  - "Limited Edition Gym Bag"
- Bulk delete button appears: "Delete Selected (3)"

**Alternative approach**: If all visible products need deletion, click "Select All" checkbox in table header.

**Step 3: Execute Bulk Delete**
- Click "Delete Selected (3)" button
- Modal appears: "Are you sure you want to delete 3 product(s)?"
- Warning: "This action cannot be undone"
- Click "Confirm Delete"

**What happens**:
- All three products delete simultaneously
- All three product images removed from server
- Page refreshes
- Products disappear from table

**Result**: Promotional items cleared in one action (much faster than deleting individually).

### Scenario 5: Fixing Incorrect Stock Counts

**Context**: During a physical inventory audit, you discover discrepancies between system stock and actual shelf counts.

**Scenario A: System Shows 15, Shelf Has 12**

**Step 1: Find the Product**
- Search for the product or browse its category
- Current display: Stock "15", Status "In Stock" (green)

**Step 2: Correct the Count**
- Click Edit button
- Change stock from "15" to "12"
- Click Save

**Result**: Stock shows "12", Status remains "In Stock" (green, because 12 > 10)

**Scenario B: System Shows 8, Shelf Has Only 2**

**Step 1: Locate Product**
- Find product in table
- Current display: Stock "8", Status "Low Stock" (yellow)

**Step 2: Update to Actual Count**
- Click Edit
- Change stock from "8" to "2"
- Save changes

**Result**: Stock shows "2", Status remains "Low Stock" (yellow, because 2 is between 1-10)

**Scenario C: System Shows 5, Shelf is Empty**

**Step 1: Find Product**
- Current display: Stock "5", Status "Low Stock" (yellow)

**Step 2: Mark as Out of Stock**
- Click Edit
- Change stock from "5" to "0"
- Save

**Result**: Stock shows "0", Status automatically changes to "Out of Stock" (red badge)

**Follow-up**: Add this product to urgent restock list.

### Scenario 6: Changing Product Categories

**Context**: A product was initially categorized incorrectly and needs reclassification.

**Example**: "Protein Bars" were listed under "Supplements" but should be under "Snacks".

**Step 1: Locate the Product**
- Set Category filter to "Supplements"
- Find "Protein Bars" in the list

**Step 2: Edit Category**
- Click Edit button
- Category dropdown currently shows "Supplements" (pre-selected)
- Click dropdown → Select "Snacks"
- Leave all other fields unchanged
- Click Save

**Step 3: Verify the Change**
- Set Category filter to "Snacks"
- Scroll down → "Protein Bars" now appears in Snacks category
- Set filter back to "Supplements" → "Protein Bars" no longer shows (moved successfully)

**Result**: Product correctly categorized, easier for staff and members to find.

### Scenario 7: Running a Flash Sale (Temporary Stock Adjustment)

**Context**: Friday flash sale—members can purchase protein shakes at 50% off. You expect high volume.

**Before the sale**:
- Product: "Protein Shake Mix"
- Stock: 30 units
- Status: In Stock (green)

**During the sale** (members purchasing rapidly):
- Every few hours, front desk staff report sales
- You update stock in the system:
  - After 2 hours: 30 → 22 (8 sold) → Still "In Stock"
  - After 4 hours: 22 → 14 (8 more sold) → Still "In Stock"
  - After 6 hours: 14 → 7 (7 more sold) → **Status changes to "Low Stock"** (yellow)
  - After 8 hours: 7 → 1 (6 more sold) → Still "Low Stock"

**End of sale**:
- Final count: 1 unit remaining
- Status: "Low Stock" (yellow badge)
- Action: Add to urgent restock list (flash sale was successful!)

**Next week**:
- Restock arrives: 50 new units
- Edit product: Change stock from "1" to "51"
- Status automatically updates to "In Stock" (green)

**Result**: Stock levels accurately tracked throughout high-volume sales event.

---

## Tips for Effective Product Management

### Best Practices

**1. Keep Product Names Clear and Descriptive**

✅ Good: "Optimum Nutrition Gold Standard Whey Protein - Chocolate (2lb)"

❌ Avoid: "ON GS Choc 2"

**Why**: Members search for products by name. Clear descriptions improve searchability and reduce confusion.

**2. Upload High-Quality Product Images**

- **Resolution**: 800x800 pixels minimum (square aspect ratio works best)
- **Format**: JPEG for photos, PNG for images with text/logos
- **Lighting**: Well-lit product on neutral background
- **File size**: Under 500KB (compress if needed to avoid slow page loads)

**Why**: Professional photos build trust and help members identify products correctly.

**3. Set Realistic Stock Thresholds**

The default thresholds are:
- 0 = Out of Stock
- 1-10 = Low Stock (yellow warning)
- 11+ = In Stock (green, healthy)

**Adjust if needed**:
- High-demand products (e.g., protein powder): Consider 1-20 as "low stock" (edit backend logic)
- Slow-moving items (e.g., specialized supplements): Default thresholds work fine
- Perishable goods: Monitor expiration dates alongside stock levels

**4. Use Consistent Category Assignment**

**Category guidelines**:
- **Supplements**: Pills, powders, capsules with nutritional value (protein, vitamins, creatine)
- **Hydration & Drinks**: Beverages (sports drinks, water, energy drinks)
- **Snacks**: Solid food items (bars, nuts, trail mix)
- **Accessories**: Non-consumable gym items (bags, bottles, towels, bands)
- **Boxing & Muay Thai**: Combat sports equipment (gloves, wraps, guards)

**Edge cases**:
- "Ready-to-drink protein shakes" → Hydration & Drinks (liquid format)
- "Protein bars" → Snacks (solid food format)
- "Hand wraps" → Boxing & Muay Thai (even though boxers from other sports might use them)

**5. Conduct Weekly Stock Audits**

**Every Monday**:
1. Filter by "Low Stock" status
2. Review each product:
   - Items with 1-3 units → Urgent restock
   - Items with 4-7 units → Restock this week
   - Items with 8-10 units → Restock next week
3. Place supplier orders
4. Update stock quantities as shipments arrive

**Monthly deep audit**:
1. Export full product list (or review on screen)
2. Physically count shelf inventory
3. Compare system counts vs physical counts
4. Adjust discrepancies via Edit function

**6. Leverage Bulk Delete for Seasonal Turnover**

**Quarterly review**:
- Filter by "Out of Stock" products
- Identify items that have been out of stock for 60+ days
- If no plans to restock → Select all and bulk delete
- Keeps product database clean and relevant

**Seasonal cleanups**:
- After summer: Remove seasonal hydration products (e.g., "Summer Berry Cooler")
- After promotions: Delete limited-edition items no longer available
- After supplier changes: Remove discontinued brand products

**7. Use Search Strategically**

**Quick tips**:
- Search by brand: "Optimum Nutrition" → Shows all ON products
- Search by flavor: "Chocolate" → Shows all chocolate-flavored items
- Search by type: "Gloves" → Shows all glove products across categories
- Search by keywords: "Pre-workout" → Finds all pre-workout supplements

**Combine with filters**:
- Search "Protein" + Category "Snacks" = All protein bars/snacks
- Search "Boxing" + Status "Low Stock" = Boxing equipment needing restock

### Workflow Efficiency Tips

**1. Keep the Side Panel Open for Batch Edits**

When updating multiple products (e.g., adjusting stock after a delivery):
- Edit first product → Save → Panel closes
- Instead of clicking Edit again, **prepare a checklist**
- Click Edit → Update → Save → Repeat quickly

**Alternative**: Use keyboard shortcuts if available (Tab through fields, Enter to save).

**2. Use "Select All" Wisely**

"Select All" checkbox selects **all visible products** (not all products in database).

**Smart workflow**:
1. Filter to show only products you want to delete (e.g., "Out of Stock" + "Accessories")
2. Click "Select All" → Selects only the filtered results
3. Bulk delete → Removes only those filtered products

**Avoid**: Clicking "Select All" without filters (might delete everything unintentionally).

**3. Bookmark Common Filter Combinations**

The page preserves filters in the URL:
- `products.php?category=supplements&status=low`

**Bookmark useful combinations**:
- "Low Stock Supplements" → `?category=supplements&status=low`
- "Out of Stock Boxing Gear" → `?category=boxing-muay-thai&status=out`
- "All Hydration Products" → `?category=hydration-drinks&status=all`

Click bookmarks to instantly load filtered views.

**4. Name Products Alphabetically for Easy Sorting**

The table sorts products alphabetically by name.

**Naming strategy**:
- "Creatine Monohydrate" (C comes early)
- "Protein Powder - Vanilla" (P comes later)
- "Whey Isolate - Chocolate" (W comes last)

**Alternative**: Prefix with brand names for brand-based sorting:
- "Optimum Nutrition - Whey Protein"
- "Optimum Nutrition - Creatine"
- "MusclePharm - BCAA"

**5. Update Stock Immediately After Sales**

**Real-time accuracy**:
- Front desk makes a sale → Update stock right away
- Don't wait until end of day (prevents overselling)

**Quick workflow**:
1. Member buys 2 protein bars
2. Staff opens Products page on admin computer
3. Search "Protein Bars"
4. Click Edit
5. Change stock from "10" to "8"
6. Save
7. Done in under 30 seconds

### Common Mistakes to Avoid

**1. Forgetting to Upload Product Images**

**Problem**: Products with no images look unprofessional and confuse members.

**Solution**: Always upload an image when adding a product. Use placeholder image if official photo unavailable.

**2. Setting Stock Quantity Without Checking Existing Value**

**Problem**: You receive 20 new units and set stock to "20", forgetting there were already 5 units on the shelf.

**Correct approach**:
1. Check current stock: 5
2. Add new shipment: 20
3. Update stock to: 5 + 20 = **25** (not just 20)

**3. Accidentally Deleting Products Instead of Marking Out of Stock**

**Problem**: Product is temporarily out of stock, but you delete it completely (loses sales history, member favorites, etc.).

**Correct approach**:
- If product will be restocked → Edit and set stock to "0" (status becomes "Out of Stock")
- If product is permanently discontinued → Then delete it

**4. Not Using Consistent Naming Conventions**

**Problem**: 
- One product: "Optimum Nutrition Gold Standard Whey"
- Another product: "ON GS Whey Chocolate"
- Third product: "Whey Protein by Optimum Nutrition"

**Solution**: Pick a format and stick with it:
- **Brand - Product - Flavor/Size**: "Optimum Nutrition - Gold Standard Whey - Chocolate 5lb"

**5. Ignoring Low Stock Warnings**

**Problem**: Products show yellow "Low Stock" badge for weeks, eventually run out completely.

**Solution**: 
- Set a weekly calendar reminder: "Check Low Stock Products"
- Filter by "Low Stock" every Monday
- Place restock orders proactively

**6. Not Verifying Bulk Delete Selections**

**Problem**: Select products for bulk delete, but accidentally included items you wanted to keep.

**Solution**: 
- Before clicking "Confirm Delete", **count the number** in the modal message
- Verify it matches your intended deletions
- Click "Cancel" if the number seems wrong
- Go back and review checkbox selections

---

**End of Products Management Documentation**

*This page is your gym store inventory command center. Use auto-status tracking to stay ahead of stockouts, leverage bulk operations to manage seasonal turnover efficiently, and maintain accurate counts to keep your gym store running smoothly. Happy managing!*