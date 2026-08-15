#!/usr/bin/env bash
set -euo pipefail

repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
base_url="${1:-}"
cd "$repo_dir"

fail() { printf 'FAIL: %s\n' "$1" >&2; exit 1; }
pass() { printf 'PASS: %s\n' "$1"; }

node --check frontend/js/cinematic-shell.js
node --check frontend/js/chatbot.js
pass "cinematic JavaScript parses"

rg -q 'css/cinematic-system.css' frontend/index.html frontend/rsvp.html || fail "cinematic stylesheet is not wired"
rg -q 'js/cinematic-shell.js' frontend/index.html frontend/rsvp.html || fail "cinematic shell is not wired"
rg -q 'data-snap=' frontend/index.html && fail "homepage still enables mandatory scene snapping"
pass "homepage and RSVP use the cinematic shell without forced snapping"

for asset in \
  frontend/assets/brand-mark/brand-mark.png \
  frontend/assets/mascot/01-wave-hello.png \
  frontend/assets/mascot/12-clipboard.png \
  frontend/assets/mascot/22-walk-frame.png \
  frontend/assets/social/2026-reunion-hype/01-hi-tide-harry-ultra-real-4x5.png \
  frontend/assets/social/2026-reunion-hype/09-ultra-real-practical-effects.png \
  frontend/assets/social/2026-reunion-hype/openart-video/hi-tide-harry-reunion-teaser-mobile-720p.mp4 \
  frontend/assets/social/2026-reunion-hype/openart-video/hi-tide-harry-reunion-teaser-openart-kling-1080p.mp4
do
  [[ -s "$asset" ]] || fail "missing or empty asset: $asset"
done
pass "cinematic media and Harry poses are present"

mobile_video="frontend/assets/social/2026-reunion-hype/openart-video/hi-tide-harry-reunion-teaser-mobile-720p.mp4"
[[ "$(stat -f%z "$mobile_video")" -lt 3145728 ]] || fail "mobile hero movie exceeds 3 MB"
rg -q 'form\.checkValidity\(\)' frontend/js/rsvp.js || fail "RSVP client validation guard is absent"
pass "mobile movie budget and RSVP validation guard are enforced"

rg -q 'border-radius: 0' frontend/css/chatbot.css || fail "full-character Harry treatment is absent"
rg -q 'data-dock="left"' frontend/css/chatbot.css || fail "Harry cross-page movement state is absent"
rg -q 'PAGE_PERSONA' frontend/js/chatbot.js || fail "Harry page personality map is absent"
pass "Harry has full-character, movement, and page-personality states"

git diff --check -- \
  frontend/index.html frontend/rsvp.html \
  frontend/css/cinematic-system.css frontend/css/chatbot.css \
  frontend/js/cinematic-shell.js frontend/js/chatbot.js
pass "focused diff is whitespace-clean"

if [[ -n "$base_url" ]]; then
  for route in index.html rsvp.html css/cinematic-system.css js/cinematic-shell.js; do
    code="$(curl -sS -o /dev/null -w '%{http_code}' "${base_url%/}/$route")"
    [[ "$code" == "200" ]] || fail "$route returned HTTP $code"
  done
  pass "served proof routes return HTTP 200"
fi

printf '\nCinematic proof checks completed. Browser QA remains required for layout and interaction evidence.\n'
