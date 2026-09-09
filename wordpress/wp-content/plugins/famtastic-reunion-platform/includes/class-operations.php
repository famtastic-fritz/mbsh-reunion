<?php

declare(strict_types=1);

final class Famtastic_Reunion_Operations
{
    public static function register(): void
    {
        add_action('famtastic_reunion_health_check', [self::class, 'health_check']);
        add_action('famtastic_reunion_reconciliation_check', [self::class, 'reconciliation_check']);
        add_action('famtastic_reunion_delivery_check', [self::class, 'delivery_check']);
        add_action('init', [self::class, 'schedule'], 20);
    }

    public static function schedule(): void
    {
        if (!wp_next_scheduled('famtastic_reunion_health_check')) {
            wp_schedule_event(time() + 300, 'hourly', 'famtastic_reunion_health_check');
        }
        if (function_exists('as_schedule_recurring_action') && function_exists('as_next_scheduled_action')) {
            if (!as_next_scheduled_action('famtastic_reunion_reconciliation_check', [], 'famtastic-reunion')) {
                as_schedule_recurring_action(time() + 120, 900, 'famtastic_reunion_reconciliation_check', [], 'famtastic-reunion', true);
            }
            if (!as_next_scheduled_action('famtastic_reunion_delivery_check', [], 'famtastic-reunion')) {
                as_schedule_recurring_action(time() + 180, 300, 'famtastic_reunion_delivery_check', [], 'famtastic-reunion', true);
            }
        }
    }

    public static function health_check(): void
    {
        update_option('famtastic_reunion_worker_heartbeat', [
            'at' => current_time('mysql', true),
            'version' => FAMTASTIC_REUNION_VERSION,
        ], false);
    }

    public static function reconciliation_check(): void
    {
        $checked = 0;
        $waiting = 0;
        if (function_exists('wc_get_orders')) {
            $orders = wc_get_orders(['status'=>['processing','completed'],'limit'=>50,'orderby'=>'date','order'=>'DESC']);
            foreach ($orders as $order) {
                if (!$order instanceof WC_Order || !$order->is_paid()) continue;
                $checked++;
                if (!$order->get_meta('_famtastic_tickets_issued', true)) {
                    Famtastic_Reunion_Tickets::issue_for_order($order->get_id());
                    if (!$order->get_meta('_famtastic_tickets_issued', true)) $waiting++;
                }
            }
        }
        update_option('famtastic_reunion_reconciliation_heartbeat', [
            'at'=>current_time('mysql',true),
            'mode'=>defined('FAMTASTIC_REUNION_MODE')?FAMTASTIC_REUNION_MODE:'unknown',
            'paid_orders_checked'=>$checked,
            'orders_waiting_for_ticket'=>$waiting,
        ], false);
    }

    public static function delivery_check(): void
    {
        update_option('famtastic_reunion_delivery_heartbeat', ['at'=>current_time('mysql',true),'provider'=>'Resend outbox','send_performed'=>false], false);
    }
}
