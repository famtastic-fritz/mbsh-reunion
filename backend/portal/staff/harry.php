<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
$method=fam_portal_json_method(['GET','POST']);
$staff=fam_require_portal_staff($pdo,'respond_harry');
$snapshot=fam_production_snapshot($config);
if($method==='GET'){
  $questions=$snapshot?$snapshot->query("SELECT CONCAT('legacy-harry-',id) public_id,question,email,matched_faq,was_fallback,responded,response_notes,created_at,responded_at FROM chatbot_questions ORDER BY responded ASC,created_at DESC LIMIT 200")->fetchAll():[];
  fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'data_context'=>fam_snapshot_context($snapshot),'questions'=>$questions]);
}
fam_require_attendee_csrf();$data=fam_read_json_body();$public=fam_required($data,'question_id',100);$response=fam_required($data,'response',5000);
if(!preg_match('/^legacy-harry-(\d+)$/',$public,$m))fam_json_response(400,['error'=>'invalid_record']);
$pdo->beginTransaction();
try{
  $source=(string)$m[1];$existing=$pdo->prepare("SELECT id,public_id FROM portal_conversations WHERE source_type='harry' AND source_id=? FOR UPDATE");$existing->execute([$source]);$conversation=$existing->fetch();
  if(!$conversation){$conversationPublic=fam_uuid_v4();$pdo->prepare("INSERT INTO portal_conversations(public_id,source_type,source_id,subject,status,assigned_to,response_due_at) VALUES (?,'harry',?,?,'waiting_attendee',?,DATE_ADD(NOW(),INTERVAL 3 DAY))")->execute([$conversationPublic,$source,'Hi-Tide Harry question',(int)$staff['attendee_id']]);$conversation=['id'=>(int)$pdo->lastInsertId(),'public_id'=>$conversationPublic];}
  $pdo->prepare("INSERT INTO portal_conversation_messages(public_id,conversation_id,author_type,author_attendee_id,body,delivery_status) VALUES (?,?,'committee',?,?,'internal')")->execute([fam_uuid_v4(),(int)$conversation['id'],(int)$staff['attendee_id'],$response]);
  $pdo->prepare("UPDATE portal_conversations SET status='waiting_attendee',assigned_to=? WHERE id=?")->execute([(int)$staff['attendee_id'],(int)$conversation['id']]);
  fam_portal_staff_audit($pdo,$staff,'harry_response_drafted','chatbot_question',$public,['conversation_public_id'=>$conversation['public_id']]);$pdo->commit();
  fam_json_response(200,['ok'=>true,'conversation_public_id'=>$conversation['public_id'],'delivery'=>'internal_draft']);
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
