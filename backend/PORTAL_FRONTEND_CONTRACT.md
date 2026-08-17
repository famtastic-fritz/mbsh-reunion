# Portal Frontend ↔ Backend Contract Review

Status: backend lifecycle and the frontend contract are implemented and proven locally.

## Implemented wiring

| Frontend surface | Backend contract | Implemented behavior |
|---|---|---|
| Create account | `POST /portal/register.php` with `first_name`, `last_name`, `email`, `password` | Real form; promotional consent remains a separate post-verification preference. |
| Verify email | Branded `auth/verify.html?token=…` calls the token API | Real link, expired-link state, and secure session creation. |
| Login | `POST /portal/login.php`; then `GET /portal/session.php` | Real generic-error login using server-managed cookies. |
| Recovery | Forgot endpoint plus branded reset page | Real request and new-password states. |
| Dashboard boot | `GET /portal/session.php` supplies account and CSRF | Private UI hydrates only after authenticated session. |
| Profile | `GET/PATCH /portal/profile.php` | Profile reads are wired; RSVP/dinner writes remain deliberately disabled until a scoped endpoint exists. |
| Preferences | `GET/PATCH /portal/preferences.php` | Four optional preference fields wired; transactional notices remain independent. |
| Suggestions | `GET/POST /portal/suggestions.php` | Song and event forms map exact category/subject/message fields. |
| Media | `GET/POST /portal/media.php` | One-file uploads, title/year/caption, explicit publication consent, and per-file state. |
| Wallet | `GET /portal/tickets.php` | Owner-scoped tickets and rotating credentials only; no browser persistence. |
| Notifications | `GET/PATCH /portal/notifications.php` | Private inbox and read transition wired. |
| Logout | `POST /portal/logout.php` with CSRF | Server session ends before navigation. |

## Response and failure rules

- All API calls use same-origin cookies with `credentials: "same-origin"` and JSON unless the media endpoint requires multipart.
- Fetch `/portal/session.php` once at boot; keep its CSRF token only in memory.
- Handle `401` by clearing private UI and returning to login; handle `403 csrf_invalid` by refreshing session once; never retry mutations blindly.
- Registration and recovery display generic accepted messages. Login always displays a generic invalid-credentials message.
- Do not place verification, reset, or ticket credentials in analytics, error telemetry, local storage, URLs shared to third parties, or persistent DOM outside the active private view.
- A `409 ticket_not_active` at check-in is a duplicate/revoked warning for staff, not a successful second scan.

## Verified locally

`RUN_INTEGRATION=1 tests/backend/run.sh` provisions an ephemeral MariaDB container and mock Resend endpoint, then exercises registration, verification, session, preferences, suggestions, recovery, login/logout, two-account ticket isolation, ticket issue/check-in/duplicate scan, suspension, email delivery, retry, dead-letter, and idempotency. The container and temporary files are removed on exit.
