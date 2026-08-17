# Committee Desk QA — 2026-08-16

> Superseded route note: Committee Desk and Site Owner Console are now one
> permission-filtered **Admin Portal** at `/portal/admin/`. This report remains
> historical component evidence; current route and authorization proof lives in
> the connected-portal QA report and manual proof guide.

## Result

The local proof now supports one attendee identity with three independently verified access levels:

- **Attendee:** attendee workspace only; no Committee Desk button, staff navigation, or staff panels.
- **Committee Member:** attendee workspace plus Committee Desk, review queue, protected people search, inbox, communications health, and operations. No owner/WordPress link.
- **Site Owner:** all attendee and committee views plus the separate owner-only site-administration link.

Every staff API reloads active membership, active attendee status, verified-email state, and the required server capability. Staff mutations require attendee-session CSRF and append an audit record.

## Browser proof

Tested against `http://127.0.0.1:8952/portal/` with the real local PHP API and MariaDB database.

| Scenario | Result |
| --- | --- |
| Site Owner role returned for Fritz | Pass |
| Committee dashboard loaded secured counts | Pass |
| Committee Member role displayed | Pass |
| Owner administration link hidden from committee | Pass |
| Committee controls absent for ordinary attendee | Pass |
| 390 × 844 viewport horizontal overflow | Pass — none |
| Workspace switch target height | Pass — 44px |
| Local owner administration route | Pass — configured for `http://localhost:8096/wp-admin/` |

## Automated proof

- Portal frontend contract: pass
- Portal security primitives: pass
- Committee parity/capability contract: pass
- PHP syntax for all staff/committee APIs: pass
- JavaScript syntax: pass
- Whitespace validation: pass

## Honest capability boundary

The secured interface and read APIs are live in the local proof. Media and suggestion status mutations are implemented and audited. The communications composer, two-way thread reply delivery, RSVP/menu staff editing, and full legacy/WooCommerce reconciliation remain intentionally marked unavailable until their adapters and end-to-end tests exist. The interface does not fake those operations.

WordPress is not the committee workspace. It remains a separate Site Owner maintenance and editorial boundary. The attendee portal is the canonical day-to-day experience for attendees and committee members.
