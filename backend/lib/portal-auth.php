<?php
// Same-origin attendee authentication and opaque credential helpers.
declare(strict_types=1);

function fam_uuid_v4(): string {
  $b = random_bytes(16);
  $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
  $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function fam_random_token(int $bytes = 32): string {
  return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function fam_token_hash(string $token): string { return hash('sha256', $token); }

function fam_password_valid(string $password): bool {
  return strlen($password) >= 12 && strlen($password) <= 1024
    && preg_match('/[A-Za-z]/', $password) === 1
    && preg_match('/\d/', $password) === 1;
}

function fam_attendee_session_start(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_name('mbsh_attendee');
  session_set_cookie_params(['lifetime'=>0, 'path'=>'/', 'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'httponly'=>true, 'samesite'=>'Strict']);
  session_start();
}

function fam_attendee_login_session(int $attendeeId, string $publicId): void {
  fam_attendee_session_start();
  session_regenerate_id(true);
  $_SESSION['attendee_id'] = $attendeeId;
  $_SESSION['attendee_public_id'] = $publicId;
  $_SESSION['attendee_last_activity'] = time();
  $_SESSION['attendee_csrf'] = fam_random_token(24);
}

function fam_attendee_logout(): void {
  fam_attendee_session_start();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}

function fam_current_attendee_id(): ?int {
  fam_attendee_session_start();
  $id = (int)($_SESSION['attendee_id'] ?? 0);
  if ($id < 1) return null;
  $last = (int)($_SESSION['attendee_last_activity'] ?? 0);
  if (!$last || time() - $last > 8 * 3600) { fam_attendee_logout(); return null; }
  $_SESSION['attendee_last_activity'] = time();
  return $id;
}

function fam_require_attendee(): int {
  $id = fam_current_attendee_id();
  if ($id === null) fam_json_response(401, ['error'=>'authentication_required']);
  return $id;
}

function fam_require_attendee_csrf(): void {
  fam_attendee_session_start();
  $expected = (string)($_SESSION['attendee_csrf'] ?? '');
  $actual = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if ($expected === '' || $actual === '' || !hash_equals($expected, $actual)) fam_json_response(403, ['error'=>'csrf_invalid']);
}

function fam_portal_staff_capabilities(string $role): array {
  $roles = [
    'committee_member' => ['view_roster','view_inbox','view_rsvp','view_menu','moderate_media','manage_suggestions','respond_harry'],
    'committee_lead' => ['view_roster','view_inbox','view_rsvp','view_menu','edit_rsvp','edit_menu','moderate_media','manage_suggestions','respond_harry','send_communications','manage_tickets','check_in_tickets','manage_event_content'],
    'site_owner' => ['view_roster','view_inbox','view_rsvp','view_menu','edit_rsvp','edit_menu','moderate_media','manage_suggestions','respond_harry','send_communications','manage_tickets','check_in_tickets','issue_manual_tickets','manage_event_content','manage_committee','manage_site_structure','manage_brand','view_audit'],
  ];
  return $roles[$role] ?? [];
}

function fam_portal_staff_access(PDO $pdo, int $attendeeId): ?array {
  $q = $pdo->prepare("SELECT m.role FROM portal_staff_memberships m JOIN attendee_accounts a ON a.id=m.attendee_id WHERE m.attendee_id=? AND m.status='active' AND a.status='active' AND a.email_verified_at IS NOT NULL");
  $q->execute([$attendeeId]);
  $role = $q->fetchColumn();
  if (!is_string($role) || $role === '') return null;
  return ['role'=>$role,'capabilities'=>fam_portal_staff_capabilities($role)];
}

function fam_portal_staff_client_access(?array $access): array {
  if(!$access) return ['authorized'=>false,'role'=>null,'label'=>null,'capabilities'=>[]];
  $role=(string)$access['role'];
  $clientRole=$role==='site_owner' ? 'site_owner' : 'committee_admin';
  $label=$role==='site_owner' ? 'Site Owner' : ($role==='committee_lead' ? 'Committee Lead' : 'Committee Admin');
  return ['authorized'=>true,'role'=>$clientRole,'membership_role'=>$role,'label'=>$label,'capabilities'=>$access['capabilities']];
}

function fam_require_portal_staff(PDO $pdo, ?string $capability = null): array {
  $attendeeId = fam_require_attendee();
  $access = fam_portal_staff_access($pdo, $attendeeId);
  if (!$access) fam_json_response(403, ['error'=>'staff_access_required']);
  if ($capability !== null && !in_array($capability, $access['capabilities'], true)) {
    fam_json_response(403, ['error'=>'staff_capability_required']);
  }
  return ['attendee_id'=>$attendeeId] + $access;
}

function fam_portal_staff_audit(PDO $pdo, array $staff, string $action, ?string $targetType = null, ?string $targetPublicId = null, array $details = []): void {
  $q = $pdo->prepare('INSERT INTO portal_staff_audit_log (actor_attendee_id,actor_role,action,target_type,target_public_id,details_json,ip_address) VALUES (?,?,?,?,?,?,?)');
  $q->execute([(int)$staff['attendee_id'],(string)$staff['role'],$action,$targetType,$targetPublicId,$details ? json_encode($details, JSON_UNESCAPED_SLASHES) : null,fam_client_ip()]);
}

function fam_issue_auth_token(PDO $pdo, int $attendeeId, string $purpose, int $ttlSeconds): string {
  $token = fam_random_token();
  $expires = (new DateTimeImmutable("+{$ttlSeconds} seconds"))->format('Y-m-d H:i:s');
  $pdo->prepare('UPDATE attendee_auth_tokens SET used_at=NOW() WHERE attendee_id=? AND purpose=? AND used_at IS NULL')->execute([$attendeeId, $purpose]);
  $pdo->prepare('INSERT INTO attendee_auth_tokens (attendee_id,purpose,token_hash,expires_at) VALUES (?,?,?,?)')->execute([$attendeeId, $purpose, fam_token_hash($token), $expires]);
  return $token;
}

function fam_consume_auth_token(PDO $pdo, string $token, string $purpose): ?int {
  if ($token === '' || strlen($token) > 200) return null;
  $pdo->beginTransaction();
  try {
    $q = $pdo->prepare('SELECT id,attendee_id FROM attendee_auth_tokens WHERE token_hash=? AND purpose=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE');
    $q->execute([fam_token_hash($token), $purpose]);
    $row = $q->fetch();
    if (!$row) { $pdo->rollBack(); return null; }
    $pdo->prepare('UPDATE attendee_auth_tokens SET used_at=NOW() WHERE id=?')->execute([(int)$row['id']]);
    $pdo->commit();
    return (int)$row['attendee_id'];
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}

function fam_ticket_credential(string $publicId, string $secret, ?int $time = null): string {
  if (strlen($secret) < 32) throw new RuntimeException('portal_token_secret must contain at least 32 characters');
  $slot = intdiv($time ?? time(), 300);
  return 'mbsh96_' . str_replace('-', '', $publicId) . '_' . $slot . '_' . hash_hmac('sha256', 'ticket|' . $publicId . '|' . $slot, $secret);
}

function fam_ticket_credential_valid(string $credential, string $publicId, string $secret, ?int $time = null): bool {
  $now=$time ?? time();
  try { return hash_equals(fam_ticket_credential($publicId,$secret,$now),$credential) || hash_equals(fam_ticket_credential($publicId,$secret,$now-300),$credential); }
  catch (Throwable $e) { return false; }
}

function fam_client_ip(): string {
  $raw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  return substr(trim(explode(',', $raw)[0]), 0, 45);
}
