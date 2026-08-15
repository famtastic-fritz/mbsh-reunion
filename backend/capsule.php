<?php
// capsule.php — POST time capsule form, queues for reunion-day delivery
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';

$config = fam_load_config();
fam_cors($config, 'public_post');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') fam_json_response(405, ['error' => 'method_not_allowed']);
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) fam_json_response(415, ['error' => 'expected_application_json']);

try {
  $data = fam_read_json_body();
  fam_honeypot_clean($data);
  fam_form_loaded_at_check($data);
  $pdo = fam_db($config);
  fam_rate_limit($pdo, 'capsule', 60, 5);

  $email = fam_email($data, 'email', true);
  $song  = fam_optional($data, 'song_answer', 1000);
  $person= fam_optional($data, 'person_answer', 1000);
  $mem   = fam_optional($data, 'memory_answer', 5000);

  $stmt = $pdo->prepare('INSERT INTO time_capsules (email, song_answer, person_answer, memory_answer) VALUES (?, ?, ?, ?)');
  $stmt->execute([$email, $song, $person, $mem]);

  fam_json_response(200, ['ok' => true]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
  error_log('[capsule] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
