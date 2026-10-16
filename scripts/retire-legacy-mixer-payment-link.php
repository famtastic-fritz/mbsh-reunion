<?php
/** WP-CLI only. Inspect by default; apply only during the reviewed release. */
if (!defined('WP_CLI') || !WP_CLI) {throw new RuntimeException('WP-CLI only.');}
$mode=$args[0]??'inspect';
if(!in_array($mode,['inspect','apply'],true)) {WP_CLI::error('Use inspect or apply.');}
$id='plink_1U9F0VD5PsE39cMxhrgkUtXV';
$expected='https://buy.stripe.com/3cIfZg8vZd5haIgb1c5Vu04';
$link=WC_Stripe_API::retrieve('payment_links/'.$id);
if(isset($link->error)||($link->url??'')!==$expected){WP_CLI::error('Legacy payment link identity could not be verified. Nothing changed.');}
$before=(bool)$link->active;
if($mode==='apply'&&$before){
    $link=WC_Stripe_API::request(['active'=>'false'],'payment_links/'.$id,'POST');
    if(isset($link->error)||!isset($link->active)||$link->active!==false){WP_CLI::error('Stripe did not confirm link deactivation. Keep mixer sales closed.');}
}
if($mode==='apply' && $link->active===false){
    update_option('famtastic_mixer_legacy_link_retired',['link_id'=>$id,'at'=>gmdate('c'),'previously_active'=>$before],false);
}
WP_CLI::line(wp_json_encode(['mode'=>$mode,'legacy_link_id'=>$id,'active'=>(bool)$link->active,'changed'=>$before&&$mode==='apply'],JSON_PRETTY_PRINT));
