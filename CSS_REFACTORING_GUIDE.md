# CSS Refactoring Guide - Using CSS Custom Variables

## Overview
This guide documents the refactoring of all CSS files in the Fit and Brawl project to use CSS custom variables (CSS variables) defined in `global.css` instead of hardcoded values. This improves maintainability, consistency, and makes theme changes easier.

## CSS Variable Reference

### Colors
```css
/* Brand Colors */
--color-primary: #002f3f;        /* Dark teal - Use instead of #002f3f */
--color-primary-light: #2d6b75;
--color-primary-dark: #0f3238;
--color-secondary: #4a7c87;
--color-accent: #d5ba2b;          /* Gold/Yellow - Use instead of #d5ba2b */
--color-accent-hover: #b8941f;

/* Background Colors */
--color-bg-dark: #010d17;
--color-bg-overlay: rgba(10, 31, 36, 0.8);
--color-card-bg: rgba(26, 74, 82, 0.9);
--color-modal-bg: rgba(0, 0, 0, 0.7);

/* Text Colors */
--color-white: #ffffff;           /* Use instead of #fff, #ffffff, white */
--color-text-light: #f8f9fa;
--color-text-muted: #a8b2b8;
--color-text-dark: #1a1a1a;      /* Use instead of #333, #1a1a1a, etc. */

/* Status Colors */
--color-success: #28a745;
--color-warning: #ffc107;
--color-error: #dc3545;
--color-info: #17a2b8;
```

### Typography
```css
/* Font Families */
--font-family-primary: 'Poppins', sans-serif;    /* Use instead of "Poppins", sans-serif */
--font-family-display: 'zuume-rough-bold', 'Poppins', sans-serif;

/* Font Sizes */
--font-size-xs: 0.75rem;     /* 12px */
--font-size-sm: 0.875rem;    /* 14px - Use instead of 14px, 14.4px */
--font-size-base: 1rem;      /* 16px - Use instead of 16px */
--font-size-lg: 1.125rem;    /* 18px - Use instead of 17px, 18px */
--font-size-xl: 1.25rem;     /* 20px */
--font-size-2xl: 1.5rem;     /* 24px - Use instead of 24px */
--font-size-3xl: 1.875rem;   /* 30px - Use instead of 28px, 30px */
--font-size-4xl: 2.25rem;    /* 36px */
--font-size-5xl: 3rem;       /* 48px */

/* Font Weights */
--font-weight-light: 300;
--font-weight-normal: 400;
--font-weight-medium: 500;    /* Use instead of 500 */
--font-weight-semibold: 600;  /* Use instead of 600 */
--font-weight-bold: 700;      /* Use instead of 700 */
--font-weight-extrabold: 800;
--font-weight-black: 900;

/* Line Heights */
--line-height-tight: 1.25;
--line-height-snug: 1.375;
--line-height-normal: 1.5;
--line-height-relaxed: 1.625;  /* Use instead of 1.6 */
--line-height-loose: 2;
```

### Spacing
```css
--spacing-0: 0;
--spacing-1: 0.25rem;   /* 4px */
--spacing-2: 0.5rem;    /* 8px - Use instead of 8px */
--spacing-3: 0.75rem;   /* 12px - Use instead of 10px, 12px */
--spacing-4: 1rem;      /* 16px - Use instead of 15px, 16px */
--spacing-5: 1.25rem;   /* 20px - Use instead of 20px */
--spacing-6: 1.5rem;    /* 24px */
--spacing-8: 2rem;      /* 32px - Use instead of 30px, 32px */
--spacing-10: 2.5rem;   /* 40px */
--spacing-12: 3rem;     /* 48px - Use instead of 3rem */
--spacing-16: 4rem;     /* 64px - Use instead of 4rem */
--spacing-20: 5rem;     /* 80px */
--spacing-24: 6rem;     /* 96px */
--spacing-32: 8rem;     /* 128px */
```

### Border Radius
```css
--radius-none: 0;
--radius-sm: 0.125rem;   /* 2px - Use instead of 2px */
--radius-base: 0.25rem;  /* 4px */
--radius-md: 0.375rem;   /* 6px */
--radius-lg: 0.5rem;     /* 8px - Use instead of 5px, 8px */
--radius-xl: 0.75rem;    /* 12px */
--radius-2xl: 1rem;      /* 16px */
--radius-3xl: 1.5rem;    /* 24px */
--radius-full: 50%;      /* Use instead of 50% for circles */
```

### Shadows
```css
--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-base: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
--shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
--shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
--shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
--shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
--shadow-card: 0 4px 12px rgba(0, 0, 0, 0.15);
--shadow-glow: 0 0 20px rgba(212, 175, 55, 0.3);
```

### Transitions
```css
--transition-fast: 150ms ease;      /* Use instead of 0.2s, 200ms */
--transition-base: 250ms ease;      /* Use instead of 0.25s, 250ms */
--transition-slow: 350ms ease;      /* Use instead of 0.3s, 300ms, 350ms */
--transition-all: all var(--transition-base);
```

### Z-Index
```css
--z-dropdown: 1000;      /* Use instead of 1000, z-index: 1000 */
--z-sticky: 1020;
--z-fixed: 1030;         /* Use instead of 1000 for fixed elements */
--z-modal-backdrop: 1040;
--z-modal: 1050;
--z-popover: 1060;
--z-tooltip: 1070;
--z-toast: 1080;
```

## Completed Files

### ✅ Component Files
1. **`components/header.css`** - ✅ Completed
   - Replaced all color values (#002f3f, #d5ba2b, #fff, etc.)
   - Replaced spacing values (padding, margin, gap)
   - Replaced font values (font-size, font-family, font-weight)
   - Replaced transition values
   - Replaced border-radius values
   - Replaced box-shadow values
   - Replaced z-index values

2. **`components/footer.css`** - ✅ Completed
   - Replaced all color values
   - Replaced spacing values
   - Replaced font values
   - Replaced line-height values
   - Replaced transition values

### 🔲 Pending Component Files
3. **`components/form.css`**
4. **`components/button.css`**
5. **`components/card.css`**
6. **`components/modal.css`**
7. **`components/navigation.css`**
8. **`components/session-warning.css`**
9. **`components/terms-modal.css`**

### 🔲 Pending Page Files
10. **`pages/homepage.css`**
11. **`pages/loggedin-homepage.css`**
12. **`pages/login.css`**
13. **`pages/sign-up.css`**
14. **`pages/contact.css`**
15. **`pages/membership.css`**
16. **`pages/membership-status.css`**
17. **`pages/equipment.css`**
18. **`pages/products.css`**
19. **`pages/reservations.css`**
20. **`pages/feedback.css`**
21. **`pages/feedback-form.css`**
22. **`pages/user-profile.css`**
23. **`pages/transaction.css`**
24. **`pages/verification.css`**
25. **`pages/forgot-password.css`**
26. **`pages/change-password.css`**

## Common Replacements Pattern

### Colors
```css
/* Before */
background-color: #002f3f;
color: #ffffff;
border-color: #d5ba2b;

/* After */
background-color: var(--color-primary);
color: var(--color-white);
border-color: var(--color-accent);
```

### Spacing
```css
/* Before */
padding: 16px 32px;
margin: 20px;
gap: 15px;

/* After */
padding: var(--spacing-4) var(--spacing-8);
margin: var(--spacing-5);
gap: var(--spacing-4);
```

### Typography
```css
/* Before */
font-family: "Poppins", sans-serif;
font-size: 24px;
font-weight: 600;
line-height: 1.6;

/* After */
font-family: var(--font-family-primary);
font-size: var(--font-size-2xl);
font-weight: var(--font-weight-semibold);
line-height: var(--line-height-relaxed);
```

### Transitions
```css
/* Before */
transition: all 0.3s ease;
transition: color 0.2s;

/* After */
transition: all var(--transition-slow);
transition: color var(--transition-fast);
```

### Border Radius
```css
/* Before */
border-radius: 5px;
border-radius: 50%;

/* After */
border-radius: var(--radius-lg);
border-radius: var(--radius-full);
```

### Shadows
```css
/* Before */
box-shadow: 0 4px 8px rgba(0,0,0,0.2);
box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);

/* After */
box-shadow: var(--shadow-md);
box-shadow: var(--shadow-lg);
```

## Steps to Update Each File

1. **Open the CSS file**
2. **Find and replace colors**:
   - `#002f3f` → `var(--color-primary)`
   - `#d5ba2b` → `var(--color-accent)`
   - `#ffffff` or `#fff` or `white` → `var(--color-white)`
   - `#333` or `#1a1a1a` → `var(--color-text-dark)`

3. **Find and replace font-family**:
   - `"Poppins", sans-serif` → `var(--font-family-primary)`
   - `"zuume-rough-bold", sans-serif` → `var(--font-family-display)`

4. **Find and replace font-sizes**:
   - `16px` → `var(--font-size-base)`
   - `24px` → `var(--font-size-2xl)`
   - `14px` or `14.4px` → `var(--font-size-sm)`
   - etc.

5. **Find and replace spacing**:
   - `16px` (in padding/margin) → `var(--spacing-4)`
   - `32px` → `var(--spacing-8)`
   - `20px` → `var(--spacing-5)`
   - etc.

6. **Find and replace other values**:
   - Font weights: `600` → `var(--font-weight-semibold)`
   - Border radius: `5px` → `var(--radius-lg)`
   - Transitions: `0.3s ease` → `var(--transition-slow)`
   - Z-index: `1000` → `var(--z-dropdown)`

## Benefits of This Refactoring

1. **Consistency**: All pages use the same values for colors, spacing, etc.
2. **Maintainability**: Change a value once in global.css, it updates everywhere
3. **Theming**: Easy to create dark mode or different color schemes
4. **Performance**: Browser can optimize CSS custom property usage
5. **Readability**: Semantic names make code self-documenting
6. **Standards**: Following modern CSS best practices

## Testing Checklist

After updating each file, verify:
- [ ] Colors render correctly
- [ ] Spacing looks consistent
- [ ] Typography displays properly
- [ ] Hover states work
- [ ] Responsive design functions
- [ ] No visual regressions
- [ ] Browser compatibility maintained

## Notes

- Some values may not have exact matches in the variable set. Use the closest match.
- For spacing: `15px` can use `var(--spacing-4)` (16px) - the 1px difference is negligible
- For colors: Keep rgba() values for transparency but use variables for base colors where possible
- Admin and API-related CSS files are excluded from this refactoring

---

**Date Created**: October 28, 2025
**Status**: In Progress (2/26 files completed)
**Last Updated**: October 28, 2025
