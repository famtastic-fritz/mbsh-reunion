<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/validate.php';
$config = fam_load_config();
fam_cors($config);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(204); exit; }
fam_json_response(410, ['error' => 'meal_selection_closed', 'message' => 'The reunion is now a mixer at Twisted Tonic. Food is on your own and no meal selection is needed.', 'information_url' => '/menu/']);
