# MBSH96 Reunion — Deployment Guide

## Architecture Overview

| Layer | Host | URL |
|-------|------|-----|
| Frontend (static HTML/JS/CSS) | Netlify | `https://mbsh96reunion.com` |
| Backend (PHP + MariaDB) | GoDaddy | `https://api.mbsh96reunion.com` |
| Admin dashboard | GoDaddy | `https://api.mbsh96reunion.com/admin/` |
| Email delivery | Resend | `send.mbsh96reunion.com` |

## Repositories

- **GitHub:** `famtastic-fritz/mbsh-reunion`
- **Branch:** `main`

## Local Development

1. Copy `.env.example` to `.env` and fill in dev credentials.
2. Serve `frontend/` from any static server (e.g. `npx serve frontend` on `localhost:3000`).
3. Backend expects a local MariaDB/MySQL instance. Run `backend/schema.sql` to create tables.
4. Set `ADMIN_PASSWORD_HASH` in `.env` using:
   ```bash
   php -r 'echo password_hash("Letmein123", PASSWORD_DEFAULT);'
   ```

## Production (GoDaddy)

### Backend code deploy
SSH into GoDaddy and pull `backend/` to the document root or symlink it:
```bash
cd ~/public_html/api
# or wherever the backend lives
git pull origin main
```

### Database
Production DB is on GoDaddy MariaDB. If the `menu_selections` table is missing, run the `CREATE TABLE` block from `backend/schema.sql` against the production database.

### Secrets file
Production config lives **outside the repo** at:
```
/home/nineoo/.config/mbsh-config.php
```

**To set the admin password to `Letmein123`:**
```bash
# Run this ON THE GODADDY SERVER via SSH:
php -r 'echo password_hash("Letmein123", PASSWORD_DEFAULT);'
# Paste the output into /home/nineoo/.config/mbsh-config.php under:
#   'admin_password_hash' => '<paste-here>',
```

**To update the notification email on production:**
In `/home/nineoo/.config/mbsh-config.php`, ensure this key is set:
```php
'menu_notification_email' => 'valerievalcourt96@gmail.com',
```

### Notification email
`menu_notification_email` in the config file (or `MENU_NOTIFICATION_EMAIL` in `.env`) controls where the third "Menu Submission Alert" email goes. Defaults to `valerievalcourt96@gmail.com`.

## Admin Access

- **Direct backend login:** `https://api.mbsh96reunion.com/admin/login.php`
- **Root-domain redirect:** `https://mbsh96reunion.com/admin/login.php` → redirects to the backend login via Netlify `302` rule in `netlify.toml`.

## Email Senders

| Purpose | From address | To address |
|---------|-------------|------------|
| Submitter confirmations | `harry@send.mbsh96reunion.com` | Person who filled out the form |
| Committee notifications | `committee@send.mbsh96reunion.com` | `mbsh96reunion@gmail.com` |
| Menu alerts | `committee@send.mbsh96reunion.com` | `valerievalcourt96@gmail.com` |

## Common Issues

- **Form submissions not hitting DB:** Verify `menu_selections` table exists on the production database. The table definition was added to `backend/schema.sql` but may need to be run manually on the server if it was created ad-hoc earlier.
- **Admin password not working:** The hash must be generated on the GoDaddy server using the PHP version installed there (`php -r 'echo password_hash(...)')`. Copying a hash generated on a different PHP version may fail.
