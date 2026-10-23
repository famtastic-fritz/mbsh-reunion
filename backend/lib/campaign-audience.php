<?php
declare(strict_types=1);

function campaign_buyer(PDO $pdo,string $email):bool {
 // Fresh database objects on every submission/retry; include paid history after refunds conservatively.
 if(function_exists('wp_cache_flush_runtime'))wp_cache_flush_runtime();
 $ids=[];for($page=1;;$page++){$batch=wc_get_orders(['type'=>'shop_order','billing_email'=>$email,'status'=>array_keys(wc_get_order_statuses()),'limit'=>100,'page'=>$page,'paginate'=>true,'return'=>'ids']);foreach($batch->orders as $id)$ids[(int)$id]=true;if($page>=$batch->max_num_pages)break;}
 $q=$pdo->prepare("SELECT l.source_id FROM attendee_record_links l JOIN attendee_accounts a ON a.id=l.attendee_id WHERE LOWER(TRIM(a.email))=? AND a.status='active' AND a.email_verified_at IS NOT NULL AND l.source_type='woocommerce_order'");$q->execute([$email]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id)if(ctype_digit((string)$id))$ids[(int)$id]=true;
 foreach(array_keys($ids) as $id){$order=new WC_Order($id);if(Famtastic_Reunion_Portal_Wallet::current_order($order)&&($order->is_paid()||$order->get_date_paid()))return true;}
 return false;
}
