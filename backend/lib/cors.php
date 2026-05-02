<?php
// lib/cors.php — explicit allow-list + Netlify regex pattern enforcement
declare(strict_types=1);

function fam_cors(array $config, string $endpoint_class = 'public_post'): void {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

  // CORS preflight
  if ($method === 'OPTIONS') {
    if ($origin && fam_origin_allowed($origin, $config)) {
      header("Access-Control-Allow-Origin: $origin");
      header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
      header('Access-Control-Max-Age: 86400');
      header('Vary: Origin');
    }
    http_response_code(204);
    exit;
  }

  // No-Origin policy per endpoint class
  if (!$origin) {
    if ($endpoint_class === 'public_post' || $endpoint_class === 'admin') {
      // Allow same-origin POST forms (browsers typically send Origin on cross-origin only)
      // Detect via Sec-Fetch-Site if available
      $secFetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
      if ($endpoint_class === 'public_post' && !in_array($secFetchSite, ['same-origin', 'same-site', ''], true)) {
        http_response_code(403);
        exit('Origin required');
      }
    }
    return;
  }

  if (fam_origin_allowed($origin, $config)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Vary: Origin');
  } else {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'origin_not_allowed', 'origin' => $origin]);
    exit;
  }
}

function fam_origin_allowed(string $origin, array $config): bool {
  if (in_array($origin, $config['allowed_origins'] ?? [], true)) return true;
  foreach (($config['allowed_origin_patterns'] ?? []) as $pattern) {
    if (@preg_match($pattern, $origin) === 1) return true;
  }
  return false;
}
