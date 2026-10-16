<?php
/** Run with wp eval-file: inspect (default) or apply. No payment, refund, or email. */
if (!defined('WP_CLI') || !WP_CLI) { throw new RuntimeException('WP-CLI only.'); }
if (!class_exists('Famtastic_Reunion_Mixer_Offer')) { WP_CLI::error('Deploy the reviewed mixer plugin before preparing its catalog.'); }
$mode=$args[0]??'inspect';
if (!in_array($mode,['inspect','apply'],true)) { WP_CLI::error('Use inspect or apply.'); }
$product=wc_get_product(26);
if (!$product || $product->get_meta('_famtastic_ticket_event')!=='mbsh-1996-30th') { WP_CLI::error('Product 26 is not the reunion admission product.'); }
$active_paid=0;
foreach(wc_get_orders(['type'=>'shop_order','status'=>['processing','completed','on-hold'],'limit'=>-1]) as $order){
    if (!$order instanceof WC_Order) {continue;}
    foreach($order->get_items() as $item){
        if ((int)$item->get_product_id()===26 && (float)$order->get_total()>(float)$order->get_total_refunded()) {$active_paid++;break;}
    }
}
$report=['mode'=>$mode,'offer_id'=>Famtastic_Reunion_Mixer_Offer::ID,'active_paid_admission_orders'=>$active_paid,'current_product_price'=>$product->get_price(),'target_unit_amount'=>30,'currency'=>get_woocommerce_currency(),'tax_status'=>$product->get_tax_status(),'taxes_enabled'=>get_option('woocommerce_calc_taxes'),'ready'=>Famtastic_Reunion_Mixer_Offer::ready()];
if($mode==='apply'){
    $retired=get_option('famtastic_mixer_legacy_link_retired',[]);
    if(($retired['link_id']??'')!=='plink_1U9F0VD5PsE39cMxhrgkUtXV'){WP_CLI::error('Retire the superseded Stripe link before opening the mixer catalog. Nothing changed.');}

    if ($active_paid>0) {WP_CLI::error('Outstanding paid admissions need individual owner review. Nothing changed.');}
    if(get_woocommerce_currency()!=='USD'||get_option('woocommerce_calc_taxes')==='yes'||$product->get_tax_status()!=='none') {WP_CLI::error('Tax/currency configuration differs from the reconciled baseline. Nothing changed.');}
    if (Famtastic_Reunion_Mixer_Offer::product_ready($product)) {
        $report['already_applied']=true;WP_CLI::line(wp_json_encode($report,JSON_PRETTY_PRINT));return;
    }
    // The additive legacy schema change must precede the new reservation writes.
    $webroot=dirname(rtrim(ABSPATH,'/'));
    require_once $webroot.'/lib/config.php';require_once $webroot.'/lib/db.php';
    $pdo=fam_db(fam_load_config());
    $column=$pdo->query("SHOW COLUMNS FROM ticket_orders LIKE 'price_tier'")->fetch();
    if(!$column){WP_CLI::error('Legacy reservation schema is missing. Nothing changed.');}
    $pdo->exec("ALTER TABLE ticket_orders MODIFY COLUMN price_tier ENUM('early_bird','regular','mixer') NOT NULL");
    update_option('famtastic_mixer_catalog_before',['name'=>$product->get_name(),'price'=>$product->get_price(),'regular_price'=>$product->get_regular_price(),'sale_price'=>$product->get_sale_price(),'offer_id'=>$product->get_meta('_famtastic_offer_id'),'recorded_at'=>gmdate('c')],false);
    $product->set_name('MBSH Class of 1996 — Reunion Mixer Admission');
    $product->set_description('Twisted Tonic · Saturday, November 7, 2026 · 7–10 PM Eastern. $30 admission per person. Food on your own. 723 Lincoln Ln N, Miami Beach, FL 33139.');
    $product->set_short_description('Reunion Mixer · $30 per person · Food on your own');
    $product->set_date_on_sale_from(null);$product->set_date_on_sale_to(null);$product->set_sale_price('');
    $product->set_regular_price('30.00');$product->set_price('30.00');
    $product->update_meta_data('_famtastic_offer_id',Famtastic_Reunion_Mixer_Offer::ID);$product->save();
    wc_delete_product_transients(26);
    update_option('famtastic_mixer_offer_revision',Famtastic_Reunion_Mixer_Offer::ID,false);
    $report['ready']=Famtastic_Reunion_Mixer_Offer::ready();$report['current_product_price']=$product->get_price();
}
WP_CLI::line(wp_json_encode($report,JSON_PRETTY_PRINT));
