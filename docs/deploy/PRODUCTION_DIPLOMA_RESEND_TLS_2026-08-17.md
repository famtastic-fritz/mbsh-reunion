# Production Diploma, Resend, and TLS Release — 2026-08-17

## Outcome

The approved Diploma RSVP experience, WordPress-to-Resend delivery bridge, and
renewed API TLS certificate are live in production. Stripe and real ticket
charges remain unchanged and disabled.

## Release

- Commit: `7bb9734`
- Branches: `main` and `feature/event-cinema-portal`
- RSVP: `https://mbsh96reunion.com/rsvp.html`
- WordPress owner plane: `https://mbsh96reunion.com/cms/wp-admin/`
- API: `https://api.mbsh96reunion.com/`

## TLS

- A new Let's Encrypt certificate was issued for
  `api.mbsh96reunion.com` through an HTTP-01 challenge served from the cPanel
  web root.
- cPanel installed the certificate and restarted the Apache virtual host.
- Verified issuer: Let's Encrypt `YE2`.
- Validity: August 17, 2026 through November 15, 2026.
- External HTTPS verification passes without `--insecure`.

## Email

- Site Studio's existing Resend key and reunion sender identities were reused;
  no secret was committed.
- Resend reports `send.mbsh96reunion.com` as verified.
- The custom portal continues using its queued Resend delivery worker.
- WordPress now intercepts `wp_mail` through the FAMtastic Reunion Platform
  plugin and sends via Resend with an idempotency key, provider-status record,
  attachment support, and `wp_mail_failed` signaling.
- Production WordPress proof delivery succeeded with provider ID
  `6daff62e-9a4b-41a3-bf27-a15af8784cb7`.
- A configuration proof message created no attendee, campaign, order, or ticket
  record.

## Diploma RSVP

- The existing production RSVP field names, PHP endpoint, database behavior,
  confirmation email, anti-spam fields, and public-display consent remain
  intact.
- Desktop visitors can choose the ceremonial book-opening sequence or skip it.
- Mobile and reduced-motion visitors receive the accessible form immediately.
- The experience uses recreated burgundy leather, parchment, school line-art,
  spine, page stack, and medallion styling. The private diploma photographs and
  their names/signatures were not published.

## Safety and rollback

- Pre-deploy archive:
  `/home/nineoo/backups/diploma-resend-20260817T150417Z`
- The archive contains the affected public files, WordPress database export,
  WordPress plugin/theme, and protected reunion configuration.
- Deployment was additive and did not delete approved uploads or production
  operational records.
- Stripe configuration and ticket charging were not modified.

## Verification

- API certificate chain and dates: pass.
- API CORS preflight from the public origin: HTTP 204, pass.
- RSVP HTML and diploma CSS/JavaScript: HTTP 200 with release markers, pass.
- WordPress FAMtastic theme active: pass.
- FAMtastic Reunion Platform version 0.4.1 active: pass.
- WordPress Resend configuration and real controlled delivery: pass.
- PHP syntax, JavaScript syntax, static security contracts, HTML references,
  and Git whitespace checks: pass.
