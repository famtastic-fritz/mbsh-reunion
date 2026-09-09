````
/make-plan Redesign the MBSH attendee, Committee, and owner administration workflow. Current design failed audit at 12/30 with critical gaps in principles #2 useful, #4 understandable, and #6 honest.

Verdict paragraph (quoted from 03-verdict.md):
> The portals need a workflow and information-architecture redesign—not a visual restart—because the audit scored 12/30 and the load-bearing honesty principle scored 0: authoritative operational state must replace static promises before the cinematic styling can be trusted.

Why redesign and not refine: The total is below 20 and static ticket, verification, RSVP, delivery, and connection claims make the load-bearing honesty score 0.

Preserve from current design:
- Burgundy, cream, gold, and red Event Cinema tokens and Playfair Display display voice.
- Server-enforced role/capability model, CSRF, opaque IDs, audit records, and protected records.
- WooCommerce as the financial source of truth and the custom portal as the attendee/committee experience.
- Existing loading, empty, error, success, focus, disabled, reduced-motion, retry, idempotency, and dead-letter primitives.

Primary user: a nontechnical reunion attendee or committee operator; Site Owner Fritz retains the superset of authority.
Primary task: see truthful current state and complete the next authorized reunion action without learning system architecture.
Constraints: vanilla HTML/CSS/JS plus PHP/WordPress/WooCommerce; mobile and keyboard usable; WCAG 2.2 AA floor; no card data or secrets in browser storage; transactional and promotional consent stay separate; Act I campaign language must be phase-configured; no production mutation until owner-approved deployment.

Fix in priority order (top moves from the audit, verbatim):
1. Principle #6 — Honest: derive every ticket, RSVP, payment, delivery, and connection label from an authoritative response; default to loading/unknown, never success. Evidence: frontend/portal/index.html:83,92,107 and frontend/portal/js/portal.js:17,25-26.
2. Principles #2/#4 — Useful and understandable: rebuild navigation around unique route IDs and explicit targets, then browser-test every link for each role. Evidence: frontend/portal/index.html:28-30,154-157,182-185 and frontend/portal/js/portal.js:10-14.
3. Principle #8 — Thorough: reconcile the deployed Committee Portal into version control and add interaction tests for production-only dinner, payment verification, sample tickets, quantity totals, failures, retries, and permissions. Evidence: production/repo hash mismatch and tests/frontend/portal-contract.test.mjs:20-52.
4. Principles #2/#10 — Useful and restrained: make command metrics actionable and consolidate three tours, duplicate ticket panels, and repeated shortcuts into one contextual help pattern. Evidence: frontend/portal/js/committee-workspace.js:2,5, frontend/portal/committee/index.html:2,5, and frontend/js/reunion-navigator.js:96-141.
5. Principles #4/#7 — Understandable and long-lasting: replace operator jargon and hard-coded event-phase language with plain labels and Act-aware configuration. Evidence: frontend/portal/committee/index.html:4,16-17 and wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-brand-experience.php:79-86,128-132.

Before planning implementation, perform an authenticated read-only browser pass through every Committee Portal and WordPress link for Site Owner, Committee Lead, and Committee Member roles. Record URL, expected job, actual result, authority, loading/empty/error/success state, keyboard behavior, mobile behavior, and console/network error for each link. Do not infer success from screenshots or source markers.

Deliverables:
- One-page information architecture separating attendee self-service, committee operations, and owner configuration.
- Truth-state contract for every metric/badge/claim, naming its authoritative endpoint and loading/empty/error/success copy.
- Route matrix with unique IDs, role/capability requirements, and expected destination.
- Migration plan to reconcile production-only files into version control without overwriting unrelated live changes.
- Per-fix target files, exact change, risk, rollback, and verification.
- Browser interaction tests for every navigation item and high-risk payment/ticket workflow.
- Mobile, keyboard, reduced-motion, duplicate, unauthorized, network-failure, and retry acceptance tests.

Anti-patterns to guard against:
- Keeping static success copy as a visual placeholder.
- Treating a configuration key as proof of provider health or delivery.
- Duplicating workspaces or help controls to compensate for unclear navigation.
- Moving financial authority out of WooCommerce or issuing credentials before a paid authoritative order.
- Changing the Event Cinema visual identity when the failure is workflow and truthfulness.
````
