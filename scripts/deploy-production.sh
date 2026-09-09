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
SSH_CONTROL="/tmp/mbsh-ssh-$$"
cleanup() {
  ssh -o ControlPath="$SSH_CONTROL" -O exit "$DEPLOY_HOST" >/dev/null 2>&1 || true
  rm -rf "$BUILD_DIR"
}
trap cleanup EXIT
"$ROOT_DIR/scripts/build-production-release.sh" "$RELEASE_SHA" "$BUILD_DIR" >/dev/null

ssh -o ControlMaster=yes -o ControlPath="$SSH_CONTROL" -o ControlPersist=120 -Nf "$DEPLOY_HOST"
SSH_OPTIONS=(-o ControlPath="$SSH_CONTROL")
RSYNC_SSH="ssh -o ControlPath=$SSH_CONTROL"

REMOTE_RELEASES="$REMOTE_ROOT/.releases/mbsh-reunion"
REMOTE_RELEASE="$REMOTE_RELEASES/releases/$RELEASE_SHA"

ssh "${SSH_OPTIONS[@]}" "$DEPLOY_HOST" "mkdir -p '$REMOTE_RELEASE/webroot'"
rsync -az -e "$RSYNC_SSH" "$BUILD_DIR/manifest.paths" "$BUILD_DIR/manifest.sha256" "$BUILD_DIR/commit" "$DEPLOY_HOST:$REMOTE_RELEASE/"

# Seed the immutable release from matching live paths. The checksum-enabled
# sync that follows transfers only files whose Git content differs.
ssh "${SSH_OPTIONS[@]}" "$DEPLOY_HOST" bash -s -- "$REMOTE_ROOT" "$REMOTE_RELEASE" <<'REMOTE_SEED'
set -euo pipefail
remote_root="$1"
release_dir="$2"
webroot="$remote_root/public_html"
while IFS= read -r path; do
  if [[ -f "$webroot/$path" && ! -f "$release_dir/webroot/$path" ]]; then
    mkdir -p "$release_dir/webroot/$(dirname "$path")"
    cp "$webroot/$path" "$release_dir/webroot/$path"
  fi
done < "$release_dir/manifest.paths"
REMOTE_SEED

ssh "${SSH_OPTIONS[@]}" "$DEPLOY_HOST" bash -s -- "$REMOTE_ROOT" "$REMOTE_RELEASE" <<'REMOTE_DIFF'
set -euo pipefail
remote_root="$1"
release_dir="$2"
(
  cd "$remote_root/public_html"
  sha256sum -c "$release_dir/manifest.sha256" 2>&1 || true
) | awk -F: '/FAILED/ {print $1}' > "$release_dir/changed.paths"
REMOTE_DIFF

rsync -az -e "$RSYNC_SSH" "$DEPLOY_HOST:$REMOTE_RELEASE/changed.paths" "$BUILD_DIR/changed.paths"
if [[ -s "$BUILD_DIR/changed.paths" ]]; then
  tar -czf "$BUILD_DIR/changed-files.tar.gz" -C "$BUILD_DIR/webroot" -T "$BUILD_DIR/changed.paths"
  scp "${SSH_OPTIONS[@]}" "$BUILD_DIR/changed-files.tar.gz" "$DEPLOY_HOST:$REMOTE_RELEASE/changed-files.tar.gz"
  ssh "${SSH_OPTIONS[@]}" "$DEPLOY_HOST" "tar -xzf '$REMOTE_RELEASE/changed-files.tar.gz' -C '$REMOTE_RELEASE/webroot'"
fi

ssh "${SSH_OPTIONS[@]}" "$DEPLOY_HOST" bash -s -- "$REMOTE_ROOT" "$REMOTE_RELEASES" "$REMOTE_RELEASE" "$RELEASE_SHA" "$MODE" <<'REMOTE_SCRIPT'
set -euo pipefail
remote_root="$1"
releases_root="$2"
release_dir="$3"
release_sha="$4"
mode="$5"
webroot="$remote_root/public_html"

mkdir -p "$releases_root/releases"
if ballot="$(readlink "$releases_root/current" 2>/dev/null)"; then
  previous_release="$ballot"
else
  previous_release=""
fi

if [[ "$(cat "$release_dir/commit")" != "$release_sha" ]]; then
  echo "Release commit marker mismatch" >&2
  exit 1
fi
(
  cd "$release_dir/webroot"
  sha256sum -c "$release_dir/manifest.sha256" >/dev/null
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
  sha256sum -c "$release_dir/manifest.sha256" >/dev/null
)

ln -sfn "$release_dir" "$releases_root/current"
printf '%s\n' "$release_sha" > "$releases_root/DEPLOYED_COMMIT"
date -u +%Y-%m-%dT%H:%M:%SZ > "$release_dir/deployed-at-utc"
echo "DEPLOYED $release_sha"
REMOTE_SCRIPT
