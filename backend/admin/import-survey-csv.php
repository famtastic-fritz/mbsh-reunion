<?php
// import-survey-csv.php — one-time import of historical MS Forms data
// Run: php import-survey-csv.php
// Then DELETE this file.
declare(strict_types=1);
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';

$config = fam_load_config();
$pdo = fam_db($config);

$csvPath = __DIR__ . '/../data/MBSH96-Survey-Historical.csv';
if (!file_exists($csvPath)) {
  echo "CSV not found at {$csvPath}\n";
  exit(1);
}

$handle = fopen($csvPath, 'r');
$header = fgetcsv($handle);
if (!$header) {
  echo "Empty CSV\n";
  exit(1);
}

$colMap = [
  'First Name' => 'first_name',
  'Last Name' => 'last_name',
  'Full Name In High School (If different from your current name)' => 'hs_name',
  'Phone Number' => 'phone',
  'Email Address' => 'email',
  'Mailing Address' => 'mailing_address',
  'T-Shirt Size' => 'tshirt_size',
  'Do you want to participate in planning the reunion?' => 'planning',
  'If you are willing to participate in planning the reunion, what role are you willing to take on?' => 'planning_role',
  'How do you prefer to be contacted?' => 'contact_pref',
  'Do you want to be added to the MBSH C/O 96 GroupMe?' => 'groupme',
  'Do you know of any former classmates that have passed away since our high school graduation?' => 'classmates_passed',
  'What month should we do the reunion?' => 'reunion_month',
  'Duration of the reunion' => 'duration',
  'What day(s) of the week?' => 'days_of_week',
  'Type of reunion' => 'reunion_type',
  'Type of venue' => 'venue_type',
  'Estimated budget per person' => 'budget',
  'Should it be open to other MBSH graduating classes?' => 'open_other_classes',
  'Do you have any questions, comments, ideas, or suggestions?' => 'comments',
];

$dbCols = array_values($colMap);
$csvToDb = [];
foreach ($header as $i => $h) {
  $h = trim($h);
  if (isset($colMap[$h])) {
    $csvToDb[$i] = $colMap[$h];
  }
}

$ph = implode(',', array_fill(0, count($dbCols), '?'));
$stmt = $pdo->prepare('INSERT INTO surveys (' . implode(',', $dbCols) . ', is_imported, imported_at) VALUES (' . $ph . ', 1, NOW())');

$count = 0;
while (($row = fgetcsv($handle)) !== false) {
  $data = array_fill(0, count($dbCols), null);
  foreach ($csvToDb as $csvIdx => $dbCol) {
    $val = isset($row[$csvIdx]) ? trim($row[$csvIdx]) : '';
    if ($val === '') $val = null;
    $data[array_search($dbCol, $dbCols)] = $val;
  }
  $stmt->execute($data);
  $count++;
}
fclose($handle);

echo "Imported {$count} historical survey responses.\n";
echo "DELETE this file after running.\n";
