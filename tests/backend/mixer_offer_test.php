<?php
declare(strict_types=1);
function add_action(...$args){}
function add_filter(...$args){}
require_once __DIR__.'/../../backend/lib/event-offer.php';
require_once __DIR__.'/../../wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-mixer-offer.php';
require_once __DIR__.'/../../wordpress/wp-content/plugins/famtastic-reunion-platform/includes/class-commerce.php';
define('FAMTASTIC_REUNION_CHECKOUT_SECRET', str_repeat('t', 64));
$GLOBALS['offer_ready'] = true;
function get_woocommerce_currency(){return 'USD';}
function get_option($key,$default=''){return $GLOBALS['offer_ready'] ? Famtastic_Reunion_Mixer_Offer::ID : '';}
function wc_add_notice($message,$type){$GLOBALS['notices'][]=[$message,$type];}
function check(bool $value,string $message):void {if(!$value)throw new RuntimeException($message);}
final class MixerProduct {
    public float $price=30;
    public function get_id(){return 26;}
    public function get_meta($key){return $key==='_famtastic_ticket_event'?'mbsh-1996-30th':Famtastic_Reunion_Mixer_Offer::ID;}
    public function get_price(){return $this->price;}
    public function set_price($price){$this->price=$price;}
}
final class MixerCart {
    public function __construct(public array $lines, public float $total){}
    public function get_cart(){return $this->lines;}
    public function remove_cart_item($key){unset($this->lines[$key]);}
    public function get_total($context='view'){return $this->total;}
}
final class MixerItem {
    public function __construct(public int $id=26,public int $quantity=2){}
    public function get_product_id(){return $this->id;}
    public function get_quantity(){return $this->quantity;}
}
final class MixerOrder {
    public function __construct(public string $offer,public float $total=60,public array $items=[]) {$this->items=$items?:[new MixerItem()];}
    public function get_meta($key){return $this->offer;}
    public function get_currency(){return 'USD';}
    public function get_total(){return $this->total;}
    public function get_items(){return $this->items;}
}
$offer=fam_mixer_offer();check($offer['TICKET_PRICE']===30,'Server offer must be $30');
$validToken=new ReflectionMethod(Famtastic_Reunion_Commerce::class,'valid_checkout_token');
$sign=static function(string $payload):string{return rtrim(strtr(base64_encode($payload),'+/','-_'),'=').'.'.hash_hmac('sha256',$payload,FAMTASTIC_REUNION_CHECKOUT_SECRET);};
$payload=fam_mixer_checkout_payload('MBSH-ABC123','fixture@example.test',2,time()+60);
check($validToken->invoke(null,$sign($payload),'MBSH-ABC123','fixture@example.test',2),'Current offer token rejected');
check(!$validToken->invoke(null,$sign($payload),'MBSH-ABC123','fixture@example.test',3),'Quantity tamper accepted');
check(!$validToken->invoke(null,$sign($payload),'MBSH-ABC123','other@example.test',2),'Purchaser tamper accepted');
check(!$validToken->invoke(null,$sign('MBSH-ABC123|fixture@example.test|2|'.(time()+60)),'MBSH-ABC123','fixture@example.test',2),'Old offer token accepted');
check(!$validToken->invoke(null,$sign(fam_mixer_checkout_payload('MBSH-ABC123','fixture@example.test',2,time()-1)),'MBSH-ABC123','fixture@example.test',2),'Expired token accepted');
$line=['product_id'=>26,'quantity'=>2,'data'=>new MixerProduct(),'famtastic_offer_id'=>$offer['TICKET_OFFER_ID'],'famtastic_reservation_code'=>'MBSH-ABC123'];
$cart=new MixerCart(['current'=>$line],60);
check(Famtastic_Reunion_Mixer_Offer::cart_error($cart)==='','Current cart rejected');
check(Famtastic_Reunion_Mixer_Offer::total_matches($cart),'Two tickets should total $60');
$cart->total=65.67;check(!Famtastic_Reunion_Mixer_Offer::total_matches($cart),'Extra fee accepted');
$cart->total=30;check(!Famtastic_Reunion_Mixer_Offer::total_matches($cart),'Underpayment accepted');
$old=$line;unset($old['famtastic_offer_id']);$cart=new MixerCart(['old'=>$old,'current'=>$line],60);
Famtastic_Reunion_Mixer_Offer::normalize_cart($cart);check(!isset($cart->lines['old'])&&isset($cart->lines['current']),'Stale cart was not retired safely');
$cart->lines['current']['data']->price=200;check(Famtastic_Reunion_Mixer_Offer::cart_error($cart)!=='','Mispriced product accepted');
$cart->lines['current']['data']->price=30;$GLOBALS['offer_ready']=false;check(Famtastic_Reunion_Mixer_Offer::cart_error($cart)!=='','Unprepared catalog accepted');$GLOBALS['offer_ready']=true;
$current=new MixerOrder($offer['TICKET_OFFER_ID']);check(Famtastic_Reunion_Mixer_Offer::order_current($current),'Correct current order rejected');
check(!Famtastic_Reunion_Mixer_Offer::order_needs_payment(true,new MixerOrder('',381.34)),'Old unpaid order remains payable');
check(!Famtastic_Reunion_Mixer_Offer::order_current(new MixerOrder($offer['TICKET_OFFER_ID'],60.01)),'Incorrect current order remains payable');
Famtastic_Reunion_Mixer_Offer::guard_new_order($current);
try {Famtastic_Reunion_Mixer_Offer::guard_new_order(new MixerOrder(''));throw new RuntimeException('Old new order accepted');}catch(Exception $e){check(str_contains($e->getMessage(),'offer has changed'),'Wrong guard failure');}
echo "PASS mixer offer: signed versioned tokens, stale carts/orders, $30 totals, fail-closed catalog and fee mismatch\n";
