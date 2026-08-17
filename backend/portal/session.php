<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
fam_portal_json_method(['GET']);
$id=fam_current_attendee_id();
if (!$id) fam_json_response(200, ['authenticated'=>false]);
$account=fam_portal_account($pdo,$id);
if (!$account || $account['status']!=='active') { fam_attendee_logout(); fam_json_response(200,['authenticated'=>false]); }
fam_attendee_session_start();
$staff=fam_portal_staff_access($pdo,$id);
fam_json_response(200, ['authenticated'=>true,'csrf_token'=>$_SESSION['attendee_csrf'],'account'=>$account,'staff'=>fam_portal_staff_client_access($staff),'staff_access'=>$staff]);
