#!/usr/bin/env bash
set -euo pipefail

# Read-only production export into a separate local database. This never writes
# to production and never merges production rows into the normal proof DB.
repo_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
snapshot_root="${MBSH_SNAPSHOT_ROOT:-$HOME/.config/famtastic/mbsh-prod-snapshots}"
snapshot_dir="$snapshot_root/$stamp"
remote_host="${MBSH_PROD_SSH:-nineoo@FAMTASTICINC.COM}"
compose_file="$repo_dir/wordpress/docker-compose.yml"
snapshot_db="mbsh_reunion_prod_snapshot"
dump_file="$snapshot_dir/production-operational.sql"
counts_file="$snapshot_dir/counts.tsv"

tables=(
  chatbot_questions in_memory memories menu_selections poll_options
  poll_questions poll_votes rsvps sponsors_approved sponsors_pending surveys
  survey2 ticket_orders time_capsules
)

umask 077
mkdir -p "$snapshot_dir"
chmod 700 "$snapshot_root" "$snapshot_dir"

printf 'Creating isolated production snapshot %s\n' "$stamp"

# The remote defaults file exists only for the duration of mysqldump. Database
# credentials never enter this process's arguments, output, or repository.
ssh -T "$remote_host" bash -s -- "${tables[@]}" > "$dump_file" <<'REMOTE'
set -euo pipefail
umask 077
defaults_file="$(mktemp)"
cleanup(){ rm -f "$defaults_file"; }
trap cleanup EXIT
php -r '$c=require "/home/nineoo/.config/mbsh-config.php"; $e=fn($v)=>str_replace(["\\","\n","\r"],["\\\\","\\n","\\r"],(string)$v); file_put_contents($argv[1],"[client]\nhost=".$e($c["db_host"])."\nuser=".$e($c["db_user"])."\npassword=".$e($c["db_password"])."\n");' "$defaults_file"
db_name="$(php -r '$c=require "/home/nineoo/.config/mbsh-config.php"; echo $c["db_name"];')"
shift 0
mysqldump --defaults-extra-file="$defaults_file" --single-transaction --quick \
  --skip-triggers --no-tablespaces "$db_name" "$@"
REMOTE
chmod 600 "$dump_file"

# Rebuild only the dedicated snapshot database. The portal's ordinary local
# proof database (mbsh_reunion) is deliberately untouched.
docker-compose -f "$compose_file" exec -T db mariadb -uroot -pchange-root-local-only \
  -e "DROP DATABASE IF EXISTS \`$snapshot_db\`; CREATE DATABASE \`$snapshot_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker-compose -f "$compose_file" exec -T db mariadb -uroot -pchange-root-local-only "$snapshot_db" < "$dump_file"
docker-compose -f "$compose_file" exec -T db mariadb -uroot -pchange-root-local-only \
  -e "GRANT SELECT ON \`$snapshot_db\`.* TO 'mbsh_local'@'%'; FLUSH PRIVILEGES;"

{
  printf 'table\tproduction_snapshot_rows\n'
  for table in "${tables[@]}"; do
    count="$(docker-compose -f "$compose_file" exec -T db mariadb -N -B -uroot -pchange-root-local-only "$snapshot_db" -e "SELECT COUNT(*) FROM \`$table\`;" | tr -d '\r')"
    printf '%s\t%s\n' "$table" "$count"
  done
} > "$counts_file"
chmod 600 "$counts_file"

printf 'Snapshot database: %s\n' "$snapshot_db"
printf 'Evidence directory: %s\n' "$snapshot_dir"
cat "$counts_file"
