# MBSH Alumni Portal Invitation — Production Release

Released: 2026-08-20T18:42:20Z
Public site: `https://mbsh96reunion.com/`

## What changed

- Added a persistent **Alumni Login** action beside the public navigation menu.
- Added a homepage campaign that explains the value of an attendee account and
  links to the real registration and login routes.
- Added a non-blocking Hi-Tide Harry invitation: it appears after 45 seconds,
  stays for 25 seconds, is dismissible, and respects reduced-motion settings.
- Kept the existing Harry guide from overlapping the invitation; it restores
  after the invitation closes.

## Release source

- Feature commit: `6f7d58a` — foreground alumni portal registration.
- Mobile overlap correction: `1678b79` — keep the guide behind the invitation.
- Frontend files promoted: homepage; eight public interior routes; shared
  cinematic CSS; shared shell JavaScript.
- No backend, database, email, WooCommerce, payment, or portal-account data was
  changed.

## Backup and integrity

- Rollback archive: `/home/nineoo/backups/mbsh-alumni-portal-invitation-20260820T183631Z.tar.gz`
- Rollback archive SHA-256:
  `db1c4d52018ee9f90e5f1307815de7abd9a27bf4b127ea19114e88c226dd3c9b`
- Each promoted frontend file was uploaded to a remote temporary name, checked
  against its local SHA-256, then atomically renamed into the GoDaddy web root.

## Production proof

- Apex and `www` return HTTP 200.
- The live shell serves the `cinematic6` release and contains the Alumni Login,
  45-second trigger, and 25-second dwell constants.
- Browser verification at 1440 × 1000 and 390 × 844 confirmed the login target,
  invitation reveal and dismissal, no horizontal overflow, no overlap with the
  Harry guide, and zero page errors.

## Rollback

Restore the archive above to `/home/nineoo/public_html/` using an additive,
file-scoped restore. Do not use `--delete`; it could affect approved uploads or
unrelated operational content.
