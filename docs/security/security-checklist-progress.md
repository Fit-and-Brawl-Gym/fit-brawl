# Security Controls Progress Checklist

Status of every checklist item based on the current Fit & Brawl codebase and the stated preferences (no HTTPS redirect or payment gateway while the site remains a demo).

## Legend
- ✅ Complete
- 🟡 In Progress / Partial
- ⏸️ Deferred (per preference or scope)
- ❌ Not Started

## 1. Authentication & Accounts
| Control | Priority | Status | Notes |
| --- | --- | --- | --- |
| Enforce strong passwords | Critical | ✅ | Centralized password policy (12+ chars, mixed classes, no spaces) enforced on signup, reset, and API endpoints. |
| Password hashing | Critical | ✅ | Uses PHP `password_hash`/bcrypt everywhere credentials are stored. |
| Rate-limit login attempts | Critical | ✅ | 5 attempts / 15 minutes with automatic lockout messaging. |
| Multi-Factor Authentication (MFA) | Critical | ⏸️ | Deferred until after demo approval. |
| Account lockout & notification | High | ✅ | Lockouts surface inline countdown/alert messaging and now send email alerts via `sendAccountLockNotification()`. |
| Session management | Critical | ✅ | `SessionManager` enforces secure, HttpOnly cookies and idle/absolute timeouts. |
| Single sign-out / revoke tokens | High | ❌ | No UI/API for device/session revocation yet. |
| Email verification on signup | High | ✅ | Verification token required before access. |
| Password strength meter & UX | Medium | ✅ | Real-time strength guidance on signup/change-password forms. |

## 2. Authorization & Access Control
| Control | Priority | Status | Notes |
| --- | --- | --- | --- |
| Role-based access control (RBAC) | Critical | 🟡 | Member/admin/trainer roles enforced; finance role not yet implemented. |
| Least privilege principle | Critical | 🟡 | Admin areas segmented but requires further audit per feature. |
| Server-side authorization checks | Critical | 🟡 | Key pages verify role/session; needs comprehensive review for every endpoint. |
| Admin IP allowlist & MFA | High | ⏸️ | Deferred for demo deployment. |
| Audit trails for admin actions | High | 🟡 | `logAction` helper writes to `admin_logs`; coverage limited to select actions. |

## 3. Payment Security *(Deferred for demo)*
All payment-gateway tasks are ⏸️ until a real processor is approved: gateway integration, PCI scope, tokenization, webhook validation, fraud tooling, and secure receipts.

## 4. Transport & Network Security
| Control | Priority | Status | Notes |
| --- | --- | --- | --- |
| HTTPS enforced everywhere | Critical | ⏸️ | Local/demo environment only; enable once hosted. |
| Redirect HTTP → HTTPS | Critical | ⏸️ | Same as above. |
| Use secure headers | High | ✅ | Global headers include X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, COOP. |
| Content Security Policy (CSP) | High | ✅ | Baseline CSP applied site-wide via `security_headers.php`. |
| WAF / CDN | Medium | ❌ | Not configured. |

## 5. Input Validation & Common Web Attacks
| Control | Priority | Status | Notes |
| --- | --- | --- | --- |
| Server-side validation & sanitization | Critical | 🟡 | Core forms validate (signup, profile updates, payments); still need global middleware for all inputs. |
| Prevent SQL injection | Critical | 🟡 | Majority of queries use prepared statements; admin contact API now parameterized, remaining legacy endpoints queued. |
| Prevent XSS | Critical | 🟡 | CSP + `htmlspecialchars` in key templates; additional output contexts need review. |
| CSRF protection | Critical | 🟡 | Tokens on auth/member flows plus admin contact actions (mark/read/delete/reply); extend to other admin APIs next. |
| Avoid open redirects | High | ❌ | No centralized validation yet. |
| Prevent clickjacking | High | ✅ | `X-Frame-Options: DENY` and CSP `frame-ancestors 'none'`. |

## 6. API Security
| Control | Priority | Status | Notes |
| --- | --- | --- | --- |
| Authentication on APIs | Critical | 🟡 | Session-based checks on PHP endpoints; no tokenized API yet. |
| Scope & rate limits | High | 🟡 | Login plus booking/cancellation APIs enforce per-user limits with shared countdown UX; expand to admin APIs next. |
| Rate limit headers | Medium | 🟡 | Booking/cancellation APIs now emit `X-RateLimit-*` and `Retry-After`; extend to remaining endpoints. |
| API keys for internal services | Medium | ❌ | Not applicable yet. |
| Input validation & output encoding | Critical | 🟡 | Mirrors web validation; needs systematic middleware. |
| Versioning & deprecation policy | Low | ❌ | Single-version API only. |

## 7. Data Protection (At Rest & In Transit)
| Control | Priority | Status | Notes |
| --- | --- | --- | --- |
| Encrypt sensitive data at rest | High | ❌ | Database relies on host defaults. |
| Use environment variables for secrets | Critical | 🟡 | `.env` loader in place; secrets rotation process not automated. |
| Rotate keys and credentials | High | ❌ | Manual process not defined. |
| Backups & encrypted backups | High | ❌ | No automated backup plan documented. |
| Limit data retention | Medium | ❌ | Policies not defined. |
| Allow data export & deletion | Medium | ❌ | Not implemented. |

## 8. Hosting & Infrastructure
Mostly not started: server hardening, managed DB, network segmentation, network least privilege, patch management, and read-only file systems remain open items pending deployment planning.

## 9. Dev Practices & CI/CD
Secure CI/CD, enforced reviews, SAST, dependency scanning, dedicated environments, and IaC reviews are ❌ (not yet established for this repo).

## 10. Monitoring, Logging & Alerting
Centralized logging, alerting, SIEM, uptime monitoring, and log retention are ❌; only basic PHP error logs exist.

## 11. Testing & Assessments
Automated vuln scans, pen tests, security regression suites, and dependency vuln jobs are ❌.

## 12. Incident Response & Recovery
Incident response plan, contact lists, breach notification process, playbooks, and DR/RTO definitions are ❌.

## 13. Compliance, Privacy & Legal
Privacy policy/ToS, DPAs, GDPR/CCPA features, and PCI documentation are ❌ (outside current demo scope).

## 14. UX / User-Facing Security Features
Account activity dashboards, login/payment notifications, and privacy settings are ❌. Easy account recovery is 🟡 (OTP-based reset exists but lacks CAPTCHA/rate limiting beyond OTP guardrails).

## 15. Backup, Storage & Secrets
Secrets vaults, encrypted backups, and hardened file uploads are ❌ (uploads currently validated for type/size only).

## 16. Third-party Integrations
Third-party script review, scoped credentials, and monitoring are ❌.

## 17. Developer & Team Security Hygiene
Security training, phishing resistance, and least-privilege dev tooling are ❌ (team processes not defined in repo).

## 18. Implementation Checklist (Quick Runbook)
| Task | Status | Notes |
| --- | --- | --- |
| Deploy HTTPS + HSTS | ⏸️ | Waiting for production hosting. |
| Integrate payment gateway + webhooks | ⏸️ | Deferred for demo scope. |
| Implement secure password storage + MFA | 🟡 | Password hashing done; MFA deferred. |
| Harden servers & DB access | ❌ | Pending infrastructure plan. |
| Add CSP & security headers | ✅ | Completed via global header helper. |
| Add rate limiting for auth/payment | ✅ | Login covered; extend to other endpoints later. |
| Centralized logging & alerting | ❌ | Not implemented. |
| Run SAST + dependency scans | ❌ | No CI pipeline yet. |
| Schedule pen test | ❌ | Not scheduled. |
| Incident response & backup restore test | ❌ | Not documented. |

## Recommended Next Steps
1. Extend CSRF middleware and validation to profile edits, bookings, and admin approval forms.
2. Draft lightweight logging/alerting plan (even simple file-based logs + cron review) and document incident contacts.
3. Decide on MFA approach for admins (TOTP or email OTP upgrade) once deployment scope is approved.
4. Capture open infrastructure items (backups, HTTPS, server hardening) in the deployment checklist so they are ready when the site moves beyond demo status.

> Continue checking items off here as new batches land so stakeholders can see security coverage at a glance.
