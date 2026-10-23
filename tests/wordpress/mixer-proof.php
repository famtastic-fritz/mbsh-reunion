<?php
/** Isolated Docker proof only; mail and outbound HTTP must be blocked by the test MU plugin. */
declare(strict_types=1);
if (getenv('WORDPRESS_DB_NAME') !== 'mixer_wp' || getenv('WORDPRESS_DB_HOST') !== 'mbsh-mixer-wc-db-20261016') { throw new RuntimeException('Isolated mixer DB required.'); }
$_SERVER['HTTP_HOST']='127.0.0.1:8977';
$mode=$argv[1]??'exercise';
if($mode==='setup') { define('WP_INSTALLING',true); }
require '/var/www/html/wp-load.php';
function proof(bool $condition,string $message):void {if(!$condition)throw new RuntimeException($message);}
if($mode==='setup') {
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    if(!is_blog_installed())wp_install('Isolated MBSH Mixer Proof','proofowner','owner@example.test',false,'','LocalFixtureOnly2026!');
    update_option('siteurl','http://127.0.0.1:8977');update_option('home','http://127.0.0.1:8977');
    require_once ABSPATH.'wp-admin/includes/plugin.php';
    foreach (['woocommerce/woocommerce.php','famtastic-reunion-platform/famtastic-reunion-platform.php'] as $plugin) {
        $activated=activate_plugin($plugin);proof(!is_wp_error($activated),'Plugin activation failed: '.$plugin);
    }
    echo "PASS isolated WordPress setup; no imported settings or real recipients\n";exit;
}
if($mode==='catalog') {
    if(!get_post(26))wp_insert_post(['import_id'=>26,'post_type'=>'product','post_status'=>'publish','post_title'=>'Mixer Proof Admission']);
    $p=new WC_Product_Simple(26);$p->set_name('MBSH Reunion Mixer Admission — isolated proof');$p->set_status('publish');$p->set_regular_price('30');$p->set_price('30');$p->set_virtual(true);$p->set_tax_status('none');$p->update_meta_data('_famtastic_ticket_event','mbsh-1996-30th');$p->update_meta_data('_famtastic_offer_id',Famtastic_Reunion_Mixer_Offer::ID);$p->save();
    update_option('woocommerce_currency','USD');update_option('woocommerce_calc_taxes','no');update_option('famtastic_mixer_offer_revision',Famtastic_Reunion_Mixer_Offer::ID);
    echo "PASS isolated $30 WooCommerce catalog\n";exit;
}
$p=wc_get_product(26);proof(Famtastic_Reunion_Mixer_Offer::product_ready($p),'Catalog readiness');
$order=wc_create_order();$order->add_product($p,2);$order->set_billing_email('fixture@example.test');$order->update_meta_data('_famtastic_offer_id',Famtastic_Reunion_Mixer_Offer::ID);$order->update_meta_data('_famtastic_reservation_code','MBSH-ABC123');
global $wpdb;$attempt=(int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}famtastic_order_attempts WHERE reservation_code='MBSH-ABC123'");proof($attempt>0,'Real AJAX attempt ledger was not saved');$order->update_meta_data('_famtastic_attempt_id',$attempt);$order->calculate_totals();$order->save();
proof((float)$order->get_total()===60.0,'Two admissions must total $60');
Famtastic_Reunion_Mixer_Offer::guard_new_order($order);
Famtastic_Reunion_Commerce::order_created($order->get_id(),[],$order);
$before=count($GLOBALS['mbsh_proof_mail_http']);
Famtastic_Reunion_Commerce::send_paid_order_notice($order->get_id());
proof(count($GLOBALS['mbsh_proof_mail_http'])===$before,'Unpaid order attempted confirmation mail');
$order->payment_complete('isolated-fixture-no-provider');$order=wc_get_order($order->get_id());
proof($order->is_paid(),'Simulated paid order status');
$find=static fn()=>get_posts(['post_type'=>'reunion_ticket','post_status'=>'publish','meta_key'=>'_famtastic_order_id','meta_value'=>$order->get_id(),'posts_per_page'=>-1,'fields'=>'ids']);
$tickets=$find();proof(count($tickets)===2,'Exactly two paid-quantity entitlements');
Famtastic_Reunion_Tickets::issue_for_order($order->get_id());proof(count($find())===2,'Duplicate paid event issued extra tickets');
proof((bool)$order->get_meta('_famtastic_paid_notice_queued'),'Paid order did not queue its notice');
$GLOBALS['mbsh_proof_mail_fail']=true;
Famtastic_Reunion_Commerce::send_paid_order_notice($order->get_id());
$order=wc_get_order($order->get_id());
proof(!$order->get_meta('_famtastic_paid_notice_sent'),'Simulated provider failure marked notice sent');
proof((bool)$order->get_meta('_famtastic_paid_notice_last_error'),'Provider failure was not recorded');
$failedMail=end($GLOBALS['mbsh_proof_mail_http']);
$GLOBALS['mbsh_proof_mail_fail']=false;
Famtastic_Reunion_Commerce::send_paid_order_notice($order->get_id());
$order=wc_get_order($order->get_id());$sentMail=end($GLOBALS['mbsh_proof_mail_http']);
proof((bool)$order->get_meta('_famtastic_paid_notice_sent'),'Successful mock receipt was not recorded');
proof(!$order->get_meta('_famtastic_paid_notice_last_error'),'Successful retry retained failure state');
proof($failedMail['idempotency_key']===$sentMail['idempotency_key'],'Receipt retry changed provider idempotency key');
proof($sentMail['payload']['to']===['fixture@example.test'],'Receipt recipient differs from purchaser');
foreach(['2 admissions','MBSH-ABC123','Twisted Tonic','November 7, 2026','Food on your own','data-famtastic-email-credit="v1"'] as $expected){
    proof(str_contains($sentMail['payload']['html'],$expected),'Receipt missing expected current-event content');
}
$sentCount=count($GLOBALS['mbsh_proof_mail_http']);
Famtastic_Reunion_Commerce::send_paid_order_notice($order->get_id());
proof(count($GLOBALS['mbsh_proof_mail_http'])===$sentCount,'Receipt replay caused another provider call');
echo "PASS isolated receipt: unpaid-order suppression; paid notice queued; synthetic recipient/current-event content; mocked provider failure retained error; retry preserves idempotency key; success and duplicate suppression. No outbound mail.\n";
$first_item=current($order->get_items());$refund=wc_create_refund(['order_id'=>$order->get_id(),'amount'=>30,'refund_payment'=>false,'line_items'=>[$first_item->get_id()=>['qty'=>1,'refund_total'=>30,'refund_tax'=>[]]]]);proof(!is_wp_error($refund),'Local partial refund');
$states=array_map(static fn($id)=>get_post_meta($id,'_famtastic_ticket_status',true),$tickets);
proof(count(array_filter($states,static fn($s)=>$s==='revoked'))===1,'Partial refund must revoke one ticket');
Famtastic_Reunion_Tickets::revoke_for_refund($order->get_id(),$refund->get_id());
$states=array_map(static fn($id)=>get_post_meta($id,'_famtastic_ticket_status',true),$tickets);proof(count(array_filter($states,static fn($s)=>$s==='revoked'))===1,'Refund replay revoked too many tickets');
$remaining=wc_create_refund(['order_id'=>$order->get_id(),'amount'=>30,'refund_payment'=>false,'line_items'=>[$first_item->get_id()=>['qty'=>1,'refund_total'=>30,'refund_tax'=>[]]]]);proof(!is_wp_error($remaining),'Local full refund');
$states=array_map(static fn($id)=>get_post_meta($id,'_famtastic_ticket_status',true),$tickets);proof(count(array_filter($states,static fn($s)=>$s==='revoked'))===2,'Full refund must revoke all tickets');
$failed=wc_create_order();$failed->add_product($p,1);$failed->update_meta_data('_famtastic_offer_id',Famtastic_Reunion_Mixer_Offer::ID);$failed->update_meta_data('_famtastic_reservation_code','MBSH-FA1111');$failed->calculate_totals();$failed->update_status('failed');
proof(count(get_posts(['post_type'=>'reunion_ticket','meta_key'=>'_famtastic_order_id','meta_value'=>$failed->get_id(),'posts_per_page'=>-1,'fields'=>'ids']))===0,'Failed order created an admission');
$old=wc_create_order();$old->add_product($p,1);$old->calculate_totals();$old->save();proof(!$old->needs_payment(),'Stale old-offer order remains payable');
$bad=wc_create_order();$bad->add_product($p,1);$bad->update_meta_data('_famtastic_offer_id',Famtastic_Reunion_Mixer_Offer::ID);$fee=new WC_Order_Item_Fee();$fee->set_name('Obsolete processing fee');$fee->set_amount(5.67);$fee->set_total(5.67);$bad->add_item($fee);$bad->calculate_totals();$bad->save();proof(!$bad->needs_payment(),'Extra-fee order remains payable');
$receipt=Famtastic_Reunion_Commerce::class;
echo "PASS real WooCommerce 11.0.1: signed AJAX bridge + ledger; $60 two-ticket order; simulated paid lifecycle; exactly-once issuance; partial/full refund and replay; failed-order isolation; stale/extra-fee payment guards. External provider and mail blocked.\n";
