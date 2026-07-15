<?php
declare(strict_types=1);
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/admin-auth.php';
fam_require_admin_auth();
$config = fam_load_config();
$pdo = fam_db($config);

$source = $_GET['source'] ?? 'rsvps';
$filename = 'mbsh-emails-' . $source . '-' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

if ($source === 'rsvps') {
  fputcsv($output, ['First Name', 'Last Name', 'Maiden Name', 'Email', 'Phone', 'City/State', 'Attending', 'Guest Count', 'Guest Names', 'Dietary', 'Help Planning', 'Display Publicly', 'Submitted At']);
  $stmt = $pdo->query("SELECT first_name, last_name, maiden_name, email, phone, city_state, attending, guest_count, guest_names, dietary, help_planning, display_publicly, created_at FROM rsvps ORDER BY created_at DESC");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
      $row['first_name'], $row['last_name'], $row['maiden_name'] ?? '',
      $row['email'], $row['phone'] ?? '', $row['city_state'] ?? '',
      $row['attending'], $row['guest_count'], $row['guest_names'] ?? '',
      $row['dietary'] ?? '', $row['help_planning'] ? 'Yes' : 'No',
      $row['display_publicly'] ? 'Yes' : 'No', $row['created_at']
    ]);
  }
} elseif ($source === 'sponsors') {
  fputcsv($output, ['Contact Name', 'Company', 'Email', 'Phone', 'Tier', 'Custom Amount', 'Message', 'Status', 'Submitted At']);
  $stmt = $pdo->query("SELECT contact_name, company_name, email, phone, tier_interest, custom_amount, message, status, created_at FROM sponsors_pending ORDER BY created_at DESC");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
      $row['contact_name'], $row['company_name'] ?? '', $row['email'],
      $row['phone'] ?? '', $row['tier_interest'], $row['custom_amount'] ?? '',
      $row['message'] ?? '', $row['status'], $row['created_at']
    ]);
  }
} elseif ($source === 'chatbot') {
  fputcsv($output, ['Email', 'Question', 'Was Fallback', 'Responded', 'Submitted At']);
  $stmt = $pdo->query("SELECT email, question, was_fallback, responded, created_at FROM chatbot_questions ORDER BY created_at DESC");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
      $row['email'] ?? '', $row['question'],
      $row['was_fallback'] ? 'Yes' : 'No',
      $row['responded'] ? 'Yes' : 'No', $row['created_at']
    ]);
  }
} elseif ($source === 'capsules') {
  fputcsv($output, ['Email', 'Song Answer', 'Person Answer', 'Memory Answer', 'Send Date', 'Sent', 'Submitted At']);
  $stmt = $pdo->query("SELECT email, song_answer, person_answer, memory_answer, send_date, sent_at, created_at FROM time_capsules ORDER BY created_at DESC");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
      $row['email'], $row['song_answer'] ?? '', $row['person_answer'] ?? '',
      $row['memory_answer'] ?? '', $row['send_date'],
      $row['sent_at'] ? 'Yes (' . $row['sent_at'] . ')' : 'No',
      $row['created_at']
    ]);
  }
}

fclose($output);
exit;
