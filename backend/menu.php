<?php
declare(strict_types=1);
// Retired mixer meal form: no configuration, database, or mail side effects.
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['error'=>'meal_selection_closed','message'=>'Food is on your own. No meal selection is needed for the reunion mixer.','information_url'=>'/index.html#venue']);
