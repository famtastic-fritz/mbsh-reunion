<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);$staff=fam_require_portal_staff($pdo,'view_audit');$snapshot=fam_production_snapshot($config);
$q=$pdo->query("SELECT l.action,l.target_type,l.target_public_id,l.details_json,l.ip_address,l.created_at,CONCAT(p.first_name,' ',p.last_name) actor FROM portal_staff_audit_log l JOIN attendee_profiles p ON p.attendee_id=l.actor_attendee_id ORDER BY l.created_at DESC LIMIT 100");
$tables=['rsvps','menu_selections','surveys','chatbot_questions','time_capsules','ticket_orders'];$counts=[];foreach($tables as $table)$counts[$table]=fam_snapshot_count($snapshot,$table);
$portal=['accounts'=>(int)$pdo->query('SELECT COUNT(*) FROM attendee_accounts')->fetchColumn(),'event_responses'=>(int)$pdo->query('SELECT COUNT(*) FROM portal_event_responses')->fetchColumn(),'conversations'=>(int)$pdo->query('SELECT COUNT(*) FROM portal_conversations')->fetchColumn(),'email_dead'=>(int)$pdo->query("SELECT COUNT(*) FROM portal_email_jobs WHERE status='dead'")->fetchColumn()];
fam_json_response(200,['data_context'=>fam_snapshot_context($snapshot),'snapshot_counts'=>$counts,'portal_counts'=>$portal,'audit'=>$q->fetchAll()]);
