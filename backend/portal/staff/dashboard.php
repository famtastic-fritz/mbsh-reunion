<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'view_inbox');
$snapshot=fam_production_snapshot($config);
$summary=[
  'pending_reviews'=>(int)$pdo->query("SELECT COUNT(*) FROM attendee_media_submissions WHERE status='pending'")->fetchColumn(),
  'unanswered_messages'=>(int)$pdo->query("SELECT COUNT(*) FROM attendee_suggestions WHERE status='new'")->fetchColumn()+fam_snapshot_count($snapshot,'chatbot_questions','responded=0'),
  'active_attendees'=>(int)$pdo->query("SELECT COUNT(*) FROM attendee_accounts WHERE status='active'")->fetchColumn(),
  'production_rsvps'=>fam_snapshot_count($snapshot,'rsvps'),
  'production_menu_selections'=>fam_snapshot_count($snapshot,'menu_selections'),
  'historical_surveys'=>fam_snapshot_count($snapshot,'surveys'),
  'operations_attention'=>(int)$pdo->query("SELECT COUNT(*) FROM portal_email_jobs WHERE status='dead'")->fetchColumn(),
];
$recent=$pdo->query("(SELECT public_id,'media' activity_type,title,created_at FROM attendee_media_submissions) UNION ALL (SELECT public_id,'message' activity_type,subject title,created_at FROM attendee_suggestions) ORDER BY created_at DESC LIMIT 12")->fetchAll();
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'data_context'=>fam_snapshot_context($snapshot),'summary'=>$summary,'recent_activity'=>$recent]);
