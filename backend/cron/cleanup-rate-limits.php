<?php
// cron/cleanup-rate-limits.php — daily. Trim rate_limits >24h + admin_login_attempts >7d
declare(strict_types=1);
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';

$config = fam_load_config();
$pdo = fam_db($config);

$d1 = $pdo->exec("DELETE FROM rate_limits WHERE attempt_at < (NOW() - INTERVAL 24 HOUR)");
$d2 = $pdo->exec("DELETE FROM admin_login_attempts WHERE attempted_at < (NOW() - INTERVAL 7 DAY)");
echo date('c'), " cleanup: rate_limits=$d1, admin_login_attempts=$d2\n";
