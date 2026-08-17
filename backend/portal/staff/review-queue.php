<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/_bootstrap.php';
fam_portal_json_method(['GET']);
$staff=fam_require_portal_staff($pdo,'moderate_media');
$items=$pdo->query("SELECT m.public_id,m.media_type type,m.title,CONCAT(p.first_name,' ',p.last_name) submitter_name,m.created_at,m.status,m.consent_to_publish,NULL preview_url FROM attendee_media_submissions m JOIN attendee_profiles p ON p.attendee_id=m.attendee_id WHERE m.status='pending' ORDER BY m.created_at ASC LIMIT 100")->fetchAll();
fam_json_response(200,['staff'=>fam_portal_staff_client_access($staff),'items'=>$items]);
