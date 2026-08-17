# FAMtastic Reunion WordPress Proof

## Production operations layer

The reviewed plugin set is captured in `plugins.lock.json`. FluentCRM receives explicit promotional opt-ins only. Fluent Forms is for public polls, surveys, volunteer interest, and contact intake—not portal identity, private media, checkout, or ticketing. WP Mail SMTP uses the native Resend mailer and reads its credential from protected server configuration. WP Crontrol is owner-only visibility; system cron remains the trigger. Two-Factor is enrolled and recovery-tested before enforcement.

The custom **Growth & Delivery** screen explains the connections and their authority boundaries to the site owner.

This directory is the reusable WordPress/WooCommerce control-plane source. A
production copy is installed under `/cms`, but it does not replace the current
custom operational backend or become payment authority until the recorded
migration and provider gates pass.

## Local proof

1. Copy `.env.example` to `.env` and change the local-only passwords.
2. Run `docker compose up -d` with Compose v2. On this workstation the v2 subcommand is absent and the installed, working legacy command is `docker-compose up -d`.
3. Open `http://localhost:8096`, complete WordPress setup, activate **FAMtastic Reunion Platform**, and activate the **FAMtastic Event Cinema** theme.
4. Install WooCommerce and the official WooCommerce Stripe gateway in test mode. Action Scheduler is provided by WooCommerce.
5. Create a WooCommerce product and add custom product metadata `_famtastic_ticket_event=mbsh-1996-30th`.

The plugin exposes authenticated REST contracts at `/wp-json/famtastic/v1/*`. Browser clients use WordPress session cookies plus the REST nonce; they must never receive Stripe or Resend secrets.

### Recorded local proof (2026-08-16)

The legacy Compose command started MariaDB, WordPress, and the cron container.
WordPress was installed locally and updated to 7.0.4; the FAMtastic plugin,
WooCommerce 11.0.1, and WooCommerce Stripe Gateway 10.8.5 activated. No provider
keys or production data were used. The pinned WordPress image matches the tested
core version because current WooCommerce requires WordPress 6.9 or newer.

The local proof also activates a from-scratch FAMtastic Event Cinema theme and
the plugin's matching committee-studio admin/login treatment. The public
WordPress fallback is intentionally `noindex`: the cinematic frontend owns
public search visibility, while WordPress owns content and commerce. Visible
“Powered by WordPress” presentation is not required and is not used; useful
WooCommerce labels, privacy links, updates, and security notices remain intact.

### Committee access

Assign trusted operators the **Committee Admin** role under Users. It provides
the branded command center plus event content, media, suggestions, products,
orders, and ticket operations. It deliberately excludes plugins, themes,
platform settings, and user administration. Full WordPress Administrators keep
those system capabilities. Non-committee WordPress users are routed away from
the staff dashboard and back to the attendee experience.

The complete role-by-role verification walkthrough is in
`../docs/operations/COMMITTEE_MANUAL_PROOF_GUIDE.md`.

## Deliberate boundaries

- WordPress: identity, editorial content, moderated uploads, preferences, orders, ticket records, staff GUI.
- WooCommerce: catalog, checkout/order state, coupons, refunds, customer order history.
- Stripe gateway: payment collection and webhook-driven payment state. No card data enters WordPress.
- Existing PHP application: stays authoritative until each capability passes migration acceptance.
- React/static frontend: branded presentation through a same-origin BFF or cookie-authenticated REST calls.

See `../docs/HEADLESS_WORDPRESS_OPERATIONS.md` for launch gates and rollback.

Run `./tests/static-check.sh` for PHP syntax, capability-manifest parsing, provider-secret detection, and required ticket reliability contract checks.
