#!/usr/bin/env sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)

find "$ROOT/wp-content/plugins/famtastic-reunion-platform" -name '*.php' -print0 \
  | xargs -0 -n1 php -l
find "$ROOT/wp-content/themes/famtastic-event-cinema" -name '*.php' -print0 \
  | xargs -0 -n1 php -l

for themed_file in style.css theme.json functions.php index.php; do
  test -f "$ROOT/wp-content/themes/famtastic-event-cinema/$themed_file" || {
    echo "Missing FAMtastic theme file: $themed_file" >&2
    exit 1
  }
done

ACCESS="$ROOT/wp-content/plugins/famtastic-reunion-platform/includes/class-access.php"
for required in \
  "famtastic_committee_admin" \
  "famtastic_access_committee" \
  "login_redirect" \
  "allowed_redirect_hosts" \
  "protect_full_admin" \
  "activate_plugins" \
  "manage_options"
do
  grep -F "$required" "$ACCESS" >/dev/null || {
    echo "Missing required committee access contract: $required" >&2
    exit 1
  }
done

python3 -m json.tool "$ROOT/../site-studio/recipe/capability-manifest.json" >/dev/null

BRAND="$ROOT/wp-content/plugins/famtastic-reunion-platform/includes/class-brand-experience.php"
ADMIN_JS="$ROOT/wp-content/plugins/famtastic-reunion-platform/assets/admin.js"
for required in "admin_footer" "harry_operator_guide" "data-harry-wp-form"; do
  grep -F "$required" "$BRAND" >/dev/null || {
    echo "Missing WordPress Harry operator guide contract: $required" >&2
    exit 1
  }
done
grep -F "data-harry-wp-form" "$ADMIN_JS" >/dev/null || {
  echo 'Missing interactive WordPress Harry behavior.' >&2
  exit 1
}

TICKETS="$ROOT/wp-content/plugins/famtastic-reunion-platform/includes/class-tickets.php"
for required in \
  "woocommerce_order_status_refunded" \
  "woocommerce_order_status_cancelled" \
  "woocommerce_order_status_failed" \
  "woocommerce_order_refunded" \
  "famtastic_refund_lock_" \
  "already_revoked" \
  "atomic_status_transition" \
  "meta_value = %s WHERE post_id = %d" \
  "_famtastic_ticket_audit"
do
  grep -F "$required" "$TICKETS" >/dev/null || {
    echo "Missing required ticket reliability contract: $required" >&2
    exit 1
  }
done

MAILER="$ROOT/wp-content/plugins/famtastic-reunion-platform/includes/class-resend-mailer.php"
for required in "pre_wp_mail" "FAMTASTIC_RESEND_API_KEY" "Idempotency-Key" "wp_mail_failed" "famtastic_resend_delivery_status"; do
  grep -F "$required" "$MAILER" >/dev/null || {
    echo "Missing required Resend mail contract: $required" >&2
    exit 1
  }
done

if grep -R -n -E '(sk|rk)_(live|test)_[A-Za-z0-9]{12,}|re_[A-Za-z0-9]{12,}' "$ROOT"; then
  echo 'Potential provider secret found in WordPress scaffold.' >&2
  exit 1
fi

echo 'FAMtastic Reunion WordPress static checks passed.'
