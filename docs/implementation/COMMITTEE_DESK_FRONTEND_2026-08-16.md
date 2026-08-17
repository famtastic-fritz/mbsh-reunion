# Committee Desk frontend — 2026-08-16

## Product decision

Committee members are attendees first. Their attendee workspace remains unchanged, while an authorized session reveals a **Committee Desk** mode switch. The desk is part of the branded portal; it does not expose WordPress terminology or unrestricted administration.

Roles remain distinct:

- Attendee: personal ticket, RSVP, memories, suggestions, event guide and preferences.
- Committee Member/Lead: shared review, people lookup, inbox, communications and event operations, limited by server capabilities.
- Site Owner: all Committee Desk features plus an explicit route to restricted platform administration.

The browser never decides authorization. Staff navigation and panels begin hidden and are revealed only when the same-origin session returns `staff.authorized: true`. Every staff API must repeat authorization server-side.

## Mobile information architecture

1. Committee Desk command center: priority counts and fast routes.
2. Review queue: private submissions, consent and moderation status.
3. People: deliberate search rather than downloading the full roster.
4. Inbox: attendee messages and Harry questions in shared threads.
5. Communications: audience-aware sending and delivery health.
6. Tickets & operations: order, entitlement, check-in and exception summaries.

All grids collapse to one column at 760px. Controls retain 44px minimum targets, headings remain semantic, loading states use live status text, and hidden staff navigation fails closed.

## API contract

The centralized portal adapter expects role-scoped JSON from:

- `GET staff/dashboard.php`
- `GET staff/review-queue.php?status=pending`
- `GET staff/people.php?q=...`
- `GET staff/inbox.php`
- `GET staff/communications.php`
- `GET staff/operations.php`

Missing endpoints produce an explicit “not connected” state. The frontend does not invent operational totals, attendee records, messages or delivery status. Mutating review, reply, broadcast, role, ticket and check-in actions remain disabled until their secured CSRF and audit contracts are connected.

## Financial and privacy boundaries

- WooCommerce remains the financial authority.
- Committee users do not receive payment configuration, plugin, theme, unrestricted user or platform-setting access.
- Private originals do not publish merely because a review button is clicked.
- People search returns only server-approved fields.
- Site Owner is the only portal role offered a platform-administration route.

## Proof

`node --check frontend/portal/js/portal-staff.js`

`node tests/frontend/portal-contract.test.mjs`

The contract test asserts that the mode switch and all six staff views fail closed, authorization comes from session state, the platform-admin route is owner-only, and disconnected endpoints never display pretend data.
