<?php
// chatbot-question.php — chatbot fallback collector (question + email)
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/resend.php';

$config = fam_load_config();
fam_cors($config, 'public_post');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') fam_json_response(405, ['error' => 'method_not_allowed']);
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) fam_json_response(415, ['error' => 'expected_application_json']);

try {
  $data = fam_read_json_body();
  $pdo = fam_db($config);
  fam_rate_limit($pdo, 'chatbot', 60, 10);

  $question = fam_required($data, 'question', 2000);
  $email = fam_email($data, 'email', false);
  $wasFallback = fam_bool($data, 'was_fallback', true);

  $stmt = $pdo->prepare('INSERT INTO chatbot_questions (question, email, was_fallback) VALUES (?, ?, ?)');
  $stmt->execute([$question, $email, $wasFallback ? 1 : 0]);

  if ($email && $wasFallback) {
    try {
      fam_send_email($config, $config['committee_email'], 'Chatbot fallback question', "<p>From {$email}:</p><blockquote>{$question}</blockquote>", 'harry');
    } catch (ResendError $e) { error_log('[chatbot] notify failed: ' . $e->getMessage()); }
  }

  fam_json_response(200, ['ok' => true]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
  error_log('[chatbot] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
