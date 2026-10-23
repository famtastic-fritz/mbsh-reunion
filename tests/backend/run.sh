#!/usr/bin/env bash
set -euo pipefail
repo_dir="$(cd "$(dirname "$0")/../.." && pwd)"
find "$repo_dir/backend" "$repo_dir/tests/backend" -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
php "$repo_dir/tests/backend/portal_security_test.php"
php "$repo_dir/tests/backend/mixer_offer_test.php"
php "$repo_dir/tests/backend/email_templates_test.php"
php "$repo_dir/tests/backend/campaign_worker_test.php"
php "$repo_dir/tests/backend/campaign_audience_test.php"
php "$repo_dir/tests/backend/portal_preferences_test.php"
echo "PASS PHP syntax"
if [[ "${RUN_INTEGRATION:-0}" == 1 ]]; then "$repo_dir/tests/backend/integration.sh"; fi
