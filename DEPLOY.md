# MBSH Reunion — Deployment Guide

## Architecture Overview

| Environment | Domain | Host | Purpose |
|-------------|--------|------|---------|
| **Production** | `mbsh96reunion.com`, `www.mbsh96reunion.com` | GoDaddy (famtasticinc) | Live site — static frontend + PHP backend |
| Staging | `mbsh-reunion-staging.netlify.app` | Netlify | Frontend preview only |
| Local dev | `localhost:8080` | Local PHP server | Development |

## Repo Layout

```
site-mbsh-reunion/
├── frontend/          # Static HTML/CSS/JS (deploy to GoDaddy web root)
├── backend/           # PHP API + admin (deploy to GoDaddy web root)
├── netlify.toml       # Netlify redirects (staging only)
└── scripts/           # Deployment helpers
```

## Production — GoDaddy

**Hosting Account:** famtasticinc  
**Server Path:** `/home/nineoo/public_html/` (or equivalent web root)  
**PHP Version:** 8.x  
**Database:** MariaDB/MySQL

### Production Secrets File

**Path:** `/home/nineoo/.config/mbsh-config.php`

This file lives **outside web root** with mode `0600`. It is the single source of truth for production credentials.

The file contains database, mail, session, and integration secrets. Never copy
its values into Git, deployment output, tickets, chat, or documentation. Use
the approved account-recovery or owner-authorized rotation procedure when an
administrator cannot sign in.

### Deployment Steps

1. Run the relevant automated and browser tests.
2. Commit and push the approved release.
3. Preview the exact commit-addressed production release:
   ```bash
   ./scripts/deploy-production.sh <commit> --dry-run
   ```
4. Review every changed and retired path, then deploy the same commit:
   ```bash
   ./scripts/deploy-production.sh <commit>
   ```
5. Verify the deployed commit, checksums, PHP syntax, routes, authentication
   boundaries, and the release-specific browser journey.

Normal application rollback redeploys a previous Git commit through
`scripts/rollback-production.sh`. See
[`docs/operations/GIT_PRODUCTION_RELEASES.md`](docs/operations/GIT_PRODUCTION_RELEASES.md).

### What Lives on GoDaddy

- All static HTML from `frontend/` (`index.html`, `menu.html`, `survey.html`, etc.)
- All PHP endpoints from `backend/` (`menu.php`, `survey.php`, `survey2.php`, `rsvp.php`, etc.)
- Role-aware attendee and committee portal at `/portal/`
- Owner-only WordPress and commerce administration under `/cms/wp-admin/`
- Database tables: `rsvps`, `menu_selections`, `surveys`, `poll_*`, `admin_*`, etc.

## Staging — Netlify

**Site:** `mbsh-reunion-staging.netlify.app`  
**Purpose:** Frontend preview only. Backend calls still hit GoDaddy.

### Deploy Staging

```bash
./scripts/push-staging.sh
```

This pushes the current branch to the `staging` branch, which triggers Netlify deploy.

## Local Development

```bash
cd sites/site-mbsh-reunion
php -S localhost:8080 -t frontend/
```

For backend testing, symlink or copy `backend/` into the docroot, or run a second PHP server on a different port.

## DNS / Domain Setup

| Record | Target | Notes |
|--------|--------|-------|
| `mbsh96reunion.com` A | `107.180.51.234` | Primary live domain on GoDaddy |
| `www.mbsh96reunion.com` CNAME | `mbsh96reunion.com` | Canonical alias to the same GoDaddy host |
| `api.mbsh96reunion.com` | Legacy/compatibility only | Do not treat as the primary production frontend path |

**Current production truth:** the live frontend and backend both resolve from the GoDaddy hosting account under the main customer domain. Netlify is not the live production host for `mbsh96reunion.com`.

## Database Schema Notes

The production database has tables that were created incrementally. Key tables:

- `rsvps` — RSVP submissions
- `menu_selections` — Gold Menu dinner preferences
- `surveys` — Quick RSVP / survey responses (includes `is_imported` flag for historical CSV)
- `poll_questions`, `poll_options`, `poll_votes` — Poll system
- `memories` — Photo/memory submissions
- `time_capsules` — Time capsule entries
- `chatbot_questions` — Hi-Tide Harry chat logs
- `sponsors_pending`, `sponsors_approved` — Sponsor system
- `admin_login_attempts`, `admin_audit_log` — Admin auth logging

**Schema file:** `backend/schema.sql` (partial — does not include all tables above)

## Email Flow

| Trigger | To | From | Content |
|---------|----|------|---------|
| Menu submission | Submitter | `harry@send.mbsh96reunion.com` | Selection confirmation |
| Menu submission | Committee | `committee@send.mbsh96reunion.com` | Full selection details |
| Menu submission | `valerievalcourt96@gmail.com` | `committee@send.mbsh96reunion.com` | Alert with admin link |
| Survey submission | Submitter | `harry@send.mbsh96reunion.com` | RSVP confirmation |

## Troubleshooting

**Admin login fails:**
- Use the portal's approved password-recovery flow.
- If recovery is unavailable, perform an owner-authorized credential rotation
  without printing or committing the replacement secret.

**Form submissions 403 / CORS error:**
- Verify `allowed_origins` in config includes `https://mbsh96reunion.com`
- If unified hosting (no subdomain), CORS should not trigger for same-origin requests

**Emails not sending:**
- Check `resend_api_key` is valid in production config
- Verify `resend_from_domain` (`send.mbsh96reunion.com`) is verified in Resend dashboard

**Database connection errors:**
- Verify `db_host`, `db_name`, `db_user`, `db_password` in production config
- Check MySQL/MariaDB is running on GoDaddy
