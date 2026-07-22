<?php
// menu.php — POST endpoint for Gold Menu dinner preference submissions
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/resend.php';

$config = fam_load_config();
$pdo = fam_db($config);

// CORS + preflight
fam_cors($config);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(204); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'method_not_allowed']);
  exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) {
  http_response_code(400);
  echo json_encode(['error' => 'invalid_json']);
  exit;
}

// Honeypot
if (!empty($body['website'])) { echo json_encode(['ok' => true]); exit; }

// Rate limit
fam_rate_limit($pdo, 'menu', 10, 60);

// Validation
$errors = [];
if (empty($body['name'])) $errors[] = 'Name is required.';
if (empty($body['email']) || !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
if (empty($body['selections']) || !is_array($body['selections'])) $errors[] = 'Selections are required.';

$selections = $body['selections'] ?? [];
if (empty($selections['hors']) || count($selections['hors']) !== 2) $errors[] = 'Please select exactly 2 Hors d\'Oeuvre stations.';
if (empty($selections['salad'])) $errors[] = 'Please select 1 salad.';
if (empty($selections['entree']) || count($selections['entree']) < 1 || count($selections['entree']) > 2) $errors[] = 'Please select 1 or 2 entrées.';
if (empty($selections['side']) || count($selections['side']) !== 2) $errors[] = 'Please select exactly 2 side items.';

if (!empty($errors)) {
  http_response_code(422);
  echo json_encode(['error' => 'validation_failed', 'messages' => $errors]);
  exit;
}

// Timestamp anti-spam
$loadedAt = (int)($body['form_loaded_at'] ?? 0);
if ($loadedAt > 0 && (time() * 1000 - $loadedAt) < 3000) {
  http_response_code(429);
  echo json_encode(['error' => 'too_fast']);
  exit;
}

try {
  $stmt = $pdo->prepare('INSERT INTO menu_selections (name, email, selections_json, dietary) VALUES (?, ?, ?, ?)');
  $stmt->execute([
    trim($body['name']),
    trim($body['email']),
    json_encode($selections),
    !empty($body['dietary']) ? trim($body['dietary']) : null,
  ]);
  $id = (int)$pdo->lastInsertId();

  // Build selection summary for emails
  $horsList = implode(', ', $selections['hors']);
  $entreeList = implode(', ', $selections['entree']);
  $sideList = implode(', ', $selections['side']);
  $dietaryNote = !empty($body['dietary']) ? htmlspecialchars(trim($body['dietary'])) : 'None';

  // Confirmation email to submitter
  $submitterHtml = "<h2>Hi {$body['name']},</h2>
    <p>Your Gold Menu preferences have been received. Here's what you selected:</p>
    <ul>
      <li><strong>Hors d'Oeuvre (2):</strong> {$horsList}</li>
      <li><strong>Salad:</strong> {$selections['salad']}</li>
      <li><strong>Entrée (1-2):</strong> {$entreeList}</li>
      <li><strong>Sides (2):</strong> {$sideList}</li>
      <li><strong>Dietary:</strong> {$dietaryNote}</li>
    </ul>
    <p>This is <strong>not a final order</strong>. The committee will review all selections before finalizing the menu.</p>
    <p>Questions? Reply to this email or ask <strong>Hi-Tide Harry</strong> on the website.</p>
    <p>— MBSH Class of '96 Reunion Committee</p>";

  // Committee notification email
  $committeeHtml = "<h2>New Menu Selection</h2>
    <p><strong>Name:</strong> {$body['name']}<br>
    <strong>Email:</strong> {$body['email']}</p>
    <ul>
      <li><strong>Hors d'Oeuvre:</strong> {$horsList}</li>
      <li><strong>Salad:</strong> {$selections['salad']}</li>
      <li><strong>Entrée:</strong> {$entreeList}</li>
      <li><strong>Sides:</strong> {$sideList}</li>
      <li><strong>Dietary:</strong> {$dietaryNote}</li>
    </ul>
    <p><a href=\"https://api.mbsh96reunion.com/admin/menu-results.php\">View all results</a></p>";

  // Send emails (best-effort; don't fail the request if email fails)
  $emailErrors = [];
  try {
    fam_send_email($config, trim($body['email']), 'Your Gold Menu Preferences — MBSH Class of \'96', $submitterHtml, 'harry');
  } catch (Throwable $e) {
    error_log('menu.php submitter email error: ' . $e->getMessage());
    $emailErrors[] = 'submitter_email_failed';
  }

  try {
    fam_send_email($config, $config['committee_email'], "Menu: {$body['name']}", $committeeHtml, 'committee');
  } catch (Throwable $e) {
    error_log('menu.php committee email error: ' . $e->getMessage());
    $emailErrors[] = 'committee_email_failed';
  }

  $response = ['ok' => true, 'id' => $id];
  if (!empty($emailErrors)) {
    $response['email_warnings'] = $emailErrors;
  }
  echo json_encode($response);
} catch (Throwable $e) {
  error_log('menu.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => 'server_error']);
}
