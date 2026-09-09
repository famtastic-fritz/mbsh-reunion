<?php
declare(strict_types=1);

require_once __DIR__ . '/portal-email-templates.php';

/**
 * Temporary Act I checkout configuration supplied by the reunion committee.
 * The publishable key and Payment Link are public identifiers; no Stripe secret
 * is stored in this repository or returned by the ticket API.
 */
function fam_ticket_payment_link(): array
{
    return [
        'provider' => 'stripe_payment_link',
        'url' => 'https://buy.stripe.com/3cIfZg8vZd5haIgb1c5Vu04',
        'buy_button_id' => 'buy_btn_1U9F3GD5PsE39cMx3iBvqu0d',
        'publishable_key' => 'pk_live_51Tu0uLD5PsE39cMxv1wBnVLQOwG1pRG4sCFtYrLJHxtkrG9ItVWwX8S71FERewpaFEWPxSc0nNmhpRpZE8KPdhMf00Sz1qpYOY',
        'unit_amount' => 190.67,
        'currency' => 'USD',
        'maximum_quantity' => 10,
        'available_through' => '2026-09-07 23:59:59',
    ];
}

function fam_ticket_payment_link_is_open(?DateTimeImmutable $now = null): bool
{
    $now ??= new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
    $closes = new DateTimeImmutable(fam_ticket_payment_link()['available_through'], new DateTimeZone('America/New_York'));
    return $now <= $closes;
}

function fam_ticket_payment_handoff_url(string $orderCode): string
{
    return 'https://mbsh96reunion.com/pay/?order=' . rawurlencode(strtoupper($orderCode));
}

function fam_ticket_payment_expected_amount(int $quantity): float
{
    return round((float) fam_ticket_payment_link()['unit_amount'] * max(1, $quantity), 2);
}

/** Issue any missing wallet admissions for a verified paid request. */
function fam_fulfill_paid_ticket_order(PDO $pdo, array $config, int $orderId): array
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT * FROM ticket_orders WHERE id=? FOR UPDATE');
        $q->execute([$orderId]);
        $order = $q->fetch();
        if (!$order || (string)$order['payment_status'] !== 'paid') {
            throw new RuntimeException('A verified paid order is required before ticket issuance.');
        }

        $email = strtolower(trim((string)$order['email']));
        $accountQ = $pdo->prepare("SELECT id,public_id FROM attendee_accounts WHERE LOWER(email)=? AND status='active' AND email_verified_at IS NOT NULL");
        $accountQ->execute([$email]);
        $account = $accountQ->fetch();
        if (!$account) {
            $pdo->commit();
            return ['status'=>'waiting_for_account','issued'=>0,'email'=>$email,'order'=>$order];
        }

        $pdo->prepare("INSERT IGNORE INTO attendee_record_links (attendee_id,source_type,source_id) VALUES (?,'ticket_order',?)")
            ->execute([(int)$account['id'], (string)$order['id']]);
        $countQ = $pdo->prepare('SELECT COUNT(*) FROM ticket_wallet_items WHERE ticket_order_id=?');
        $countQ->execute([(int)$order['id']]);
        $issued = (int)$countQ->fetchColumn();
        $quantity = max(1, (int)$order['quantity']);
        $names = array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string)($order['guest_names'] ?? '')) ?: [])));
        array_unshift($names, trim((string)$order['contact_name']) ?: 'Ticket holder');
        while (count($names) < $quantity) $names[] = 'Guest ' . (count($names) + 1);

        $newTickets = [];
        for ($i = $issued; $i < $quantity; $i++) {
            $public = fam_uuid_v4();
            $pdo->prepare("INSERT INTO ticket_wallet_items (public_id,attendee_id,ticket_order_id,ticket_type,holder_name,credential_fingerprint,status,issued_at) VALUES (?,?,?,?,?,?,'active',NOW())")
                ->execute([$public, (int)$account['id'], (int)$order['id'], 'MBSH Class of 1996 30th Reunion', $names[$i], fam_token_hash('ticket|' . $public)]);
            $newTickets[] = $public;
        }

        if ($newTickets) {
            $pdo->prepare("INSERT INTO attendee_notifications (public_id,attendee_id,notification_type,title,message,action_url) VALUES (?,?,'ticket','Your reunion ticket is ready','Stripe payment was verified. Open your private wallet to view your reunion admission.','/portal/#ticket')")
                ->execute([fam_uuid_v4(), (int)$account['id']]);
        }
        $pdo->commit();

        if ($newTickets && empty($order['ticket_email_sent_at'])) {
            $message = fam_ticket_transaction_email('ticket_issued', [
                'first_name'=>(string)$order['contact_name'],
                'order_number'=>(string)$order['order_code'],
                'quantity'=>$quantity,
                'total'=>'$' . number_format((float)($order['payment_amount'] ?? fam_ticket_payment_expected_amount($quantity)), 2),
                'payment_reference'=>(string)($order['payment_reference'] ?? ''),
            ]);
            fam_send_email($config, $email, $message['subject'], $message['html'], 'harry');
            $pdo->prepare('UPDATE ticket_orders SET ticket_email_sent_at=NOW() WHERE id=? AND ticket_email_sent_at IS NULL')->execute([$orderId]);
        }
        return ['status'=>'issued','issued'=>count($newTickets),'email'=>$email,'order'=>$order];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

/** Fulfill paid requests after an attendee verifies the matching portal email. */
function fam_fulfill_paid_ticket_orders_for_email(PDO $pdo, array $config, string $email): array
{
    $q = $pdo->prepare("SELECT id FROM ticket_orders WHERE LOWER(email)=? AND payment_status='paid' ORDER BY id");
    $q->execute([strtolower(trim($email))]);
    $results = [];
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $results[] = fam_fulfill_paid_ticket_order($pdo, $config, (int)$id);
    }
    return $results;
}
