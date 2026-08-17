# Portal staff authorization

## Decision

Committee members remain attendees. They authenticate once through the branded attendee login and receive a conditional **Committee Desk** only when an active `portal_staff_memberships` record exists. The browser never decides authorization; every staff API request reloads the membership and verified/active attendee state from the database.

WordPress is a separate editorial and platform boundary. Only the site owner uses WordPress administration. A committee role does not imply a WordPress user, cookie, capability, or URL.

## Roles

- `committee_member`: roster, inbox, moderation, suggestions, and Hi-Tide Harry response work.
- `committee_lead`: committee-member access plus communications, tickets, and event operations.
- `site_owner`: all portal staff capabilities, committee access administration, audit visibility, and a separately authenticated owner-only WordPress account.

The client receives `committee_admin` for either committee membership and `site_owner` for the owner. The source membership role remains available as `membership_role` for labels and future refinement.

## API contract

`GET /portal/session.php` returns `staff: { authorized, role, membership_role, label, capabilities }`. Ordinary attendees receive `authorized: false`.

Scoped reads:

- `/portal/staff/dashboard.php`
- `/portal/staff/review-queue.php`
- `/portal/staff/people.php?q=`
- `/portal/staff/inbox.php`
- `/portal/staff/communications.php`
- `/portal/staff/operations.php`

Mutations use `POST /portal/staff/action.php`, the attendee session's CSRF token, and a capability check appropriate to the action. Initial actions cover media moderation and suggestion status. Every successful staff mutation writes `portal_staff_audit_log` with actor, role, action, target, IP address, timestamp, and structured details.

## Security behavior

- Active membership, active attendee status, and verified email are checked on every request.
- Revoking or suspending membership takes effect on the next API call; no privilege is cached in browser storage.
- Staff APIs return `403` to authenticated attendees without the required capability.
- Staff and attendee views share an identity but data access remains server-scoped.
- WordPress credentials and capabilities are never accepted by the attendee API.
- The portal must not expose private upload paths. A separately authorized media-serving endpoint is required before previews are enabled.

## Remaining operational gates

- Seed Fritz's matching attendee record as `site_owner` in each environment through a controlled migration.
- Add owner-only membership grant/revoke actions and require recent re-authentication for them.
- Connect legacy RSVP, ticket-order, memory, chatbot, mailbox, and WooCommerce authorities through adapters before claiming full data parity.
- Implement reply delivery and thread persistence before presenting the inbox as two-way email.
- Add audit retention/export policy and production alerting for repeated authorization failures.
