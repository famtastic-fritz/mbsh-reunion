# Attendee portal components

This proof uses framework-neutral, progressively enhanced components so the same
markup can be rendered by WordPress, PHP, or a future React frontend.

- `portal-shell`: masthead, expandable navigation, account controls
- `scene-card`: one focused attendee action with state and next step
- `ticket-pass`: mobile wallet surface with an opaque QR/check-in zone
- `preference-row`: accessible transactional and promotional controls
- `harry-guide`: page-aware mascot guidance, never a blocking modal
- `toast`: polite live-region feedback for proof interactions

## Runtime and API contract

Production defaults to the same-origin PHP API through `../js/portal-api.js`.
Every request uses `credentials: "same-origin"`; mutating authenticated requests
bootstrap `session.php` and send its `csrf_token` as `X-CSRF-Token`.

The browser calls only the existing backend contracts:

| Experience | Endpoint | Method / fields |
|---|---|---|
| Session | `session.php` | `GET` |
| Register | `register.php` | `POST email, first_name, last_name, password` |
| Login/logout | `login.php`, `logout.php` | `POST`; logout requires CSRF |
| Recovery | `forgot-password.php`, `reset-password.php` | `POST email`; `POST token, password` |
| Email verification | `verify-email.php?token=` | `GET` |
| Profile | `profile.php` | `GET/PATCH` |
| Preferences | `preferences.php` | `GET/PATCH event_updates, memory_updates, promotional_email, sms_notifications` |
| Ticket wallet | `tickets.php` | `GET` |
| Media | `media.php` | `GET`; multipart `POST file, title, caption, event_year, consent_to_publish` |
| Suggestions | `suggestions.php` | `GET`; `POST category, subject, message` |
| Notifications | `notifications.php` | `GET`; `PATCH id` |

There is no authenticated RSVP/dinner write endpoint in the backend yet. The
portal therefore exposes the saved state but deliberately disables the save
button. It must not post to the anonymous legacy RSVP endpoint or report success.

## Explicit demo mode

`portal-config.js` defaults to `mode: "api"`. A non-production host may inject:

```html
<script>window.MBSH_PORTAL_CONFIG = { mode: 'demo' };</script>
```

before `portal-config.js`. This is the only way to enable fixture responses.
Production failures remain visible and never fall back to demo data.

Authentication uses server-managed HttpOnly cookies. QR codes contain only a
revocable opaque check-in credential—never a name, email, order ID, or sequential
record ID. Credentials are held only in memory and are not written to localStorage.

## Email navigation

Resend verification and reset templates land on the branded frontend screens,
which consume the single-use token through the JSON API. The token is never sent
to analytics or stored in browser persistence.
