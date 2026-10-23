<?php
declare(strict_types=1);

/** Read-through portal view. WooCommerce remains the only paid entitlement authority. */
final class Famtastic_Reunion_Portal_Wallet
{
    public static function current_order(WC_Abstract_Order $order): bool
    {
        if (!$order instanceof WC_Order) return false;
        if ($order->get_meta('_famtastic_offer_id') !== Famtastic_Reunion_Mixer_Offer::ID
            || $order->get_currency() !== 'USD' || !$order->get_meta('_famtastic_reservation_code')) return false;
        $quantity = 0;
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() !== 26 || (float)$item->get_total() !== 30.0 * $item->get_quantity()) return false;
            $quantity += $item->get_quantity();
        }
        return $quantity > 0 && (float)$order->get_total() === 30.0 * $quantity;
    }

    public static function ticket_ids(int $order_id, ?int $item_id = null): array
    {
        $query = [['key'=>'_famtastic_order_id','value'=>$order_id]];
        if ($item_id !== null) $query[] = ['key'=>'_famtastic_order_item_id','value'=>$item_id];
        return get_posts(['post_type'=>'reunion_ticket','post_status'=>'any','meta_query'=>$query,
            'posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC','fields'=>'ids']);
    }

    /** Trusted caller supplies the authenticated, verified portal account email, never request input. */
    public static function tickets_for_email(string $email): array
    {
        $email = strtolower(trim($email));
        if (!is_email($email)) return [];
        $rows = [];
        for ($page = 1; ; $page++) {
            $batch = wc_get_orders(['type'=>'shop_order','billing_email'=>$email,'limit'=>100,'page'=>$page,'paginate'=>true,
                'status'=>array_keys(wc_get_order_statuses()),'orderby'=>'date','order'=>'DESC']);
            foreach ($batch->orders as $order) {
                if (!$order instanceof WC_Order) continue;
                if (strtolower(trim($order->get_billing_email())) !== $email || !self::current_order($order)) continue;
                foreach (self::rows_for_order($order) as $row) $rows[] = $row;
            }
            if ($page >= $batch->max_num_pages) break;
        }
        return $rows;
    }

    public static function rows_for_order(WC_Order $order): array
    {
        if (!self::current_order($order)) return [];
        $rows = [];
        foreach ($order->get_items() as $item_id=>$item) {
            $remaining = $order->is_paid() ? max(0, $item->get_quantity() + $order->get_qty_refunded_for_item($item_id)) : 0;
            foreach (self::ticket_ids($order->get_id(), $item_id) as $ticket_id) {
                $public = (string)get_post_meta($ticket_id, '_famtastic_ticket_public_id', true);
                if (!preg_match('/^[a-f0-9]{32}$/', $public) || get_post_meta($ticket_id, '_famtastic_ticket_is_test', true) === 'yes') continue;
                $state = (string)get_post_meta($ticket_id, '_famtastic_ticket_status', true);
                $usable = get_post_status($ticket_id) === 'publish' && in_array($state, ['valid','checked_in'], true) && $remaining > 0;
                if ($usable) $remaining--;
                $rows[] = ['public_id'=>'wc-'.$public,'ticket_type'=>'MBSH Class of 1996 Reunion Mixer',
                    'holder_name'=>trim($order->get_billing_first_name().' '.$order->get_billing_last_name()) ?: 'Ticket holder',
                    'status'=>$usable ? ($state === 'valid' ? 'active' : 'checked_in') : 'void',
                    'issued_at'=>get_post_meta($ticket_id, '_famtastic_ticket_issued_at', true),
                    'checked_in_at'=>get_post_meta($ticket_id, '_famtastic_ticket_checked_in_at', true) ?: null,
                    'created_at'=>get_post_field('post_date_gmt', $ticket_id)];
            }
        }
        return $rows;
    }

    /** Authorization and rotating-token validation belong to the verified portal service. */
    public static function transition(string $public, string $to, int $portal_actor): bool
    {
        if (!preg_match('/^wc-([a-f0-9]{32})$/', $public, $match) || !in_array($to,['checked_in','revoked'],true) || $portal_actor < 1) return false;
        $ids = get_posts(['post_type'=>'reunion_ticket','post_status'=>'publish','meta_key'=>'_famtastic_ticket_public_id','meta_value'=>$match[1],'fields'=>'ids','posts_per_page'=>2]);
        if (count($ids) !== 1) return false;
        $id = (int)$ids[0]; $order = wc_get_order((int)get_post_meta($id,'_famtastic_order_id',true));
        if (!$order) return false;
        $row = array_values(array_filter(self::rows_for_order($order), static fn($r)=>$r['public_id'] === $public));
        if (count($row)!==1 || !in_array($row[0]['status'], $to === 'revoked' ? ['active','checked_in'] : ['active'], true)) return false;
        global $wpdb;
        $from = $row[0]['status'] === 'checked_in' ? 'checked_in' : 'valid';
        $changed = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value=%s WHERE post_id=%d AND meta_key='_famtastic_ticket_status' AND meta_value=%s",$to,$id,$from));
        if ($changed !== 1) return false;
        wp_cache_delete($id,'post_meta');
        update_post_meta($id, $to === 'checked_in' ? '_famtastic_ticket_checked_in_at' : '_famtastic_ticket_revoked_at', current_time('mysql',true));
        if ($to === 'revoked') update_post_meta($id,'_famtastic_ticket_revoke_reason','portal_staff');
        add_post_meta($id,'_famtastic_ticket_audit',wp_json_encode(['event'=>$to,'source'=>'portal_attendee','sourceId'=>$portal_actor,'at'=>current_time('mysql',true)]));
        return true;
    }
}
