<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';

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
if (empty($selections['hors']) || count($selections['hors']) < 2) $errors[] = 'Please select 2 Hors d\'Oeuvre stations.';
if (empty($selections['salad'])) $errors[] = 'Please select 1 salad.';
if (empty($selections['entree']) || count($selections['entree']) < 1) $errors[] = 'Please select at least 1 entrée.';
if (empty($selections['side']) || count($selections['side']) < 2) $errors[] = 'Please select 2 side items.';

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

  echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
  error_log('menu.php error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => 'server_error']);
}
