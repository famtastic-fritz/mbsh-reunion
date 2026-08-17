# FAMtastic Event Cinema — Canonical Architecture

## Three experiences, one governed system

1. **My Reunion** (`/portal/`) is the verified attendee experience. It owns the
   attendee-managed RSVP, dinner, ticket wallet, memories, suggestions,
   messages, notifications, and preferences.
2. **Admin Portal** (`/portal/admin/`) is the shared operational experience for
   Committee Members, Committee Leads, and Site Owners. Every route and action
   is filtered by a server-checked capability; hiding a link is never the
   authorization boundary.
3. **Full Site Admin** (`/wp-admin/`) is the owner-only CMS and commerce studio.
   It is not an attendee or ordinary committee interface.

## Authorities

- WordPress: editable pages, components, announcements, approved archive
  records, FAQs, sponsors, and editorial revisions.
- WooCommerce: products, carts, orders, payments, refunds, and financial truth.
- Portal service: attendee identity, verification, sessions, staff membership,
  current event response, private submissions, conversations, notifications,
  and ticket-wallet presentation.
- Resend/outbox: queued transactional delivery with retry and dead-letter state.
- Production snapshot: read-only legacy evidence until reconciled and migrated.

## Non-negotiable boundaries

- Attendees never receive WordPress access.
- Committee access is revocable and least-privilege.
- Site Owners cannot remove their own owner access from the portal.
- Payment, audit, delivery, and snapshot records use explicit lifecycle actions;
  they are not generic editable/deletable CRUD rows.
- Private uploads remain quarantined until moderation and approved-public
  derivatives are created.
- Every destructive action requires confirmation and preserves an audit trail.
- Live payments, production imports, and live mail remain closed until their
  launch gates are explicitly approved and evidenced.

## Reusable product boundary

The reusable offering is **FAMtastic Event Cinema**: a cinematic public site,
verified attendee portal, role-based event operations console, owner CMS,
commerce/ticket adapter, moderated archive, virtual ticket wallet, contextual
assistant curriculum, and evidence-driven launch gate.
