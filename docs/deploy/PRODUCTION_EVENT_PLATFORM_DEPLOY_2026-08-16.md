# Event Platform Production Deployment — 2026-08-16

## Outcome

The attendee portal, role-aware Admin Portal, trivia engine, transactional
outbox worker, FAMtastic WordPress control plane, WooCommerce, and the Stripe
gateway extension were installed on `mbsh96reunion.com`.

Production received no local demo trivia, ticket, conversation, submission, or
email rows. Existing reunion records and approved uploads remain in place.

## Release

- Commit: `410e748`
- GitHub branches: `main` and `feature/event-cinema-portal`
- Public application: `/`
- Attendee workspace: `/portal/`
- Branded identity entry: `/portal/login`
- Role-aware administration: `/portal/admin/`
- Owner WordPress control plane: `/cms/wp-admin/` (also linked from `/wp-admin/`)

## Safety and rollback

- Pre-deploy archive:
  `/home/nineoo/backups/event-cinema-20260817T014211Z/public_html.tar.gz`
- Pre-deploy database dump:
  `/home/nineoo/backups/event-cinema-20260817T014211Z/database.sql`
- Pre-deploy secret-config copy:
  `/home/nineoo/backups/event-cinema-20260817T014211Z/mbsh-config.php`
- Deployment was additive and did not use `rsync --delete`.
- Portal tables were created empty. The local proof round was not promoted.
- WordPress uses the isolated `mbshwp_` table prefix and `/cms` filesystem path.
- WooCommerce Stripe is installed but disabled; its setting remains test mode.
- Existing custom tables remain the rollback and operational authority until
  the parity audit declares each capability migrated or bridged read/write.

## Production workers

- Portal Resend outbox: every minute through system cron.
- WordPress/Action Scheduler: every minute through system cron using PHP CLI.
- Both commands use durable queues and may be inspected from the Admin Portal
  or owner control plane.

## Proof

- Public, attendee, login, registration, Admin Portal, trivia JavaScript,
  ticket art, and WordPress login returned HTTP 200.
- Anonymous portal session returned `authenticated:false`.
- Anonymous trivia access returned `authentication_required`.
- Legacy `/admin/login.php` redirects to `/portal/login`.
- `/wp-admin/` redirects to the isolated `/cms/wp-admin/` owner plane.
- An authenticated production smoke test proved Fritz is an active Site Owner,
  could access the staff dashboard and Trivia Studio, and saw zero production
  trivia games. The temporary smoke password was immediately retired.
- WordPress 7.0.4, FAMtastic Reunion Platform 0.3.1, WooCommerce 11.0.1, and
  WooCommerce Stripe Gateway 10.8.5 are active.
- WordPress REST and the FAMtastic REST namespace returned HTTP 200.
- Password-setup messages for both the attendee Site Owner identity and the
  separate WordPress owner were sent to `fritz.medine@gmail.com`.

## Deliberate launch gates

This deployment makes the experiences and administration available; it does
not authorize real charges or bulk migration. Stripe remains disabled, and
legacy RSVP/menu/ticket/memory/capsule records stay authoritative until the
documented parity, payment-provider, legal, and rollback acceptance checks pass.
