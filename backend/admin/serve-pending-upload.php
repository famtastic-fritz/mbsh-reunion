<?php
// serve-pending-upload.php — auth-gated streaming of files from /home/<user>/uploads-pending/
// Pending uploads live OUTSIDE web root and CANNOT be served as static files.
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();

$path = (string)($_GET['path'] ?? '');
// Only allow paths inside the configured pending uploads dir
$pendingBase = realpath($config['pending_uploads_path']) ?: '';
$realPath = realpath($path) ?: '';
if (!$pendingBase || !$realPath || strpos($realPath, $pendingBase) !== 0) {
  http_response_code(403); exit('forbidden');
}
if (!is_file($realPath)) { http_response_code(404); exit('not found'); }

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($realPath) ?: 'application/octet-stream';
header("Content-Type: $mime");
header('Content-Length: ' . filesize($realPath));
header('Content-Disposition: inline; filename="' . basename($realPath) . '"');
header('X-Content-Type-Options: nosniff');
readfile($realPath);
