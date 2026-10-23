<?php
declare(strict_types=1);
require_once __DIR__.'/reunion-experience.php';

function fam_media_private_file(array $config,string $path): string {
    $root=realpath(rtrim((string)$config['pending_uploads_path'],'/').'/portal-media');
    $real=realpath($path);
    if(!$root||!$real||!str_starts_with($real,$root.DIRECTORY_SEPARATOR)||!is_file($real))throw new RuntimeException('private_media_unavailable');
    return $real;
}
/** PHP's configured byte limit; -1 alone means unlimited. Unknown units fail closed. */
function fam_media_memory_limit_bytes(string $value): ?int {
    $value=trim($value);
    if($value==='-1')return null;
    if(!preg_match('/^(\d+)\s*([KMG]?)$/i',$value,$match))throw new RuntimeException('image_memory_limit_unavailable');
    $factor=match(strtoupper($match[2])){'K'=>1024,'M'=>1024**2,'G'=>1024**3,default=>1};
    $bytes=(float)$match[1]*$factor;
    if($bytes>PHP_INT_MAX)throw new RuntimeException('image_memory_limit_unavailable');
    return (int)$bytes;
}
/** Conservative peak: compressed input/copy, decoded image/workspace, output/resampling and reserve. */
function fam_media_decode_fits_memory(int $width,int $height,int $fileBytes,int $usedBytes,?int $limitBytes): bool {
    if($width<1||$height<1||$fileBytes<0||$usedBytes<0)return false;
    if($limitBytes===null)return true;
    $scale=min(1,1600/max($width,$height));
    $outputWidth=max(1,(int)round($width*$scale));$outputHeight=max(1,(int)round($height*$scale));
    $estimatedPeak=(float)$fileBytes*2+(float)$width*$height*8+(float)$outputWidth*$outputHeight*8+16*1024*1024;
    return $estimatedPeak<=max(0,$limitBytes-$usedBytes);
}
/** Decode and encode, never copy an original into a response. This removes EXIF/GPS and active metadata. */
function fam_media_derivative(array $config,string $path): string {
    $path=fam_media_private_file($config,$path);
    if(!function_exists('imagecreatefromstring'))throw new RuntimeException('image_processing_unavailable');
    $size=@getimagesize($path);if(!$size||!in_array($size['mime']??'',['image/jpeg','image/png','image/webp'],true)||$size[0]<1||$size[1]<1||$size[0]*$size[1]>24000000)throw new ValidationError('Use a JPG, PNG or WebP photo under 24 megapixels for gallery review.');
    $bytes=filesize($path);
    if($bytes===false||!fam_media_decode_fits_memory((int)$size[0],(int)$size[1],$bytes,memory_get_usage(true),fam_media_memory_limit_bytes((string)ini_get('memory_limit'))))throw new ValidationError('This photo needs more memory than is safely available. Resize it so the longest edge is at most 1600 pixels, then upload again.');
    $raw=file_get_contents($path);$source=@imagecreatefromstring($raw);if(!$source)throw new ValidationError('This photo could not be safely opened.');unset($raw);
    $scale=min(1,1600/max($size[0],$size[1]));$w=max(1,(int)round($size[0]*$scale));$h=max(1,(int)round($size[1]*$scale));
    $dest=imagecreatetruecolor($w,$h);$white=imagecolorallocate($dest,255,255,255);imagefill($dest,0,0,$white);imagecopyresampled($dest,$source,0,0,0,0,$w,$h,$size[0],$size[1]);
    $output=dirname($path).'/'.bin2hex(random_bytes(20)).'.safe.jpg';
    $ok=imagejpeg($dest,$output,85);unset($dest,$source);
    if(!$ok)throw new RuntimeException('image_derivative_failed');chmod($output,0640);return $output;
}
function fam_review_experience_media(PDO $pdo,array $config,array $staff,string $public,string $status,?string $note): void {
    $pdo->beginTransaction();
    $q=$pdo->prepare('SELECT * FROM attendee_media_submissions WHERE public_id=? FOR UPDATE');$q->execute([$public]);$row=$q->fetch();
    if(!$row||!in_array($row['status'],['pending','approved'],true))fam_json_response(409,['error'=>'submission_not_reviewable']);
    $derivative=$row['derivative_path'];
    if($status==='approved'){
        if(!(bool)$row['consent_to_publish'])throw new ValidationError('The submitter has not given permission to publish this photo.');
        if($row['media_type']!=='photo')throw new ValidationError('Only reviewed photo derivatives can enter this gallery. Keep other file formats private.');
        if(!$derivative)$derivative=fam_media_derivative($config,$row['file_path']);else fam_media_private_file($config,$derivative);
    }
    $pdo->prepare('UPDATE attendee_media_submissions SET status=?,moderation_note=?,derivative_path=?,reviewed_at=NOW() WHERE id=?')->execute([$status,$note,$derivative,$row['id']]);
    fam_portal_staff_audit($pdo,$staff,'media_'.$status,'attendee_media_submissions',$public,['note'=>$note]);$pdo->commit();
}
