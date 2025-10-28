# Header and Footer Migration Guide

## Overview
This guide documents the migration of header and footer HTML code to separate include files to improve code consistency and reduce redundancy across the Fit and Brawl Gym website.

## Changes Made

### 1. Created Include Files

#### `includes/header.php`
- **Location**: `c:\xampp\htdocs\fit-brawl\includes\header.php`
- **Purpose**: Contains the HTML header structure used across all pages
- **Features**:
  - Dynamic page title configuration via `$pageTitle` variable
  - Active menu item highlighting via `$currentPage` variable
  - Support for additional CSS files via `$additionalCSS` array
  - Support for additional JS files via `$additionalJS` array
  - Automatic membership link calculation based on user status
  - Avatar handling for logged-in/non-logged-in states
  - Session timeout warning integration
  - Responsive navigation with hamburger menu

#### `includes/footer.php`
- **Location**: `c:\xampp\htdocs\fit-brawl\includes\footer.php`
- **Purpose**: Contains the HTML footer structure used across all pages
- **Features**:
  - Consistent footer menu links
  - Contact information
  - Opening hours
  - Copyright notice

### 2. Files Successfully Updated

The following files have been successfully converted to use the new header and footer includes:

#### ✅ Completed Files:
1. **`public/php/index.php`** - Homepage (non-logged-in)
2. **`public/php/loggedin-index.php`** - Homepage (logged-in)
3. **`public/php/contact.php`** - Contact page
4. **`public/php/login.php`** - Login page
5. **`public/php/membership.php`** - Membership page
6. **`public/php/products.php`** - Products page
7. **`public/php/equipment.php`** - Equipment page
8. **`public/php/feedback.php`** - Feedback page (partially updated)

### 3. How to Use the Header and Footer Includes

#### Setting Up Variables Before Including Header

```php
<?php
// ... your PHP logic here ...

// Set variables for header
$pageTitle = "Fit and Brawl - Your Page Title";
$currentPage = "home"; // Options: "home", "membership", "equipment", "products", "contact", "feedback"
$additionalCSS = ["../css/pages/your-page.css", "../css/components/your-component.css"];
$additionalJS = ["../js/your-script.js"]; // Optional

// Include header
require_once '../../includes/header.php';
?>

<!-- Your page content here -->
```

#### Including Footer

```php
    </main>

    <!-- Any page-specific scripts -->
    <script src="../js/your-script.js"></script>

<?php require_once '../../includes/footer.php'; ?>
```

### 4. Files Still Requiring Migration

The following user-facing PHP files still need to be updated to use the header and footer includes:

#### 🔲 Pending Files:
1. **`public/php/sign-up.php`** - Sign-up/Registration page
2. **`public/php/reservations.php`** - Reservations page
3. **`public/php/user_profile.php`** - User profile page
4. **`public/php/transaction.php`** - Transaction page
5. **`public/php/transaction_service.php`** - Transaction service page
6. **`public/php/forgot-password.php`** - Forgot password page
7. **`public/php/change-password.php`** - Change password page
8. **`public/php/verification.php`** - Email verification page
9. **`public/php/verify-email.php`** - Verify email page
10. **`public/php/membership-status.php`** - Membership status page
11. **`public/php/feedback-form.php`** - Feedback form page

#### Files to SKIP (APIs and Admin):
- `public/php/admin/*` - All admin files
- `public/php/api/*` - All API files
- `public/php/auth.php` - Authentication API
- `public/php/trainer_api.php` - Trainer API
- `public/php/check_session.php` - Session check API
- `public/php/extend_session.php` - Session extend API
- `public/php/resend-otp.php` - OTP API
- `public/php/update_profile.php` - Profile update API
- `public/php/logout.php` - Logout (redirect only)

### 5. Migration Steps for Remaining Files

For each file that needs to be migrated, follow these steps:

1. **Read the file** to understand its current structure
2. **Identify the header section** (from `<!DOCTYPE html>` to `</header>`)
3. **Identify the footer section** (from `<!--Footer-->` or `<footer>` to `</html>`)
4. **Set up the required variables**:
   ```php
   $pageTitle = "Appropriate page title";
   $currentPage = "appropriate menu item"; // or "" for none
   $additionalCSS = ["path/to/page-specific.css"];
   ```
5. **Replace the header** with:
   ```php
   require_once '../../includes/header.php';
   ```
6. **Replace the footer** with:
   ```php
   <?php require_once '../../includes/footer.php'; ?>
   ```
7. **Test the page** to ensure it renders correctly

### 6. Benefits of This Migration

1. **Consistency**: All pages now use the same header and footer structure
2. **Maintainability**: Changes to header/footer only need to be made in one place
3. **Reduced Code**: Eliminated hundreds of lines of duplicate code
4. **Easier Updates**: Future changes to navigation, links, or styling are centralized
5. **Less Error-Prone**: No risk of inconsistent headers across pages

### 7. Important Notes

- The header include automatically handles:
  - Logged-in vs non-logged-in states
  - Dynamic avatar display
  - Membership link calculation (membership.php vs reservations.php vs membership-status.php)
  - Active menu item highlighting
  - Session timeout warnings for logged-in users

- Always set the `$pageTitle` variable before including the header
- Set `$currentPage` to highlight the appropriate menu item
- Use `$additionalCSS` and `$additionalJS` arrays for page-specific assets
- The includes use relative paths, so they work from `public/php/` directory

### 8. Testing Checklist

After migrating a file, test the following:

- [ ] Page loads without errors
- [ ] Header displays correctly
- [ ] Navigation menu items are present
- [ ] Correct menu item is highlighted (if applicable)
- [ ] Avatar/account icon displays correctly
- [ ] Dropdown menu works (for logged-in users)
- [ ] Footer displays correctly
- [ ] All footer links work
- [ ] Page-specific CSS is loaded
- [ ] Page-specific JS is loaded
- [ ] Responsive design works (hamburger menu)

### 9. Example Migration

**Before:**
```php
<?php
session_start();
// ... logic ...
?>
<!DOCTYPE html>
<html>
<head>
    <title>Page Title</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/page.css">
    <!-- ... more links ... -->
</head>
<body>
    <header>
        <!-- ... full header HTML ... -->
    </header>

    <main>
        <!-- ... content ... -->
    </main>

    <footer>
        <!-- ... full footer HTML ... -->
    </footer>
</body>
</html>
```

**After:**
```php
<?php
session_start();
// ... logic ...

// Set variables for header
$pageTitle = "Page Title - Fit and Brawl";
$currentPage = "appropriate_page";
$additionalCSS = ["../css/pages/page.css"];

// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main>
        <!-- ... content ... -->
    </main>

<?php require_once '../../includes/footer.php'; ?>
```

### 10. Next Steps

1. Continue migrating the remaining files listed in section 4
2. Test each migrated file thoroughly
3. Update this document as files are completed
4. Consider creating similar includes for other repeated components (modals, forms, etc.)

---

**Date Created**: October 28, 2025
**Last Updated**: October 28, 2025
**Status**: In Progress (8/19 main user-facing files completed)
