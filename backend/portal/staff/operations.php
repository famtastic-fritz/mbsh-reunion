<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'manage_event_content');
$snapshot=fam_production_snapshot($config);
$summary=[
  'pending_orders'=>fam_snapshot_count($snapshot,'ticket_orders',"payment_status='pending'"),
  'active_tickets'=>(int)$pdo->query("SELECT COUNT(*) FROM ticket_wallet_items WHERE status='active'")->fetchColumn(),
  'checked_in'=>(int)$pdo->query("SELECT COUNT(*) FROM ticket_wallet_items WHERE status='checked_in'")->fetchColumn(),
  'exceptions'=>(int)$pdo->query("SELECT COUNT(*) FROM portal_email_jobs WHERE status='dead'")->fetchColumn()+fam_snapshot_count($snapshot,'time_capsules','send_error IS NOT NULL'),
];
$tickets=$pdo->query("SELECT t.public_id,t.ticket_type,t.holder_name,t.status,t.issued_at,t.checked_in_at,a.public_id attendee_public_id,a.email FROM ticket_wallet_items t JOIN attendee_accounts a ON a.id=t.attendee_id ORDER BY t.created_at DESC LIMIT 100")->fetchAll();
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'data_context'=>fam_snapshot_context($snapshot),'summary'=>$summary,'tickets'=>$tickets]);
