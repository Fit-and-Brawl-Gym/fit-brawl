# Fit & Brawl Gym - System Architecture Diagram

## System Overview

Fit & Brawl is a comprehensive gym management system built with PHP, MySQL, and deployed on Google Cloud Platform.

---

## Architecture Diagram

```mermaid
graph TB
    subgraph "Client Layer"
        Browser[Web Browser]
        Mobile[Mobile Browser]
    end

    subgraph "Google Cloud Platform - App Engine"
        subgraph "Frontend Layer"
            PHP_Frontend[PHP Pages<br/>- Public Pages<br/>- Admin Panel<br/>- Trainer Dashboard]
            StaticAssets[Static Assets<br/>CSS/JS/Images]
        end

        subgraph "Application Layer"
            Auth[Authentication Module<br/>- Login/Logout<br/>- Session Management<br/>- Email Verification]
            UserMgmt[User Management<br/>- Registration<br/>- Profile Management<br/>- Role Management]
            Membership[Membership Module<br/>- Subscription Requests<br/>- Approval Workflow<br/>- Status Tracking]
            Booking[Reservation System<br/>- Trainer Booking<br/>- Session Management<br/>- Availability Checking]
            TrainerMgmt[Trainer Management<br/>- Trainer CRUD<br/>- Schedule Management<br/>- Day-off Management]
            Equipment[Equipment Management<br/>- Equipment Tracking<br/>- Status Updates]
            Products[Products Management<br/>- Inventory Management<br/>- Stock Tracking]
            Feedback[Feedback System<br/>- User Feedback<br/>- Voting System<br/>- Moderation]
            Contact[Contact/Inquiry System<br/>- Contact Forms<br/>- Message Management]
            Receipt[Receipt Generation<br/>- PDF Generation<br/>- QR Code Generation]
        end

        subgraph "Business Logic Layer"
            SessionMgr[Session Manager<br/>- Session Handling<br/>- Timeout Management]
            ActivityLog[Activity Logger<br/>- Admin Actions<br/>- Audit Trail]
            EmailService[Email Service<br/>- PHPMailer<br/>- SMTP Configuration]
            FileUpload[File Upload Handler<br/>- Security Validation<br/>- Cloud Storage Integration]
            RateLimiter[Rate Limiter<br/>- API Protection<br/>- Request Throttling]
        end

        subgraph "API Layer"
            REST_API[REST APIs<br/>- User APIs<br/>- Booking APIs<br/>- Admin APIs]
            AJAX_Handlers[AJAX Handlers<br/>- Real-time Updates<br/>- Form Submissions]
        end
    end

    subgraph "Data Layer"
        MySQL[(MySQL Database<br/>Cloud SQL<br/>- Users<br/>- Memberships<br/>- Reservations<br/>- Trainers<br/>- Equipment<br/>- Products<br/>- Feedback<br/>- Activity Logs)]
    end

    subgraph "External Services"
        GCS[Google Cloud Storage<br/>- Avatar Uploads<br/>- Receipt Images<br/>- Product Images<br/>- Equipment Images]
        SMTP[SMTP Server<br/>Gmail<br/>- Email Verification<br/>- Notifications]
        CloudRun[Cloud Run Service<br/>- Receipt Renderer<br/>- PDF Generation]
    end

    Browser --> PHP_Frontend
    Mobile --> PHP_Frontend
    Browser --> StaticAssets
    Mobile --> StaticAssets

    PHP_Frontend --> Auth
    PHP_Frontend --> UserMgmt
    PHP_Frontend --> Membership
    PHP_Frontend --> Booking
    PHP_Frontend --> TrainerMgmt
    PHP_Frontend --> Equipment
    PHP_Frontend --> Products
    PHP_Frontend --> Feedback
    PHP_Frontend --> Contact
    PHP_Frontend --> Receipt

    Auth --> SessionMgr
    UserMgmt --> SessionMgr
    Membership --> ActivityLog
    TrainerMgmt --> ActivityLog
    Equipment --> ActivityLog
    Products --> ActivityLog
    Feedback --> ActivityLog

    UserMgmt --> EmailService
    Membership --> EmailService
    Contact --> EmailService

    UserMgmt --> FileUpload
    Membership --> FileUpload
    Products --> FileUpload
    Equipment --> FileUpload

    REST_API --> UserMgmt
    REST_API --> Booking
    REST_API --> TrainerMgmt
    AJAX_Handlers --> REST_API

    Auth --> MySQL
    UserMgmt --> MySQL
    Membership --> MySQL
    Booking --> MySQL
    TrainerMgmt --> MySQL
    Equipment --> MySQL
    Products --> MySQL
    Feedback --> MySQL
    Contact --> MySQL
    ActivityLog --> MySQL

    FileUpload --> GCS
    Receipt --> CloudRun
    EmailService --> SMTP

    style Browser fill:#e1f5ff
    style Mobile fill:#e1f5ff
    style PHP_Frontend fill:#fff4e6
    style StaticAssets fill:#fff4e6
    style MySQL fill:#ffe6e6
    style GCS fill:#e6ffe6
    style SMTP fill:#e6ffe6
    style CloudRun fill:#e6ffe6
```

---

## Detailed Component Architecture

```mermaid
graph LR
    subgraph "User Roles"
        Member[Member<br/>- View Equipment<br/>- Book Sessions<br/>- Submit Feedback<br/>- Manage Profile]
        Trainer[Trainer<br/>- View Schedule<br/>- Manage Sessions<br/>- View Feedback]
        Admin[Admin<br/>- Full System Access<br/>- User Management<br/>- Approval Workflows<br/>- Analytics]
    end

    subgraph "Core Modules"
        Mod1[Authentication<br/>& Authorization]
        Mod2[Membership<br/>Management]
        Mod3[Reservation<br/>System]
        Mod4[Content<br/>Management]
    end

    subgraph "Database Tables"
        T1[users]
        T2[user_memberships]
        T3[user_reservations]
        T4[trainers]
        T5[equipment]
        T6[products]
        T7[feedback]
        T8[admin_logs]
    end

    Member --> Mod1
    Member --> Mod2
    Member --> Mod3
    Member --> Mod4

    Trainer --> Mod1
    Trainer --> Mod3
    Trainer --> Mod4

    Admin --> Mod1
    Admin --> Mod2
    Admin --> Mod3
    Admin --> Mod4

    Mod1 --> T1
    Mod2 --> T2
    Mod3 --> T3
    Mod3 --> T4
    Mod4 --> T5
    Mod4 --> T6
    Mod4 --> T7
    Mod4 --> T8
```

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla) |
| **Backend** | PHP 8.1 |
| **Database** | MySQL (Cloud SQL) |
| **Cloud Platform** | Google App Engine |
| **Storage** | Google Cloud Storage |
| **Email** | PHPMailer + SMTP (Gmail) |
| **PDF Generation** | Cloud Run Service |
| **Session Management** | PHP Sessions |
| **Security** | Password Hashing (bcrypt), CSRF Protection, Rate Limiting |

---

## Database Schema Overview

### Core Tables
1. **users** - User accounts (members, admins, trainers)
2. **user_memberships** - Membership subscriptions and approvals
3. **user_reservations** - Training session bookings
4. **trainers** - Trainer information and availability
5. **trainer_day_offs** - Weekly day-off schedule
6. **trainer_availability_blocks** - Admin-blocked dates/times
7. **equipment** - Gym equipment inventory
8. **products** - Product inventory (consumables)
9. **feedback** - User feedback and reviews
10. **feedback_votes** - Feedback voting system
11. **contact** - Contact form submissions
12. **admin_logs** - Audit trail for admin actions
13. **activity_log** - System activity tracking

---

## Authentication Flow

```mermaid
sequenceDiagram
    participant User
    participant Frontend
    participant AuthModule
    participant Database
    participant EmailService

    User->>Frontend: Register/Login
    Frontend->>AuthModule: Validate Credentials
    AuthModule->>Database: Check User
    Database-->>AuthModule: User Data
    AuthModule->>AuthModule: Verify Password
    alt New Registration
        AuthModule->>EmailService: Send Verification Email
        EmailService-->>User: Verification Link
        User->>Frontend: Click Verification Link
        Frontend->>AuthModule: Verify Token
        AuthModule->>Database: Update is_verified
    end
    AuthModule->>Frontend: Create Session
    Frontend-->>User: Redirect to Dashboard
```

---

## Booking Flow

```mermaid
sequenceDiagram
    participant Member
    participant BookingSystem
    participant Validation
    participant Database
    participant EmailService

    Member->>BookingSystem: Select Trainer/Date/Session
    BookingSystem->>Validation: Check Membership Status
    Validation->>Database: Query Active Membership
    Database-->>Validation: Membership Data
    Validation->>Validation: Check Availability
    Validation->>Database: Check Trainer Schedule
    Validation->>Database: Check Facility Capacity
    Validation->>Database: Check Weekly Limit
    alt All Validations Pass
        BookingSystem->>Database: Create Reservation
        BookingSystem->>EmailService: Send Confirmation
        BookingSystem-->>Member: Booking Confirmed
    else Validation Failed
        BookingSystem-->>Member: Error Message
    end
```

---

## File Upload Flow

```mermaid
sequenceDiagram
    participant User
    participant Frontend
    participant FileUpload
    participant Security
    participant GCS
    participant Database

    User->>Frontend: Upload File (Avatar/Receipt)
    Frontend->>FileUpload: Process Upload
    FileUpload->>Security: Validate File
    Security->>Security: Check File Type
    Security->>Security: Check File Size
    Security->>Security: Scan for Malware
    alt File Valid
        FileUpload->>GCS: Upload to Cloud Storage
        GCS-->>FileUpload: File URL
        FileUpload->>Database: Save File Path
        FileUpload-->>Frontend: Upload Success
        Frontend-->>User: File Uploaded
    else File Invalid
        FileUpload-->>Frontend: Error Message
        Frontend-->>User: Upload Failed
    end
```

---

## Deployment Architecture

```mermaid
graph TB
    subgraph "Development"
        LocalDev[Local Development<br/>XAMPP/WAMP<br/>MySQL Local]
    end

    subgraph "Production - Google Cloud Platform"
        AppEngine[App Engine<br/>PHP 8.1 Runtime<br/>Auto-scaling]
        CloudSQL[Cloud SQL<br/>MySQL Instance<br/>High Availability]
        CloudStorage[Cloud Storage<br/>File Storage<br/>CDN Enabled]
        CloudRun[Cloud Run<br/>Receipt Renderer<br/>Containerized]
    end

    subgraph "External Services"
        Gmail[Gmail SMTP<br/>Email Service]
        DNS[DNS Provider<br/>Domain Management]
    end

    LocalDev -.->|Deploy| AppEngine
    AppEngine --> CloudSQL
    AppEngine --> CloudStorage
    AppEngine --> CloudRun
    AppEngine --> Gmail
    DNS --> AppEngine

    style AppEngine fill:#4285f4,color:#fff
    style CloudSQL fill:#4285f4,color:#fff
    style CloudStorage fill:#4285f4,color:#fff
    style CloudRun fill:#4285f4,color:#fff
```

---

## Security Features

1. **Authentication**
   - Password hashing with bcrypt
   - Email verification
   - OTP (One-Time Password) support
   - Session management with timeout

2. **Authorization**
   - Role-based access control (RBAC)
   - Page-level access restrictions
   - API endpoint protection

3. **Data Protection**
   - Prepared statements (SQL injection prevention)
   - CSRF token protection
   - Input validation and sanitization
   - File upload security checks

4. **Rate Limiting**
   - API request throttling
   - Login attempt limiting
   - OTP request limiting

---

## API Endpoints

### Public APIs
- `/api/check_username.php` - Username availability check
- `/api/get_trainers.php` - Get available trainers
- `/api/get_available_dates.php` - Get available booking dates
- `/api/book_session.php` - Book a training session
- `/api/cancel_booking.php` - Cancel a booking
- `/api/contact_api.php` - Submit contact form
- `/api/feedback_vote.php` - Vote on feedback

### Admin APIs
- `/admin/api/admin_dashboard_api.php` - Dashboard statistics
- `/admin/api/admin_users_api.php` - User management
- `/admin/api/admin_subscriptions_api.php` - Subscription management
- `/admin/api/admin_reservations.php` - Reservation management
- `/admin/api/admin_equipment_api.php` - Equipment management
- `/admin/api/admin_products_api.php` - Product management
- `/admin/api/admin_feedback_api.php` - Feedback management
- `/admin/api/admin_contact_api.php` - Contact management

---

## Questions for Clarification

Before finalizing the diagram, I'd like to confirm:

1. **External Integrations**: Are there any third-party services (payment gateways, SMS services, etc.) that should be included?

2. **Caching Layer**: Is there any caching mechanism (Redis, Memcached) in use?

3. **CDN**: Is Cloud CDN used for static assets, or are they served directly from App Engine?

4. **Monitoring**: Are there any monitoring/logging services integrated (Cloud Monitoring, Cloud Logging)?

5. **Backup Strategy**: Should database backup/replication strategy be included?

6. **Mobile App**: Is there a native mobile app, or is it web-only?

7. **Load Balancing**: Are there multiple App Engine instances behind a load balancer?

8. **CI/CD**: Is there a CI/CD pipeline (Cloud Build) that should be documented?

Would you like me to:
- Add more detail to any specific component?
- Create separate diagrams for specific flows (e.g., membership approval workflow)?
- Include infrastructure diagrams (network, security groups)?
- Add data flow diagrams for specific processes?

