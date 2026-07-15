<?php
// survey.php — POST endpoint for Class Survey submissions
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
  fam_rate_limit($pdo, 'survey', 60, 5);
  fam_rate_limit($pdo, 'survey_hourly', 3600, 20);

  $name        = fam_required($data, 'full_name', 255);
  $email       = fam_email($data, 'email', true);
  $city        = fam_optional($data, 'current_city', 255);
  $vibe        = fam_optional($data, 'reunion_vibe', 100);
  $timing      = fam_optional($data, 'reunion_timing', 100);
  $travel      = fam_optional($data, 'travel_method', 100);
  $needHotel   = fam_bool($data, 'need_hotel', false);
  $plusCount   = fam_int($data, 'plus_one_count', 0, 10, 0);
  $plusNames   = fam_optional($data, 'plus_one_names', 500);
  $teacher     = fam_optional($data, 'favorite_teacher', 255);
  $memory      = fam_optional($data, 'wildest_memory', 2000);
  $lifeUpdate  = fam_optional($data, 'life_update', 2000);
  $dietary     = fam_optional($data, 'dietary', 1000);
  $allergies   = fam_optional($data, 'allergies', 1000);
  $songs       = fam_optional($data, 'song_requests', 2000);

  $stmt = $pdo->prepare('INSERT INTO surveys
    (full_name, email, current_city, reunion_vibe, reunion_timing, travel_method, need_hotel, plus_one_count, plus_one_names, favorite_teacher, wildest_memory, life_update, dietary, allergies, song_requests)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([
    $name, $email, $city, $vibe, $timing, $travel,
    $needHotel ? 1 : 0, $plusCount, $plusNames,
    $teacher, $memory, $lifeUpdate, $dietary, $allergies, $songs
  ]);
  $surveyId = (int)$pdo->lastInsertId();

  // Committee notification (best-effort)
  $committeeHtml = "<p>New Class Survey — {$name} ({$email}).</p>";
  try {
    fam_send_email($config, $config['committee_email'], "Survey: {$name}", $committeeHtml, 'committee');
  } catch (Throwable $e) {
    error_log('[survey] Email send failed: ' . $e->getMessage());
  }

  fam_json_response(200, ['ok' => true, 'id' => $surveyId]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
  error_log('[survey] DB error: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'db_error']);
} catch (Throwable $e) {
  error_log('[survey] Uncaught: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
