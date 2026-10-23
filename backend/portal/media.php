<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php'; require_once dirname(__DIR__).'/lib/portal-upload.php'; require_once dirname(__DIR__).'/lib/reunion-media.php'; fam_experience_errors();
$method=strtoupper($_SERVER['REQUEST_METHOD']??'GET'); if(!in_array($method,['GET','POST','PATCH','DELETE'],true)) fam_json_response(405,['error'=>'method_not_allowed']);
$id=fam_require_active_attendee($pdo);
if($method==='GET'){ $q=$pdo->prepare('SELECT public_id,media_type,title,caption,event_year,original_filename,consent_to_publish,status,moderation_note,created_at,reviewed_at FROM attendee_media_submissions WHERE attendee_id=? ORDER BY created_at DESC'); $q->execute([$id]); fam_json_response(200,['submissions'=>$q->fetchAll()]); }
fam_require_attendee_csrf();
// Owners may read and withdraw their files even after admission is refunded.
if($method!=='DELETE')fam_require_experience_ticket($pdo,$config,$id);
if($method==='PATCH'){
  if(stripos($_SERVER['CONTENT_TYPE']??'','application/json')===false)fam_json_response(415,['error'=>'expected_application_json']);
  try{$d=fam_read_json_body();$public=fam_required($d,'id',36);$title=fam_required($d,'title',200);$caption=fam_optional($d,'caption',1000);$year=fam_int($d,'event_year',1900,2100,null);$consent=fam_bool($d,'consent_to_publish',false);
    $q=$pdo->prepare("UPDATE attendee_media_submissions SET title=?,caption=?,event_year=?,consent_to_publish=?,status='pending',moderation_note=NULL,reviewed_at=NULL WHERE public_id=? AND attendee_id=? AND status IN ('pending','rejected')");$q->execute([$title,$caption,$year,$consent?1:0,$public,$id]);
    if($q->rowCount()!==1)fam_json_response(409,['error'=>'submission_not_editable']);fam_experience_audit($pdo,$id,'media_edited','media',$public);fam_json_response(200,['ok'=>true,'status'=>'pending']);
  }catch(ValidationError $e){fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]);}
}
if($method==='DELETE'){
  if(stripos($_SERVER['CONTENT_TYPE']??'','application/json')===false)fam_json_response(415,['error'=>'expected_application_json']);
  $d=fam_read_json_body();$public=fam_required($d,'id',36);$q=$pdo->prepare("UPDATE attendee_media_submissions SET status='withdrawn',moderation_note='Withdrawn by attendee',reviewed_at=NOW() WHERE public_id=? AND attendee_id=? AND status IN ('pending','rejected','approved')");$q->execute([$public,$id]);
  if($q->rowCount()!==1)fam_json_response(409,['error'=>'submission_not_withdrawable']);fam_experience_audit($pdo,$id,'media_withdrawn','media',$public);fam_json_response(200,['ok'=>true,'status'=>'withdrawn']);
}
fam_rate_limit($pdo,'portal_media',3600,500);fam_experience_rate($pdo,$id,'media',20);
if(stripos($_SERVER['CONTENT_TYPE']??'','multipart/form-data')===false) fam_json_response(415,['error'=>'expected_multipart']);
try {
  $title=fam_required($_POST,'title',200); $caption=fam_optional($_POST,'caption',1000); $year=fam_int($_POST,'event_year',1900,2100,null); $consent=fam_bool($_POST,'consent_to_publish',false);
  $pending=$_FILES['file']??[];
  $hash=isset($pending['tmp_name'])&&is_uploaded_file($pending['tmp_name'])?hash_file('sha256',$pending['tmp_name']):null;
  if($hash){$dupe=$pdo->prepare('SELECT public_id,status FROM attendee_media_submissions WHERE attendee_id=? AND content_hash=?');$dupe->execute([$id,$hash]);if($saved=$dupe->fetch())fam_json_response(200,['ok'=>true,'id'=>$saved['public_id'],'status'=>$saved['status'],'already_recorded'=>true]);}
  $usage=$pdo->prepare('SELECT COUNT(*) files,COALESCE(SUM(stored_bytes),0) bytes FROM attendee_media_submissions WHERE attendee_id=?');$usage->execute([$id]);$used=$usage->fetch();
  $bytes=(int)($pending['size']??0);if((int)$used['files']>=100||(int)$used['bytes']+$bytes>500*1024*1024)throw new ValidationError('Your upload allowance is full. Contact the committee for help sharing more photos.');
  $stored=fam_portal_upload($_FILES['file']??[],rtrim($config['pending_uploads_path'],'/').'/portal-media/'.$id); $public=fam_uuid_v4(); $derivative=null;
  if($stored['media_type']==='photo'){try{$derivative=fam_media_derivative($config,$stored['path']);}catch(Throwable $e){@unlink($stored['path']);throw $e;}}
  try{$pdo->prepare('INSERT INTO attendee_media_submissions (public_id,attendee_id,media_type,title,caption,event_year,file_path,original_filename,consent_to_publish,content_hash,derivative_path,stored_bytes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$public,$id,$stored['media_type'],$title,$caption,$year,$stored['path'],$stored['original_filename'],$consent?1:0,$hash,$derivative,$bytes]);}catch(Throwable $e){@unlink($stored['path']);if($derivative)@unlink($derivative);if($e instanceof PDOException&&(string)$e->getCode()==='23000')fam_json_response(409,['error'=>'upload_already_saved','message'=>'This upload is already saved. Refresh your submissions.']);throw $e;}
  fam_experience_audit($pdo,$id,'media_submitted','media',$public);
  fam_json_response(201,['ok'=>true,'id'=>$public,'status'=>'pending']);
} catch(ValidationError|PortalUploadError $e){ fam_json_response(400,['error'=>'upload_error','message'=>$e->getMessage()]); }
