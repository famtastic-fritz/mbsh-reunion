<?php
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/cors.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validate.php';
require_once __DIR__ . '/lib/rate-limit.php';
require_once __DIR__ . '/lib/resend.php';

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
  $orderCode = 'MBSH-' . strtoupper(bin2hex(random_bytes(3)));

  $stmt = $pdo->prepare('INSERT INTO ticket_orders (order_code, contact_name, email, phone, quantity, guest_names, unit_price, total_amount, price_tier, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
  $stmt->execute([$orderCode, $name, $email, $phone, $quantity, $guestNames, $unitPrice, $total, $priceTier, $notes]);

  $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
  $safeGuests = htmlspecialchars($guestNames ?? 'Not provided', ENT_QUOTES, 'UTF-8');
  $safePhone = htmlspecialchars($phone ?? 'Not provided', ENT_QUOTES, 'UTF-8');
  $safeNotes = htmlspecialchars($notes ?? 'None', ENT_QUOTES, 'UTF-8');
  $priceLabel = $priceTier === 'early_bird' ? 'Early Bird' : 'Regular';
  $money = number_format($total, 2);

  $guestHtml = "<h2>Ticket order received</h2><p>Hi {$safeName}, your request for <strong>{$quantity}</strong> reunion ticket(s) has been saved.</p><p><strong>Order:</strong> {$orderCode}<br><strong>Price:</strong> {$priceLabel} at $" . number_format($unitPrice, 2) . " per person<br><strong>Total due:</strong> $" . $money . "</p><p><strong>No payment has been collected yet.</strong> The committee will contact you with payment instructions as soon as the payment account is ready.</p><p>Questions? Reply to this email or contact committee@mbsh96reunion.com.</p>";
  $committeeHtml = "<h2>New ticket order request</h2><p><strong>Order:</strong> {$orderCode}<br><strong>Name:</strong> {$safeName}<br><strong>Email:</strong> {$email}<br><strong>Phone:</strong> {$safePhone}<br><strong>Quantity:</strong> {$quantity}<br><strong>Guests:</strong> {$safeGuests}<br><strong>Tier:</strong> {$priceLabel}<br><strong>Total due:</strong> $" . $money . "<br><strong>Notes:</strong> {$safeNotes}</p><p>Payment status: pending.</p>";
  try {
    fam_send_email($config, $email, "Ticket order received — {$orderCode}", $guestHtml, 'harry');
    fam_send_email($config, $config['committee_email'], "Ticket order: {$safeName} — {$quantity} ticket(s)", $committeeHtml, 'committee');
  } catch (Throwable $emailError) {
    error_log('[ticket-order] Email error: ' . $emailError->getMessage());
  }

  fam_json_response(200, ['ok' => true, 'order_code' => $orderCode, 'quantity' => $quantity, 'unit_price' => $unitPrice, 'total_amount' => $total, 'price_tier' => $priceTier]);
} catch (ValidationError $e) {
  fam_json_response(400, ['error' => 'validation_error', 'message' => $e->getMessage()]);
} catch (PDOException $e) {
  error_log('[ticket-order] DB error: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'db_error']);
} catch (Throwable $e) {
  error_log('[ticket-order] Uncaught: ' . $e->getMessage());
  fam_json_response(500, ['error' => 'internal_error']);
}
