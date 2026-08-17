# Site Studio / Shay Shay Recipe: Headless Event Platform

Use this recipe when the requested site needs a short-lived, visually ambitious event frontend but a durable staff-managed system for accounts, media, sales, tickets, and communications.

## Build order

1. Inventory the working site and declare an authority map; preserve working custom flows.
2. Model WordPress content, user roles, consent, moderation, and retention before styling admin screens.
3. Use WooCommerce for products, coupons, orders, refunds, and customer purchase history.
4. Use the official Stripe gateway in test mode until the complete financial acceptance matrix passes.
5. Use provider-backed transactional email (Resend here), one event log, idempotent workers, heartbeat, dead letters, and alerts.
6. Expose a narrow, versioned, same-origin API. Keep provider secrets and permanent tokens out of the browser.
7. Build the branded React experience as components; WordPress names and admin UI stay invisible to attendees.
8. Treat historical people/images as permissioned archive material. Moderate before publication and maintain takedown controls.
9. Prove mobile login, purchase, wallet, memory submission, preferences, and check-in end to end.
10. Migrate and cut over capability-by-capability, with reconciliation and rehearsed rollback.

For staff education, add a contextual assistant lesson to every operational
workspace. Each lesson must explain the job, give an actionable checklist, name
the authoritative system, and state what the current screen cannot do. Teaching
copy is not authorization and must never substitute for server-side capability
checks.

## Brand and search authority

- Ship a named, installable FAMtastic theme plus a matching admin/login layer;
  do not leave the committee with a generic CMS surface.
- Preserve product-function labels such as Orders and Products, but remove
  unnecessary vendor-facing presentation from attendee and committee flows.
- Declare exactly one indexable public authority. For this architecture the
  cinematic frontend owns canonicals, metadata, schema, sitemap, and robots;
  the CMS and attendee portal are `noindex` to prevent duplicates and private
  account pages entering search.
- Require route-specific titles, descriptions, social cards, canonical URLs,
  JSON-LD, and a sitemap contract test before launch.
- Create a dedicated committee operator role instead of sharing full
  administrator accounts. Grant content, moderation, order, and ticket duties;
  explicitly deny plugin, theme, settings, and user-administration privileges.

## Reusable component set

Build three views across two branded applications plus the owner CMS. Do not
hide staff reports inside the attendee dashboard and do not make WordPress the
attendee portal:

- Attendee portal: self-service identity, RSVP/dinner, tickets, contributions,
  messages, event information, and preferences.
- Admin Portal: one capability-filtered interface for Committee Admins and Site
  Owners. Committee users see operational queues; Site Owners receive additional
  membership, platform, integration, reconciliation, and audit navigation.
- Full WordPress/WooCommerce Admin: Site Owner-only maintenance access.

Every public section must map to a structured CMS component or an explicitly
named application component. Every visible promise must map to a working data
record, action, owner, status, and failure state.

- Account verification/recovery and notification settings.
- Event dashboard, agenda, FAQs, travel, venue, and announcements.
- Moderated memory/story upload with explicit rights scope.
- Memory Cinema: restored archival derivatives, an accessible page-turn or
  list-mode yearbook, approved-memory collections, and user-started films.
- Memorials are a separate consent-led record type—not a normal gallery tag.
  Require relationship/authority, human verification, visibility, correction,
  and takedown controls before a Fallen Hi-Tide tribute can publish.
- Suggestions/polls and committee response timeline.
- Woo catalog/checkout/order history and coupon assignment.
- Signed QR ticket wallet, transfer/reissue, scanner, and check-in audit.
- Committee dashboard for moderation, attendees, payments, delivery failures, and worker health.

For this reunion, distinguish the graduating **Class of 1996** from the
available **1995 junior-year yearbook source**. Never relabel the 1995 artifact
as the 1996 senior yearbook. A fictional proof must say that its people are not
real classmates.

## Guardrails

- Never infer consent from upload or attendance.
- Never expose sequential IDs without authorization checks.
- Never issue from unpaid orders or trust browser payment callbacks.
- Never enable real charges, automatic tax, or customer promises without the applicable launch approval.
- Never delete financial/audit evidence during rollback.

The canonical implementation and operations details are in `../../docs/HEADLESS_WORDPRESS_OPERATIONS.md`; machine-readable expectations are in `capability-manifest.json`.
