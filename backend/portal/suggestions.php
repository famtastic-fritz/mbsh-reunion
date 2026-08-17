<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','POST','PATCH','DELETE']); $id=fam_require_active_attendee($pdo);
if($method==='GET'){ $q=$pdo->prepare('SELECT public_id,category,subject,message,status,admin_note,created_at,updated_at FROM attendee_suggestions WHERE attendee_id=? ORDER BY created_at DESC'); $q->execute([$id]); fam_json_response(200,['suggestions'=>$q->fetchAll()]); }
fam_require_attendee_csrf();$d=fam_read_json_body();
if($method==='PATCH'){
  try{$public=fam_required($d,'id',36);$category=fam_enum($d,'category',['music','event','website','accessibility','other'],true);$subject=fam_required($d,'subject',200);$message=fam_required($d,'message',2000);
    $q=$pdo->prepare("UPDATE attendee_suggestions SET category=?,subject=?,message=? WHERE public_id=? AND attendee_id=? AND status IN ('new','reviewing')");$q->execute([$category,$subject,$message,$public,$id]);if($q->rowCount()!==1)fam_json_response(409,['error'=>'suggestion_not_editable']);fam_json_response(200,['ok'=>true]);
  }catch(ValidationError $e){fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]);}
}
if($method==='DELETE'){$public=fam_required($d,'id',36);$q=$pdo->prepare("UPDATE attendee_suggestions SET status='closed',admin_note=COALESCE(admin_note,'Closed by attendee') WHERE public_id=? AND attendee_id=? AND status IN ('new','reviewing')");$q->execute([$public,$id]);if($q->rowCount()!==1)fam_json_response(409,['error'=>'suggestion_not_closable']);fam_json_response(200,['ok'=>true,'status'=>'closed']);}
fam_rate_limit($pdo,'portal_suggestion',3600,10);
try { $category=fam_enum($d,'category',['music','event','website','accessibility','other'],true); $subject=fam_required($d,'subject',200); $message=fam_required($d,'message',2000); $public=fam_uuid_v4();
  $pdo->prepare('INSERT INTO attendee_suggestions (public_id,attendee_id,category,subject,message) VALUES (?,?,?,?,?)')->execute([$public,$id,$category,$subject,$message]);
  $a=fam_portal_account($pdo,$id); fam_queue_portal_email($pdo,'suggestion:'.$public,$config['committee_email'],'New portal suggestion: '.$subject,fam_portal_email_shell('New attendee suggestion','<p>'.htmlspecialchars($a['first_name'].' '.$a['last_name'],ENT_QUOTES,'UTF-8').' submitted a '.htmlspecialchars($category,ENT_QUOTES,'UTF-8').' suggestion.</p>'),'committee');
  fam_json_response(201,['ok'=>true,'id'=>$public]);
} catch(ValidationError $e){ fam_json_response(400,['error'=>'validation_error','message'=>$e->getMessage()]); }
