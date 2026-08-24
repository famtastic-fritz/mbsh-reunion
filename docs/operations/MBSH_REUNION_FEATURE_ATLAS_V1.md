# MBSH Reunion Feature Atlas v1

Status: current capability map; this document is the source material for the
online **Reunion Field Guide** at `/manual/`.

## Purpose

Explain every real visitor, attendee, committee, and owner function without
turning private operations into a public control surface. A manual describes a
route and its boundary; it never includes passwords, ticket credentials,
personal records, payment data, provider credentials, or raw private uploads.

## Three experiences plus the owner control plane

| Audience | Canonical destination | What it is for |
| --- | --- | --- |
| Visitor | Public site routes and `/portal/register` | Event information, RSVP, seat request, dinner preferences, class activity, and account creation. |
| Verified attendee | `/portal/` | Private reunion record, RSVP/dinner response, wallet, memories, ideas, trivia, messages, and preferences. |
| Committee member / lead | `/portal/admin/` | Capability-filtered operational workspace. A committee member is still an attendee first. |
| Site Owner | `/portal/admin/` plus separate CMS/commerce control plane | Role management, component registry, delivery health, audit, maintenance, editorial and financial authority. |

`/portal/committee/` and `/portal/owner/` are compatibility redirects, not
manual destinations. The owner CMS destination is not advertised to public or
ordinary committee users.

## Public capability map

| Destination | Actual function | Honest status |
| --- | --- | --- |
| `/` | Reunion landing, directions, public actions, and Hi-Tide Harry FAQ/fallback | Public and live. |
| `/rsvp.html` | Diploma-style RSVP and committee/guest email attempt | Route and endpoint exist; inbox delivery needs separate proof. |
| `/tickets.html` | Seat request and sponsor inquiry | **Not checkout.** Current configuration is order-request only. |
| `/menu/` | Dinner choice and dietary note | Canonical public dinner route. |
| `/survey.html` | Class check-in / headcount | Public form; controlled submission evidence remains separate. |
| `/through-years.html` | Centennial story and memory/photo submission | Submission path exists; archive reel remains coming soon. |
| `/memorial.html` | Committee-curated memorial feed | Empty feed is a valid, truthful state. |
| `/capsule.html` | Private note for scheduled reunion-day delivery | Actual delivery depends on cron/provider launch gates. |
| `/playlist.html` | Song suggestion into committee queue | Player is intentionally unconfigured; do not promise music playback. |
| `/portal/register`, `/portal/login` | Attendee identity entry points | Verified account required for private data. |

## Attendee capability map

The attendee portal is account-scoped. It uses server-managed sessions and
in-memory CSRF state; it does not retain private data or ticket credentials in
browser storage.

| Portal view | Function | Boundary |
| --- | --- | --- |
| `#home` | Personal dashboard and real next actions | Guidance does not replace record state. |
| `#rsvp` | Attendance, guests, meal, phone, dietary/accessibility response | Legacy history is preserved separately. |
| `#ticket` | Issued tickets and current rotating check-in credential | Account-scoped; no credential persistence. |
| `#memories` | Quarantined upload, consent, revision/withdrawal lifecycle | Approval does not auto-publish. |
| `#suggestions` | Music/event/accessibility/site ideas | Submitter sees only their own status. |
| `#trivia` | Server-scored class game | Empty game is a truthful state. |
| `#event` | Event guidance and directions | Informational, not an operations console. |
| `#notifications` | Optional communication preferences and private notices | Security/payment/ticket essentials remain separate. |
| `#inbox` | Durable attendee/committee conversation | Replies are auditable; delivery state is separate. |

## Committee and owner map

Every Admin Portal action repeats its authorization check on the server. A
hidden link is never the authorization boundary.

| Admin view | Minimum access | Function | Important limit |
| --- | --- | --- | --- |
| `#command` | Authorized staff | Operational signals | Counts do not grant mutation rights. |
| `#people` | `view_roster` | Search protected attendee/RVSP context | Sensitive fields are access-scoped. |
| `#dinner` | `view_menu` | Linked dinner context | Existing history is read-only. |
| `#harry` | `respond_harry` | Review question and record response draft | No automatic public/email reply. |
| `#review` | `moderate_media` | Approve or return uploads | Originals remain private. |
| `#messages` | `view_inbox`; reply additionally scoped | Read and respond to conversations | Provider delivery is a separate state. |
| `#tickets` | `manage_tickets` / `check_in_tickets` | Operations and credential check-in | WooCommerce remains payment truth. |
| `#content` | `manage_event_content` | Structured content registry | Publishing belongs to authorized owner/CMS workflow. |
| `#access` | Site Owner | Membership and role management | Audited; self-lockout is blocked. |
| `#platform` | Site Owner | Component map | Owner-only control plane. |
| `#delivery` | Site Owner | Outbox/worker health | Sent does not prove inbox delivery. |
| `#audit` | Site Owner | Reconciliation and staff audit | No raw exports in public/manual material. |

## Maintenance rule

When a route, capability, live status, or authority changes, update this atlas
and `frontend/js/reunion-manual.js` in the same commit. The field guide must
never market a planned, launch-gated, or empty-state feature as live.
