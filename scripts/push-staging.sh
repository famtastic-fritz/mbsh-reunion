#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

STAGING_BRANCH="${STAGING_BRANCH:-staging}"
STAGING_SITE_ID="${NETLIFY_STAGING_SITE_ID:-3b4f9abd-d0cd-4b78-9ac1-d1b4b51606bf}"
STAGING_SITE_NAME="${NETLIFY_STAGING_SITE_NAME:-mbsh-reunion-staging}"

if ! command -v git >/dev/null 2>&1; then
  echo "git is required" >&2
  exit 1
fi

if ! command -v netlify >/dev/null 2>&1; then
  echo "netlify CLI is required" >&2
  exit 1
fi

if ! command -v jq >/dev/null 2>&1; then
  echo "jq is required" >&2
  exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Working tree has uncommitted changes. Commit or stash before staging deploy." >&2
  git status --short >&2
  exit 1
fi

current_ref="$(git rev-parse --abbrev-ref HEAD)"
current_sha="$(git rev-parse --short HEAD)"

echo "Ensuring Netlify staging URL: https://${STAGING_SITE_NAME}.netlify.app"
netlify api updateSite \
  --data "{\"site_id\":\"${STAGING_SITE_ID}\",\"body\":{\"name\":\"${STAGING_SITE_NAME}\"}}" \
  >/dev/null

actual_name="$(netlify api getSite --data "{\"site_id\":\"${STAGING_SITE_ID}\"}" | jq -r '.name')"
if [[ "$actual_name" != "$STAGING_SITE_NAME" ]]; then
  echo "Netlify staging site name mismatch: expected ${STAGING_SITE_NAME}, got ${actual_name}" >&2
  exit 1
fi

echo "Pushing ${current_ref}@${current_sha} to origin/${STAGING_BRANCH}"
git push origin "HEAD:${STAGING_BRANCH}"

echo "Staging deploy queued:"
echo "  https://${STAGING_SITE_NAME}.netlify.app"
