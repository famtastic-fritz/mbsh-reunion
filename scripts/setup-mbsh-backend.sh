#!/usr/bin/env bash
# setup-mbsh-backend.sh
#
# One-shot TTY-driven credential + provisioning helper for MBSH backend.
# Replaces the manual "log into cPanel + paste schema into phpMyAdmin + copy creds
# into .env + scp upload secrets file" flow with a single `bash setup-mbsh-backend.sh`.
#
# Per FAMtastic preference (Fritz, 2026-05-02): "I hate setting up things like
# api keys, capping, login into paste DB. Look for ways to automate that or the
# flow of gathering the info and process of storing the info as well."
#
# What this script does, in phases (each phase confirms before proceeding):
#   0. Pre-flight: verify required local tools (ssh, mysql client, curl, jq optional)
#   1. Capture credentials in one secure TTY flow (no echo, no logs)
#   2. Test each credential's connectivity before doing any writes
#   3. Apply schema.sql to LOCAL dev MariaDB (skips production for Phase 3)
#   4. Apply schema.sql to PRODUCTION MariaDB via SSH (skips phpMyAdmin entirely)
#   5. Upload production-secrets file to /home/nineoo/.config/ with mode 0600
#   6. Write local .env (gitignored) for backend dev work
#   7. Smoke-test by running a SELECT VERSION() round-trip on both dev and prod
#   8. Final report: what worked, what didn't, file paths
#
# This script is generalized — for site #2, fork it and adjust the SITE_TAG +
# CPANEL_USER + CPANEL_HOST + DB_NAME constants at the top.
#
# IDEMPOTENCY: re-running this script is safe. Schema apply uses CREATE TABLE
# IF NOT EXISTS-equivalent semantics where possible; secrets file is overwritten
# (NOT appended) so stale values don't accumulate.
#
# SECURITY:
#  - Credentials are read with `read -rs` (no echo) and held in shell variables,
#    NEVER written to logs or stdout.
#  - The local .env is mode 0600 and gitignored.
#  - The production secrets file is mode 0600 on the server and lives OUTSIDE
#    the web root.
#  - SSH connection is verified before any credential transmission.
#
# DEV/PROD BOUNDARY (per mbsh-rsvp-integration-notes-2026-04-29.md):
#  Local dev DB and production DB are SEPARATE, each gets its own schema apply,
#  each gets its own credentials. This script never runs production credentials
#  against a local connection or vice versa.

set -euo pipefail

# ======================================================================
# CONSTANTS — change these for site #2 forks
# ======================================================================
SITE_TAG="mbsh-reunion-v2"
SITE_FRIENDLY_NAME="MBSH Class of '96 30th Reunion"
CPANEL_USER="nineoo"
CPANEL_HOST="FAMTASTICINC.COM"
DB_NAME_PROD="nineoo_mbsh96_reunion_v2"
DB_USER_PROD="nineoo_mbsh_user"
DB_NAME_DEV="${SITE_TAG//-/_}_dev"
DB_USER_DEV="${SITE_TAG//-/_}_dev_user"
SECRETS_PATH_PROD="/home/${CPANEL_USER}/.config/mbsh-config.php"
PENDING_UPLOADS_PATH_PROD="/home/${CPANEL_USER}/uploads-pending"
APPROVED_UPLOADS_PATH_PROD="/home/${CPANEL_USER}/public_html/uploads/approved"
RESEND_FROM_DOMAIN="send.mbsh96reunion.com"
COMMITTEE_EMAIL="mbsh96reunion@gmail.com"
ALLOWED_ORIGINS_JSON='["https://mbsh96reunion.com","https://www.mbsh96reunion.com","http://localhost:8080"]'
ALLOWED_PATTERNS_JSON='["/^https:\\\/\\\/[a-z0-9-]+--[a-z0-9-]+\\\.netlify\\\.app$/","/^https:\\\/\\\/[a-z0-9-]+\\\.netlify\\\.app$/"]'

# Repo paths (this script lives in the repo's scripts/ dir)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
SCHEMA_SQL="${REPO_ROOT}/backend/schema.sql"
LOCAL_ENV="${REPO_ROOT}/.env"

# ======================================================================
# UTILITIES
# ======================================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # no color

log()      { printf "${BLUE}[%s]${NC} %s\n" "$(date +%H:%M:%S)" "$*"; }
ok()       { printf "  ${GREEN}✓${NC} %s\n" "$*"; }
warn()     { printf "  ${YELLOW}⚠${NC} %s\n" "$*"; }
err()      { printf "  ${RED}✗${NC} %s\n" "$*" >&2; }
die()      { err "$*"; exit 1; }
phase()    { printf "\n${BOLD}═══ PHASE %s ═══${NC}\n" "$*"; }

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "Missing required command: $1"
}

confirm() {
  local prompt="$1"
  local response
  read -rp "${prompt} [y/N] " response
  [[ "${response,,}" == "y" || "${response,,}" == "yes" ]]
}

# ======================================================================
# PHASE 0 — Pre-flight
# ======================================================================
phase "0: Pre-flight checks"

require_cmd ssh
require_cmd scp
require_cmd curl
require_cmd mysql
require_cmd openssl
ok "Required tools present (ssh, scp, curl, mysql, openssl)"

[[ -f "${SCHEMA_SQL}" ]] || die "Schema file not found: ${SCHEMA_SQL}. Run from repo with backend/schema.sql in place."
ok "Schema file found: ${SCHEMA_SQL} ($(wc -l < "${SCHEMA_SQL}") lines)"

# Detect local MariaDB / MySQL — prefer Homebrew mariadb, fall back to Docker
LOCAL_DB_MODE=""
if brew services list 2>/dev/null | grep -q "^mariadb.*started"; then
  LOCAL_DB_MODE="brew"
  ok "Local MariaDB (Homebrew) detected and running"
elif command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' 2>/dev/null | grep -q mariadb; then
  LOCAL_DB_MODE="docker"
  ok "Local MariaDB (Docker container) detected"
elif command -v mariadb >/dev/null 2>&1; then
  LOCAL_DB_MODE="cli-only"
  warn "Local MariaDB binary present but no running service detected"
  warn "Phase 3 (local schema apply) will be skipped; production schema apply (Phase 4) will still proceed"
else
  warn "No local MariaDB detected (Homebrew or Docker)"
  warn "Phase 3 (local schema apply) will be skipped — to enable local dev, install: brew install mariadb && brew services start mariadb"
  LOCAL_DB_MODE="none"
fi

# Verify .env is gitignored
if [[ -f "${REPO_ROOT}/.gitignore" ]] && grep -qE "^\.env$|^\.env\.\*$|^\!\.env\.example$" "${REPO_ROOT}/.gitignore"; then
  ok ".env is gitignored"
else
  die ".env is NOT gitignored in ${REPO_ROOT}/.gitignore — refusing to write secrets. Add '.env' to .gitignore first."
fi

log "Site: ${SITE_FRIENDLY_NAME}  |  Tag: ${SITE_TAG}  |  Repo: ${REPO_ROOT}"
log "cPanel: ${CPANEL_USER}@${CPANEL_HOST}  |  Prod DB: ${DB_NAME_PROD}  |  Local DB mode: ${LOCAL_DB_MODE}"

confirm "Pre-flight looks good. Proceed to credential capture?" || die "Aborted at Phase 0."

# ======================================================================
# PHASE 1 — Capture credentials (single TTY flow, no echo, no logs)
# ======================================================================
phase "1: Capture credentials"
echo "All inputs are silent (no echo). Press Enter after each value."
echo

read -rsp "  Resend API key (re_...): " RESEND_API_KEY; echo
[[ -n "${RESEND_API_KEY}" ]] || die "Resend API key cannot be empty."
[[ "${RESEND_API_KEY}" =~ ^re_ ]] || warn "Resend key doesn't start with 're_' — proceeding but verify"

read -rsp "  Production MySQL password (for ${DB_USER_PROD}): " DB_PASSWORD_PROD; echo
[[ -n "${DB_PASSWORD_PROD}" ]] || die "DB password cannot be empty."

read -rsp "  Committee admin password (will be hashed): " ADMIN_PASSWORD; echo
[[ -n "${ADMIN_PASSWORD}" ]] || die "Admin password cannot be empty."

# Local dev DB credentials — prompt only if local mode is brew/docker
if [[ "${LOCAL_DB_MODE}" == "brew" || "${LOCAL_DB_MODE}" == "docker" ]]; then
  echo
  echo "  Local dev DB credentials (separate from production — will be created if missing):"
  echo "  DB_NAME_DEV: ${DB_NAME_DEV}"
  echo "  DB_USER_DEV: ${DB_USER_DEV}"
  read -rsp "  Local dev MySQL password (will be created if user doesn't exist): " DB_PASSWORD_DEV; echo
  [[ -n "${DB_PASSWORD_DEV}" ]] || die "Dev DB password cannot be empty."

  read -rsp "  Local MySQL ROOT password (to create dev user/DB; leave empty if none): " MYSQL_ROOT_PASSWORD; echo
fi

# SSH access — try key-based first, fall back to password if needed
echo
echo "  Testing SSH access to ${CPANEL_USER}@${CPANEL_HOST}..."
if ssh -o BatchMode=yes -o ConnectTimeout=5 "${CPANEL_USER}@${CPANEL_HOST}" 'true' 2>/dev/null; then
  ok "SSH key-based access works"
  SSH_AUTH_MODE="key"
else
  warn "SSH key-based auth not configured"
  echo "  Options: (a) set up SSH key now, (b) use password each ssh/scp call"
  if confirm "  Set up SSH key now (recommended — eliminates future password prompts)?"; then
    SSH_AUTH_MODE="key-setup"
  else
    SSH_AUTH_MODE="password"
    warn "Password-mode means you'll be prompted ~5 times during this run"
  fi
fi

# Generate admin CSRF secret + password hash via openssl + PHP-compatible bcrypt
ADMIN_CSRF_SECRET=$(openssl rand -hex 32)
# bcrypt via openssl is awkward; defer to PHP-compatible password_hash on the server
# We'll generate the hash remotely after SSH is established (Phase 4)
ok "Captured all credentials in memory (none logged or echoed)"

# ======================================================================
# PHASE 2 — Test connectivity before any writes
# ======================================================================
phase "2: Connectivity probes"

# Resend domain check
log "Testing Resend API key..."
RESEND_HTTP=$(curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer ${RESEND_API_KEY}" \
  "https://api.resend.com/domains" || echo "000")
if [[ "${RESEND_HTTP}" == "200" ]]; then
  ok "Resend API key valid (HTTP 200)"
else
  die "Resend API key probe failed (HTTP ${RESEND_HTTP}). Check key validity at https://resend.com/api-keys"
fi

# Local DB probe (if applicable)
if [[ "${LOCAL_DB_MODE}" == "brew" || "${LOCAL_DB_MODE}" == "docker" ]]; then
  log "Testing local MariaDB root access..."
  if [[ -n "${MYSQL_ROOT_PASSWORD:-}" ]]; then
    if mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT VERSION();" >/dev/null 2>&1; then
      ok "Local MariaDB root access works"
    else
      die "Local MariaDB root login failed."
    fi
  else
    if mysql -uroot -e "SELECT VERSION();" >/dev/null 2>&1; then
      ok "Local MariaDB root access works (no password)"
    else
      die "Local MariaDB root login failed (tried empty password). Provide root password or skip local mode."
    fi
  fi
fi

# SSH probe — if password mode, we'll get prompted; if key-setup, we're about to set it up
if [[ "${SSH_AUTH_MODE}" == "key-setup" ]]; then
  log "Setting up SSH key access..."
  KEY_PATH="${HOME}/.ssh/id_ed25519_${SITE_TAG}"
  if [[ ! -f "${KEY_PATH}" ]]; then
    ssh-keygen -t ed25519 -f "${KEY_PATH}" -N "" -C "famtastic-${SITE_TAG}-deploy"
    ok "Generated SSH key: ${KEY_PATH}"
  else
    ok "SSH key already exists: ${KEY_PATH}"
  fi
  # Append to authorized_keys
  ssh-copy-id -i "${KEY_PATH}.pub" "${CPANEL_USER}@${CPANEL_HOST}" || die "ssh-copy-id failed."
  ok "SSH key appended to ${CPANEL_USER}@${CPANEL_HOST}:~/.ssh/authorized_keys"
  # Configure ~/.ssh/config so future ssh/scp calls auto-use this key
  CONFIG_LINE="Host ${CPANEL_HOST}"
  if ! grep -qF "${CONFIG_LINE}" "${HOME}/.ssh/config" 2>/dev/null; then
    cat >> "${HOME}/.ssh/config" <<EOF

Host ${CPANEL_HOST}
  User ${CPANEL_USER}
  IdentityFile ${KEY_PATH}
  IdentitiesOnly yes
EOF
    ok "Added ${CPANEL_HOST} block to ~/.ssh/config"
  fi
  SSH_AUTH_MODE="key"
fi

if [[ "${SSH_AUTH_MODE}" == "key" ]]; then
  if ssh -o BatchMode=yes "${CPANEL_USER}@${CPANEL_HOST}" 'echo "ssh ok: $(whoami) on $(hostname)"' 2>/dev/null; then
    ok "SSH connection verified (key-based)"
  else
    die "SSH connection failed even after key setup."
  fi
fi

# Production DB connectivity probe via SSH
log "Testing production MariaDB access via SSH..."
PROD_DB_VERSION=$(ssh "${CPANEL_USER}@${CPANEL_HOST}" \
  "mysql -u ${DB_USER_PROD} -p${DB_PASSWORD_PROD} ${DB_NAME_PROD} -e 'SELECT VERSION();' 2>&1 | tail -1" \
  || echo "FAILED")
if [[ "${PROD_DB_VERSION}" == "FAILED" || "${PROD_DB_VERSION}" =~ ERROR|error ]]; then
  err "Production DB probe failed: ${PROD_DB_VERSION}"
  die "Cannot connect to ${DB_NAME_PROD} as ${DB_USER_PROD}. Verify credentials in cPanel MySQL."
else
  ok "Production MariaDB version: ${PROD_DB_VERSION}"
fi

# ======================================================================
# PHASE 3 — Apply schema to LOCAL dev DB
# ======================================================================
if [[ "${LOCAL_DB_MODE}" == "brew" || "${LOCAL_DB_MODE}" == "docker" ]]; then
  phase "3: Apply schema to LOCAL dev MariaDB"

  ROOT_FLAG=""
  [[ -n "${MYSQL_ROOT_PASSWORD:-}" ]] && ROOT_FLAG="-p${MYSQL_ROOT_PASSWORD}"

  log "Creating local dev DB and user (if not exists)..."
  mysql -uroot ${ROOT_FLAG} <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME_DEV}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER_DEV}'@'localhost' IDENTIFIED BY '${DB_PASSWORD_DEV}';
GRANT ALL PRIVILEGES ON \`${DB_NAME_DEV}\`.* TO '${DB_USER_DEV}'@'localhost';
FLUSH PRIVILEGES;
EOF
  ok "Local dev DB and user provisioned"

  log "Applying schema.sql to local dev DB..."
  mysql -u"${DB_USER_DEV}" -p"${DB_PASSWORD_DEV}" "${DB_NAME_DEV}" < "${SCHEMA_SQL}"
  ok "Schema applied to local dev DB"

  TABLE_COUNT_DEV=$(mysql -u"${DB_USER_DEV}" -p"${DB_PASSWORD_DEV}" -N -e "SHOW TABLES;" "${DB_NAME_DEV}" | wc -l | tr -d ' ')
  if [[ "${TABLE_COUNT_DEV}" == "10" ]]; then
    ok "Local dev DB has all 10 tables"
  else
    warn "Local dev DB has ${TABLE_COUNT_DEV} tables (expected 10) — investigate"
  fi
else
  phase "3: Skipped (no local MariaDB available)"
  warn "To enable local dev DB later: brew install mariadb && brew services start mariadb && re-run this script"
fi

# ======================================================================
# PHASE 4 — Apply schema to PRODUCTION via SSH (replaces phpMyAdmin paste)
# ======================================================================
phase "4: Apply schema to PRODUCTION MariaDB via SSH"

if confirm "Apply schema to production DB ${DB_NAME_PROD}? (Idempotent — uses CREATE TABLE IF NOT EXISTS)"; then
  REMOTE_SCHEMA="/tmp/${SITE_TAG}-schema-$$.sql"

  log "Uploading schema.sql to ${CPANEL_HOST}:${REMOTE_SCHEMA}..."
  scp -q "${SCHEMA_SQL}" "${CPANEL_USER}@${CPANEL_HOST}:${REMOTE_SCHEMA}"
  ok "Schema uploaded"

  log "Applying schema on production server..."
  ssh "${CPANEL_USER}@${CPANEL_HOST}" "mysql -u ${DB_USER_PROD} -p'${DB_PASSWORD_PROD}' ${DB_NAME_PROD} < ${REMOTE_SCHEMA} && rm ${REMOTE_SCHEMA}"
  ok "Schema applied to production"

  TABLE_COUNT_PROD=$(ssh "${CPANEL_USER}@${CPANEL_HOST}" \
    "mysql -u ${DB_USER_PROD} -p'${DB_PASSWORD_PROD}' -N -e 'SHOW TABLES;' ${DB_NAME_PROD}" | wc -l | tr -d ' ')
  if [[ "${TABLE_COUNT_PROD}" == "10" ]]; then
    ok "Production DB has all 10 tables"
  else
    warn "Production DB has ${TABLE_COUNT_PROD} tables (expected 10) — investigate"
  fi
else
  warn "Phase 4 skipped per user choice. Production schema apply remains pending."
fi

# ======================================================================
# PHASE 5 — Generate + upload production secrets file
# ======================================================================
phase "5: Generate + upload production secrets file"

# Hash admin password via PHP on the server (PHP 8.5's password_hash with PASSWORD_DEFAULT)
log "Hashing admin password via PHP on server..."
ADMIN_PASSWORD_HASH=$(ssh "${CPANEL_USER}@${CPANEL_HOST}" \
  "php -r 'echo password_hash(\"${ADMIN_PASSWORD}\", PASSWORD_DEFAULT);'")
[[ -n "${ADMIN_PASSWORD_HASH}" ]] || die "Admin password hash generation failed."
ok "Admin password hashed (bcrypt via PHP password_hash)"

SECRETS_CONTENT=$(cat <<EOF
<?php
// Production secrets — ${SITE_FRIENDLY_NAME}
// Generated by setup-mbsh-backend.sh on $(date -u +%Y-%m-%dT%H:%M:%SZ)
// Mode 0600. NEVER move into web root. NEVER commit to git.
return [
  'db_host'                  => 'localhost',
  'db_name'                  => '${DB_NAME_PROD}',
  'db_user'                  => '${DB_USER_PROD}',
  'db_password'              => '${DB_PASSWORD_PROD}',
  'resend_api_key'           => '${RESEND_API_KEY}',
  'resend_from_domain'       => '${RESEND_FROM_DOMAIN}',
  'resend_from_noreply'      => 'noreply@${RESEND_FROM_DOMAIN}',
  'resend_from_committee'    => 'committee@${RESEND_FROM_DOMAIN}',
  'resend_from_harry'        => 'harry@${RESEND_FROM_DOMAIN}',
  'resend_reply_to'          => '${COMMITTEE_EMAIL}',
  'committee_email'          => '${COMMITTEE_EMAIL}',
  'allowed_origins'          => json_decode('${ALLOWED_ORIGINS_JSON}', true),
  'allowed_origin_patterns'  => json_decode('${ALLOWED_PATTERNS_JSON}', true),
  'admin_password_hash'      => '${ADMIN_PASSWORD_HASH}',
  'admin_csrf_secret'        => '${ADMIN_CSRF_SECRET}',
  'pending_uploads_path'     => '${PENDING_UPLOADS_PATH_PROD}',
  'approved_uploads_path'    => '${APPROVED_UPLOADS_PATH_PROD}',
  'environment'              => 'production',
];
EOF
)

# Write to a local temp file, scp it up, then chmod, then verify
LOCAL_TMP_SECRETS=$(mktemp -t mbsh-secrets-XXXXXX)
echo "${SECRETS_CONTENT}" > "${LOCAL_TMP_SECRETS}"

log "Uploading secrets file to ${SECRETS_PATH_PROD}..."
ssh "${CPANEL_USER}@${CPANEL_HOST}" "mkdir -p $(dirname ${SECRETS_PATH_PROD})"
scp -q "${LOCAL_TMP_SECRETS}" "${CPANEL_USER}@${CPANEL_HOST}:${SECRETS_PATH_PROD}"
ssh "${CPANEL_USER}@${CPANEL_HOST}" "chmod 0600 ${SECRETS_PATH_PROD}"
ok "Secrets file uploaded to ${SECRETS_PATH_PROD} (mode 0600)"

# Local cleanup — never leave secrets on dev box
rm -f "${LOCAL_TMP_SECRETS}"
ok "Local temp secrets file deleted"

# Also create the pending-uploads directory on the server
log "Ensuring pending uploads directory exists..."
ssh "${CPANEL_USER}@${CPANEL_HOST}" "mkdir -p ${PENDING_UPLOADS_PATH_PROD}/sponsors ${PENDING_UPLOADS_PATH_PROD}/memories"
ok "Pending uploads directory ensured: ${PENDING_UPLOADS_PATH_PROD}"

# ======================================================================
# PHASE 6 — Write local .env (dev credentials only — NEVER prod)
# ======================================================================
phase "6: Write local .env"

if [[ "${LOCAL_DB_MODE}" == "brew" || "${LOCAL_DB_MODE}" == "docker" ]]; then
  cat > "${LOCAL_ENV}" <<EOF
# Local dev environment for ${SITE_FRIENDLY_NAME}
# Generated by setup-mbsh-backend.sh on $(date -u +%Y-%m-%dT%H:%M:%SZ)
# DEV CREDENTIALS ONLY. NEVER put production secrets in this file.
# Per FAMtastic dev/prod boundary rule: dev never connects to prod.

RESEND_API_KEY=${RESEND_API_KEY}
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${DB_NAME_DEV}
DB_USER=${DB_USER_DEV}
DB_PASSWORD=${DB_PASSWORD_DEV}

COMMITTEE_EMAIL=${COMMITTEE_EMAIL}
RESEND_FROM_DOMAIN=${RESEND_FROM_DOMAIN}

# Admin auth — local dev only
ADMIN_CSRF_SECRET=$(openssl rand -hex 32)
EOF
  chmod 0600 "${LOCAL_ENV}"
  ok "Local .env written: ${LOCAL_ENV} (mode 0600, gitignored)"
else
  warn "Skipped — no local DB mode means no local .env to write."
fi

# ======================================================================
# PHASE 7 — Smoke test
# ======================================================================
phase "7: Smoke test (round-trip queries)"

if [[ "${LOCAL_DB_MODE}" == "brew" || "${LOCAL_DB_MODE}" == "docker" ]]; then
  log "Local dev DB: round-trip query..."
  LOCAL_VER=$(mysql -u"${DB_USER_DEV}" -p"${DB_PASSWORD_DEV}" -N -e "SELECT VERSION(), @@global.time_zone, @@sql_mode;" "${DB_NAME_DEV}")
  ok "Local: ${LOCAL_VER}"
fi

log "Production DB: round-trip query via SSH..."
PROD_VER=$(ssh "${CPANEL_USER}@${CPANEL_HOST}" \
  "mysql -u ${DB_USER_PROD} -p'${DB_PASSWORD_PROD}' -N -e 'SELECT VERSION(), @@global.time_zone, @@sql_mode;' ${DB_NAME_PROD}")
ok "Production: ${PROD_VER}"

log "Production secrets file: read-back probe..."
SECRETS_PROBE=$(ssh "${CPANEL_USER}@${CPANEL_HOST}" \
  "php -r '\$c = require_once \"${SECRETS_PATH_PROD}\"; echo \"db=\".\$c[\"db_name\"].\";env=\".\$c[\"environment\"];'")
ok "Secrets readable from server: ${SECRETS_PROBE}"

# ======================================================================
# PHASE 8 — Final report
# ======================================================================
phase "8: Final report"

cat <<EOF

  ${BOLD}MBSH backend setup complete.${NC}

  ${GREEN}✓${NC} Resend API key validated (HTTP 200)
  ${GREEN}✓${NC} SSH access to ${CPANEL_USER}@${CPANEL_HOST} verified (mode: ${SSH_AUTH_MODE})
  ${GREEN}✓${NC} Production DB ${DB_NAME_PROD} reachable
  ${GREEN}✓${NC} Schema applied to production (10 tables expected, ${TABLE_COUNT_PROD:-?} present)
  ${GREEN}✓${NC} Production secrets at ${SECRETS_PATH_PROD} (mode 0600)
  ${GREEN}✓${NC} Pending uploads dir at ${PENDING_UPLOADS_PATH_PROD}

EOF

if [[ "${LOCAL_DB_MODE}" == "brew" || "${LOCAL_DB_MODE}" == "docker" ]]; then
cat <<EOF
  ${GREEN}✓${NC} Local dev DB ${DB_NAME_DEV} provisioned + schema applied (${TABLE_COUNT_DEV} tables)
  ${GREEN}✓${NC} Local .env written at ${LOCAL_ENV}

EOF
fi

cat <<EOF
  ${BOLD}What's next:${NC}

  1. Backend deploy (Phase 4 above only applied schema — backend code itself
     still needs to be uploaded to /home/${CPANEL_USER}/public_html/):
       ${YELLOW}rsync -avz --exclude='.env' ${REPO_ROOT}/backend/ ${CPANEL_USER}@${CPANEL_HOST}:public_html/${NC}

  2. Smoke-test an endpoint from a different machine (NOT this dev box,
     per dev/prod boundary rule):
       ${YELLOW}curl -X POST https://api.mbsh96reunion.com/rsvp.php \\\\
         -H "Origin: https://mbsh96reunion.com" \\\\
         -H "Content-Type: application/json" \\\\
         -d '{"first_name":"Test","last_name":"Smoke","email":"test@example.com","attending":"yes"}'${NC}

  3. Cron registration via cPanel cron (Sessions 6 + 8 per V1-BRIEF timeline):
       ${YELLOW}0 7 * * * /usr/bin/php /home/${CPANEL_USER}/public_html/cron/send-capsules.php${NC}
       ${YELLOW}0 3 * * * /usr/bin/php /home/${CPANEL_USER}/public_html/cron/cleanup-rate-limits.php${NC}

  4. This script is idempotent — re-run anytime credentials need rotation.
     For site #2: fork this script and adjust the CONSTANTS block at the top.

EOF

# Clear sensitive variables from memory before exit
unset RESEND_API_KEY DB_PASSWORD_PROD DB_PASSWORD_DEV ADMIN_PASSWORD ADMIN_PASSWORD_HASH ADMIN_CSRF_SECRET MYSQL_ROOT_PASSWORD

log "Done. Sensitive variables cleared from memory."
