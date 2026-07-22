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
} elseif ($source === 'menu') {
  fputcsv($output, ['Name', 'Email', 'Hors', 'Salad', 'Entree', 'Sides', 'Dietary', 'Submitter Email Status', 'Submitter Email Sent At', 'Submitter Email Error', 'Committee Email Status', 'Committee Email Sent At', 'Committee Email Error', 'Submitted At']);
  $stmt = $pdo->query("SELECT name, email, selections_json, dietary, submitter_email_status, submitter_email_sent_at, submitter_email_error, committee_email_status, committee_email_sent_at, committee_email_error, created_at FROM menu_selections ORDER BY created_at DESC");
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $selections = json_decode($row['selections_json'], true) ?: [];
    fputcsv($output, [
      $row['name'],
      $row['email'],
      implode(', ', $selections['hors'] ?? []),
      $selections['salad'] ?? '',
      implode(', ', $selections['entree'] ?? []),
      implode(', ', $selections['side'] ?? []),
      $row['dietary'] ?? '',
      $row['submitter_email_status'] ?? '',
      $row['submitter_email_sent_at'] ?? '',
      $row['submitter_email_error'] ?? '',
      $row['committee_email_status'] ?? '',
      $row['committee_email_sent_at'] ?? '',
      $row['committee_email_error'] ?? '',
      $row['created_at']
    ]);
  }
} elseif ($source === 'historical_missing_rsvp' || $source === 'historical_missing_menu') {
  fputcsv($output, ['First Name', 'Last Name', 'HS Name', 'Email', 'Phone', 'Mailing Address', 'Preferred Reunion Month', 'Budget', 'Comments', 'Imported At', 'Survey Row Count', 'RSVP Created At', 'Menu Created At']);
  $whereClause = $source === 'historical_missing_menu'
    ? 'm.menu_created_at IS NULL'
    : 'r.rsvp_created_at IS NULL';
  $sql = <<<SQL
SELECT h.*, r.rsvp_created_at, m.menu_created_at
FROM (
  SELECT
    LOWER(TRIM(email)) AS email_key,
    MAX(NULLIF(first_name, '')) AS first_name,
    MAX(NULLIF(last_name, '')) AS last_name,
    MAX(NULLIF(hs_name, '')) AS hs_name,
    MAX(NULLIF(phone, '')) AS phone,
    MAX(NULLIF(mailing_address, '')) AS mailing_address,
    MAX(NULLIF(reunion_month, '')) AS reunion_month,
    MAX(NULLIF(budget, '')) AS budget,
    MAX(NULLIF(comments, '')) AS comments,
    MAX(imported_at) AS imported_at,
    COUNT(*) AS survey_row_count
  FROM surveys
  WHERE is_imported = 1
    AND email IS NOT NULL
    AND TRIM(email) <> ''
  GROUP BY LOWER(TRIM(email))
) h
LEFT JOIN (
  SELECT LOWER(TRIM(email)) AS email_key, MAX(created_at) AS rsvp_created_at
  FROM rsvps
  WHERE email IS NOT NULL AND TRIM(email) <> ''
  GROUP BY LOWER(TRIM(email))
) r ON r.email_key = h.email_key
LEFT JOIN (
  SELECT LOWER(TRIM(email)) AS email_key, MAX(created_at) AS menu_created_at
  FROM menu_selections
  WHERE email IS NOT NULL AND TRIM(email) <> ''
  GROUP BY LOWER(TRIM(email))
) m ON m.email_key = h.email_key
WHERE {$whereClause}
ORDER BY COALESCE(NULLIF(h.last_name, ''), NULLIF(h.hs_name, ''), h.email_key), COALESCE(NULLIF(h.first_name, ''), h.email_key)
SQL;
  $stmt = $pdo->query($sql);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
      $row['first_name'] ?? '',
      $row['last_name'] ?? '',
      $row['hs_name'] ?? '',
      $row['email_key'] ?? '',
      $row['phone'] ?? '',
      $row['mailing_address'] ?? '',
      $row['reunion_month'] ?? '',
      $row['budget'] ?? '',
      $row['comments'] ?? '',
      $row['imported_at'] ?? '',
      $row['survey_row_count'] ?? 0,
      $row['rsvp_created_at'] ?? '',
      $row['menu_created_at'] ?? '',
    ]);
  }
}

fclose($output);
exit;
