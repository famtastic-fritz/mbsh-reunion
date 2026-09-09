# Scope

- Audited surfaces: production Admin Portal at `https://mbsh96reunion.com/portal/admin/`, attendee portal at `https://mbsh96reunion.com/portal/`, WordPress full-site administration at `https://mbsh96reunion.com/cms/wp-admin/`, and their local implementations under `frontend/portal/` and `wordpress/`.
- Primary users: the Site Owner (Fritz), Committee Leads (including Valerie), Committee Members (including Gussie), and registered reunion attendees.
- Primary tasks: understand current reunion operations, reconcile people/RSVP/dinner/tickets/messages, act within role permissions, inspect authoritative payment and delivery state, and reach full-site editing only when authorized.
- Constraints: read-only audit; no production mutations; FAMtastic Event Cinema brand; WordPress/WooCommerce plus the custom portal service; server-enforced least privilege; usable on desktop and mobile; keyboard accessible; reduced-motion capable; financial and email claims must map to authoritative records.
- Reference direction: Stripe-like operational clarity for payment lifecycle and exceptions, while retaining the MBSH cinematic identity and the Act I campaign language.
- Audit method: physical navigation of every visible portal link, console/error inspection, responsive and keyboard checks, implementation tracing, local-run attempt where supported, and defect-first review of the current branch.
