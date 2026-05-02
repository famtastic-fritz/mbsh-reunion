<?php
// lib/rate-limit.php — MySQL-backed rate limiting per IP per endpoint
declare(strict_types=1);

function fam_rate_limit(PDO $pdo, string $endpoint, int $windowSec = 60, int $maxAttempts = 5): void {
  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
  $ip = explode(',', $ip)[0];
  $ip = substr(trim($ip), 0, 45);
  $cutoff = (new DateTimeImmutable("-{$windowSec} seconds"))->format('Y-m-d H:i:s');
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND attempt_at >= ?');
  $stmt->execute([$ip, $endpoint, $cutoff]);
  $count = (int)$stmt->fetchColumn();
  if ($count >= $maxAttempts) {
    http_response_code(429);
    header('Retry-After: ' . $windowSec);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'rate_limited', 'retry_after' => $windowSec]);
    exit;
  }
  $ins = $pdo->prepare('INSERT INTO rate_limits (ip_address, endpoint) VALUES (?, ?)');
  $ins->execute([$ip, $endpoint]);
}
