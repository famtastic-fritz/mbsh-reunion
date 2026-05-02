<?php
// sponsors.php — GET approved sponsor wall feed
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
  $stmt = $pdo->query("SELECT display_name, tier, logo_path, website_url FROM sponsors_approved WHERE active = 1 ORDER BY FIELD(tier, 'diamond','captain','crew','friend'), display_order");
  fam_json_response(200, $stmt->fetchAll());
} catch (Throwable $e) {
  error_log('[sponsors] err: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
