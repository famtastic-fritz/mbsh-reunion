<?php
// lib/admin-auth.php — session-cookie admin auth + login throttle + audit
declare(strict_types=1);

function fam_admin_session_start(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
  ]);
  session_name('mbsh_admin');
  session_start();
}

function fam_admin_authenticated(): bool {
  fam_admin_session_start();
  if (empty($_SESSION['admin_logged_in'])) return false;
  $last = $_SESSION['admin_last_activity'] ?? 0;
  if ((time() - $last) > 4 * 3600) {
    fam_admin_logout();
    return false;
  }
  $_SESSION['admin_last_activity'] = time();
  return true;
}

function fam_require_admin_auth(): void {
  if (!fam_admin_authenticated()) {
    http_response_code(401);
    header('Location: /admin/login.php');
    exit;
  }
}

function fam_admin_login(string $password, array $config, PDO $pdo): bool {
  fam_admin_session_start();
  $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

  // Throttle: 5 fails per 15 min → 1 hour lockout
  $cutoff = (new DateTimeImmutable('-15 minutes'))->format('Y-m-d H:i:s');
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM admin_login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at >= ?');
  $stmt->execute([$ip, $cutoff]);
  $fails = (int)$stmt->fetchColumn();
  if ($fails >= 5) {
    $lockoutCheck = $pdo->prepare('SELECT MAX(attempted_at) FROM admin_login_attempts WHERE ip_address = ? AND success = 0');
    $lockoutCheck->execute([$ip]);
    $lastFail = $lockoutCheck->fetchColumn();
    if ($lastFail && (time() - strtotime($lastFail)) < 3600) return false;
  }

  $ok = !empty($config['admin_password_hash']) && password_verify($password, $config['admin_password_hash']);
  $log = $pdo->prepare('INSERT INTO admin_login_attempts (ip_address, success) VALUES (?, ?)');
  $log->execute([$ip, $ok ? 1 : 0]);

  if ($ok) {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_label'] = 'committee';
  }
  return $ok;
}

function fam_admin_logout(): void {
  fam_admin_session_start();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function fam_admin_audit(PDO $pdo, string $action, ?string $targetTable = null, ?int $targetId = null, ?string $notes = null): void {
  $stmt = $pdo->prepare('INSERT INTO admin_audit_log (admin_session_id, admin_label, action, target_table, target_id, notes, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([
    session_id() ?: 'cli',
    $_SESSION['admin_label'] ?? 'committee',
    $action,
    $targetTable,
    $targetId,
    $notes,
    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
  ]);
}
