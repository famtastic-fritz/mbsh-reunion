<?php
// in-memory.php — GET In Memory list (active only, ordered)
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';

$config = fam_load_config();
fam_cors($config, 'public_get');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') fam_json_response(405, ['error' => 'method_not_allowed']);

try {
  $pdo = fam_db($config);
  $stmt = $pdo->query('SELECT full_name, graduation_year, year_passed, tribute FROM in_memory WHERE active = 1 ORDER BY display_order, full_name');
  fam_json_response(200, $stmt->fetchAll());
} catch (Throwable $e) {
  error_log('[in-memory] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
