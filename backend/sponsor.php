<?php
// sponsor.php — POST endpoint for sponsor inquiries (multipart with optional logo)
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
  fam_rate_limit($pdo, 'sponsor', 60, 5);

  $name = fam_required($_POST, 'contact_name', 255);
  $company = fam_optional($_POST, 'company_name', 255);
  $email = fam_email($_POST, 'email', true);
  $phone = fam_optional($_POST, 'phone', 50);
  $tier = fam_enum($_POST, 'tier_interest', ['diamond','captain','crew','friend','custom'], true);
  $custom = $tier === 'custom' ? fam_int($_POST, 'custom_amount', 1, 1000000, null) : null;
  $message = fam_optional($_POST, 'message', 2000);

  $logoPath = null;
  if (!empty($_FILES['logo']['name'] ?? '')) {
    $logoPath = fam_handle_upload($_FILES['logo'] ?? null, $config['pending_uploads_path'] . '/sponsors');
  }

  $stmt = $pdo->prepare('INSERT INTO sponsors_pending (contact_name, company_name, email, phone, tier_interest, custom_amount, logo_path, message, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$name, $company, $email, $phone, $tier, $custom, $logoPath, $message, 'pending']);
  $id = (int)$pdo->lastInsertId();

  $reviewUrl = 'https://api.mbsh96reunion.com/admin/review-sponsor.php?id=' . $id;
  $html = "<p>New sponsor inquiry from {$name} ({$company}) — tier: {$tier}.</p><p>Email: {$email} | Phone: " . ($phone ?: '—') . "</p><p>Review: <a href=\"{$reviewUrl}\">{$reviewUrl}</a></p>";
  try {
    fam_send_email($config, $config['committee_email'], "Sponsor inquiry: {$name} ({$tier})", $html, 'committee');
  } catch (ResendError $e) {
    error_log('[sponsor] notify failed: ' . $e->getMessage());
  }

  fam_json_response(200, ['ok' => true, 'id' => $id]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (UploadError $e) {
  fam_json_response(400, ['error' => 'upload_error', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
  error_log('[sponsor] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
