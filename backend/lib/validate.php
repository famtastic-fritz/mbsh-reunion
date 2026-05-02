<?php
// lib/validate.php — server-side input validation helpers
declare(strict_types=1);

class ValidationError extends RuntimeException {}

function fam_required(array $data, string $key, ?int $maxLen = null): string {
  $v = trim((string)($data[$key] ?? ''));
  if ($v === '') throw new ValidationError("Field required: $key");
  if ($maxLen !== null && mb_strlen($v) > $maxLen) throw new ValidationError("Field too long: $key");
  return $v;
}

function fam_optional(array $data, string $key, ?int $maxLen = null): ?string {
  $v = trim((string)($data[$key] ?? ''));
  if ($v === '') return null;
  if ($maxLen !== null && mb_strlen($v) > $maxLen) throw new ValidationError("Field too long: $key");
  return $v;
}

function fam_email(array $data, string $key, bool $required = true): ?string {
  $v = fam_optional($data, $key, 255);
  if ($v === null) {
    if ($required) throw new ValidationError("Email required: $key");
    return null;
  }
  if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $v)) throw new ValidationError("Invalid email: $key");
  return strtolower($v);
}

function fam_enum(array $data, string $key, array $allowed, bool $required = true): ?string {
  $v = fam_optional($data, $key, 100);
  if ($v === null) {
    if ($required) throw new ValidationError("Field required: $key");
    return null;
  }
  if (!in_array($v, $allowed, true)) throw new ValidationError("Invalid value for $key");
  return $v;
}

function fam_int(array $data, string $key, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX, ?int $default = null): ?int {
  if (!isset($data[$key]) || $data[$key] === '' || $data[$key] === null) return $default;
  $n = filter_var($data[$key], FILTER_VALIDATE_INT);
  if ($n === false) throw new ValidationError("Not an integer: $key");
  if ($n < $min || $n > $max) throw new ValidationError("Out of range: $key");
  return $n;
}

function fam_bool(array $data, string $key, bool $default = false): bool {
  if (!isset($data[$key])) return $default;
  $v = $data[$key];
  return in_array($v, [true, 1, '1', 'true', 'yes', 'on'], true);
}

function fam_honeypot_clean(array $data): void {
  $v = trim((string)($data['website'] ?? ''));
  if ($v !== '') {
    // Silent reject for spam — return success so bots don't learn
    http_response_code(204);
    exit;
  }
}

function fam_form_loaded_at_check(array $data, int $minMs = 3000): void {
  $ts = (int)($data['form_loaded_at'] ?? 0);
  if ($ts <= 0) {
    http_response_code(204);
    exit;
  }
  $elapsed = (time() * 1000) - $ts;
  if ($elapsed < $minMs) {
    http_response_code(204);
    exit;
  }
}

function fam_read_json_body(): array {
  $raw = file_get_contents('php://input') ?: '';
  if ($raw === '') return [];
  $j = json_decode($raw, true);
  if (!is_array($j)) throw new ValidationError('Invalid JSON body');
  return $j;
}

function fam_json_response(int $status, array $body): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($body, JSON_UNESCAPED_SLASHES);
  exit;
}
