# FAMtastic Event Cinema agent contract

Read `docs/architecture/EVENT_CINEMA_ARCHITECTURE.md` and
`docs/operations/PRIVATE_ARCHIVE_SOURCE_MANIFEST.md` before changing identity,
commerce, uploads, archival media, email, or scheduled jobs.

Before changing any public page, also read
`docs/architecture/PUBLIC_PAGE_COMPONENT_CONTRACT_2026-08-17.md`. The visible
header, site navigation, and footer are owned by
`frontend/js/cinematic-shell.js`; a page supplies only its ordered content
sections and `data-page` identity. Never add page-relative global navigation.
Run `node tests/frontend/shared-public-shell.test.mjs` after every public-page
or template change.

## Non-negotiable boundaries

- The cinematic frontend owns presentation. WordPress owns editable editorial
  content and approved archive metadata. WooCommerce owns products, orders,
  payments, refunds, and ticket issuance. Temporary PHP adapters may bridge the
  legacy workflows, but must not create a second financial source of truth.
- Originals are private and quarantined. Public pages receive only approved,
  redacted derivatives. Never publish diploma names, signatures, yearbook faces,
  or source scans by default. Do not perform automatic face identification.
- A ticket illustration is not an admission credential. The credential uses a
  random, revocable identifier rendered as a rotating/signed QR code after an
  authorized order is paid.
- Use verified-email accounts, secure HttpOnly cookies, CSRF protection, rate
  limits, opaque public identifiers, organization/attendee scoping, and audit
  records. Never put secrets or card data in browser storage.
- Email and worker actions must be idempotent, retryable, observable, and have a
  dead-letter path. Promotional consent is separate from transactional notices.
- Essential forms, navigation, checkout, tickets, and archive content must work
  without 3D or motion. Respect reduced motion and data-saver preferences.

## Proof standard

Do not call a feature complete from screenshots alone. Test success, invalid,
duplicate, unauthorized, cross-account, network-failure, retry, mobile, keyboard,
and reduced-motion states. Keep test payments and test email recipients gated
until launch approval.

The reusable personal Codex skill is `build-famtastic-event-cinema`. The repo
recipe under `site-studio/recipe/` is canonical for Shay, Claude, Site Studio,
and other agents.
