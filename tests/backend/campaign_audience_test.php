<?php
declare(strict_types=1);
require __DIR__.'/../../backend/lib/campaign-audience.php';
function check_a(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}
$orders=[];$flushes=0;
function wp_cache_flush_runtime(){global $flushes;$flushes++;}
function wc_get_order_statuses(){return ['wc-pending'=>1,'wc-processing'=>1,'wc-completed'=>1,'wc-refunded'=>1];}
function wc_get_orders($q){global $orders;$ids=[];foreach($orders as $id=>$r)if(strtolower(trim($r['email']))===$q['billing_email'])$ids[]=$id;return (object)['orders'=>$ids,'max_num_pages'=>1];}
class WC_Order{public array $row;function __construct($id){global $orders;$this->row=$orders[$id];}function is_paid(){return $this->row['paid'];}function get_date_paid(){return $this->row['date_paid']??null;}}
class Famtastic_Reunion_Portal_Wallet{static function current_order($o){return $o->row['current'];}}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE attendee_accounts(id INTEGER,email TEXT,status TEXT,email_verified_at TEXT); CREATE TABLE attendee_record_links(attendee_id INTEGER,source_type TEXT,source_id TEXT)');
check_a(!campaign_buyer($db,'person@example.test'),'nonbuyer initially allowed');
$orders[1]=['email'=>' PERSON@EXAMPLE.TEST ','paid'=>false,'current'=>true];check_a(!campaign_buyer($db,'person@example.test'),'abandoned request is not a purchase');
$orders[1]['paid']=true;check_a(campaign_buyer($db,'person@example.test'),'new payment read immediately without frozen buyer list');
$orders[1]['current']=false;check_a(!campaign_buyer($db,'person@example.test'),'other product does not count as current mixer');
$orders[1]['current']=true;$orders[1]['paid']=false;$orders[1]['date_paid']='2026-10-22';check_a(campaign_buyer($db,'person@example.test'),'former paid buyer remains suppressed after refund');
$db->exec("INSERT INTO attendee_accounts VALUES(1,'alias@example.test','active','2026-10-22'); INSERT INTO attendee_record_links VALUES(1,'woocommerce_order','1')");
check_a(campaign_buyer($db,'alias@example.test'),'verified linked purchase identity excluded');
$db->exec('UPDATE attendee_accounts SET email_verified_at=NULL');check_a(!campaign_buyer($db,'alias@example.test'),'unverified alias does not borrow purchase identity');
check_a(!campaign_buyer($db,'p.erson@example.test'),'no guessed Gmail-style identity merges');
check_a($flushes>=8,'purchase authority refreshed for every check');
