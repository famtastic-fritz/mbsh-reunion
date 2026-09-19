# Creator credit — owner-authorized rollout

Scope: MBSH96 Reunion must be the first live site receiving the new mandatory
final-row logo. Existing customer/footer wording, identity, forms, accounts,
payments, tickets, uploads, records and email history are preserved.

Source: `frontend/templates/creator-credit.html`; deterministic sync/check in
`scripts/sync-creator-credit.mjs`. All 52 complete authored frontend/backend/theme
HTML documents covered, including 3 source templates and legacy backend HTML
copies. API/JSON bodies, content fragments and prior report/evidence archives are
not web-page targets. Plain-text messages cannot carry clickable image rows.
The existing PHP and WordPress Resend adapters append the same centered credit
to future rendered HTML deliveries; message content, recipients and transport
remain unchanged. No mail is sent or resent for this rollout, and stored mail
history is not rewritten. Existing plain-text alternatives get a text link.

Asset: unmodified September 17 owner-approved PNG from agency canonical brand
directory. SHA256: `ebb0477344132d32e449ba19e2b622921585aa71af0decdbcf8abfbe033fa950`.
Link: https://famtasticdesigns.com/ with public `mbsh96reunion` source,
`creator_credit` medium and `created_by_famtastic` campaign. No new analytics
script, cookies or person-specific identifiers.

Local: 9 test files pass (shared shell, creator credit, email credit, portal, commerce, SEO,
analytics, manual, navigator); foundation identity validation passes. The
production builder checks the candidate's own source and original logo before
packaging. Existing historical commits remain usable for code-only rollback.

Baseline live commit: `5ed9a49314aac4195354094b56c1e1abe2603d4d`.
Source base: `29e687e`, independent-source docs only since live baseline.
First production release: `0653d920d20fe82aa429be7901adcd8544b7be65`,
43 changed files, zero retired. Browser verified the exact logo loads once and
links to the tagged agency URL. Follow-up `ce933426390eda6682105fb481396e883021e49b`
adds clearance for fixed guide buttons and the mobile chat greeting. PHP lint
passes on every changed PHP file. The 390px browser check showed a centered
190px logo; layout needs to retain clearance below it for persistent controls.
Final runtime release: `76b578c23fe76202fb39323367de1412afa1a6de` is on
`origin/main` and production. The email follow-up changed exactly three runtime
PHP files and retired none. Deployment verified the full release manifest;
the remote logo SHA256 equals the approved source. Public homepage mobile and
desktop plus portal sign-in desktop were visually checked. No authenticated
admin workflow, inbox delivery or Gmail/Outlook rendering is claimed by this
footer-only check.

One intermediate dry-run failed due to temporary local disk exhaustion; the
remote host had ample space. The retry passed without changing the deployment
method or deleting user data. Immutable release checksums validate production.
