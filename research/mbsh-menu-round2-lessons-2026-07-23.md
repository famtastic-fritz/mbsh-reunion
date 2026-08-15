# MBSH menu round 2 lessons — 2026-07-23

## What broke

1. Runtime navigation drift
   - `frontend/js/page-sequence.js` was updated to include `menu` and `survey`, but `frontend/js/premiere.js` still had separate hard-coded nav manifests.
   - Result: the live runtime knew the nine-scene sequence in one place, but the medallion drawer and footer still rendered the old seven-link program.

2. Rendered file vs source confusion
   - `frontend/menu.html` and `frontend/menu/index.html` are generated artifacts.
   - The real source chain is:
     - `frontend/templates/simple-page-template.html`
     - `frontend/page-content/menu.content.html`
     - `frontend/css/menu.css`
     - `scripts/render-menu-page.py`
   - Direct edits to rendered menu files are disposable and will be overwritten by the render step.

3. Production target mismatch
   - Netlify deploy succeeded, but `mbsh96reunion.com` does not point at Netlify.
   - Canonical prod is GoDaddy at `107.180.51.234`, with deploy path `/home/nineoo/public_html/`.
   - Netlify is staging/sidecar here, not the live domain.

## What changed

### Source fixes
- Updated `frontend/page-content/menu.content.html`
  - Success copy now includes the committee contact path:
    - `mbsh96reunion@gmail.com`
- Updated `frontend/css/menu.css`
  - Tightened menu section padding
  - Reduced the oversized closing-section feel on `Why your vote matters`
- Re-rendered menu outputs with:
  - `python3 scripts/render-menu-page.py`

### Runtime navigation fixes
- Updated `frontend/js/page-sequence.js`
  - Sequence is now:
    - `home -> rsvp -> tickets -> menu -> survey -> through-years -> memorial -> capsule -> playlist`
- Updated `frontend/js/premiere.js`
  - Added `menu` and `survey` to:
    - medallion filmstrip drawer nav
    - footer final-reel nav
    - legacy nav manifest still present in file
  - Added menu/survey runtime metadata

## Production deploy that actually mattered

Uploaded to GoDaddy, not Netlify:
- `/home/nineoo/public_html/menu.html`
- `/home/nineoo/public_html/menu/index.html`
- `/home/nineoo/public_html/js/page-sequence.js`
- `/home/nineoo/public_html/js/premiere.js`
- `/home/nineoo/public_html/css/menu.css`

SSH target used:
- `nineoo@FAMTASTICINC.COM`

## Live verification proof

Verified on `https://mbsh96reunion.com/menu/`:
- runtime sequence reports all nine scenes, including `menu` and `survey`
- medallion drawer shows:
  - Scene 04 Menu
  - Scene 05 Survey
- footer shows:
  - Menu
  - Survey
- success note shows committee email update path
- section heights after spacing fix:
  - `How menu voting works`: 600px
  - `Gold menu dinner preferences`: 2351px
  - `Why your vote matters`: 219px
  - `The final reel`: 838px

## Rules to keep

1. If menu route changes, patch source files first, then re-render.
2. If navigation changes, patch every runtime manifest or consolidate them into one source.
3. Always verify where the canonical domain points before calling a deploy “prod”.
4. For this site, Netlify proof is not production proof.
