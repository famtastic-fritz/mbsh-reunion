<?php
declare(strict_types=1);
// Destructive fixture operations are confined to the disposable, provider-blocked proof DB.
if (getenv('WORDPRESS_DB_NAME') !== 'mixer_wp' || getenv('MBSH_CONFIG_PATH') !== '/proof/wallet-config.php') throw new RuntimeException('Isolated proof only');
require '/var/www/html/wp-load.php';
require_once '/portal-backend/lib/config.php';
require_once '/portal-backend/lib/db.php';
$pdo = fam_db(fam_load_config());
$mode = $argv[1] ?? '';
if ($mode === 'fixtures') {
    $order = wc_create_order();
    $order->add_product(wc_get_product(26), 1);
    $order->set_billing_email('experience-c@example.test');
    $order->set_billing_first_name('Casey'); $order->set_billing_last_name('Fixture');
    $order->update_meta_data('_famtastic_offer_id', Famtastic_Reunion_Mixer_Offer::ID);
    $order->update_meta_data('_famtastic_reservation_code', 'MBSH-EXPERIENCE-PROOF');
    $order->calculate_totals(); $order->save(); $order->payment_complete('isolated-no-provider');
    file_put_contents('/tmp/experience-order.txt', (string)$order->get_id());
    $pdo->exec('DELETE FROM rate_limits');
    $im = imagecreatetruecolor(80, 60); $color = imagecolorallocate($im, 185, 35, 50); imagefill($im, 0, 0, $color); imagepng($im, '/tmp/experience-photo.png'); imagedestroy($im);
    file_put_contents('/tmp/experience-disguised.jpg', '<?php echo "not an image";');
    echo "PASS isolated experience fixtures created; no provider called.\n";
} elseif ($mode === 'refund') {
    $order = wc_get_order((int)file_get_contents('/tmp/experience-order.txt'));
    $item = current($order->get_items());
    $refund = wc_create_refund(['order_id'=>$order->get_id(),'amount'=>30,'refund_payment'=>false,'line_items'=>[$item->get_id()=>['qty'=>1,'refund_total'=>30,'refund_tax'=>[]]]]);
    if (is_wp_error($refund)) throw new RuntimeException($refund->get_error_message());
    Famtastic_Reunion_Tickets::issue_for_order($order->get_id());
    echo "PASS local refund fixture applied without provider.\n";
} elseif ($mode === 'member') {
    $pdo->exec("INSERT INTO portal_staff_memberships(attendee_id,role,status) SELECT id,'committee_member','active' FROM attendee_accounts WHERE email='experience-n@example.test'");
} elseif ($mode === 'audit') {
    echo json_encode(['audit_rows'=>(int)$pdo->query("SELECT COUNT(*) FROM portal_staff_audit_log WHERE action LIKE 'guess_%' OR action LIKE 'trivia_%' OR action LIKE 'media_%' OR action LIKE 'suggestion_%'")->fetchColumn(), 'outbound_activity_jobs'=>(int)$pdo->query("SELECT COUNT(*) FROM portal_email_jobs WHERE idempotency_key LIKE 'suggestion:%' OR idempotency_key LIKE 'media:%'")->fetchColumn()]);
} else throw new RuntimeException('Unknown fixture action');
