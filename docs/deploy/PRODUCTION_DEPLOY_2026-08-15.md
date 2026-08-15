# MBSH Cinematic Production Deployment — 2026-08-15

## Outcome

The QA-approved cinematic homepage, RSVP entry experience, shared navigation, optimized movie, and personality-driven Hi-Tide Harry were promoted to the live GoDaddy production web root for `mbsh96reunion.com`.

## Source

- Proof worktree: `/Users/famtastic-fritz/Development/FAMtastic/sites/site-mbsh-reunion-cinematic-proof`
- Proof branch: `codex/cinematic-facelift-proof`
- Feature commit: `fa8e0d9`
- Final mobile-collision correction: `99fabac`
- GitHub `main` was fast-forwarded to the approved commits.

## Safety and Rollback

- Production secrets file verified before upload: `/home/nineoo/.config/mbsh-config.php`.
- Pre-deploy production archive:
  `/home/nineoo/backups/mbsh-public-html-pre-fa8e0d9-20260815T165716Z.tar.gz`
- Archive size: 47 MB.
- Existing approved sponsor and memory uploads were excluded from the archive operation and were not modified by deployment.
- Deployment used additive/overwrite rsync without `--delete`.

## Deployment Layers

1. Frontend HTML, CSS, JavaScript, configuration, and existing runtime assets were synced to `public_html/`.
2. Only the three cinematic social assets referenced by live pages were promoted:
   - Harry homepage poster
   - RSVP practical-effects image
   - Optimized 1.1 MB 720×988 Harry movie
3. Backend deployment was filtered to PHP endpoints, admin, libraries, cron, and the upload hardening file.
4. Duplicate HTML inside `backend/` was deliberately excluded so it could not overwrite the canonical frontend pages.
5. A temporary erroneous `public_html/frontend/` upload path and root `public_html/chatbot.js` file created during rsync correction were validated and removed. Canonical files were confirmed under `public_html/assets/...` and `public_html/js/chatbot.js`.

## Production Proof

External HTTP 200 checks passed for:

- `/`
- `/rsvp.html`
- `/css/cinematic-system.css?v=cinematic3`
- `/js/cinematic-shell.js?v=cinematic3`
- Homepage poster
- RSVP hero image
- Optimized MP4
- `/attendees.php`
- `/sponsors.php`
- `/in-memory.php`

Content-marker checks confirmed the cinematic homepage and RSVP headline were live.

Browser proof at 390×844 confirmed:

- No horizontal overflow
- 44 px mobile menu control
- Movie loaded and ready
- Full-character Harry active
- Legacy medallion hidden
- Harry edge-perch collision avoidance active on the status card
- Empty RSVP blocked by native constraints and focused on First Name
- No browser console errors

The production MP4 returned `video/mp4`, byte-range support, and a 1,146,235-byte content length.

## Remaining Controlled Test

No synthetic RSVP was transmitted during deployment verification. A complete database-write and email-confirmation test should use an approved test identity and should be recorded separately so production contact data remains intentional.

## Repeatable Learning

On unified cPanel web roots, do not blindly sync a backend directory after the frontend when both contain HTML with identical names. Treat frontend pages and backend runtime endpoints as filtered deployment layers, or build a single explicit release artifact before upload.
