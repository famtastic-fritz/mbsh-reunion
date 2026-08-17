<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'view_roster');
$snapshot=fam_production_snapshot($config);
if(!$snapshot) fam_json_response(503,['error'=>'snapshot_unavailable']);
$public=trim((string)($_GET['id']??''));
if(preg_match('/^legacy-rsvp-(\d+)$/',$public,$m)){
  $q=$snapshot->prepare('SELECT id,first_name,last_name,maiden_name,email,phone,city_state,attending,guest_count,guest_names,dietary,help_planning,message,display_publicly,created_at,updated_at FROM rsvps WHERE id=?');
  $q->execute([(int)$m[1]]);$rsvp=$q->fetch();if(!$rsvp)fam_json_response(404,['error'=>'not_found']);
}elseif(preg_match('/^[a-f0-9-]{36}$/i',$public)){
  $q=$pdo->prepare("SELECT NULL id,p.first_name,p.last_name,p.maiden_name,a.email,p.phone,p.city_state,COALESCE(er.attendance,'unknown') attending,COALESCE(er.guest_count,0) guest_count,er.guest_names,er.dietary_accessibility dietary,0 help_planning,NULL message,p.display_in_directory,a.created_at,a.updated_at FROM attendee_accounts a JOIN attendee_profiles p ON p.attendee_id=a.id LEFT JOIN portal_event_responses er ON er.attendee_id=a.id WHERE a.public_id=?");$q->execute([$public]);$rsvp=$q->fetch();if(!$rsvp)fam_json_response(404,['error'=>'not_found']);
  $legacy=$snapshot->prepare('SELECT id FROM rsvps WHERE LOWER(TRIM(email))=LOWER(TRIM(?)) ORDER BY updated_at DESC,id DESC LIMIT 1');$legacy->execute([$rsvp['email']]);$legacyId=$legacy->fetchColumn();if($legacyId)$rsvp['id']=(int)$legacyId;
}else fam_json_response(400,['error'=>'invalid_record']);
$menu=$snapshot->prepare('SELECT id,name,email,selections_json,dietary,submitter_email_status,committee_email_status,notification_email_status,created_at FROM menu_selections WHERE LOWER(TRIM(email))=LOWER(TRIM(?)) ORDER BY created_at DESC');
$menu->execute([$rsvp['email']]);
$overrides=$pdo->prepare("SELECT field_name,value_json,note,created_at FROM portal_legacy_record_overrides WHERE source_type='rsvp' AND source_id=? ORDER BY created_at DESC");
$overrides->execute([(string)($rsvp['id']??'')]);
$portal=$pdo->prepare('SELECT er.attendance,er.guest_count,er.guest_names,er.phone,er.meal_choice,er.dietary_accessibility,er.status,er.migration_sync_status,er.updated_at,a.public_id attendee_public_id,a.status account_status FROM portal_event_responses er JOIN attendee_accounts a ON a.id=er.attendee_id WHERE er.legacy_rsvp_id=? OR LOWER(TRIM(a.email))=LOWER(TRIM(?)) ORDER BY er.updated_at DESC LIMIT 1');
$portal->execute([(int)($rsvp['id']??0),$rsvp['email']]);
fam_portal_staff_audit($pdo,$staff,'legacy_rsvp_viewed','rsvp',$public,[]);
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'data_context'=>fam_snapshot_context($snapshot),'record'=>['public_id'=>$public,'rsvp'=>$rsvp,'portal_response'=>$portal->fetch()?:null,'menu_selections'=>$menu->fetchAll(),'local_overrides'=>$overrides->fetchAll()]]);
