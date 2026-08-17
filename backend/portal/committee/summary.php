<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'view_inbox');

$counts=[];
foreach([
  'attendees_active'=>"SELECT COUNT(*) FROM attendee_accounts WHERE status='active'",
  'media_pending'=>"SELECT COUNT(*) FROM attendee_media_submissions WHERE status='pending'",
  'suggestions_new'=>"SELECT COUNT(*) FROM attendee_suggestions WHERE status='new'",
  'emails_pending'=>"SELECT COUNT(*) FROM portal_email_jobs WHERE status='pending'",
  'emails_dead'=>"SELECT COUNT(*) FROM portal_email_jobs WHERE status='dead'",
] as $key=>$sql) $counts[$key]=(int)$pdo->query($sql)->fetchColumn();

$media=$pdo->query("SELECT m.public_id,m.media_type,m.title,m.caption,m.event_year,m.consent_to_publish,m.status,m.created_at,a.email,p.first_name,p.last_name FROM attendee_media_submissions m JOIN attendee_accounts a ON a.id=m.attendee_id JOIN attendee_profiles p ON p.attendee_id=m.attendee_id WHERE m.status='pending' ORDER BY m.created_at ASC LIMIT 50")->fetchAll();
$suggestions=$pdo->query("SELECT s.public_id,s.category,s.subject,s.message,s.status,s.created_at,a.email,p.first_name,p.last_name FROM attendee_suggestions s JOIN attendee_accounts a ON a.id=s.attendee_id JOIN attendee_profiles p ON p.attendee_id=s.attendee_id WHERE s.status IN ('new','reviewing') ORDER BY s.created_at ASC LIMIT 50")->fetchAll();
$roster=[];
if(in_array('view_roster',$staff['capabilities'],true)) $roster=$pdo->query("SELECT a.public_id,a.email,a.status,a.email_verified_at,p.first_name,p.last_name,p.city_state,p.graduation_year,a.created_at FROM attendee_accounts a JOIN attendee_profiles p ON p.attendee_id=a.id ORDER BY p.last_name,p.first_name LIMIT 250")->fetchAll();

fam_json_response(200,['staff_access'=>['role'=>$staff['role'],'capabilities'=>$staff['capabilities']],'counts'=>$counts,'queues'=>['media'=>$media,'suggestions'=>$suggestions],'roster'=>$roster]);
