<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/lib/config.php'; require_once dirname(__DIR__).'/lib/db.php'; require_once dirname(__DIR__).'/lib/admin-auth.php'; require_once dirname(__DIR__).'/lib/validate.php';
$config=fam_load_config(); $pdo=fam_db($config); fam_require_admin_auth();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET') fam_json_response(405,['error'=>'method_not_allowed']);
$counts=[];
foreach([
  'accounts_total'=>'SELECT COUNT(*) FROM attendee_accounts',
  'accounts_pending'=>'SELECT COUNT(*) FROM attendee_accounts WHERE status="pending_verification"',
  'accounts_active'=>'SELECT COUNT(*) FROM attendee_accounts WHERE status="active"',
  'tickets_active'=>'SELECT COUNT(*) FROM ticket_wallet_items WHERE status="active"',
  'tickets_checked_in'=>'SELECT COUNT(*) FROM ticket_wallet_items WHERE status="checked_in"',
  'media_pending'=>'SELECT COUNT(*) FROM attendee_media_submissions WHERE status="pending"',
  'suggestions_new'=>'SELECT COUNT(*) FROM attendee_suggestions WHERE status="new"',
  'unread_notifications'=>'SELECT COUNT(*) FROM attendee_notifications WHERE read_at IS NULL',
  'email_jobs_dead'=>'SELECT COUNT(*) FROM portal_email_jobs WHERE status="dead"',
  'email_jobs_pending'=>'SELECT COUNT(*) FROM portal_email_jobs WHERE status="pending"',
] as $key=>$sql) $counts[$key]=(int)$pdo->query($sql)->fetchColumn();
$recent=$pdo->query('SELECT a.public_id,a.email,a.status,a.email_verified_at,p.first_name,p.last_name,a.created_at FROM attendee_accounts a JOIN attendee_profiles p ON p.attendee_id=a.id ORDER BY a.created_at DESC LIMIT 50')->fetchAll();
fam_json_response(200,['counts'=>$counts,'recent_accounts'=>$recent]);
