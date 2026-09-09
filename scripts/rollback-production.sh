#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_COMMIT="${1:-}"

if [[ -z "$TARGET_COMMIT" ]]; then
  echo "Usage: $0 <previous-production-commit> [--dry-run]" >&2
  exit 1
fi

exec "$ROOT_DIR/scripts/deploy-production.sh" "$TARGET_COMMIT" "${2:-apply}"

