# MBSH Class of '96 — 30th Reunion (v2 Cowork build)

**Built by Cowork as A/B counterpart to the canonical `mbsh-reunion` repo.** Same V1-BRIEF spec, full multi-page implementation, live PHP backend, Hi-Tide Compass nav, chatbot widget Phase 1, sponsor flow with manual approval gate, time capsule, memorial, playlist.

The canonical hero (at `mbsh-reunion`) is preserved verbatim here. Sections 2-11 + backend + admin are the new build.

---

## What's in this repo (full inventory)

### Frontend (`frontend/`)

| File | Role |
|---|---|
| `index.html` | Hero (matches canonical) + Story (Then/Now/Forever) + event details + previews to other pages |
| `rsvp.html` | Countdown + progressive-disclosure RSVP form + confetti success |
| `tickets.html` | Two ticket tier cards + 5 sponsor tier cards + sponsor inquiry modal + sponsor wall preview |
| `through-years.html` | Five-era timeline + memory submission form |
| `memorial.html` | In Memory list (auto-hides if empty) |
| `capsule.html` | Wax-sealed envelope time capsule form |
| `playlist.html` | Spotify embed + suggest-a-track form |
| `404.html` | Confused-Harry not-found page |
| `css/base.css` | Resets, root vars (palette + font system), layout primitives |
| `css/typography.css` | Font scales, page-header treatments |
| `css/hero.css` | Hero composition (matches canonical) |
| `css/story.css` | Three-moment scroll narrative + event details + previews |
| `css/sections.css` | RSVP, Tickets, Sponsors, Timeline, Memorial, Capsule, Playlist styling |
| `css/compass.css` | Hi-Tide Compass right-side circular medallion nav (radial menu, mobile-stacked) |
| `css/chatbot.css` | Hi-Tide Harry floating widget (FAQ + fallback) |
| `css/footer.css` | Three-column footer with FAMtastic credit |
| `js/config.js` | Loads `/config/site-config.json`, applies `data-config-bind` + `data-payments-state` |
| `js/main.js` | IntersectionObserver scroll fades, form-loaded-at stamping, shared helpers |
| `js/countdown.js` | Live countdown to reunion date 7:00 PM ET |
| `js/compass.js` | Compass nav toggle + outside-click + Escape |
| `js/chatbot.js` | 8-FAQ matcher + graceful fallback collector (NO hallucinated answers) |
| `js/rsvp.js` | Progressive disclosure on attending=yes + form submit + confetti success |
| `js/sponsor.js` | Modal open/close + tier preselect + form submit + sponsor wall poll |
| `js/memory.js` | Memory submission with optional photo upload |
| `js/memorial.js` | Renders /in-memory.php; hides section if empty |
| `js/time-capsule.js` | Capsule form submit + wax-seal success |
| `js/playlist.js` | Spotify embed activation + suggest-a-track |
| `assets/mascot/01..10.png` | Hi-Tide Harry pose library (md5-verified copies from canonical) |
| `assets/backgrounds/01..05.mp4` | Leonardo background loops (dancefloor in hero) |
| `assets/brand-mark/brand-mark.png` | The 30+100 commissioned mark |

### Backend (`backend/`) — PHP/MySQL

| File | Role |
|---|---|
| `lib/config.php` | Dual-source config loader (production: `/home/<user>/.config/mbsh-config.php`; dev: `.env` at repo root) |
| `lib/db.php` | PDO MariaDB factory with utf8mb4 + exception mode |
| `lib/cors.php` | Allow-list + Netlify regex pattern enforcement + per-endpoint-class No-Origin policy |
| `lib/validate.php` | Required, optional, email, enum, int, bool, honeypot, form-load-timestamp helpers |
| `lib/rate-limit.php` | MySQL-backed per-IP per-endpoint rate limiting |
| `lib/resend.php` | Resend `POST /v1/emails` wrapper + ResendError |
| `lib/upload.php` | MIME-via-finfo + size + dimensions + UUID rename + SVG explicit reject |
| `lib/csrf.php` | HMAC tokens for admin state-change |
| `lib/admin-auth.php` | Session cookie + login throttle + audit log helper |
| `rsvp.php` | RSVP submission → `rsvps` + Resend confirm + committee notify |
| `sponsor.php` | Sponsor inquiry (multipart) → `sponsors_pending` + Resend notify with admin review link |
| `memory.php` | Memory submission (multipart) → `memories` (approved=0) + Resend notify |
| `capsule.php` | Time capsule → `time_capsules` (queued for July 12) |
| `chatbot-question.php` | Chatbot fallback question + email → `chatbot_questions` |
| `attendees.php` | GET public attendee feed (display_publicly only, no PII) |
| `sponsors.php` | GET approved sponsor wall feed |
| `in-memory.php` | GET In Memory list (active only) |
| `admin/login.php` | Admin login form + throttle + audit |
| `admin/logout.php` | Session destroy + redirect |
| `admin/dashboard.php` | Counts (RSVPs, sponsors, memories, capsules, chatbot fallbacks) |
| `admin/review-sponsor.php` | Approve / reject pending sponsors (CSRF-gated) |
| `admin/review-memory.php` | Approve / reject pending memories (CSRF-gated, moves photo on approve) |
| `admin/manage-in-memory.php` | Add / deactivate In Memory entries |
| `admin/serve-pending-upload.php` | Auth-gated streaming of files OUTSIDE web root |
| `cron/send-capsules.php` | Daily 7am UTC. Sends capsules whose send_date passed. CLI-guarded. |
| `cron/cleanup-rate-limits.php` | Daily. Trims rate_limits >24h + admin_login_attempts >7d. CLI-guarded. |
| `schema.sql` | 10 tables, utf8mb4, idempotent (CREATE TABLE IF NOT EXISTS) |
| `uploads/approved/.htaccess` | Apache hardening: deny PHP exec, deny non-image, nosniff |

### Config + scripts

| File | Role |
|---|---|
| `config/site-config.json` | Public site config (REUNION_DATE, prices, payment status, etc.) |
| `netlify.toml` | Publish dir + headers (CSP, X-Frame-Options, immutable assets cache) |
| `.gitignore` | .env, secrets, uploads, story stills work |
| `.env.example` | Dev environment template |
| `DATA-PERSISTENCE.md` | Architecture + secrets strategy + dev/prod boundary |

---

## Deploy (manual today, platform-capability tomorrow)

### Frontend — Netlify

```
publish dir: frontend/
custom domain: mbsh96reunion.com (apex + www)
SSL: Let's Encrypt via Netlify
```

Future: invocation should be `platform.connect_netlify(site=mbsh-reunion-v2)` per the platform capability spec at `~/famtastic/platform/capabilities/deploy/connect-netlify.sh`. Today: manual UI flow.

### Backend — GoDaddy cPanel (`nineoo` account)

```
1. Run: bash ~/famtastic-sites/mbsh-reunion-v2/scripts/setup-mbsh-backend.sh
   (captures credentials in TTY, applies schema via SSH, writes
    /home/nineoo/.config/mbsh-config.php mode 0600)
2. rsync -avz --exclude=.env backend/ nineoo@FAMTASTICINC.COM:public_html/
3. Register cron in cPanel:
     0 7 * * * /usr/bin/php /home/nineoo/public_html/cron/send-capsules.php
     0 3 * * * /usr/bin/php /home/nineoo/public_html/cron/cleanup-rate-limits.php
4. Smoke test from EXTERNAL machine:
     curl -X POST https://api.mbsh96reunion.com/rsvp.php \
       -H "Origin: https://mbsh96reunion.com" -H "Content-Type: application/json" \
       -d '{"first_name":"Test","last_name":"Smoke","email":"test@example.com","attending":"yes","form_loaded_at":TIMESTAMP_4S_AGO}'
```

Future: invocation should be `platform.deploy_backend(site=mbsh-reunion-v2)`.

---

## Local dev

```bash
# Frontend
cd frontend && python3 -m http.server 3333
# http://localhost:3333

# Backend (separate terminal)
cp .env.example .env  # fill DEV values
cd backend && php -S localhost:8888
# Frontend's API_BASE_URL_DEV points at this

# Test the flow
open http://localhost:3333/rsvp.html
```

---

## Provenance

This v2 build is the proof-of-concept output from `cowork-audit-001` (`~/famtastic/docs/sites/site-mbsh-reunion/cowork-audit-001/`). The audit's recommendations B.1 (chatbot skeleton), B.2 (sponsor flow), B.4 (per-feature CSS split), B.5 (compass nav), G.1 (PHP backend), are all materialized here as MBSH-bespoke implementations that should later promote to Studio-side capabilities.

The brand mark, mascot poses, and background loops are md5-verified copies from canonical (`~/famtastic-sites/mbsh-reunion/`).

**A/B testable** — both repos can be deployed; committee can compare side-by-side.
