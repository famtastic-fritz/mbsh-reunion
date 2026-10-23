<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';require_once dirname(__DIR__).'/lib/reunion-media.php';
fam_experience_errors();fam_portal_json_method(['GET']);$id=fam_require_active_attendee($pdo);
fam_experience_text_fields($_GET,['id']);$public=fam_required($_GET,'id',36);$review=($_GET['review']??'')==='1';
if($review)fam_require_portal_staff($pdo,'moderate_media');else fam_require_experience_ticket($pdo,$config,$id);
$q=$pdo->prepare('SELECT media_type,status,consent_to_publish,file_path,derivative_path FROM attendee_media_submissions WHERE public_id=?');$q->execute([$public]);$row=$q->fetch();
if(!$row||$row['media_type']!=='photo'||$row['status']==='withdrawn'||(!$review&&($row['status']!=='approved'||!(bool)$row['consent_to_publish'])))fam_json_response(404,['error'=>'not_found']);
if(!$row['derivative_path']){
    if(!$review)fam_json_response(404,['error'=>'not_found']);
    $row['derivative_path']=fam_media_derivative($config,$row['file_path']);
    $pdo->prepare('UPDATE attendee_media_submissions SET derivative_path=? WHERE public_id=? AND derivative_path IS NULL')->execute([$row['derivative_path'],$public]);
}
$file=fam_media_private_file($config,$row['derivative_path']);
header('Content-Type: image/jpeg');header('X-Content-Type-Options: nosniff');header('Content-Disposition: inline; filename="reunion-photo.jpg"');header('Content-Security-Policy: default-src \'none\'');header('Content-Length: '.filesize($file));readfile($file);
