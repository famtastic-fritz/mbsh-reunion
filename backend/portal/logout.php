<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
fam_portal_json_method(['POST']);
fam_require_attendee(); fam_require_attendee_csrf(); fam_attendee_logout();
fam_json_response(200, ['ok'=>true]);
