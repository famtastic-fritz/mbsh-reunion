<?php
// lib/config.php — dual-source config loader. Production reads from
// /home/<user>/.config/mbsh-config.php; dev reads from .env at the repo root.
// Production secrets file lives OUTSIDE web root with mode 0600.
declare(strict_types=1);

function fam_load_config(): array {
  // Allow tests/dev to override via env var
  $explicit = getenv('MBSH_CONFIG_PATH');
  if ($explicit && is_readable($explicit)) {
    $cfg = require $explicit;
    if (is_array($cfg)) return $cfg;
  }

  // Try production secrets paths in order
  $candidates = [
    '/home/nineoo/.config/mbsh-config.php',
    dirname(__DIR__, 2) . '/.mbsh-config.local.php', // local dev override (gitignored)
  ];
  foreach ($candidates as $p) {
    if (is_readable($p)) {
      $cfg = require $p;
      if (is_array($cfg)) return $cfg;
    }
  }

  // Fall back to .env at repo root (dev mode)
  $envPath = dirname(__DIR__, 2) . '/.env';
  if (is_readable($envPath)) {
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW);
    if ($env !== false) {
      return [
        'db_host'                  => $env['DB_HOST']     ?? '127.0.0.1',
        'db_name'                  => $env['DB_NAME']     ?? '',
        'db_user'                  => $env['DB_USER']     ?? '',
        'db_password'              => $env['DB_PASSWORD'] ?? '',
        'resend_api_key'           => $env['RESEND_API_KEY'] ?? '',
        'resend_from_domain'       => $env['RESEND_FROM_DOMAIN'] ?? 'send.mbsh96reunion.com',
        'resend_from_noreply'      => 'noreply@' . ($env['RESEND_FROM_DOMAIN'] ?? 'send.mbsh96reunion.com'),
        'resend_from_committee'    => 'committee@' . ($env['RESEND_FROM_DOMAIN'] ?? 'send.mbsh96reunion.com'),
        'resend_from_harry'        => 'harry@' . ($env['RESEND_FROM_DOMAIN'] ?? 'send.mbsh96reunion.com'),
        'resend_reply_to'          => $env['COMMITTEE_EMAIL'] ?? 'mbsh96reunion@gmail.com',
        'committee_email'          => $env['COMMITTEE_EMAIL'] ?? 'mbsh96reunion@gmail.com',
        'allowed_origins'          => ['http://localhost:8080', 'http://localhost:3333', 'http://127.0.0.1:8080'],
        'allowed_origin_patterns'  => ['/^https:\/\/[a-z0-9-]+--[a-z0-9-]+\.netlify\.app$/', '/^https:\/\/[a-z0-9-]+\.netlify\.app$/'],
        'admin_password_hash'      => $env['ADMIN_PASSWORD_HASH'] ?? '',
        'admin_csrf_secret'        => $env['ADMIN_CSRF_SECRET'] ?? bin2hex(random_bytes(16)),
        'pending_uploads_path'     => $env['PENDING_UPLOADS_PATH'] ?? sys_get_temp_dir() . '/mbsh-pending',
        'approved_uploads_path'    => $env['APPROVED_UPLOADS_PATH'] ?? dirname(__DIR__) . '/uploads/approved',
        'environment'              => 'development',
      ];
    }
  }

  // Last resort: error out loudly. Never silently degrade.
  http_response_code(500);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'config_unavailable', 'detail' => 'No mbsh-config.php or .env found']);
  exit;
}
