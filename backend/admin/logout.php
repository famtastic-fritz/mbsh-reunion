<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/admin-auth.php';
fam_admin_logout();
header('Location: login.php');
exit;
