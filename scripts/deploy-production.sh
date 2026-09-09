#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_REF="${1:-HEAD}"
MODE="${2:-apply}"
DEPLOY_HOST="${MBSH_DEPLOY_HOST:-nineoo@FAMTASTICINC.COM}"
REMOTE_ROOT="${MBSH_REMOTE_ROOT:-/home/nineoo}"
PRODUCTION_SOURCE_REF="${MBSH_PRODUCTION_SOURCE_REF:-origin/main}"

if [[ "$MODE" != "apply" && "$MODE" != "--dry-run" ]]; then
  echo "Usage: $0 <git-commit-or-ref> [--dry-run]" >&2
  exit 1
fi

if [[ -n "$(git -C "$ROOT_DIR" status --porcelain --untracked-files=no)" ]]; then
  echo "Tracked files are modified. Commit them before building a production release." >&2
  exit 1
fi

RELEASE_SHA="$(git -C "$ROOT_DIR" rev-parse "${RELEASE_REF}^{commit}")"
if ! git -C "$ROOT_DIR" merge-base --is-ancestor "$RELEASE_SHA" "$PRODUCTION_SOURCE_REF"; then
  echo "Commit $RELEASE_SHA is not present on the configured production source branch." >&2
  echo "Push the commit before production deployment (checked $PRODUCTION_SOURCE_REF)." >&2
  exit 1
fi

BUILD_DIR="$(mktemp -d "${TMPDIR:-/tmp}/mbsh-deploy.${RELEASE_SHA:0:12}.XXXXXX")"
trap 'rm -rf "$BUILD_DIR"' EXIT
"$ROOT_DIR/scripts/build-production-release.sh" "$RELEASE_SHA" "$BUILD_DIR" >/dev/null

REMOTE_RELEASES="$REMOTE_ROOT/.releases/mbsh-reunion"
REMOTE_RELEASE="$REMOTE_RELEASES/releases/$RELEASE_SHA"
REMOTE_INCOMING="$REMOTE_RELEASES/incoming-$RELEASE_SHA.tar.gz"

ssh "$DEPLOY_HOST" "mkdir -p '$REMOTE_RELEASES/releases'"
rsync -az --partial "$BUILD_DIR/release.tar.gz" "$DEPLOY_HOST:$REMOTE_INCOMING"

ssh "$DEPLOY_HOST" bash -s -- "$REMOTE_ROOT" "$REMOTE_RELEASES" "$REMOTE_RELEASE" "$REMOTE_INCOMING" "$RELEASE_SHA" "$MODE" <<'REMOTE_SCRIPT'
set -euo pipefail
remote_root="$1"
releases_root="$2"
release_dir="$3"
incoming="$4"
release_sha="$5"
mode="$6"
webroot="$remote_root/public_html"

mkdir -p "$releases_root/releases"
if ballot="$(readlink "$releases_root/current" 2>/dev/null)"; then
  previous_release="$ballot"
else
  previous_release=""
fi

if [[ ! -d "$release_dir/webroot" ]]; then
  mkdir -p "$release_dir"
  tar -xzf "$incoming" -C "$release_dir"
fi
rm -f "$incoming"

if [[ "$(cat "$release_dir/commit")" != "$release_sha" ]]; then
  echo "Release commit marker mismatch" >&2
  exit 1
fi
(
  cd "$release_dir/webroot"
  shasum -a 256 -c "$release_dir/manifest.sha256" >/dev/null
)

changed=0
while IFS= read -r path; do
  if [[ ! -f "$webroot/$path" ]] || ! cmp -s "$release_dir/webroot/$path" "$webroot/$path"; then
    printf 'CHANGE %s\n' "$path"
    changed=$((changed + 1))
  fi
done < "$release_dir/manifest.paths"

retired=0
if [[ -n "$previous_release" && -f "$previous_release/manifest.paths" ]]; then
  while IFS= read -r path; do
    if ! grep -Fqx "$path" "$release_dir/manifest.paths"; then
      printf 'RETIRE %s\n' "$path"
      retired=$((retired + 1))
    fi
  done < "$previous_release/manifest.paths"
fi

echo "Commit: $release_sha"
echo "Changed files: $changed"
echo "Retired files: $retired"
if [[ "$mode" == "--dry-run" ]]; then
  echo "Dry run only; production was not changed."
  exit 0
fi

retired_dir="$release_dir/retired-from-previous"
if [[ -n "$previous_release" && -f "$previous_release/manifest.paths" ]]; then
  while IFS= read -r path; do
    if ! grep -Fqx "$path" "$release_dir/manifest.paths" && [[ -f "$webroot/$path" ]]; then
      mkdir -p "$retired_dir/$(dirname "$path")"
      mv "$webroot/$path" "$retired_dir/$path"
    fi
  done < "$previous_release/manifest.paths"
fi

rsync -a --files-from="$release_dir/manifest.paths" "$release_dir/webroot/" "$webroot/"
(
  cd "$webroot"
  shasum -a 256 -c "$release_dir/manifest.sha256" >/dev/null
)

ln -sfn "$release_dir" "$releases_root/current"
printf '%s\n' "$release_sha" > "$releases_root/DEPLOYED_COMMIT"
date -u +%Y-%m-%dT%H:%M:%SZ > "$release_dir/deployed-at-utc"
echo "DEPLOYED $release_sha"
REMOTE_SCRIPT
