<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
require_once __DIR__ . '/../lib/csrf.php';

$config = fam_load_config();
$pdo = fam_db($config);
$err = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $pw = (string)($_POST['password'] ?? '');
  if (fam_admin_login($pw, $config, $pdo)) {
    fam_admin_audit($pdo, 'login_success');
    header('Location: dashboard.php');
    exit;
  }
  fam_admin_audit($pdo, 'login_failure');
  $err = 'Invalid password or too many attempts.';
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — MBSH</title>
<style>
body{font-family:Inter,sans-serif;background:#0A0A0A;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
form{background:#1A1A1A;padding:2.5rem;border-radius:6px;width:min(360px,90vw)}
h1{font-family:Georgia,serif;margin:0 0 1.5rem;font-size:1.5rem}
input{width:100%;padding:.7rem;border-radius:3px;border:1px solid #444;background:#0A0A0A;color:#fff;font-size:1rem;margin-bottom:1rem;box-sizing:border-box}
button{width:100%;padding:.85rem;background:#C8102E;color:#fff;border:none;border-radius:3px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;cursor:pointer}
.err{color:#FF6B6B;margin-bottom:1rem}
</style></head><body>
<form method="POST" autocomplete="off">
  <h1>Committee Admin</h1>
  <?php if ($err): ?><p class="err"><?= htmlspecialchars($err) ?></p><?php endif; ?>
  <input type="password" name="password" required autofocus placeholder="Password">
  <button type="submit">Sign In</button>
</form>
</body></html>
