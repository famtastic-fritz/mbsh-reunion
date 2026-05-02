<?php
// lib/csrf.php — HMAC-based CSRF tokens for admin state-change actions
declare(strict_types=1);

function fam_csrf_issue(string $sessionId, string $secret): string {
  $expires = time() + 4 * 3600; // 4 hours
  $payload = $sessionId . '|' . $expires;
  $sig = hash_hmac('sha256', $payload, $secret);
  return base64_encode("$payload|$sig");
}

function fam_csrf_validate(string $token, string $sessionId, string $secret): bool {
  $decoded = base64_decode($token, true);
  if (!$decoded) return false;
  $parts = explode('|', $decoded, 3);
  if (count($parts) !== 3) return false;
  [$sid, $exp, $sig] = $parts;
  if ($sid !== $sessionId) return false;
  if ((int)$exp < time()) return false;
  $expected = hash_hmac('sha256', "$sid|$exp", $secret);
  return hash_equals($expected, $sig);
}

function fam_require_csrf(string $secret): void {
  $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
  $sid = session_id();
  if (!$token || !$sid || !fam_csrf_validate($token, $sid, $secret)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'csrf_invalid']);
    exit;
  }
}
