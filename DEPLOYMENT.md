# MBSH96 Reunion — Deployment

**This file is superseded by [`DEPLOY.md`](DEPLOY.md).**

Production is unified on GoDaddy hosting: both the static frontend and the PHP
backend serve from the same webroot under `mbsh96reunion.com` /
`www.mbsh96reunion.com`. Netlify is staging/preview only. The `api.mbsh96reunion.com`
subdomain referenced in older material is legacy/compatibility only — it is
not the primary production path.

The legacy shared-password admin panel at `/admin/login.php` is also gone —
`.htaccess` now redirects that path to `/portal/login`, the role-aware
attendee/committee portal. Any doc or link still pointing at
`/admin/login.php` as the way in is stale.

See `DEPLOY.md` for the current architecture overview, GoDaddy deployment
steps, staging deploy, local dev, DNS, database schema notes, email flow, and
troubleshooting.
