<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'view_inbox');
$threads=$pdo->query("SELECT c.public_id,c.subject,COALESCE(CONCAT(p.first_name,' ',p.last_name),'Unlinked visitor') participant_name,c.updated_at,c.status,IF(c.status='new',1,0) unread_count,c.source_type source FROM portal_conversations c LEFT JOIN attendee_profiles p ON p.attendee_id=c.attendee_id ORDER BY c.updated_at DESC LIMIT 100")->fetchAll();
$suggestions=$pdo->query("SELECT s.public_id,s.subject,CONCAT(p.first_name,' ',p.last_name) participant_name,s.updated_at,s.status,IF(s.status='new',1,0) unread_count,'suggestion' source,s.category,s.message body,s.admin_note FROM attendee_suggestions s JOIN attendee_profiles p ON p.attendee_id=s.attendee_id ORDER BY s.updated_at DESC LIMIT 100")->fetchAll();
$threads=array_merge($threads,$suggestions);
$snapshot=fam_production_snapshot($config);
if($snapshot){
  $legacy=$snapshot->query("SELECT CONCAT('legacy-harry-',id) public_id,LEFT(question,120) subject,COALESCE(NULLIF(email,''),'Anonymous visitor') participant_name,created_at updated_at,IF(responded=1,'closed','new') status,IF(responded=1,0,1) unread_count,'harry' source FROM chatbot_questions ORDER BY created_at DESC LIMIT 100")->fetchAll();
  $threads=array_merge($threads,$legacy);
  usort($threads,fn($a,$b)=>strcmp((string)$b['updated_at'],(string)$a['updated_at']));
}
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'data_context'=>fam_snapshot_context($snapshot),'threads'=>array_slice($threads,0,100)]);
