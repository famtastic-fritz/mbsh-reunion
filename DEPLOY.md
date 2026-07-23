# MBSH Reunion — Deployment Guide

## Architecture Overview

| Environment | Domain | Host | Purpose |
|-------------|--------|------|---------|
| **Production** | `mbsh96reunion.com` | GoDaddy (famtasticinc) | Live site — static frontend + PHP backend |
| ~~Production (old)~~ | ~~`api.mbsh96reunion.com`~~ | ~~GoDaddy~~ | ~~Legacy backend subdomain — being retired~~ |
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

```php
<?php
return [
  'db_host'               => 'localhost',
  'db_name'               => 'mbsh_reunion_v2',
  'db_user'               => 'mbsh_reunion_v2_user',
  'db_password'           => '***REDACTED***',
  'resend_api_key'        => '***REDACTED***',
  'resend_from_domain'    => 'send.mbsh96reunion.com',
  'committee_email'       => 'mbsh96reunion@gmail.com',
  'menu_notification_email' => 'valerievalcourt96@gmail.com',
  'admin_password_hash'   => '$2y$12$teqyXpBFkhT2sEaiW77ZguekS5wB4cVQF.wnyG0UNrdgjECTJOB8u',
  'admin_csrf_secret'     => '***REDACTED***',
  'allowed_origins'       => ['https://mbsh96reunion.com', 'https://www.mbsh96reunion.com'],
  'environment'           => 'production',
];
```

**Current admin password:** `Letmein123`  
**Password hash (above):** bcrypt hash of `Letmein123`

### Deployment Steps

1. SSH into GoDaddy hosting
2. `cd /home/nineoo/public_html/` (or the actual web root)
3. Pull latest from GitHub OR rsync the repo:
   ```bash
   # Frontend assets
   rsync -avz --delete frontend/ /home/nineoo/public_html/
   
   # Backend PHP
   rsync -avz --delete backend/ /home/nineoo/public_html/
   ```
4. Verify `/home/nineoo/.config/mbsh-config.php` exists and has the correct hash
5. Test: `https://mbsh96reunion.com/menu.php` should respond to POST

### What Lives on GoDaddy

- All static HTML from `frontend/` (`index.html`, `menu.html`, `survey.html`, etc.)
- All PHP endpoints from `backend/` (`menu.php`, `survey.php`, `survey2.php`, `rsvp.php`, etc.)
- Admin panel at `/admin/` (PHP, session-based auth)
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
| `mbsh96reunion.com` A | GoDaddy server IP | Primary domain |
| `www.mbsh96reunion.com` CNAME | `mbsh96reunion.com` | Redirect to primary |
| ~~`api.mbsh96reunion.com`~~ | ~~GoDaddy~~ | ~~RETIRING — do not use~~ |

**Goal:** Everything under `mbsh96reunion.com`. No `api.` subdomain.

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
- Check `/home/nineoo/.config/mbsh-config.php` has correct `admin_password_hash`
- Current hash above is for password `Letmein123`

**Form submissions 403 / CORS error:**
- Verify `allowed_origins` in config includes `https://mbsh96reunion.com`
- If unified hosting (no subdomain), CORS should not trigger for same-origin requests

**Emails not sending:**
- Check `resend_api_key` is valid in production config
- Verify `resend_from_domain` (`send.mbsh96reunion.com`) is verified in Resend dashboard

**Database connection errors:**
- Verify `db_host`, `db_name`, `db_user`, `db_password` in production config
- Check MySQL/MariaDB is running on GoDaddy
