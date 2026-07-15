<?php
// survey.php — POST endpoint for Class Survey submissions
// Fields match the Microsoft Forms survey sent to the class.
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

  $firstName       = fam_required($data, 'first_name', 100);
  $lastName        = fam_required($data, 'last_name', 100);
  $hsName          = fam_optional($data, 'hs_name', 255);
  $phone           = fam_optional($data, 'phone', 50);
  $email           = fam_email($data, 'email', true);
  $mailingAddress  = fam_optional($data, 'mailing_address', 500);
  $tshirtSize      = fam_optional($data, 'tshirt_size', 20);
  $planning        = fam_optional($data, 'planning', 10);
  $planningRole    = fam_optional($data, 'planning_role', 100);
  $contactPref     = fam_optional($data, 'contact_pref', 50);
  $groupme         = fam_optional($data, 'groupme', 10);
  $classmatesPassed = fam_optional($data, 'classmates_passed', 1000);
  $reunionMonth    = fam_optional($data, 'reunion_month', 100);
  $duration        = fam_optional($data, 'duration', 100);
  $daysOfWeek      = fam_optional($data, 'days_of_week', 100);
  $reunionType     = fam_optional($data, 'reunion_type', 100);
  $venueType       = fam_optional($data, 'venue_type', 100);
  $budget          = fam_optional($data, 'budget', 100);
  $openOther       = fam_optional($data, 'open_other_classes', 100);
  $comments        = fam_optional($data, 'comments', 2000);

  $stmt = $pdo->prepare('INSERT INTO surveys
    (first_name, last_name, hs_name, phone, email, mailing_address, tshirt_size, planning, planning_role, contact_pref, groupme, classmates_passed, reunion_month, duration, days_of_week, reunion_type, venue_type, budget, open_other_classes, comments)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([
    $firstName, $lastName, $hsName, $phone, $email, $mailingAddress,
    $tshirtSize, $planning, $planningRole, $contactPref, $groupme,
    $classmatesPassed, $reunionMonth, $duration, $daysOfWeek,
    $reunionType, $venueType, $budget, $openOther, $comments
  ]);
  $surveyId = (int)$pdo->lastInsertId();

  // Committee notification (best-effort)
  $committeeHtml = "<p>New Class Survey &mdash; {$firstName} {$lastName} ({$email}).</p>";
  try {
    fam_send_email($config, $config['committee_email'], "Survey: {$firstName} {$lastName}", $committeeHtml, 'committee');
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
