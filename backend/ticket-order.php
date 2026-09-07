<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';

$config = fam_load_config();
fam_cors($config, 'public_post');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') fam_json_response(405, ['error' => 'method_not_allowed']);
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) fam_json_response(415, ['error' => 'expected_application_json']);

try {
  $data = fam_read_json_body();
  fam_honeypot_clean($data);
  fam_form_loaded_at_check($data);
  $pdo = fam_db($config);
  fam_rate_limit($pdo, 'ticket_order', 3600, 10);

  $name = fam_required($data, 'contact_name', 200);
  $email = fam_email($data, 'email', true);
  $phone = fam_optional($data, 'phone', 50);
  $quantity = fam_int($data, 'quantity', 1, 10, 1);
  $guestNames = fam_optional($data, 'guest_names', 1000);
  $notes = fam_optional($data, 'notes', 1000);

  $today = new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
  $regularStarts = new DateTimeImmutable('2026-09-08 00:00:00', new DateTimeZone('America/New_York'));
  $priceTier = $today < $regularStarts ? 'early_bird' : 'regular';
  $unitPrice = $priceTier === 'early_bird' ? 185.00 : 200.00;
  $total = $unitPrice * $quantity;
  $replayed = false;
  $checkoutSecret = trim((string) ($config['checkout_secret'] ?? ''));
  if ($checkoutSecret === '') {
    fam_json_response(503, ['error' => 'checkout_unavailable', 'message' => 'Secure checkout is temporarily unavailable.']);
  }

  // Serialize same-purchaser submissions so a double click, browser retry, or
  // network retry cannot create another legacy reservation before WooCommerce
  // becomes the financial authority. This is deliberately narrow: only an
  // identical request in the last 30 minutes is replayed.
  $pdo->beginTransaction();
  try {
    // created_at uses the database session's CURRENT_TIMESTAMP, so compare it
    // with NOW() from that same clock instead of UTC_TIMESTAMP().
    $recent = $pdo->prepare('SELECT order_code, contact_name, phone, quantity, guest_names, unit_price, total_amount, price_tier, notes FROM ticket_orders WHERE email = ? AND created_at >= (NOW() - INTERVAL 30 MINUTE) ORDER BY id DESC LIMIT 20 FOR UPDATE');
    $recent->execute([$email]);
    foreach ($recent->fetchAll() as $existing) {
      if ((string) $existing['contact_name'] === $name
        && (string) ($existing['phone'] ?? '') === (string) ($phone ?? '')
        && (string) ($existing['guest_names'] ?? '') === (string) ($guestNames ?? '')
        && (string) ($existing['notes'] ?? '') === (string) ($notes ?? '')
        && (int) $existing['quantity'] === $quantity
        && (float) $existing['unit_price'] === $unitPrice
        && (float) $existing['total_amount'] === $total
      ) {
        $replayed = true;
        $orderCode = (string) $existing['order_code'];
        $unitPrice = (float) $existing['unit_price'];
        $total = (float) $existing['total_amount'];
        $priceTier = (string) $existing['price_tier'];
        break;
      }
    }

    if (!$replayed) {
      $orderCode = 'MBSH-' . strtoupper(bin2hex(random_bytes(3)));
      $stmt = $pdo->prepare('INSERT INTO ticket_orders (order_code, contact_name, email, phone, quantity, guest_names, unit_price, total_amount, price_tier, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
      $stmt->execute([$orderCode, $name, $email, $phone, $quantity, $guestNames, $unitPrice, $total, $priceTier, $notes]);
    }
    $pdo->commit();
  } catch (Throwable $transactionError) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $transactionError;
  }

  // This endpoint records a reservation attempt only. It intentionally sends
  // no customer or committee mail; WooCommerce's paid-order lifecycle owns
  // receipts, tickets, and post-payment notifications.
  $tokenPayload = implode('|', [$orderCode, $email, $quantity, time() + 86400]);
  $tokenEncoded = rtrim(strtr(base64_encode($tokenPayload), '+/', '-_'), '=');
  $checkoutToken = $tokenEncoded . '.' . hash_hmac('sha256', $tokenPayload, $checkoutSecret);
  fam_json_response(200, [
    'ok' => true,
    'order_code' => $orderCode,
    'quantity' => $quantity,
    'unit_price' => $unitPrice,
    'total_amount' => $total,
    'price_tier' => $priceTier,
    'replayed' => $replayed,
    'payment_status' => 'attempt',
    'checkout_token' => $checkoutToken,
  ]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
  error_log('[ticket-order] DB error: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'db_error']);
} catch (Throwable $e) {
  error_log('[ticket-order] Uncaught: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
