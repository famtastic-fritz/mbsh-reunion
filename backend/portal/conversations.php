<?php
declare(strict_types=1);
require_once __DIR__.'/_bootstrap.php';
$method=fam_portal_json_method(['GET','POST','PATCH']); $id=fam_require_active_attendee($pdo);
if($method==='GET'){
  $public=trim((string)($_GET['id']??''));
  if($public!==''){
    $q=$pdo->prepare("SELECT id,public_id,subject,status,priority,response_due_at,created_at,updated_at FROM portal_conversations WHERE public_id=? AND attendee_id=?");$q->execute([$public,$id]);$conversation=$q->fetch();if(!$conversation)fam_json_response(404,['error'=>'not_found']);
    $m=$pdo->prepare("SELECT public_id,author_type,body,delivery_status,created_at FROM portal_conversation_messages WHERE conversation_id=? ORDER BY created_at,id");$m->execute([$conversation['id']]);unset($conversation['id']);
    fam_json_response(200,['conversation'=>$conversation,'messages'=>$m->fetchAll()]);
  }
  $q=$pdo->prepare("SELECT c.public_id,c.subject,c.status,c.priority,c.updated_at,(SELECT body FROM portal_conversation_messages m WHERE m.conversation_id=c.id ORDER BY m.created_at DESC LIMIT 1) latest_message FROM portal_conversations c WHERE c.attendee_id=? ORDER BY c.updated_at DESC");$q->execute([$id]);
  fam_json_response(200,['conversations'=>$q->fetchAll()]);
}
fam_require_attendee_csrf();$d=fam_read_json_body();
if($method==='PATCH'){
  $public=fam_required($d,'id',36);$status=fam_enum($d,'status',['closed','waiting_committee'],true);
  $q=$pdo->prepare("UPDATE portal_conversations SET status=?,response_due_at=IF(?='waiting_committee',DATE_ADD(NOW(),INTERVAL 3 DAY),NULL) WHERE public_id=? AND attendee_id=? AND status IN ('new','assigned','waiting_attendee','waiting_committee','resolved','closed')");$q->execute([$status,$status,$public,$id]);if(!$q->rowCount()){$exists=$pdo->prepare('SELECT 1 FROM portal_conversations WHERE public_id=? AND attendee_id=?');$exists->execute([$public,$id]);if(!$exists->fetchColumn())fam_json_response(404,['error'=>'not_found']);}
  fam_json_response(200,['ok'=>true,'status'=>$status]);
}
$existing=trim((string)($d['conversation_id']??''));
if($existing!==''){
  $body=fam_required($d,'message',5000);$q=$pdo->prepare("SELECT id,subject FROM portal_conversations WHERE public_id=? AND attendee_id=? AND status<>'closed' FOR UPDATE");
  $pdo->beginTransaction();try{$q->execute([$existing,$id]);$conversation=$q->fetch();if(!$conversation){$pdo->rollBack();fam_json_response(404,['error'=>'not_found']);}$messagePublic=fam_uuid_v4();$pdo->prepare("INSERT INTO portal_conversation_messages(public_id,conversation_id,author_type,author_attendee_id,body,delivery_status) VALUES (?,?,'attendee',?,?,'internal')")->execute([$messagePublic,$conversation['id'],$id,$body]);$pdo->prepare("UPDATE portal_conversations SET status='waiting_committee',response_due_at=DATE_ADD(NOW(),INTERVAL 3 DAY) WHERE id=?")->execute([$conversation['id']]);$a=fam_portal_account($pdo,$id);fam_queue_portal_email($pdo,'conversation-followup:'.$messagePublic,$config['committee_email'],'Attendee reply: '.$conversation['subject'],fam_portal_email_shell('An attendee replied','<p>'.htmlspecialchars(($a['first_name']??'').' '.($a['last_name']??''),ENT_QUOTES,'UTF-8').' added a reply in the portal.</p><p>'.nl2br(htmlspecialchars($body,ENT_QUOTES,'UTF-8')).'</p>'),'committee');$pdo->commit();fam_json_response(201,['ok'=>true,'status'=>'waiting_committee']);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
$subject=fam_required($d,'subject',255);$body=fam_required($d,'message',5000);$public=fam_uuid_v4();
$pdo->beginTransaction();try{$pdo->prepare("INSERT INTO portal_conversations(public_id,attendee_id,source_type,subject,status,response_due_at) VALUES (?,?,'portal',?,'new',DATE_ADD(NOW(),INTERVAL 3 DAY))")->execute([$public,$id,$subject]);$cid=(int)$pdo->lastInsertId();$pdo->prepare("INSERT INTO portal_conversation_messages(public_id,conversation_id,author_type,author_attendee_id,body,delivery_status) VALUES (?,?,'attendee',?,?,'internal')")->execute([fam_uuid_v4(),$cid,$id,$body]);$a=fam_portal_account($pdo,$id);fam_queue_portal_email($pdo,'conversation-new:'.$public,$config['committee_email'],'New attendee message: '.$subject,fam_portal_email_shell('New attendee message','<p>'.htmlspecialchars(($a['first_name']??'').' '.($a['last_name']??''),ENT_QUOTES,'UTF-8').' sent a message in the portal.</p><p>'.nl2br(htmlspecialchars($body,ENT_QUOTES,'UTF-8')).'</p>'),'committee');$pdo->commit();fam_json_response(201,['ok'=>true,'public_id'=>$public,'status'=>'new']);}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
