# Committee and Attendee Manual Proof Guide

Date: 2026-08-16
Scope: local Event Cinema proof only

## Identity boundaries

| Person | Destination | What they can access |
|---|---|---|
| Attendee | Branded attendee portal | Their profile, RSVP/dinner display, ticket wallet, uploads, suggestions, guide, and preferences |
| Committee Admin | Branded Admin Portal | Only the operational tools granted by their committee role |
| Site Owner | Branded Admin Portal + Full Site Admin | All reunion operations plus permissions, integrations, audit, WordPress, WooCommerce, themes, and platform maintenance |

Attendees must never receive WordPress navigation or WordPress administration
capabilities. The attendee portal uses its own verified-account/session API.
Committee Admin is deliberately less powerful than Site Owner.

The same verified portal account may be both an attendee and staff member. It
uses one secure session, but the attendee and Admin Portal are separate user
experiences. Every staff API checks the current membership and capability on
the server. WordPress remains a third, Site-Owner-only control plane.

## Sign in as the Site Owner

1. Open `http://127.0.0.1:8952/portal/login`.
2. Sign in as `fritz.medine@gmail.com`.
3. Open **Admin Portal** and confirm the header identifies **Site Owner**.
4. Confirm the owner-only side links appear: Users & permissions, Pages &
   components, Email & workers, and Audit & data.
5. Confirm **Full Site Admin** opens WordPress. Use **Users & permissions** in
   the branded portal for routine committee access assignment; do not grant
   WordPress Administrator access to committee members.
6. Keep the separate emergency local administrator as a recovery identity and
   replace all temporary passwords before any external deployment.

## Sign in as a Committee Admin

1. Open `http://127.0.0.1:8952/portal/login` and use the committee member's
   verified attendee account.
2. Open **Admin Portal**. Confirm the member can still switch back to **My
   Reunion** to see their attendee experience.
3. Confirm only side links granted by that member's capabilities are visible.
4. Paste an ungranted Admin Portal hash directly. Expected: the command center,
   not the protected panel.
5. Open `http://localhost:8096/wp-admin/` directly. Expected: a redirect back
   to the branded Admin Portal. Only Site Owner may enter full WordPress.

Never record real passwords in Git. The current local test password must be
changed before the account exists anywhere externally accessible.

## Command center functions

### Event details

1. Select **Events** or **Event details**.
2. Add or edit the event title, description, featured image, date/location
   content, SEO title, description, social image, and canonical frontend URL.
3. Preview before publishing.
4. Expected: the event remains an editable CMS record; production frontend
   publication still requires the documented headless synchronization/cutover.

### Announcements

1. Select **Announcements → Add Post**.
2. Draft an update, add media, save it as Draft, then preview it.
3. Expected: committee members can create and publish announcements without
   gaining access to plugins or system configuration.

### Media Archive

1. Select **Media Archive**.
2. Upload a harmless test image that contains no private yearbook data.
3. Confirm it appears in the library.
4. Expected: this CMS library is for approved editorial derivatives. Attendee
   uploads still enter the separate private quarantine/moderation pipeline.

### Memory review

1. Select **Memories**.
2. Open a pending/test memory, review the story and rights state, add a featured
   derivative if appropriate, and save or publish it.
3. Expected: only approved material becomes eligible for a public collection.
   Raw yearbook/diploma sources stay private.

### Suggestions

1. Select **Suggestions**.
2. Review a test playlist or event suggestion.
3. Edit its internal response/status and save it.
4. Expected: committee review is available; no suggestion publishes itself.

### Virtual tickets

1. Select **Virtual Tickets**.
2. Review status and ownership on a synthetic ticket.
3. Use the recorded scanner/check-in proof for a valid credential, then scan it
   again.
4. Expected: first scan succeeds; duplicate scan returns a conflict. Refunded,
   cancelled, or failed orders revoke eligible tickets.

### Orders

1. Select **WooCommerce → Orders**.
2. Open only a synthetic/test order.
3. Confirm customer, line items, totals, notes, and order state.
4. Expected: a verified paid transition issues exactly one entitlement/ticket;
   browser redirects alone never issue a ticket.

### Products

1. Select **Products**.
2. Create or edit a test ticket/sponsor product.
3. Confirm its price, visibility, inventory policy, description, image, and
   `_famtastic_ticket_event` mapping before publishing.
4. Expected: unapproved/test products remain non-public. Live prices and real
   charges remain launch-gated.

### Analytics and marketing

1. Select **Analytics** to inspect WooCommerce test-order reporting.
2. Select **Marketing** to inspect available promotion surfaces.
3. Expected: Committee Admin can evaluate operational commerce data but cannot
   install extensions or alter platform-wide configuration.

### Profile

1. Open the account menu and select **Edit Profile**.
2. Confirm the committee member can change their own display/profile details.
3. Expected: they cannot create, promote, delete, or administer other users.

## Attendee portal proof

1. Create an attendee account at
   `http://127.0.0.1:8952/portal/auth/register.html`. The public homepage,
   Explore menu, and footer now expose this path.
2. Use `http://127.0.0.1:8952/portal/auth/login.html` for returning attendees,
   or open `http://127.0.0.1:8952/portal/?mode=demo` only for the visual fixture.
3. Test registration, email verification, login, recovery, and logout.
4. Review the ticket wallet, event guide, uploads, suggestions, and notification
   preferences.
5. Attempt to open `/wp-admin/` with a non-committee WordPress identity.
6. Expected: the account is routed away from staff administration. No WordPress
   menus or CMS terminology appear in the attendee experience.

The WordPress environment must define `FAMTASTIC_FRONTEND_URL`; this is the
explicit redirect boundary that keeps non-committee identities out of the CMS.

Current honest limitation: RSVP/dinner editing remains disabled in the portal
until its authenticated bridge replaces the anonymous legacy form. The UI says
this explicitly and does not fake a successful save.

## Mobile and SEO proof

1. Test attendee and committee screens at 320, 360, and 390 px.
2. Confirm no horizontal overflow, controls remain at least 44 px, and Harry
   does not obstruct forms.
3. Confirm the nine public routes appear in `/sitemap.xml` with route-specific
   metadata and JSON-LD.
4. Confirm WordPress, `/portal/`, and all portal authentication pages are
   `noindex` and omitted from the sitemap.

## Before any production account or payment

- Replace every temporary password with a unique password-manager-generated
  value and enable MFA when the production identity stack supports it.
- Run Stripe success, decline, 3DS, refund, replay, and inventory tests.
- Configure Resend secrets, reply routing, worker heartbeat, retries, and alerts.
- Approve ticket/refund, archive consent, privacy, and retention language.
- Complete backup/restore, migration reconciliation, committee acceptance, and
  explicit cutover approval.

## Committee address and Resend status

- Canonical public address: `committee@mbsh96reunion.com`.
- The public footer, site configuration, backend fallback configuration, and
  transactional templates use the canonical address.
- The Resend HTTPS adapter, queued outbox worker, retries, dead-letter state,
  and integration tests are implemented.
- The real reunion Resend API key is not stored in this worktree. Install it as
  a hosting secret, verify the sender/domain in Resend, and complete one
  controlled external delivery before describing production email as active.
