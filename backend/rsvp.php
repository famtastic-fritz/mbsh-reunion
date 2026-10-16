<?php
declare(strict_types=1);
// Mixer ticket purchase replaces the old public planning form. Historical rows remain intact.
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['error'=>'planning_form_retired','message'=>'No separate RSVP or planning survey is needed. Get your $30 mixer ticket on the ticket page.','url'=>'/tickets.html#ticket-order']);
