<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
$method=fam_portal_json_method(['GET','POST']);
$staff=fam_require_portal_staff($pdo,'manage_committee');
if($method==='GET'){
  $rows=$pdo->query("SELECT a.public_id,a.email,CONCAT(p.first_name,' ',p.last_name) name,m.role,m.status,m.granted_at,m.updated_at FROM attendee_accounts a JOIN attendee_profiles p ON p.attendee_id=a.id LEFT JOIN portal_staff_memberships m ON m.attendee_id=a.id ORDER BY p.last_name,p.first_name")->fetchAll();
  fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'memberships'=>$rows]);
}
fam_require_attendee_csrf();$data=fam_read_json_body();$public=fam_required($data,'attendee_id',36);$role=fam_enum($data,'role',['attendee','committee_member','committee_lead','site_owner'],true);
$q=$pdo->prepare('SELECT id FROM attendee_accounts WHERE public_id=?');$q->execute([$public]);$target=(int)$q->fetchColumn();if(!$target)fam_json_response(404,['error'=>'not_found']);
if($target===(int)$staff['attendee_id']&&$role!=='site_owner')fam_json_response(409,['error'=>'self_lockout_prevented','message'=>'A Site Owner cannot remove or reduce their own access. Ask another Site Owner to make this change.']);
if($role==='attendee'){$pdo->prepare("UPDATE portal_staff_memberships SET status='revoked',revoked_at=NOW() WHERE attendee_id=?")->execute([$target]);}
else{$pdo->prepare("INSERT INTO portal_staff_memberships(attendee_id,role,status,granted_by) VALUES (?,?,'active',?) ON DUPLICATE KEY UPDATE role=VALUES(role),status='active',revoked_at=NULL,granted_by=VALUES(granted_by)")->execute([$target,$role,'portal-owner:'.$staff['attendee_id']]);}
fam_portal_staff_audit($pdo,$staff,'membership_changed','attendee',$public,['role'=>$role]);fam_json_response(200,['ok'=>true]);
