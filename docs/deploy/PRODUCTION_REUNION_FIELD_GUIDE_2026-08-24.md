# MBSH Reunion Field Guide — Production Release

Released: 2026-08-24

## Outcome

The MBSH Reunion Field Guide is live at:

- `https://mbsh96reunion.com/manual/`

The release also made the existing non-mutating orientation tours available:

- Public site: persistent **How it works** launcher.
- Verified attendee portal: **Guide** and **Manual** entry points.
- Committee Desk: **Start tour** and **Manual** entry points.

The Field Guide is a browseable owner/reference experience, not a replacement
for those tours. Its public chapter is available to everyone; its attendee,
committee, and owner material remains role-filtered through the existing portal
session and authorization boundaries.

## Release source

- GitHub production source: `acbe1d0` — `fix: refresh cinematic shell for guide release`
- Feature commits: `8ddcfaa` (role-aware tour) and `3543593` (Field Guide)
- Production reconciliation commits: `d4ea8aa`, `fde29cd`, and `41f9a6f`

The reconciliation records the analytics and Alumni Login code that had already
been deployed to GoDaddy but had not yet been represented on `main`.

## Promotion and rollback

- Release directory: `/home/nineoo/.releases/mbsh-field-guide-20260824T194259Z.YpFWaL`
- Backup: `/home/nineoo/backups/mbsh-field-guide-predeploy-20260824T194259Z.tar.gz`
- Backup SHA-256: `b1bd7f4570a8ce719865498afdbe93c61867c52c5aa5386ea063a67ee34d37ab`
- Promotion was file-scoped and checksum-verified. It did not use a recursive
  delete operation.

Nineteen frontend files were promoted atomically, including the manual/tour
assets, shared shell, portal entry points, and public-page shell cache refresh.
No backend code, database table, attendee record, payment, email, upload, or
WordPress/WooCommerce data changed.

To roll back, restore only the archived files into `/home/nineoo/public_html/`
using a file-scoped, additive operation. Do not use `--delete` and do not touch
approved upload directories.

## Verification

Before promotion:

- `node tests/frontend/shared-public-shell.test.mjs`
- `node tests/frontend/analytics-contract.test.mjs`
- `node tests/frontend/reunion-navigator-contract.test.mjs`
- `node tests/frontend/reunion-manual-contract.test.mjs`
- `node tests/frontend/portal-contract.test.mjs`
- JavaScript syntax checks and `git diff --check`

After promotion:

- `/manual/`, manual assets, and tour assets returned HTTP 200.
- Live HTML returned the `cinematic7` shell reference.
- Browser verification confirmed the live Field Guide title, public route map,
  role-safe locked chapters, presentation mode, and active public `How it
  works` tour.

## Boundaries kept intact

- The guide never exposes passwords, payment data, provider keys, ticket
  credentials, private uploads, dietary details, or another attendee's record.
- It never submits a form, sends an email, approves media, creates payment, or
  changes an account.
- Payment and production-email launch gates remain unchanged.
