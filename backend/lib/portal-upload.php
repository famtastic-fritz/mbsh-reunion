<?php
declare(strict_types=1);

class PortalUploadError extends RuntimeException {}

const FAM_PORTAL_UPLOAD_MAX_BYTES = 25 * 1024 * 1024;
const FAM_PORTAL_UPLOAD_MIMES = [
  'image/jpeg'=>['photo','jpg'], 'image/png'=>['photo','png'], 'image/webp'=>['photo','webp'],
  'video/mp4'=>['video','mp4'], 'video/quicktime'=>['video','mov'],
  'audio/mpeg'=>['audio','mp3'], 'audio/mp4'=>['audio','m4a'], 'audio/wav'=>['audio','wav'],
  'application/pdf'=>['document','pdf'],
];

function fam_portal_upload(array $file, string $destDir): array {
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new PortalUploadError('A valid file is required');
  if ((int)($file['size'] ?? 0) > FAM_PORTAL_UPLOAD_MAX_BYTES) throw new PortalUploadError('File exceeds the 25MB limit');
  $tmp=(string)($file['tmp_name']??'');
  if ($tmp==='' || !is_uploaded_file($tmp)) throw new PortalUploadError('Invalid upload');
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
  if (!isset(FAM_PORTAL_UPLOAD_MIMES[$mime])) throw new PortalUploadError('File type is not allowed');
  [$type,$ext]=FAM_PORTAL_UPLOAD_MIMES[$mime];
  if (!is_dir($destDir) && !@mkdir($destDir,0750,true) && !is_dir($destDir)) throw new PortalUploadError('Upload storage unavailable');
  $path=rtrim($destDir,'/').'/'.bin2hex(random_bytes(20)).'.'.$ext;
  if (!move_uploaded_file($tmp,$path)) throw new PortalUploadError('Could not store upload');
  @chmod($path,0640);
  return ['path'=>$path,'media_type'=>$type,'original_filename'=>substr(basename((string)($file['name']??'upload')),0,255)];
}
