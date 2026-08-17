# Attendee Portal Backend Proof

This proof adds a same-origin attendee identity and virtual-ticket wallet without changing production or making WordPress/WooCommerce the authentication authority.

## Implemented surface

- `portal/register.php`: creates or refreshes an unverified account, links a matching legacy RSVP, and sends a 24-hour verification message through Resend.
- `portal/verify-email.php`: consumes the single-use hashed token and opens a protected session.
- `portal/login.php`, `logout.php`, `session.php`: generic-error login, throttling, strict HttpOnly cookie, idle expiry, CSRF token, and logout.
- `portal/forgot-password.php`, `reset-password.php`: enumeration-resistant request and one-hour single-use reset.
- `portal/profile.php`: private attendee profile and opt-in directory visibility.
- `portal/preferences.php`: independent event, memory, promotional, and SMS choices. Transactional security/ticket messages are not disabled here.
- `portal/tickets.php`: owner-scoped wallet. Ticket credentials use a random public identifier plus an HMAC signature that rotates every five minutes; revocation is immediate through ticket status.
- `portal/media.php`: owner-scoped, private pending uploads with explicit publication consent and committee review.
- `portal/suggestions.php`: attendee ideas with status feedback and committee notification.
- `portal/notifications.php`: private in-app notification inbox.
- `admin/portal-report.php`: committee totals and recent account report.
- `admin/portal-action.php`: suspend/reactivate accounts, review media/suggestions, issue tickets, and atomically check in an active ticket.
- `cron/process-portal-email.php`: idempotent Resend outbox with exponential retry, provider IDs, and dead-letter status.

## Required private configuration

Add these keys to the server-side config outside the web root:

```php
'portal_base_url' => 'https://mbsh96reunion.com',
'portal_frontend_base_url' => 'https://mbsh96reunion.com/portal',
'portal_token_secret' => 'a unique random secret of at least 32 characters',
```

Keep the existing Resend keys and verified reunion sending domain. `portal_token_secret` must not be shared with WordPress, JavaScript, Stripe, QR rendering, or a repository.

## Commerce adapter boundary

`attendee_record_links` accepts `woocommerce_customer` and `woocommerce_order` identifiers. WooCommerce/Stripe webhooks should verify the provider signature, locate the account by normalized verified email or an existing link, create the link, and ask the ticket issuance service to create wallet rows. Payment data and Stripe secrets never enter the portal tables.

The current `ticket_orders` relation remains optional so the proof can run before Commerce migration. A production migration may add its foreign key after confirming table types and historical integrity.

## Deployment sequence

1. Back up database and private config.
2. Add the two private configuration keys.
3. Apply `schema.sql` to a staging database.
4. Verify the Resend domain/from identities and reply-to address.
5. Schedule `php backend/cron/process-portal-email.php` every minute and alert on `portal_email_jobs.status='dead'`.
6. Run `tests/backend/run.sh`.
   Use `RUN_INTEGRATION=1 tests/backend/run.sh` for the disposable MariaDB and mock-Resend lifecycle proof.
7. Register a synthetic account, receive verification, log in, update preferences, reset its password, submit media, and issue/check in a synthetic ticket.
8. Confirm cross-account identifiers return no records.
9. Connect WooCommerce/Stripe only after webhook signature and replay tests pass.

## Deliberate limits

- This is backend proof code; the cinematic portal UI and QR renderer are separate frontend work.
- No schema or endpoint was deployed to production.
- No real payment is initiated.
- Upload malware scanning/transcoding needs a production worker before accepting public video/audio.
- A CAPTCHA or managed bot challenge is recommended before public registration.
- Email delivery-event ingestion, queue retries, and dead-letter monitoring remain deployment work.
