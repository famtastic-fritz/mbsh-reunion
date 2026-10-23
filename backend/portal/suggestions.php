<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php'; require_once dirname(__DIR__).'/lib/reunion-experience.php'; fam_experience_errors();
$method=fam_portal_json_method(['GET','POST','PATCH','DELETE']); $id=fam_require_active_attendee($pdo);
if($method==='GET'){ $q=$pdo->prepare('SELECT public_id,category,subject,message,status,admin_note,created_at,updated_at FROM attendee_suggestions WHERE attendee_id=? ORDER BY created_at DESC'); $q->execute([$id]); fam_json_response(200,['suggestions'=>$q->fetchAll()]); }
fam_require_attendee_csrf();$d=fam_read_json_body();fam_experience_text_fields($d,['id','category','subject','message','request_key']);
if($method!=='DELETE'&&in_array($d['category']??'', ['music','event'],true))fam_require_experience_ticket($pdo,$config,$id);
// Do not allow editing an existing activity into another category to bypass admission.
if($method==='PATCH'){$check=$pdo->prepare('SELECT category FROM attendee_suggestions WHERE public_id=? AND attendee_id=?');$check->execute([$d['id']??'', $id]);if(in_array($check->fetchColumn(),['music','event'],true))fam_require_experience_ticket($pdo,$config,$id);}
if($method==='PATCH'){
  try{$public=fam_required($d,'id',36);$category=fam_enum($d,'category',['music','event','website','accessibility','other'],true);$subject=fam_required($d,'subject',200);$message=fam_required($d,'message',2000);
    $q=$pdo->prepare("UPDATE attendee_suggestions SET category=?,subject=?,message=? WHERE public_id=? AND attendee_id=? AND status IN ('new','reviewing')");$q->execute([$category,$subject,$message,$public,$id]);if($q->rowCount()!==1)fam_json_response(409,['error'=>'suggestion_not_editable']);fam_experience_audit($pdo,$id,'suggestion_edited','attendee_suggestions',$public);fam_json_response(200,['ok'=>true]);
  }catch(ValidationError $e){fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]);}
}
if($method==='DELETE'){$public=fam_required($d,'id',36);$q=$pdo->prepare("UPDATE attendee_suggestions SET status='closed',admin_note=COALESCE(admin_note,'Closed by attendee') WHERE public_id=? AND attendee_id=? AND status IN ('new','reviewing')");$q->execute([$public,$id]);if($q->rowCount()!==1)fam_json_response(409,['error'=>'suggestion_not_closable']);fam_experience_audit($pdo,$id,'suggestion_withdrawn','attendee_suggestions',$public);fam_json_response(200,['ok'=>true,'status'=>'closed']);}
fam_rate_limit($pdo,'portal_suggestion',3600,500);fam_experience_rate($pdo,$id,'suggestion',20);
try { $category=fam_enum($d,'category',['music','event','website','accessibility','other'],true); $subject=fam_required($d,'subject',200); $message=fam_required($d,'message',2000); $public=fam_uuid_v4();$key=fam_optional($d,'request_key',36);if($key!==null&&!preg_match('/^[a-f0-9-]{36}$/',$key))throw new ValidationError('Invalid request key.');
  $pdo->beginTransaction();$lock=$pdo->prepare('SELECT id FROM attendee_accounts WHERE id=? FOR UPDATE');$lock->execute([$id]);
  $dupe=$pdo->prepare('SELECT public_id,status FROM attendee_suggestions WHERE attendee_id=? AND ((request_key IS NOT NULL AND request_key=?) OR (category=? AND subject=? AND message=?)) ORDER BY id DESC LIMIT 1');$dupe->execute([$id,$key,$category,$subject,$message]);if($saved=$dupe->fetch()){$pdo->commit();fam_json_response(200,['ok'=>true,'id'=>$saved['public_id'],'status'=>$saved['status'],'already_recorded'=>true]);}
  $pdo->prepare('INSERT INTO attendee_suggestions (public_id,attendee_id,category,subject,message,request_key) VALUES (?,?,?,?,?,?)')->execute([$public,$id,$category,$subject,$message,$key]);
  fam_experience_audit($pdo,$id,'suggestion_submitted','attendee_suggestions',$public);$pdo->commit();
  fam_json_response(201,['ok'=>true,'id'=>$public,'status'=>'new']);
} catch(ValidationError $e){ fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]); }
