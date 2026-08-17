<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
$config = fam_load_config();
$target = (string)($config['committee_login_url'] ?? '/wp-login.php');
header('Cache-Control: no-store');
header('Location: ' . $target, true, 302);
exit;
