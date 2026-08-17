<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/lib/config.php';
require_once dirname(__DIR__) . '/lib/cors.php';
require_once dirname(__DIR__) . '/lib/db.php';
require_once dirname(__DIR__) . '/lib/validate.php';
require_once dirname(__DIR__) . '/lib/rate-limit.php';
require_once dirname(__DIR__) . '/lib/resend.php';
require_once dirname(__DIR__) . '/lib/portal-auth.php';
require_once dirname(__DIR__) . '/lib/portal-email.php';
require_once dirname(__DIR__) . '/lib/production-snapshot.php';

$config = fam_load_config();
$config['portal_token_secret'] = $config['portal_token_secret'] ?? ($config['admin_csrf_secret'] ?? '');
$config['portal_base_url'] = rtrim((string)($config['portal_base_url'] ?? 'https://mbsh96reunion.com'), '/');
fam_cors($config, 'same_origin');
$pdo = fam_db($config);

function fam_portal_json_method(array $allowed): string {
  $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
  if (!in_array($method, $allowed, true)) fam_json_response(405, ['error'=>'method_not_allowed']);
  if ($method !== 'GET' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) fam_json_response(415, ['error'=>'expected_application_json']);
  return $method;
}

function fam_portal_account(PDO $pdo, int $id): ?array {
  $q = $pdo->prepare('SELECT a.public_id,a.email,a.status,a.email_verified_at,p.first_name,p.last_name,p.maiden_name,p.phone,p.city_state,p.graduation_year,p.bio,p.display_in_directory FROM attendee_accounts a JOIN attendee_profiles p ON p.attendee_id=a.id WHERE a.id=?');
  $q->execute([$id]);
  $row = $q->fetch();
  return $row ?: null;
}

function fam_require_active_attendee(PDO $pdo): int {
  $id=fam_require_attendee();
  $q=$pdo->prepare("SELECT COUNT(*) FROM attendee_accounts WHERE id=? AND status='active' AND email_verified_at IS NOT NULL"); $q->execute([$id]);
  if((int)$q->fetchColumn()!==1){ fam_attendee_logout(); fam_json_response(401,['error'=>'authentication_required']); }
  return $id;
}
