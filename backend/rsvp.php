<?php
// rsvp.php — POST endpoint for RSVP form submissions
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
  fam_honeypot_clean($data);
  fam_form_loaded_at_check($data);
  $pdo = fam_db($config);
  fam_rate_limit($pdo, 'rsvp', 60, 5);
  fam_rate_limit($pdo, 'rsvp_hourly', 3600, 20);

  $first = fam_required($data, 'first_name', 100);
  $last  = fam_required($data, 'last_name', 100);
  $email = fam_email($data, 'email', true);
  $attending = fam_enum($data, 'attending', ['yes','maybe','no'], true);
  $maiden = fam_optional($data, 'maiden_name', 100);
  $phone  = fam_optional($data, 'phone', 50);
  $city   = fam_optional($data, 'city_state', 255);
  $guests = fam_int($data, 'guest_count', 1, 10, 1);
  $names  = fam_optional($data, 'guest_names', 500);
  $diet   = fam_optional($data, 'dietary', 1000);
  $help   = fam_bool($data, 'help_planning', false);
  $msg    = fam_optional($data, 'message', 2000);
  $public = fam_bool($data, 'display_publicly', true);

  $stmt = $pdo->prepare('INSERT INTO rsvps (first_name, last_name, maiden_name, email, phone, city_state, attending, guest_count, guest_names, dietary, help_planning, message, display_publicly) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$first, $last, $maiden, $email, $phone, $city, $attending, $guests, $names, $diet, $help ? 1 : 0, $msg, $public ? 1 : 0]);
  $rsvpId = (int)$pdo->lastInsertId();

  // Send confirmation + committee notification (best-effort; don't fail the RSVP if email fails)
  $logoUrl = ''; // will be wired post-deploy
  $confirmHtml = "<h2 style=\"font-family:Georgia,serif;\">Welcome back, {$first}.</h2><p>We've got you down. See you on the night.</p><p style=\"color:#888;font-size:0.9em;\">If this wasn't you, reply to this email.</p>";
  $committeeHtml = "<p>New RSVP — {$first} {$last} ({$attending}). Email: {$email}. Guests: {$guests}.</p>";
  try {
    fam_send_email($config, $email, "You're on the list, {$first}", $confirmHtml, 'noreply');
    fam_send_email($config, $config['committee_email'], "RSVP: {$first} {$last} — {$attending}", $committeeHtml, 'committee');
  } catch (ResendError $e) {
    error_log('[rsvp] Resend send failed: ' . $e->getMessage());
  }

  fam_json_response(200, ['ok' => true, 'id' => $rsvpId]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
  error_log('[rsvp] DB error: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'db_error']);
} catch (Throwable $e) {
  error_log('[rsvp] Uncaught: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
