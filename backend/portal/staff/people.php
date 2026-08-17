<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'view_roster');
$term=trim((string)($_GET['q']??''));
if(strlen($term)>100) fam_json_response(400,['error'=>'invalid_search']);
$sql="SELECT a.public_id,CONCAT(p.first_name,' ',p.last_name) name,a.email,NULL rsvp_status,COALESCE(MAX(t.status),'none') ticket_status,m.role access_role FROM attendee_accounts a JOIN attendee_profiles p ON p.attendee_id=a.id LEFT JOIN ticket_wallet_items t ON t.attendee_id=a.id LEFT JOIN portal_staff_memberships m ON m.attendee_id=a.id AND m.status='active'";
$params=[];
if($term!==''){ $sql.=" WHERE a.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?"; $like='%'.$term.'%'; $params=[$like,$like,$like]; }
$sql.=" GROUP BY a.id,a.public_id,p.first_name,p.last_name,a.email,m.role ORDER BY p.last_name,p.first_name LIMIT 500";
$q=$pdo->prepare($sql); $q->execute($params);
$people=$q->fetchAll();
$snapshot=fam_production_snapshot($config);
if($snapshot){
  $legacySql="SELECT CONCAT('legacy-rsvp-',id) public_id,TRIM(CONCAT(first_name,' ',last_name)) name,email,attending rsvp_status,'none' ticket_status,NULL access_role,'production_rsvp' source FROM rsvps";
  $legacyParams=[];
  if($term!==''){ $legacySql.=" WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR maiden_name LIKE ?"; $like='%'.$term.'%'; $legacyParams=[$like,$like,$like,$like]; }
  $legacySql.=" ORDER BY last_name,first_name LIMIT 500";
  $legacyQ=$snapshot->prepare($legacySql);$legacyQ->execute($legacyParams);
  $seen=[];foreach($people as &$person){$person['source']='portal';$seen[strtolower(trim((string)$person['email']))]=true;}unset($person);
  foreach($legacyQ->fetchAll() as $person){$key=strtolower(trim((string)$person['email']));if(!isset($seen[$key]))$people[]=$person;}
}
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'data_context'=>fam_snapshot_context($snapshot),'people'=>$people]);
