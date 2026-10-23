<?php
/** wp eval-file ... audit|apply. Private evidence only; never sends email or changes financial state. */
if(!defined('WP_CLI')||!WP_CLI)throw new RuntimeException('WP CLI required');
$mode=$args[0]??'audit';if(!in_array($mode,['audit','apply'],true))throw new RuntimeException('Invalid mode');
if($mode==='apply'&&getenv('MBSH_RECONCILE_PAID_WALLETS')!=='1')throw new RuntimeException('Explicit reconciliation environment required');
require_once dirname(ABSPATH).'/lib/config.php';require_once dirname(ABSPATH).'/lib/db.php';if(is_file(dirname(ABSPATH).'/lib/commerce-wallet.php'))require_once dirname(ABSPATH).'/lib/commerce-wallet.php';
$config=fam_load_config();$pdo=fam_db($config);$summary=['mode'=>$mode,'checked_at'=>gmdate('c'),'captured_cents'=>0,'refunded_cents'=>0,'net_collected_cents'=>0,'paid_orders'=>0,'purchasers'=>0,'expected_admissions'=>0,'existing_admissions'=>0,'created_admissions'=>0,'portal_available_admissions'=>0,'verified_purchasers'=>0,'unverified_purchasers'=>0,'unregistered_purchasers'=>0,'credentials_preserved'=>true,'emails_sent'=>0,'exceptions'=>[]];$people=[];$records=[];
for($page=1;;$page++){$batch=wc_get_orders(['type'=>'shop_order','status'=>['processing','completed'],'limit'=>100,'page'=>$page,'paginate'=>true,'orderby'=>'ID','order'=>'ASC']);foreach($batch->orders as $order){
 if(!$order instanceof WC_Order||!Famtastic_Reunion_Portal_Wallet::current_order($order)||!$order->is_paid())continue;
 $email=strtolower(trim($order->get_billing_email()));if(preg_match('/@(example\.(com|net|org|test)|[^@]*\.test)$/',$email))continue;
 $id=$order->get_id();$hash=hash('sha256',$email);$summary['paid_orders']++;
 $charge=null;$txn=$order->get_transaction_id();if(str_starts_with($txn,'ch_'))$charge=WC_Stripe_API::retrieve('charges/'.rawurlencode($txn));elseif($order->get_meta('_stripe_intent_id')){$intent=WC_Stripe_API::retrieve('payment_intents/'.rawurlencode($order->get_meta('_stripe_intent_id')).'?expand[]=latest_charge');$charge=$intent->latest_charge??null;}
 if(!is_object($charge)||isset($charge->error)||!($charge->livemode??false)||!($charge->paid??false)||!($charge->captured??false)||($charge->currency??'')!=='usd'||(int)($charge->amount_captured??0)!==(int)round(100*(float)$order->get_total())||(int)($charge->amount_refunded??-1)!==(int)round(100*(float)$order->get_total_refunded())||strtolower(trim($charge->billing_details->email??''))!==$email){$summary['exceptions'][]=['order_id'=>$id,'reason'=>'provider_reconciliation_mismatch'];continue;}
 $summary['captured_cents']+=(int)$charge->amount_captured;$summary['refunded_cents']+=(int)$charge->amount_refunded;$summary['net_collected_cents']+=(int)$charge->amount_captured-(int)$charge->amount_refunded;
 $expected=0;foreach($order->get_items() as $item_id=>$item)$expected+=max(0,$item->get_quantity()+$order->get_qty_refunded_for_item($item_id));$summary['expected_admissions']+=$expected;
 $before=Famtastic_Reunion_Portal_Wallet::ticket_ids($id);$fingerprints=[];foreach($before as $tid)$fingerprints[$tid]=hash('sha256',get_post_meta($tid,'_famtastic_ticket_public_id',true));$summary['existing_admissions']+=count($before);
 if($mode==='apply')Famtastic_Reunion_Tickets::issue_for_order($id);
 $after=Famtastic_Reunion_Portal_Wallet::ticket_ids($id);$summary['created_admissions']+=count($after)-count($before);foreach($fingerprints as $tid=>$fp)if(!hash_equals($fp,hash('sha256',get_post_meta($tid,'_famtastic_ticket_public_id',true))))$summary['credentials_preserved']=false;
 $rows=Famtastic_Reunion_Portal_Wallet::rows_for_order(wc_get_order($id));$usable=count(array_filter($rows,fn($r)=>in_array($r['status'],['active','checked_in'],true)));$summary['portal_available_admissions']+=$usable;
 if($usable!==$expected)$summary['exceptions'][]=['order_id'=>$id,'reason'=>'active_quantity_mismatch','expected'=>$expected,'available'=>$usable];
 if(count(array_unique(array_column($rows,'public_id')))!==count($rows))$summary['exceptions'][]=['order_id'=>$id,'reason'=>'duplicate_identity'];
 if(!isset($people[$hash])){$q=$pdo->prepare('SELECT id,status,email_verified_at FROM attendee_accounts WHERE LOWER(TRIM(email))=?');$q->execute([$email]);$account=$q->fetch();$state=!$account?'unregistered':($account['status']==='active'&&!empty($account['email_verified_at'])?'verified':'unverified');$people[$hash]=$state;$summary[$state.'_purchasers']++;
  if($state==='verified'&&function_exists('fam_commerce_wallet_for_attendee')){$actual=fam_commerce_wallet_for_attendee($pdo,$config,(int)$account['id']);$expectedRows=Famtastic_Reunion_Portal_Wallet::tickets_for_email($email);if(array_column($actual,'public_id')!==array_column($expectedRows,'public_id'))$summary['exceptions'][]=['order_id'=>$id,'reason'=>'verified_identity_projection_mismatch'];}
 }
 $records[]=['order_id'=>$id,'purchaser_sha256'=>$hash,'expected'=>$expected,'before'=>count($before),'after'=>count($after),'available'=>$usable,'account_state'=>$people[$hash],'credential_fingerprints'=>$fingerprints];
}if($page>=$batch->max_num_pages)break;}
$summary['purchasers']=count($people);
$evidence=getenv('MBSH_WALLET_EVIDENCE');if($evidence){if(!str_starts_with($evidence,'/home/nineoo/.releases/'))throw new RuntimeException('Private evidence path required');file_put_contents($evidence,json_encode(['summary'=>$summary,'orders'=>$records],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n",LOCK_EX);chmod($evidence,0600);}
echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n";
if($summary['exceptions']||!$summary['credentials_preserved'])throw new RuntimeException('Reconciliation exceptions require investigation');
