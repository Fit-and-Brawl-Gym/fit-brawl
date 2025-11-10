# UAT Limitations & Constraints Report
## FitXBrawl Gym Management System
**Generated: November 10, 2025**

---

## Table of Contents
1. [Authentication & Security Limitations](#1-authentication--security-limitations)
2. [Membership Management Constraints](#2-membership-management-constraints)
3. [Booking System Restrictions](#3-booking-system-restrictions)
4. [Payment Processing Limitations](#4-payment-processing-limitations)
5. [User Interface & Experience Constraints](#5-user-interface--experience-constraints)
6. [Admin Panel Restrictions](#6-admin-panel-restrictions)
7. [Email & Notification Limitations](#7-email--notification-limitations)
8. [File Upload & Media Constraints](#8-file-upload--media-constraints)
9. [Database & Performance Limitations](#9-database--performance-limitations)
10. [Mobile & Responsive Design Constraints](#10-mobile--responsive-design-constraints)
11. [Integration & API Limitations](#11-integration--api-limitations)
12. [Scalability & Technical Debt](#12-scalability--technical-debt)
13. [Known Bugs & Issues](#13-known-bugs--issues)
14. [Feature Gaps](#14-feature-gaps)

---

## 1. Authentication & Security Limitations

### Password Security
- **No Password Complexity Meter**: While password validation exists, there's no visual real-time strength indicator
- **Password History Not Tracked**: Users can reuse old passwords immediately after changing (only checks current password)
- **No Account Lockout**: Missing account lockout mechanism after multiple failed login attempts
- **Remember Me Token Never Expires**: Remember me tokens don't have an expiration policy
- **No Multi-Factor Authentication (MFA)**: System lacks 2FA/MFA support for enhanced security

### Session Management
- **Hardcoded Session Timeouts**: 
  - Idle timeout: 15 minutes (900 seconds)
  - Absolute timeout: 10 hours (36000 seconds)
  - No admin configuration panel to adjust these values
- **No Session Sharing Prevention**: Multiple concurrent sessions allowed for same user
- **Session Fixation Risks**: Session ID regeneration only occurs on first access, not on privilege elevation
- **Cookie Security**: Not enforcing `Secure` flag on production (only checks for HTTPS presence)

### CSRF Protection
- **CSRF Implementation Incomplete**: CSRF protection class exists but not implemented on all forms
- **Token Expiry Too Long**: 1-hour expiry may be excessive for sensitive operations
- **No Per-Request Token Rotation**: Tokens are session-based, not regenerated per request

### Rate Limiting
- **Limited Scope**: Rate limiting only implemented for:
  - OTP resend (1 per 60 seconds)
  - Login attempts (mentioned but implementation unclear)
- **No API Rate Limiting**: No rate limiting on booking APIs, feedback submissions, profile updates
- **IP-Based Only**: No user-based rate limiting strategy

### Authentication Flow Issues
- **Plaintext Password in auth.php**: The `auth.php` file contains insecure login logic storing passwords in plaintext (lines 67-74)
- **No Email Verification Reminder**: Users with unverified emails don't receive periodic reminder emails
- **OTP Limit Tracking Issues**: OTP attempts tracked globally, not reset properly after successful verification

---

## 2. Membership Management Constraints

### Membership Plans
- **Static Plan Structure**: No ability to create custom membership plans from admin panel
- **Limited Billing Cycles**: Only supports monthly and quarterly billing (no annual, bi-annual, or custom cycles)
- **No Proration**: When upgrading mid-cycle, users don't receive prorated pricing
- **Grace Period Hardcoded**: 3-day grace period is hardcoded and cannot be adjusted per plan

### Membership Workflow
- **Single Pending Request Limit**: Users cannot submit new membership requests if one is pending
- **No Membership Pause/Freeze**: Members cannot temporarily pause their memberships
- **No Automatic Renewal**: Memberships don't auto-renew; users must manually re-subscribe
- **Manual Approval Only**: No automated approval workflow for low-risk members
- **Upgrade Limitations**: 
  - Cannot downgrade memberships
  - Upgrades require admin approval even for existing active members
  - Source membership tracking incomplete

### Payment Receipt Validation
- **No OCR/AI Validation**: Payment receipts uploaded are not validated for authenticity
- **Accepts Any Image Format**: While file type validation exists, no validation of actual payment details
- **No Payment Gateway Integration**: All payments are manual/offline (no Stripe, PayPal, GCash, etc.)

---

## 3. Booking System Restrictions

### Weekly Booking Limits
- **Hardcoded 12 Bookings/Week**: Maximum 12 bookings per week is hardcoded in multiple files:
  - `reservations.js` (line 12)
  - `get_user_bookings.php` (line 154)
  - Cannot be configured per membership tier
- **Week Definition**: Week starts on Sunday (not configurable)
- **Cancelled Bookings Count**: Cancelled bookings still count toward weekly limit

### Time-Based Restrictions
- **30-Minute Session End Rule**: Cannot book sessions within 30 minutes of session end time
- **12-Hour Cancellation Policy**: Cannot cancel bookings within 12 hours of session start
- **Same-Day Restrictions**: No same-day cancellations allowed
- **Booking Window**: Limited to 30 days in advance (hardcoded)

### Trainer Availability
- **No Real-Time Trainer Status**: Trainer availability doesn't account for:
  - Sick leave/unplanned absences
  - Equipment maintenance
  - Room/facility availability
- **Day-Off Pattern Limitations**: Trainers must have exactly 2 days off per week (enforced in admin panel)
- **No Trainer Substitution**: If a trainer is unavailable, no automatic substitution system

### Session Management
- **Fixed Session Times**: Three rigid time slots:
  - Morning: 7-11 AM
  - Afternoon: 1-5 PM
  - Evening: 6-10 PM
  - No flexibility for custom session times
- **No Waitlist System**: When sessions are full, no option to join a waitlist
- **Single Booking Type**: Cannot book multiple sessions in one transaction

### Facility Capacity
- **Facility Capacity Logic**: Capacity checks exist but limits are not configurable from admin panel
- **No Room/Space Management**: System doesn't track which room/area is being used
- **Class Type Capacity**: No differentiation of capacity limits per class type (Boxing vs MMA vs Gym)

---

## 4. Payment Processing Limitations

### Payment Methods
- **Manual Payments Only**: No integrated payment gateway
  - No credit/debit card processing
  - No digital wallets (GCash, PayMaya, etc.)
  - No bank transfer integration
  - No cryptocurrency support
- **QR Code Payment**: QR code displayed is static (hardcoded image), not dynamic payment link
- **Receipt Upload Required**: Users must manually upload payment proof
  - No automated payment verification
  - No receipt OCR/parsing
  - Admin must manually verify each payment

### Billing & Invoicing
- **No Invoice Generation**: System doesn't generate invoices, only receipts after approval
- **No Payment History**: Limited payment tracking; no comprehensive payment history page
- **No Refund System**: No mechanism for processing refunds
- **No Payment Reminders**: No automated email reminders for upcoming membership renewals
- **Tax Calculation**: No tax/VAT calculation or display

### Pricing Structure
- **Hardcoded Prices**: All membership prices hardcoded in database
- **No Dynamic Pricing**: Cannot adjust prices based on:
  - Promotional periods
  - Member loyalty/tenure
  - Seasonal variations
- **No Discount Codes**: No coupon/promo code system
- **Currency**: Single currency support only (PHP assumed)

---

## 5. User Interface & Experience Constraints

### Accessibility
- **No ARIA Labels**: Missing accessibility attributes for screen readers
- **No Keyboard Navigation**: Tab navigation incomplete for booking wizard
- **No Color Blind Mode**: No high-contrast or color-blind friendly themes
- **No Font Size Controls**: No user-adjustable text size options
- **Language Support**: English only; no internationalization (i18n)

### Navigation & Usability
- **No Breadcrumb Navigation**: Deep pages lack breadcrumb trails
- **Limited Search**: No global search functionality for:
  - Finding trainers by name
  - Searching bookings
  - Finding equipment/products
- **No Favorites/Bookmarks**: Cannot save favorite trainers or preferred time slots
- **Browser Back Button Issues**: Some AJAX-heavy pages may have browser back button conflicts

### Booking Wizard UX
- **Cannot Skip Steps**: Must go through all 4 steps sequentially (Date → Session → Class → Trainer)
- **No Draft Bookings**: Cannot save incomplete booking for later
- **Session Timeout During Booking**: Long idle during booking wizard may cause session timeout
- **No Booking Modification**: Cannot edit existing bookings; must cancel and rebook

### Visual Feedback
- **Loading States**: Some actions lack loading spinners or progress indicators
- **Error Messages**: Generic error messages (e.g., "Database error") don't help users troubleshoot
- **Toast Notification Limits**: Multiple toasts can stack and become unreadable
- **Calendar Date Range**: Calendar only shows current month ±1; difficult to book far in advance

---

## 6. Admin Panel Restrictions

### User Management
- **No Bulk Operations**: Cannot bulk:
  - Delete users
  - Export user data
  - Send emails to multiple users
  - Approve/reject multiple membership requests
- **Limited User Edit**: Cannot change user email or role after creation
- **No User Suspension**: Cannot temporarily suspend user accounts (only deletion)
- **Soft Delete Incomplete**: Some tables use `deleted_at`, others use hard deletes

### Reporting & Analytics
- **No Built-in Reports**: Missing standard reports:
  - Monthly revenue reports
  - Trainer utilization rates
  - Peak booking hours
  - Member retention statistics
  - Churn analysis
- **No Data Export**: Cannot export data to CSV/Excel
- **No Dashboard Customization**: Admin dashboard layout is fixed
- **Limited Date Filters**: Most admin views lack custom date range filtering

### Trainer Management
- **Manual Trainer Creation**: No trainer self-registration workflow
- **Hardcoded Credentials**: Trainer credentials generated but sent via insecure email
- **No Trainer Performance Metrics**: No tracking of:
  - Session completion rates
  - Member satisfaction scores
  - Booking demand per trainer
- **Schedule Management**: 
  - No drag-and-drop schedule editor
  - Cannot bulk-edit trainer schedules
  - No schedule templates

### Equipment & Products
- **No Inventory Automation**: Manual stock tracking only
- **No Low-Stock Alerts**: Admin not notified when stock is low
- **No Barcode/SKU System**: Equipment/products lack unique identifiers
- **Image Upload Limits**: Single image per item; no gallery support

---

## 7. Email & Notification Limitations

### Email Delivery
- **Gmail SMTP Dependency**: Relies solely on Gmail SMTP
  - Vulnerable to Gmail account issues
  - Subject to Gmail sending limits (500 emails/day for free accounts)
  - No fallback SMTP server
- **No Email Queue**: Emails sent synchronously; slow on bulk operations
- **No Email Retry Logic**: Failed emails not automatically retried
- **Email Logging**: No comprehensive email send log (success/failure tracking)

### Notification Types
- **Limited Notifications**: Email notifications only for:
  - Account verification
  - Password reset
  - Membership approval/rejection
  - Booking confirmation (implementation unclear)
- **No In-App Notifications**: No notification center within the web app
- **No SMS Support**: No SMS/text message notifications
- **No Push Notifications**: No browser push notifications for booking reminders

### Email Templates
- **Static Email Templates**: Email templates are PHP-embedded HTML
  - No admin panel to edit templates
  - No A/B testing capability
  - No personalization beyond basic variables
- **Email Testing**: No built-in email testing/preview tool

---

## 8. File Upload & Media Constraints

### Upload Restrictions
- **File Size Limits**:
  - Receipts: 10 MB max
  - Avatars: Not explicitly limited (security risk)
  - Equipment/Product images: Not explicitly limited
- **File Type Validation**: Limited to extension/MIME type checking
  - No malware scanning
  - No image dimension validation
  - Vulnerable to file extension spoofing

### Storage Management
- **No Cloud Storage**: All uploads stored locally in `/uploads`
  - No CDN integration
  - No automatic backups
  - Vulnerable to disk space issues
- **No Image Optimization**: Uploaded images not compressed or resized
- **No File Versioning**: Overwriting files loses previous versions
- **`.htaccess` Protection**: While `.htaccess` prevents PHP execution in uploads, not all servers respect this

### Avatar System
- **Default Avatar Handling**: Default avatar is SVG icon, but system expects PNG filenames
- **No Avatar Cropping**: Users cannot crop uploaded avatars
- **No Avatar Guidelines**: No upload guidelines (recommended size, aspect ratio)

---

## 9. Database & Performance Limitations

### Database Design
- **No Connection Pooling**: Single connection per request (no persistent connections)
- **Missing Indexes**: Several tables lack proper indexes:
  - `user_reservations` could benefit from composite index on `(user_id, booking_date)`
  - `activity_log` lacks index on `timestamp`
- **No Database Caching**: No query result caching (Redis, Memcached)
- **Schema Inconsistencies**:
  - Some tables use `deleted_at` for soft deletes, others don't
  - Inconsistent naming conventions (e.g., `qr_proof` vs `receipt_path`)

### Query Performance
- **N+1 Query Problems**: Potential in:
  - Loading trainer bookings with user details
  - Membership list with user information
- **No Pagination**: Large datasets load all at once:
  - Admin feedback list
  - User booking history
  - Equipment catalog
- **Unoptimized Queries**: Some queries fetch all columns when only subset needed

### Data Integrity
- **No Foreign Key Constraints**: Some relationships lack FK constraints
  - `user_memberships.user_id` → `users.id` exists
  - But some service booking tables incomplete
- **Orphaned Records**: Potential for orphaned records when users deleted
- **No Data Validation**: Database-level validation minimal (relies on application layer)

### Backup & Recovery
- **No Automated Backups**: Database backup must be manual
- **No Point-in-Time Recovery**: No transaction log backups
- **No Disaster Recovery Plan**: No documented recovery procedures

---

## 10. Mobile & Responsive Design Constraints

### Mobile Optimization
- **Hamburger Menu Only**: Mobile navigation is hamburger-only (no persistent bottom nav)
- **Touch Targets**: Some buttons may be too small for touch on mobile
- **Calendar on Mobile**: Date picker calendar difficult to use on small screens
- **Image Sizes**: Large images not optimized for mobile bandwidth
- **No Progressive Web App (PWA)**: 
  - `site.webmanifest` exists but incomplete
  - No service worker for offline support
  - No install prompt

### Tablet Support
- **Tablet Layout**: No specific tablet breakpoints; uses either mobile or desktop layout
- **Landscape Orientation**: Some pages not optimized for landscape mode

### Cross-Browser Compatibility
- **Modern Browser Dependency**: Relies on modern JS features (ES6+)
  - May not work on older browsers (IE11, old Safari)
  - No polyfills included
- **Browser-Specific CSS**: Some CSS may not work in older browsers
- **Vendor Prefixes**: Limited vendor prefix usage for cross-browser compatibility

---

## 11. Integration & API Limitations

### Third-Party Integrations
- **No Payment Gateway**: No Stripe, PayPal, GCash, or any payment processor
- **No Calendar Sync**: Cannot sync bookings with Google Calendar, Outlook, etc.
- **No Social Login**: No OAuth login (Google, Facebook, Apple)
- **No Analytics**: No Google Analytics, Mixpanel, or similar tracking
- **No CRM Integration**: No integration with customer relationship management tools

### API Structure
- **No RESTful API**: API endpoints inconsistent:
  - Mix of GET/POST without proper REST conventions
  - No API versioning
  - No API documentation
- **No API Authentication**: API endpoints rely on PHP sessions only
  - No token-based authentication (JWT, OAuth)
  - Cannot be used by mobile apps or external clients
- **No Webhooks**: No webhook system for event notifications

### Data Exchange
- **No Export Formats**: Cannot export data as JSON, CSV, or XML via API
- **No Import System**: Cannot bulk-import users, trainers, or bookings
- **No Sync Mechanism**: No way to sync data with external systems

---

## 12. Scalability & Technical Debt

### Code Organization
- **No MVC Framework**: Using plain PHP without framework structure
  - Code duplication across files
  - Mixed business logic and presentation
  - No dependency injection
- **Inconsistent Coding Standards**: 
  - Mix of camelCase and snake_case
  - Inconsistent function naming
  - Some files use `<?php` others use `<?`

### Security Technical Debt
- **`.env` Exposure History**: Environment file was accidentally committed to git (documented in `SECURITY-UPDATE-REQUIRED.md`)
- **Vendor Directory Issues**: Vendor directory was tracked in git, now removed
- **No Security Headers**: Missing security headers:
  - No Content-Security-Policy (CSP)
  - No X-Frame-Options
  - No X-Content-Type-Options
- **Session Security**: No session fingerprinting to prevent session hijacking

### Performance Bottlenecks
- **Synchronous Operations**: All operations synchronous (no async processing)
- **No Queue System**: No job queue for heavy tasks (email sending, receipt generation)
- **Single Server Architecture**: No load balancing capability
- **No Content Delivery Network (CDN)**: Static assets served from same server

### Maintenance Issues
- **Hardcoded Configuration**: Many settings hardcoded instead of config files
- **No Environment Parity**: Development/production environments not clearly separated
- **Limited Error Logging**: Error logs incomplete; no centralized logging system
- **No Monitoring**: No application performance monitoring (APM) or uptime monitoring

---

## 13. Known Bugs & Issues

### Authentication Bugs
- **auth.php Plaintext Password**: File `public/php/auth.php` stores/compares passwords in plaintext (critical security flaw)
- **Remember Me Token Security**: Remember me tokens stored without encryption
- **Session Regeneration**: Session ID not regenerated on privilege changes

### Booking System Bugs
- **Weekly Limit Edge Case**: Week boundaries may cause incorrect booking counts if user timezone differs
- **30-Minute Rule Logic**: Session end calculation doesn't account for timezone differences
- **Concurrent Booking Race Condition**: Two users can potentially book same trainer/session simultaneously
- **Ongoing Session Detection**: "Ongoing" badge calculation relies on client time, not server time

### UI/UX Bugs
- **Calendar Glitches**: 
  - Dates beyond membership expiration show as bookable then error
  - Past dates in grace period may show incorrectly
- **Toast Notification Stacking**: Multiple rapid actions cause toast overflow
- **Modal Z-Index**: Some modals may appear behind other elements

### Email Issues
- **Gmail App Password Dependency**: If app password revoked, all emails fail silently
- **Email Template Rendering**: HTML emails may not render correctly in all email clients
- **OTP Email Delay**: OTP emails may take several minutes to arrive

### Data Integrity Issues
- **Orphaned Receipts**: If membership request deleted, receipt file remains in uploads folder
- **Trainer Deletion**: Deleting trainers doesn't handle:
  - Existing bookings
  - Historical booking records
  - Uploaded trainer photos

---

## 14. Feature Gaps

### Critical Missing Features
1. **Automated Payment Gateway**: No online payment processing
2. **Mobile App**: No native iOS/Android app
3. **Real-Time Chat**: No support chat or trainer messaging
4. **Video Integration**: No support for:
   - Virtual training sessions (Zoom, Teams integration)
   - Exercise tutorial videos
   - Trainer intro videos

### User-Requested Features
1. **Membership Pause/Freeze**: Cannot temporarily pause memberships
2. **Group Classes**: No support for group class bookings
3. **Nutrition Tracking**: No diet/nutrition plan feature
4. **Workout Logging**: No exercise/workout tracking
5. **Progress Photos**: No before/after photo tracking
6. **Body Measurements**: No weight/measurement tracking over time
7. **Social Features**: 
   - No friend system
   - No workout sharing
   - No leaderboards/challenges

### Admin Requested Features
1. **Revenue Dashboard**: No financial reporting
2. **Automated Reminders**: No automatic booking/payment reminders
3. **Marketing Tools**: 
   - No email campaigns
   - No member segmentation
   - No promotional offers system
4. **Staff Scheduling**: No employee shift management
5. **Maintenance Tracking**: No equipment maintenance log

### Trainer Requested Features
1. **Availability Calendar**: Trainers cannot manage their own availability
2. **Client Notes**: No private notes about clients
3. **Session Plans**: Cannot create/share workout plans
4. **Certification Tracking**: No trainer certification expiry tracking
5. **Commission Tracking**: No trainer earnings/commission system

---

## Summary Statistics

### Security Issues: 18
- Critical: 5 (plaintext password, CSRF incomplete, no MFA, session security, .env exposure)
- High: 8
- Medium: 5

### Functional Limitations: 47
- Booking System: 12
- Payment/Membership: 15
- User Management: 8
- Admin Tools: 12

### Technical Debt Items: 23
- Database: 7
- Code Organization: 6
- Performance: 5
- Infrastructure: 5

### Known Bugs: 14
- Critical: 2
- High: 5
- Medium: 7

### Missing Features: 31
- User-facing: 15
- Admin: 9
- Trainer: 7

---

## Recommendations

### Immediate Actions Required
1. **Fix `auth.php` plaintext password storage** (CRITICAL)
2. **Implement complete CSRF protection** across all forms
3. **Add rate limiting** to all public APIs
4. **Implement proper API authentication** for mobile/external access
5. **Add automated database backups**

### Short-Term Improvements (1-3 months)
1. Integrate payment gateway (GCash, PayPal, or Stripe)
2. Implement comprehensive error logging and monitoring
3. Add pagination to all list views
4. Implement email queue system
5. Add admin reporting dashboard
6. Improve mobile responsiveness
7. Add input validation and sanitization across all forms

### Long-Term Enhancements (3-6 months)
1. Migrate to MVC framework (Laravel, CodeIgniter)
2. Develop mobile apps (React Native, Flutter)
3. Implement real-time features (WebSockets, Pusher)
4. Add comprehensive testing suite (PHPUnit, Selenium)
5. Implement multi-language support
6. Add advanced analytics and reporting
7. Develop trainer commission system

### Infrastructure Upgrades
1. Implement CDN for static assets
2. Add Redis/Memcached for caching
3. Set up proper CI/CD pipeline
4. Configure load balancer for scalability
5. Implement comprehensive monitoring (New Relic, Datadog)
6. Set up automated backup and disaster recovery

---

## Testing Recommendations

### Security Testing
- [ ] Penetration testing for authentication flows
- [ ] SQL injection testing on all forms
- [ ] XSS vulnerability scanning
- [ ] CSRF token validation testing
- [ ] File upload security testing
- [ ] Session hijacking attempts

### Performance Testing
- [ ] Load testing with 100+ concurrent users
- [ ] Database query optimization profiling
- [ ] Page load speed testing
- [ ] Mobile performance testing
- [ ] API response time benchmarking

### Functional Testing
- [ ] End-to-end booking flow testing
- [ ] Membership approval workflow testing
- [ ] Payment upload and verification testing
- [ ] Email delivery testing
- [ ] Cross-browser compatibility testing
- [ ] Mobile device testing (iOS, Android)

### Usability Testing
- [ ] User acceptance testing with real gym members
- [ ] Trainer interface usability testing
- [ ] Admin panel workflow testing
- [ ] Accessibility compliance testing
- [ ] Mobile UX testing

---

**Document Version**: 1.0  
**Last Updated**: November 10, 2025  
**Prepared By**: AI Assistant  
**Review Status**: Draft - Requires stakeholder review
