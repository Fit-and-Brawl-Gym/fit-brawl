# Fit & Brawl Gym Security Features: Technical Documentation

**Date:** November 21, 2025
**Version:** 2.0

---

## Introduction

This document provides a technical overview of all security features implemented in the Fit & Brawl Gym Management System. It explains how each feature works, where it is located in the codebase, and how it protects the application against common threats.

---

## 1. Authentication & Session Management

**How it works:**
- Passwords are hashed using Bcrypt before storage (`includes/init.php`, user registration/login scripts).
- Password strength is enforced (minimum 12 characters, mixed types) in registration (`public/php/sign-up.php`).
- Password history is tracked to prevent reuse (last 5 passwords).
- Account lockout after 5 failed login attempts within 15 minutes (`includes/session_manager.php`).
- Rate limiting on login/signup via `includes/rate_limiter.php`.
- Sessions expire after 15 minutes idle or 10 hours absolute (`includes/session_manager.php`).
- Session fixation is prevented by regenerating session IDs on login.
- Secure session cookies: HttpOnly, Secure, SameSite set in `includes/session_manager.php`.
- Multi-session management allows users to revoke sessions (`public/php/user_profile.php`).

**Key files:**
- `includes/session_manager.php`
- `includes/rate_limiter.php`
- `public/php/sign-up.php`, `public/php/login.php`

---

## 2. Authorization & Access Control

**How it works:**
- Role-based access control (RBAC) is enforced in all entry points (`includes/init.php`, `ApiSecurityMiddleware`).
- Server-side checks ensure users only access their own data.
- Admin actions require admin role (`public/php/admin/*`).
- API endpoints check user roles and permissions (`includes/api_security_middleware.php`).

**Key files:**
- `includes/init.php`
- `includes/api_security_middleware.php`
- `public/php/admin/*`

---

## 3. Input Validation & XSS Prevention

**How it works:**
- All user input is validated server-side using `InputValidator` (`includes/input_validator.php`).
- SQL injection is prevented by using prepared statements throughout the codebase.
- XSS is prevented by escaping output with `htmlspecialchars` and enforcing Content Security Policy (CSP) headers (`includes/security_headers.php`).
- CSRF protection is implemented for all state-changing operations (`includes/csrf_protection.php`).
- CSP nonces are deployed on critical pages to prevent inline script attacks (`includes/csp_nonce.php`).
- Uploaded images are reprocessed to strip EXIF metadata (`includes/file_upload_security.php`).

**Key files:**
- `includes/input_validator.php`
- `includes/security_headers.php`
- `includes/csp_nonce.php`
- `includes/csrf_protection.php`
- `includes/file_upload_security.php`

---

## 4. Security Headers

**How it works:**
- Security headers are set globally via `includes/security_headers.php`.
- Headers include X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, Cross-Origin-Opener-Policy, Content-Security-Policy (with nonce), and HSTS (when HTTPS is enabled).

**Key files:**
- `includes/security_headers.php`

---

## 5. File Upload Security

**How it works:**
- Uploaded files are validated for MIME type and extension (`includes/file_upload_security.php`).
- File size limits are enforced (2MB for images, 10MB for receipts).
- Images are reprocessed to remove metadata and prevent decompression bombs.
- Secure filenames are generated and upload directories are protected with `.htaccess`.

**Key files:**
- `includes/file_upload_security.php`
- `.htaccess` in upload directories

---

## 6. API Security

**How it works:**
- APIs require session-based authentication and role-based authorization (`includes/api_security_middleware.php`).
- Per-endpoint rate limiting is enforced (`includes/api_rate_limiter.php`).
- All API inputs are validated and outputs are encoded as safe JSON.
- CSRF tokens are required for write operations.

**Key files:**
- `includes/api_security_middleware.php`
- `includes/api_rate_limiter.php`
- `includes/csrf_protection.php`

---

## 7. Logging & Monitoring

**How it works:**
- Centralized logging system records security events, admin actions, and failed logins (`includes/activity_logger.php`, `includes/security_event_logger.php`).
- Logs are structured in JSON for easy analysis.
- Automated log rotation scripts keep logs manageable (`scripts/rotate-logs.sh`, `scripts/rotate-logs.bat`, `scripts/rotate_logs.php`).
- 30-day log retention policy is enforced.

**Key files:**
- `includes/activity_logger.php`
- `includes/security_event_logger.php`
- `scripts/rotate-logs.sh`, `scripts/rotate-logs.bat`, `scripts/rotate_logs.php`

---

## 8. Error Handling

**How it works:**
- Users see generic error messages; detailed errors are logged for debugging.
- Stack traces are hidden from users.
- Custom error pages for 404 and 500 errors are branded and secure (`public/php/error/404.php`, `public/php/error/500.php`).
- Error sanitization prevents information leaks.

**Key files:**
- `public/php/error/404.php`
- `public/php/error/500.php`
- `public/css/pages/error.css`

---

## 9. Data Encryption at Rest

**How it works:**
- Sensitive data (e.g., email addresses) is encrypted using AES-256-GCM (`includes/encryption.php`).
- Encrypted data is stored in dedicated columns (e.g., `users.email_encrypted`).
- Encryption keys are generated and managed securely (`generate_encryption_key.php`).
- Automated tests verify encryption/decryption (`test_encryption.php`).
- Migration scripts convert existing data to encrypted format (`migrate_encrypt_data.php`).

**Key files:**
- `includes/encryption.php`
- `generate_encryption_key.php`
- `test_encryption.php`, `test_user.php`, `test_stats.php`
- `migrate_encrypt_data.php`

---

## 10. Rate Limiting

**How it works:**
- Rate limiting is applied to login, signup, and API endpoints to prevent brute force and abuse (`includes/rate_limiter.php`, `includes/api_rate_limiter.php`).
- Limits are configurable per endpoint and user.

**Key files:**
- `includes/rate_limiter.php`
- `includes/api_rate_limiter.php`

---

## 11. Security Infrastructure Overview

**Location:** All security infrastructure files are in the `includes/` directory. Helper scripts are in the project root and `scripts/`.

**Directory Map:**
```
includes/
├── security_headers.php
├── csp_nonce.php
├── csrf_protection.php
├── session_manager.php
├── rate_limiter.php
├── input_validator.php
├── file_upload_security.php
├── encryption.php
├── activity_logger.php
├── security_event_logger.php
└── centralized_logger.php
scripts/
├── rotate-logs.sh
├── rotate-logs.bat
└── rotate_logs.php
```

---

## 12. Security Testing & Documentation

- Automated tests for encryption and input validation (`test_encryption.php`, `test_user.php`, `test_stats.php`).
- Security documentation in `docs/security/` (e.g., `AES-ENCRYPTION-GUIDE.md`).

---

## 13. Remaining & Optional Features

- Enable HTTPS/TLS and server hardening (production checklist).
- Database connection encryption and IP whitelisting for admin.
- Antivirus scanning for uploads, cryptographic audit log signing, SIEM integration.

---

## Conclusion

Fit & Brawl Gym Management System implements comprehensive security controls across authentication, authorization, input validation, encryption, logging, and error handling. All critical features are documented and located in the `includes/` directory, with helper scripts and documentation available for maintenance and future improvements.

---

*For further details, see the full security summary in `docs/security/SECURITY-SUMMARY.md`.*
