<?php
declare(strict_types=1);

/** The authenticated endpoint passes its own attendee ID after CSRF validation. */
function fam_save_attendee_preferences(PDO $pdo,int $id,bool $event,bool $memory,bool $promo,bool $sms):void {
    // An explicit Save with unchanged false defaults is still a recorded preference choice.
    // Scheduled campaigns can distinguish that later choice from imported defaults.
    $pdo->prepare('UPDATE attendee_preferences SET event_updates=?,memory_updates=?,promotional_email=?,sms_notifications=?,updated_at=CURRENT_TIMESTAMP WHERE attendee_id=?')
        ->execute([$event?1:0,$memory?1:0,$promo?1:0,$sms?1:0,$id]);
}
