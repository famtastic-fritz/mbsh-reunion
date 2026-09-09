#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_REF="${1:-HEAD}"
OUTPUT_DIR="${2:-}"

if ! git -C "$ROOT_DIR" rev-parse --verify "${RELEASE_REF}^{commit}" >/dev/null 2>&1; then
  echo "Unknown Git commit or ref: ${RELEASE_REF}" >&2
  exit 1
fi

RELEASE_SHA="$(git -C "$ROOT_DIR" rev-parse "${RELEASE_REF}^{commit}")"
if [[ -z "$OUTPUT_DIR" ]]; then
  OUTPUT_DIR="$(mktemp -d "${TMPDIR:-/tmp}/mbsh-release.${RELEASE_SHA:0:12}.XXXXXX")"
fi

mkdir -p "$OUTPUT_DIR/source" "$OUTPUT_DIR/webroot"
git -C "$ROOT_DIR" archive "$RELEASE_SHA" | tar -xf - -C "$OUTPUT_DIR/source"

# The production webroot is unified. Public presentation owns ordinary HTML,
# CSS, JS, and assets; the backend contributes runtime PHP and access-control
# files; WordPress receives only this repository's custom plugin and theme.
rsync -a \
  --exclude='assets/social/' \
  --exclude='assets/yearbook-proof/' \
  "$OUTPUT_DIR/source/frontend/" "$OUTPUT_DIR/webroot/"

while IFS= read -r source_path; do
  relative_path="${source_path#backend/}"
  mkdir -p "$OUTPUT_DIR/webroot/$(dirname "$relative_path")"
  cp "$OUTPUT_DIR/source/$source_path" "$OUTPUT_DIR/webroot/$relative_path"
done < <(
  git -C "$ROOT_DIR" ls-tree -r --name-only "$RELEASE_SHA" backend |
    awk '/\.php$/ || /(^|\/)\.htaccess$/' |
    grep -v '^backend/uploads/' || true
)

while IFS= read -r source_path; do
  relative_path="cms/${source_path#wordpress/}"
  mkdir -p "$OUTPUT_DIR/webroot/$(dirname "$relative_path")"
  cp "$OUTPUT_DIR/source/$source_path" "$OUTPUT_DIR/webroot/$relative_path"
done < <(
  git -C "$ROOT_DIR" ls-tree -r --name-only "$RELEASE_SHA" wordpress/wp-content |
    grep -E '^wordpress/wp-content/(plugins/famtastic-reunion-platform|themes/famtastic-event-cinema)/'
)

(
  cd "$OUTPUT_DIR/webroot"
  find . -type f -print | LC_ALL=C sort | sed 's#^\./##' > "$OUTPUT_DIR/manifest.paths"
  while IFS= read -r path; do
    shasum -a 256 "$path"
  done < "$OUTPUT_DIR/manifest.paths" > "$OUTPUT_DIR/manifest.sha256"
)

printf '%s\n' "$RELEASE_SHA" > "$OUTPUT_DIR/commit"
COPYFILE_DISABLE=1 tar --no-xattrs -czf "$OUTPUT_DIR/release.tar.gz" -C "$OUTPUT_DIR" webroot manifest.paths manifest.sha256 commit

echo "Release commit: $RELEASE_SHA"
echo "Release directory: $OUTPUT_DIR"
echo "Artifact: $OUTPUT_DIR/release.tar.gz"
echo "Files: $(wc -l < "$OUTPUT_DIR/manifest.paths" | tr -d ' ')"
