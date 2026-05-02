<?php
// lib/upload.php — file upload safety: MIME via finfo, size, dimensions, UUID rename, NO SVG
declare(strict_types=1);

class UploadError extends RuntimeException {}

const FAM_UPLOAD_MAX_BYTES = 2 * 1024 * 1024;          // 2 MB
const FAM_UPLOAD_MAX_DIM   = 4000;                     // 4000x4000 px
const FAM_UPLOAD_ALLOWED_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

function fam_handle_upload(?array $file, string $destDir): ?string {
  if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
  if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) throw new UploadError("Upload failed: {$file['error']}");
  if (($file['size'] ?? 0) > FAM_UPLOAD_MAX_BYTES) throw new UploadError('File too large (max 2MB)');
  $tmp = $file['tmp_name'] ?? '';
  if (!$tmp || !is_uploaded_file($tmp)) throw new UploadError('Invalid upload');

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmp) ?: '';
  if (!isset(FAM_UPLOAD_ALLOWED_MIMES[$mime])) throw new UploadError("MIME not allowed: $mime");

  // Reject SVG explicitly even if it slipped past
  if (strpos($mime, 'svg') !== false || strpos(strtolower($file['name'] ?? ''), '.svg') !== false) {
    throw new UploadError('SVG uploads not accepted');
  }

  $dim = @getimagesize($tmp);
  if (!$dim) throw new UploadError('Could not read image dimensions');
  if ($dim[0] > FAM_UPLOAD_MAX_DIM || $dim[1] > FAM_UPLOAD_MAX_DIM) throw new UploadError('Image dimensions exceed 4000x4000');

  if (!is_dir($destDir)) {
    if (!@mkdir($destDir, 0755, true) && !is_dir($destDir)) throw new UploadError('Could not create dest dir');
  }
  $ext = FAM_UPLOAD_ALLOWED_MIMES[$mime];
  $uuid = bin2hex(random_bytes(16));
  $destPath = rtrim($destDir, '/') . '/' . $uuid . '.' . $ext;
  if (!move_uploaded_file($tmp, $destPath)) throw new UploadError('Could not move uploaded file');
  @chmod($destPath, 0644);
  return $destPath;
}
