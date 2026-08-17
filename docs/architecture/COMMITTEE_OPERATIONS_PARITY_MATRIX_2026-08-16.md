# Committee Operations Parity and Source-of-Truth Matrix

Date: 2026-08-16
Status: implementation contract; legacy retirement is blocked until every P0/P1 row passes.

## 2026-08-16 connected-build update

The role bridge, separate attendee/admin experiences, attendee event-response authority,
clickable RSVP/menu records, conversation ledger, committee replies, Resend
outbox enqueue, WordPress content registry, role management, and owner audit/data
views are now implemented and locally proven. Rows below retain their original
discovery wording as the migration checklist; use
`docs/qa/CONNECTED_PORTAL_QA_2026-08-16.md` for current proof status. Legacy
retirement remains blocked by real-identity reconciliation, provider tests,
payments, and production migration—not by the former static Committee Desk.

## Product decision

Committee members are attendees first. They use the same branded account and
session, then receive an additional **Committee Studio** workspace when an owner
grants a committee membership. They do not receive a generic WordPress dashboard
or a public `wp-login.php` entry point.

The attendee and committee experiences therefore share one shell:

```text
/portal/                   attendee home
/portal/admin/             shared administrative portal (capability-filtered)
/portal/admin/#messages    conversations and Harry questions
/portal/admin/#review      memory, photo, video, sponsor moderation
/portal/admin/#people      attendee, RSVP, dinner, and outreach operations
/portal/admin/#tickets     orders, ticket status, and check-in
/portal/admin/#content     announcements, program, and archive curation
/portal/admin/#platform    owner-only pages/components controls
/portal/admin/#audit       owner-only imports, privacy, and audit
```

The site owner receives a separate **Owner Console** inside the same branded
portal for role assignment, integrations, payment settings, email configuration,
workers, audit records, privacy exports, and platform health. Direct WordPress
administration is break-glass/maintenance access for the owner only.

## Roles

| Role | Attendee workspace | Committee Studio | Owner Console | WordPress maintenance |
|---|---:|---:|---:|---:|
| Attendee | Yes | No | No | No |
| Committee member | Yes | Yes, scoped capabilities | No | No |
| Site owner | Yes | Yes | Yes | Yes, private/break-glass |
| Recovery administrator | No routine use | No routine use | Recovery only | Recovery only |

Committee access must be represented by a durable membership linked to the
attendee account. Email equality alone never grants a role. Every committee
mutation records actor, action, target, before/after state, timestamp, and IP or
session fingerprint.

## Workflow parity matrix

Priority meaning: **P0** blocks safe launch or data protection; **P1** blocks
legacy `/admin/` retirement; **P2** improves operations after parity.

| Pri. | Committee job | Legacy source/current surface | Launch authority | Committee capability | Required proof | State |
|---|---|---|---|---|---|---|
| P0 | Sign in and switch between attendee/committee views | Shared legacy admin password; separate attendee login | Portal identity + explicit committee membership | `committee.access` | attendee cannot see Studio; committee can switch views; revoked membership ends access immediately | Missing unified role bridge |
| P0 | Owner assigns/removes committee access | WordPress user roles | WordPress owner directory linked to portal account | `owner.manage_roles` | grant, revoke, expired session, audit entry | Partial: WP role exists, portal link missing |
| P0 | Review uploaded photos/video/audio/documents | `memories`, `attendee_media_submissions`, pending upload endpoints | Portal media table while intake; WordPress only after approved derivative publication | `media.review` | original remains private; approve/reject/notes; rights state; unsafe file fails closed | Partial: two moderation queues |
| P0 | View and answer attendee messages | Suggestions and notifications are one-way; no conversation model | Portal conversation/thread store | `inbox.read`, `inbox.reply` | attendee message -> committee alert -> reply -> attendee portal/email -> one timeline | Missing |
| P0 | Receive and answer Harry questions | `chatbot_questions`, legacy `admin/chatbot.php` | Conversation store with source=`harry`; legacy adapter until imported | `harry.review`, `inbox.reply` | fallback creates thread; assigned reply is visible to attendee; status/audit preserved | Legacy only; notes are not customer replies |
| P0 | See operational alerts | Email notifications plus scattered dashboard counts | Portal operations/notification store | `alerts.read`, scoped acknowledge | new message/upload/order/failure creates alert; unread/acknowledged states; no duplicate alert | Missing unified inbox |
| P0 | Ticket and paid-order truth | Legacy `ticket_orders`, portal wallet, Woo orders | WooCommerce orders/refunds; ticket entitlement service | `tickets.read`, `tickets.check_in`; owner-only manual issue/refund | paid order issues once; refund revokes; duplicate scan warns; manual action audited | Partial; three ticket stores need reconciliation |
| P0 | Email delivery and failures | Form-specific columns plus `portal_email_jobs` | Portal outbox/Resend delivery ledger | `email.read_status`, `email.retry_failed`; owner configures provider | provider ID, retries, dead letter, bounce/suppression, worker heartbeat | Partial |
| P1 | Search attendee/classmate directory | `rsvps`, imported surveys, portal accounts | Portal identity index with immutable links to legacy records | `people.read` | search name/email/maiden name/phone; duplicates and unmatched records explicit | Missing unified index |
| P1 | View and edit RSVP | `rsvps`, `admin/rsvps.php`, `rsvp-edit.php` | Legacy RSVP through read/write adapter, then portal migration | `rsvp.read`, `rsvp.edit` | edits visible to attendee and reports; collision/audit behavior | Legacy only |
| P1 | View and edit dinner choice/dietary needs | `menu_selections`, `admin/menu-results.php` | Legacy menu through read/write adapter, then portal migration | `menu.read`, `menu.edit_sensitive` | attendee/committee edits agree; dietary fields access-logged | Legacy only |
| P1 | Moderate written memories and archive publication | `memories` plus WP `reunion_memory` | Portal intake; WordPress approved editorial record | `memory.review`, `memory.publish` | approval creates one WP record; rejection/withdrawal propagates; provenance retained | Split authority |
| P1 | Review sponsor inquiries/assets | `sponsors_pending`, `sponsors_approved` | Legacy adapter until WordPress sponsor model is migrated | `sponsor.review` | approve/reject; private logo handling; active/public state parity | Legacy only |
| P1 | Manage memorial records | `in_memory` | WordPress curated memorial record after sensitive migration | `memorial.manage` | add/edit/archive; no accidental public PII; audit and preview | Legacy only |
| P1 | Manage time capsules | `time_capsules`, cron `send-capsules.php` | Legacy worker until full portal schedule/state migration | `capsule.read`, `capsule.manage` | schedule/edit/revoke; send exactly once; attempt/delivery status; privacy scope | Legacy only |
| P1 | View surveys and historical contacts | `surveys`, historical CSV/import | Read-only historical archive + linked portal identity | `reports.survey`, `people.read` | count parity; duplicate policy; restricted export | Legacy only |
| P1 | Follow up missing RSVP/menu | legacy reports/follow-up/export | Portal segments + outbox campaigns | `outreach.segment`, `outreach.send_transactional` | preview recipients; dedupe; suppression; test send; delivery report | Legacy export only |
| P1 | Publish announcements/program changes | WordPress posts/pages/events | WordPress editorial content | `content.edit`, optional `content.publish` | draft/preview/publish; frontend update; revision/rollback | Implemented in WP, not portal-framed |
| P1 | Manage polls and view votes | `poll_questions`, `poll_options`, `poll_votes` | Portal database until migrated to one service | `poll.manage`, `poll.results` | draft/activate/close; one active; vote update semantics; export control | Legacy only |
| P1 | View reports/dashboard | Scattered legacy pages and WP command center | Aggregation API reading authoritative systems | `reports.operations` | counts reconcile to sources; timestamps; stale-source warning | Partial/static links |
| P1 | Export attendee information | Several CSV endpoints | Server-side export service with purpose/audit/expiry | `export.attendees` (limited committee); owner broader | permission, redaction, audit, short-lived download, no public file | Legacy unrestricted-by-purpose |
| P2 | Assign work and response ownership | None | Portal operations store | `work.assign` | owner, due date, status, escalation, audit | Missing |
| P2 | Send announcements/newsletters | Manual exports | Approved messaging service; promotional consent separate | `campaign.preview`; owner or designated sender can `campaign.send` | consent/suppression, preview, approval, metrics | Missing |
| P2 | Sponsor benefit fulfillment | None | Woo/order + sponsor entitlement model | `sponsor.fulfill` | purchased tier maps to deliverables and completion | Missing |
| P2 | Day-of-event mode | Basic check-in endpoint | Ticket service | `tickets.check_in`, `door.view` | mobile scanner, guest lookup, offline recovery, duplicate warning | Partial |

## Committee Studio navigation

1. **Today** — urgent alerts, pending approvals, unanswered messages, orders
   requiring attention, worker failures, and upcoming deadlines.
2. **Inbox** — attendee conversations, Harry fallbacks, support questions, and
   unmatched email replies in one assignable timeline.
3. **People** — attendee/classmate lookup, RSVP, dinner, guests, communication
   preferences, tickets, and record-link conflicts.
4. **Review** — memories, media, memorial submissions, sponsor requests, and
   explicit rights/visibility decisions.
5. **Tickets & Check-in** — Woo orders, entitlements, refunds/revocations,
   scanner, guest lookup, and duplicate-scan handling.
6. **Content & Program** — announcements, event details, FAQs, polls, playlist,
   archive collections, and approved sponsor presentation.
7. **Outreach** — missing RSVP/menu segments, transactional reminders, campaign
   drafts, test sends, consent/suppression, and delivery results.
8. **Reports** — attendance, menu, survey, sales, moderation, response time,
   delivery, and data-parity health.

The Site Owner additionally sees **Platform** for people/roles, integrations,
Resend, Woo/Stripe configuration, cron/workers, audit, privacy, backups, and
deployment health.

## Authority and adapter rules

| Record | Authority now | Authority after cutover | Migration rule |
|---|---|---|---|
| Attendee identity/preferences | Portal tables | Portal identity service | Link WP user and legacy records; never match roles by email alone |
| RSVP/menu | Legacy MySQL | Portal service | Read/write adapter first; migrate only with count and mutation parity |
| Pending media | Portal + legacy queues | Portal private intake | Merge with checksum/provenance; no public originals |
| Approved memories/content | Legacy + WP | WordPress | One idempotent publication adapter and source link |
| Conversations/Harry | Legacy questions + suggestions | Portal threads | Import all statuses/notes; preserve original timestamps |
| Orders/payments/refunds | Legacy requests + Woo | WooCommerce | Reconcile historical requests; never duplicate paid orders |
| Tickets/check-in | Portal wallet + WP tickets | Entitlement service backed by Woo | One opaque credential per entitlement; revoke/refund parity |
| Sponsors | Legacy | WordPress + Woo entitlement where paid | Preserve review state and private source assets |
| Capsules | Legacy DB/cron | Portal scheduler/outbox | Preserve schedule, attempts, delivery, revoke state |
| Email | Mixed direct sends + portal queue | Portal outbox via Resend | Every send has purpose, idempotency, provider status, and failure state |

## Required API groups

These are contracts, not permission to expose WordPress directly to browsers.
A same-origin portal controller/BFF verifies the portal session and committee
membership, performs capability checks, then calls the authoritative adapter.

- `GET /portal/committee/session`
- `GET /portal/committee/summary`
- `GET/PATCH /portal/committee/people/{publicId}`
- `GET/PATCH /portal/committee/rsvps/{publicId}`
- `GET/PATCH /portal/committee/menu/{publicId}`
- `GET/POST/PATCH /portal/committee/conversations`
- `POST /portal/committee/conversations/{publicId}/reply`
- `GET/PATCH /portal/committee/review/{type}/{publicId}`
- `GET/POST/PATCH /portal/committee/polls`
- `GET /portal/committee/orders` and scoped Woo actions
- `POST /portal/committee/tickets/{publicId}/check-in`
- `GET/POST /portal/committee/outreach/segments`
- `POST /portal/committee/outreach/test`
- `POST /portal/committee/outreach/send` (extra capability/approval)
- `GET /portal/committee/reports`
- `GET /portal/owner/platform-health`
- `GET/PUT /portal/owner/roles`
- `GET/PUT /portal/owner/integrations`

## Safe implementation order

1. Unified portal identity, committee memberships, capability checks, audit,
   revocation, and attendee/committee view switching.
2. Read-only source adapters and reconciliation dashboard for every legacy table.
3. Inbox/thread model combining suggestions, Harry fallbacks, and email replies.
4. Media/memory/sponsor moderation with private asset serving and rights review.
5. RSVP/menu read-write parity and attendee search.
6. Woo order/ticket/check-in parity and legacy ticket reconciliation.
7. Outreach segments, transactional test/send approval, delivery/failure views.
8. Polls, capsules, memorial management, content framing, and reports.
9. Owner Console, operations health, privacy exports, backups, and launch gate.
10. Disable legacy `/admin/` only after the parity suite passes and rollback is rehearsed.

## Legacy retirement acceptance

- Every P0/P1 row has success, unauthorized, invalid, conflict, and rollback tests.
- Legacy and replacement row counts reconcile, with an explicit unmatched queue.
- A committee user can complete all routine work from a 390 px mobile viewport.
- A committee user cannot reach WordPress, plugins, themes, settings, user
  administration, payment secrets, provider secrets, or owner-only exports.
- An owner can grant/revoke committee access and inspect all administrative audit
  events without using database tools.
- Transactional replies and promotional broadcasts remain separate workflows.
- `/admin/login.php` redirects only after the replacement has passed parity; it
  remains available behind rollback controls until final cutover acceptance.
