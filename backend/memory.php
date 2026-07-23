<?php
// memory.php — POST memory submission with optional photo upload
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/upload.php';
require_once __DIR__ . '/lib/resend.php';

$config = fam_load_config();
fam_cors($config, 'public_post');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') fam_json_response(405, ['error' => 'method_not_allowed']);
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') === false) fam_json_response(415, ['error' => 'expected_multipart']);

try {
  fam_honeypot_clean($_POST);
  fam_form_loaded_at_check($_POST);
  $pdo = fam_db($config);
  fam_rate_limit($pdo, 'memory', 60, 5);

  $name = fam_required($_POST, 'contributor_name', 255);
  $email = fam_email($_POST, 'contributor_email', false);
  $text = fam_required($_POST, 'memory_text', 1000);
  $photoPath = !empty($_FILES['photo']['name'] ?? '') ? fam_handle_upload($_FILES['photo'] ?? null, $config['pending_uploads_path'] . '/memories') : null;

  $stmt = $pdo->prepare('INSERT INTO memories (contributor_name, contributor_email, memory_text, photo_path, approved) VALUES (?, ?, ?, ?, 0)');
  $stmt->execute([$name, $email, $text, $photoPath]);
  $id = (int)$pdo->lastInsertId();

  try {
    $reviewUrl = 'https://mbsh96reunion.com/admin/review-memory.php?id=' . $id;
    fam_send_email($config, $config['committee_email'], "New memory from {$name}", "<p>{$name} sent a memory.</p><p>Review: <a href=\"{$reviewUrl}\">{$reviewUrl}</a></p>", 'committee');
  } catch (ResendError $e) { error_log('[memory] notify failed: ' . $e->getMessage()); }

  fam_json_response(200, ['ok' => true, 'id' => $id]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (UploadError $e) {
  fam_json_response(400, ['error' => 'upload_error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
  error_log('[memory] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
