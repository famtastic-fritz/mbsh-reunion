<?php
// attendees.php — GET public attendee feed (display_publicly only, no PII)
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';

$config = fam_load_config();
fam_cors($config, 'public_get');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') fam_json_response(405, ['error' => 'method_not_allowed']);

try {
  $pdo = fam_db($config);
  $stmt = $pdo->query("SELECT first_name, last_name, maiden_name, city_state, attending FROM rsvps WHERE attending IN ('yes','maybe') AND display_publicly = 1 ORDER BY created_at DESC");
  $rows = $stmt->fetchAll();
  fam_json_response(200, $rows);
} catch (Throwable $e) {
  error_log('[attendees] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
