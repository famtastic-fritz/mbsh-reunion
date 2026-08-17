# Event Cinema portal build record

Date: 2026-08-16
Branch: `feature/event-cinema-portal`
Worktree: isolated from the production/main working tree
Deployment status: not deployed

## Objective

Turn the cinematic reunion experience into a reusable event platform with
attendee identity, a virtual-ticket wallet, moderated community contributions,
committee operations, durable email/workers, and a staged path to headless
WordPress plus WooCommerce.

## Inputs and safety decisions

- The provided yearbook and diploma photographs are private source material.
  They remain outside Git; hashes and usage restrictions are recorded in the
  private-source manifest.
- No automated face identification or bulk public OCR is allowed.
- The generated ticket artwork is presentation, not the admission credential.
  Admission uses an opaque, revocable, short-lived signed value.
- No real Stripe charge, production mail campaign, production schema migration,
  commit, push, or deployment was performed from this worktree.

## Architecture produced

- Mobile-first cinematic attendee portal and authentication screens.
- PHP attendee identity/session/recovery APIs with verified-email access.
- Owner-scoped profiles, preferences, submissions, notifications, and tickets.
- Resend outbox with bounded retry, stale-job recovery, and dead-letter state.
- Committee reports, moderation operations, ticket revocation, and check-in.
- Headless WordPress/WooCommerce proof plugin and versioned API contract.
- Woo paid-order-to-ticket issuance and signed check-in boundary.
- Site Studio/Shay recipe plus Codex/Claude/agent repository guidance.

## Visual proof

The ticket illustration was generated from the reunion medallion, the approved
Hi-Tide Harry visual language, and the yearbook-cover material language. It
deliberately avoids using student faces, diploma names, or signatures.

Output: `frontend/assets/tickets/virtual-ticket-premiere-v1.png`

## Verification record

- Personal Codex skill validation: pass.
- PHP syntax across custom backend: pass.
- Portal security primitives: pass.
- WordPress plugin PHP/static/secret checks: pass.
- Portal JavaScript syntax and HTML parse: pass.
- Local asset/link resolution and HTTP responses: pass.
- Browser proof at 390 × 844: no horizontal overflow; responsive shell works.
- Portal route matrix at 320, 360, and 390 px: no content overflow across the
  wallet, RSVP, memory, suggestion, guide, and preference views. Narrow-header
  crowding and the undersized archive-consent target were corrected.
- From-scratch FAMtastic Event Cinema WordPress theme: active in the local proof,
  responsive at 390 px, branded fallback verified, and generic public WordPress
  wording absent.
- Public SEO contract: nine canonical routes have route-specific metadata,
  Open Graph/Twitter tags, JSON-LD, and sitemap membership; CMS and portal are
  explicitly excluded from indexing.
- Committee identity: a dedicated, assignable `Committee Admin` role reaches
  the branded command center and operational content/commerce surfaces while
  remaining unable to administer plugins, themes, system settings, or users.
  Non-committee accounts are denied the staff dashboard and routed to the
  attendee experience.
- Local role proof: a synthetic Committee Admin signed in through the branded
  login, reached the command center, saw the required content/commerce menus,
  received a hard authorization denial for the plugins screen, and rendered
  without horizontal overflow at 390 px. No real committee identity was used.
- The committee login and command center now reuse the owned foil reunion
  medallion from the cinematic frontend instead of the early placeholder mark.
- A local Fritz Committee Admin identity was created for owner evaluation while
  the emergency full-administrator account was preserved under a local-only
  address. No temporary credential was written to repository documentation.
- Browser identity separation proof: the synthetic attendee was redirected
  from `/wp-admin/` to the standalone attendee portal, then the browser was
  returned to Fritz's restricted Committee Admin command center.
- Docker Compose validation and runtime through legacy `docker-compose`: pass.
- Local WordPress 7.0.4, MariaDB, and cron containers: running proof completed.
- FAMtastic Reunion Platform, WooCommerce 11.0.1, and WooCommerce Stripe
  Gateway 10.8.5: installed and active locally.
- FAMtastic REST namespace: registered; unauthenticated `/me` correctly returns
  `401 authentication_required`.
- Database-backed registration, verification, session, preferences, suggestion,
  recovery, ticket isolation/check-in, suspension, and Resend retry/dead-letter
  journey against disposable MariaDB and mock Resend: pass.
- Stripe test payment journey remains a controlled-staging launch gate; no real
  payment was attempted.
- Replaced the legacy committee Gmail address across public content,
  configuration defaults, backend fallbacks, and email templates with
  `committee@mbsh96reunion.com`.
- Added attendee account entry points to the public homepage, shared Explore
  drawer, and shared footer. Registration and returning-user sign-in remain
  separate from the restricted committee management login.
- Resend delivery code and worker behavior are proven against the mock provider;
  the real reunion Resend key/domain still require secret installation and a
  controlled external delivery proof before production activation.
- Corrected the local attendee-login proof server: a same-origin PHP router now
  serves the cinematic frontend and executes `/portal/*.php`, replacing the
  static server that returned an HTML 404 to JSON API requests. The local
  WordPress MariaDB instance now contains the idempotent attendee schema.
- Refined the login composition by moving seated Hi-Tide Harry to the left side
  of the poster and reducing “Return to your story” to supporting text above
  the form. Desktop and 390 × 844 checks show no horizontal overflow.
- Added clean attendee routes (`/portal/`, `/portal/login`, and
  `/portal/register`) and retired the legacy `/admin/login.php` form in favor
  of a redirect to the restricted committee WordPress login.
- Added an executable portal cutover audit and a source-by-source migration
  matrix. The gate correctly remains blocked because RSVP/menu/ticket/survey,
  archive, capsule, sponsor, and memorial parity has not yet been completed.
- Corrected local ownership roles: `fritz.medine@gmail.com` is now the full
  Site Owner/Administrator with platform, user, integration, and WooCommerce
  administration. Ordinary committee identities remain on the restricted
  Committee Admin role; attendee access remains a separate portal identity.

## What improved during review

- Frontend demo-only behavior was identified as insufficient and replaced with
  an explicit adapter boundary so production cannot silently report fake saves.
- Mobile card targets and Harry collision behavior were identified by a real
  viewport pass rather than source inspection alone.
- Check-in concurrency and refund/cancellation revocation were promoted from
  documentation notes to implementation requirements.
- Raw archival inputs were inventoried by checksum without copying them into the
  repository.

## Repeatable workflow

1. Create an isolated clean worktree.
2. Inventory existing contracts and declare one authority for every data type.
3. Record private sources without importing them into Git.
4. Create the visual proof while keeping decorative art separate from secrets.
5. Build identity and authorization before contribution or wallet features.
6. Build commerce adapters around provider-verified paid transitions.
7. Queue mail and worker activity through idempotent operational records.
8. Test contracts, security primitives, mobile behavior, then the full provider
   journey in test mode.
9. Reconcile and cut over capability by capability with rollback.
10. Capture the result in the reusable recipe and capability manifest.

## Remaining external launch gates

- Provision a controlled WordPress/MariaDB environment and install WooCommerce
  plus the official Stripe extension.
- Configure test Stripe keys/webhooks and the existing reunion Resend key/domain
  as hosting secrets, never committed values.
- Execute the recorded success/decline/3DS/refund/replay/email/worker/mobile
  acceptance matrix with evidence.
- Approve ticket prices, transfer/refund language, archive consent language, and
  retention/removal policy.
- Perform staged data migration, committee acceptance, backup/restore rehearsal,
  and an explicit production cutover decision.

## Product capability learned

This proof establishes a sellable FAMtastic capability: a branded “Event Cinema”
platform combining a custom cinematic frontend, headless editorial CMS,
commerce-backed virtual tickets, moderated archives, attendee self-service,
committee operations, transactional communications, and reusable launch gates.

## Connected-operations refinement

The initial Committee Desk was rejected because it summarized work without
letting the committee perform it. The final access structure has three views
but only two branded portal applications:

- `/portal/` is attendee self-service. RSVP, guests, dinner, accessibility,
  memories, suggestions, conversations, tickets, and preferences belong here.
- `/portal/admin/` is the shared administrative portal. Committee members,
  committee leads, and Site Owners see different navigation and actions from
  the same capability-filtered interface. Owner-only sections govern roles,
  components, providers/workers, reconciliation, and audit.
- `/wp-admin/` is the third view: full WordPress/WooCommerce maintenance and is
  restricted to the Site Owner role. Committee identities are redirected to
  the branded Admin Portal.

Every source now has an explicit authority. Production RSVP/menu/survey data is
mounted read-only; attendee changes enter `portal_event_responses`; conversations
and replies enter the portal thread ledger; WordPress owns structured page and
knowledge records; WooCommerce remains the only intended payment/order authority.

The local proof now includes:

- authenticated attendee RSVP/dinner read and write;
- committee visibility into the same current response;
- attendee message creation, three-day response due date, committee timeline,
  audited reply, and idempotent Resend outbox enqueue;
- actual WordPress component/content inventory in the branded portals;
- owner-controlled attendee/committee/lead/owner memberships;
- owner-only data reconciliation counts and audit activity;
- Woo Action Scheduler heartbeat jobs for reconciliation and delivery health;
- browser-proven 390 × 844 layouts with no horizontal overflow for attendee,
  committee, and owner workspaces;
- ordinary-attendee rejection from committee and owner routes.

No live provider send, payment, production mutation, deployment, commit, or push
was performed in this refinement.

## Completion refinement — connected lifecycles and embedded training

The proof now covers the operational actions that were previously only visible
as counts:

- attendee conversation create, timeline, reply, close, and reopen;
- committee reply with a three-day response state, audited author, and queued
  transactional notification;
- attendee media and suggestion correction, resubmission, close, and withdrawal;
- owner-only exception-ticket issue and void plus capability-scoped check-in;
- duplicate/replay conflicts and cross-account denial;
- account suspension plus owner self-suspension and self-demotion protection;
- contextual Hi-Tide Harry lessons for all twelve administrative workspaces;
- interactive Admin Portal questions that route staff to the authoritative
  workspace, plus a persistent screen-aware WordPress owner guide;
- destructive-action confirmation for ticket voiding.

The disposable integration suite proves these with two independent accounts,
a fresh MariaDB schema, mock Resend delivery/retry/dead-letter states, and exact
audit assertions. Real browser proof passed at 390 × 844 and 1440 × 900 with no
horizontal overflow, no console warning/error, role-filtered owner navigation,
13 connected people records, 9 inbox rows, and contextual lesson switching.

Canonical architecture is now recorded in
`docs/architecture/EVENT_CINEMA_ARCHITECTURE.md`, closing the stale AGENTS.md
reference. Live Stripe/Resend, production migration, archival publication,
backup/restore, and cutover remain explicit external launch gates.
