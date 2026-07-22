<?php
// survey2.php — POST endpoint for Quick RSVP (Survey 2) submissions
// Simplified 7-field form: first_name, last_name, email, phone, attending, guest_count, comments
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
  fam_rate_limit($pdo, 'survey2', 60, 5);
  fam_rate_limit($pdo, 'survey2_hourly', 3600, 20);

  $firstName  = fam_required($data, 'first_name', 100);
  $lastName   = fam_required($data, 'last_name', 100);
  $email      = fam_email($data, 'email', true);
  $phone      = fam_optional($data, 'phone', 50);
  $attending  = fam_required($data, 'attending', 20);
  $guestCount = fam_required($data, 'guest_count', 10);
  $comments   = fam_optional($data, 'comments', 1000);

  // Validate attending enum
  if (!in_array($attending, ['yes', 'maybe', 'no'], true)) {
    fam_json_response(400, ['error' => 'validation_error', 'message' => 'Invalid attending value.']);
  }

  $stmt = $pdo->prepare('INSERT INTO survey2
    (first_name, last_name, email, phone, attending, guest_count, comments)
    VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([
    $firstName, $lastName, $email, $phone, $attending, $guestCount, $comments
  ]);
  $surveyId = (int)$pdo->lastInsertId();

  // Build summary for emails
  $attendingLabel = ['yes' => 'Yes, attending', 'maybe' => 'Maybe', 'no' => 'Cannot attend'][$attending] ?? $attending;
  $selectionsHtml = "<ul>
    <li><strong>Name:</strong> {$firstName} {$lastName}</li>
    <li><strong>Email:</strong> {$email}</li>
    <li><strong>Phone:</strong> " . ($phone ?: '—') . "</li>
    <li><strong>Attending:</strong> {$attendingLabel}</li>
    <li><strong>Guests:</strong> {$guestCount}</li>
    <li><strong>Comments:</strong> " . ($comments ?: '—') . "</li>
  </ul>";

  // Committee notification
  $committeeHtml = "<p>New Quick RSVP &mdash; {$firstName} {$lastName} ({$email}).</p>" . $selectionsHtml;
  try {
    fam_send_email($config, $config['committee_email'], "Quick RSVP: {$firstName} {$lastName}", $committeeHtml, 'committee');
  } catch (Throwable $e) {
    error_log('[survey2] Committee email failed: ' . $e->getMessage());
  }

  // Submitter confirmation (best-effort)
  $submitterHtml = "<p>Hi {$firstName},</p>
    <p>Thanks for your RSVP! Here's what we received:</p>
    {$selectionsHtml}
    <p>The committee will keep you posted as details firm up. If anything changes, just submit again or reach out.</p>
    <p>&mdash; MBSH Class of '96 Reunion Committee</p>";
  try {
    fam_send_email($config, $email, 'Your MBSH Class of \'96 Reunion RSVP', $submitterHtml, 'public');
  } catch (Throwable $e) {
    error_log('[survey2] Submitter email failed: ' . $e->getMessage());
  }

  fam_json_response(200, ['ok' => true, 'id' => $surveyId]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
  error_log('[survey2] DB error: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'db_error']);
} catch (Throwable $e) {
  error_log('[survey2] Uncaught: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
