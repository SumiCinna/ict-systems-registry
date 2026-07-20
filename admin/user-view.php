<?php
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$targetId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, agency_name, last_name, first_name, middle_initial, position_designation, email, telephone_number, survey_stage, submitted_at, is_admin, is_disabled FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $targetId]);
$target = $stmt->fetch();

if (!$target) {
    header('Location: index.php');
    exit;
}

$fullName = trim($target['first_name'] . ' ' . ($target['middle_initial'] !== null && $target['middle_initial'] !== '' ? $target['middle_initial'] . '. ' : '') . $target['last_name']);

$appSystemsStmt = $pdo->prepare('SELECT * FROM application_systems WHERE user_id = :id ORDER BY id ASC');
$appSystemsStmt->execute(['id' => $targetId]);
$appSystems = $appSystemsStmt->fetchAll();

$ictProjectsStmt = $pdo->prepare('SELECT * FROM ict_projects WHERE user_id = :id ORDER BY id ASC');
$ictProjectsStmt->execute(['id' => $targetId]);
$ictProjects = $ictProjectsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($target['agency_name'], ENT_QUOTES, 'UTF-8') ?> — Admin — ICT Systems Registry</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ledger: { navy: '#0B2340', steel: '#1B4B72', gold: '#C9A227', paper: '#F7F5EF', line: '#D9D3C3', ink: '#28313A', muted: '#5B6B79' }
        },
        fontFamily: { display: ['Georgia', 'Cambria', 'Times New Roman', 'serif'], body: ['"Inter"', 'system-ui', 'sans-serif'] }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-ledger-paper font-body text-ledger-ink min-h-screen">

<header class="bg-ledger-navy text-white">
  <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
    <div>
      <p class="text-[10px] tracking-[0.25em] uppercase text-white/60">ICT Systems &amp; Projects Registry</p>
      <p class="font-display text-lg">Admin Panel</p>
    </div>
    <div class="flex items-center gap-4">
      <a href="index.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">BACK TO ACCOUNTS</a>
      <a href="../logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
    </div>
  </div>
</header>

<div class="max-w-5xl mx-auto px-6 py-10">

  <div class="mb-8 bg-white border border-ledger-line shadow-sm p-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Account</p>
        <h1 class="font-display text-2xl text-ledger-navy"><?= htmlspecialchars($target['agency_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-sm text-ledger-muted mt-1"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($target['position_designation'], ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-sm text-ledger-muted"><?= htmlspecialchars($target['email'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($target['telephone_number'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="text-right">
        <span class="inline-block px-2 py-1 text-[10px] font-semibold uppercase <?= (int) $target['is_disabled'] === 1 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
          <?= (int) $target['is_disabled'] === 1 ? 'Disabled' : 'Active' ?>
        </span>
        <p class="text-xs text-ledger-muted mt-2">Stage: <?= htmlspecialchars(ucfirst($target['survey_stage']), ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($target['submitted_at']): ?>
          <p class="text-xs text-ledger-muted">Submitted <?= htmlspecialchars(date('M j, Y g:i A', strtotime($target['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="mb-10">
    <h2 class="font-display text-xl text-ledger-navy mb-4">List of Application Systems (<?= count($appSystems) ?>)</h2>
    <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto">
      <table class="w-full text-xs">
        <thead>
          <tr class="bg-ledger-navy text-white text-left">
            <th class="px-4 py-3 font-semibold">Application</th>
            <th class="px-4 py-3 font-semibold">Implemented</th>
            <th class="px-4 py-3 font-semibold">Strategy</th>
            <th class="px-4 py-3 font-semibold">Own IP</th>
            <th class="px-4 py-3 font-semibold">Mode</th>
            <th class="px-4 py-3 font-semibold">Acquisition Cost</th>
            <th class="px-4 py-3 font-semibold">Annual Maint. Cost</th>
            <th class="px-4 py-3 font-semibold">Annual Txn Amount</th>
            <th class="px-4 py-3 font-semibold">Users</th>
            <th class="px-4 py-3 font-semibold">Info Type</th>
            <th class="px-4 py-3 font-semibold">Scope</th>
            <th class="px-4 py-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($appSystems as $entry): ?>
          <tr class="border-t border-ledger-line">
            <td class="px-4 py-3"><?= htmlspecialchars($entry['application_name_version'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= $entry['date_of_implementation'] ? htmlspecialchars($entry['date_of_implementation'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['development_strategy'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['owns_ip'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['mode_of_implementation'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= $entry['acquisition_cost'] !== null ? number_format((float) $entry['acquisition_cost'], 2) : '—' ?></td>
            <td class="px-4 py-3"><?= $entry['annual_maintenance_cost'] !== null ? number_format((float) $entry['annual_maintenance_cost'], 2) : '—' ?></td>
            <td class="px-4 py-3"><?= $entry['annual_transaction_amount'] !== null ? number_format((float) $entry['annual_transaction_amount'], 2) : '—' ?></td>
            <td class="px-4 py-3"><?= $entry['no_of_users'] !== null ? (int) $entry['no_of_users'] : '—' ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['type_of_information'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['scope_of_operation'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($appSystems)): ?>
          <tr><td colspan="12" class="px-4 py-6 text-center text-ledger-muted">No entries.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mb-10">
    <h2 class="font-display text-xl text-ledger-navy mb-4">List of ICT Projects (<?= count($ictProjects) ?>)</h2>
    <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto">
      <table class="w-full text-xs">
        <thead>
          <tr class="bg-ledger-navy text-white text-left">
            <th class="px-4 py-3 font-semibold">Project</th>
            <th class="px-4 py-3 font-semibold">Description</th>
            <th class="px-4 py-3 font-semibold">Start</th>
            <th class="px-4 py-3 font-semibold">End</th>
            <th class="px-4 py-3 font-semibold">Cost</th>
            <th class="px-4 py-3 font-semibold">Provider</th>
            <th class="px-4 py-3 font-semibold">Funding Source</th>
            <th class="px-4 py-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ictProjects as $entry): ?>
          <tr class="border-t border-ledger-line">
            <td class="px-4 py-3"><?= htmlspecialchars($entry['project_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= $entry['start_date'] ? htmlspecialchars($entry['start_date'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td class="px-4 py-3"><?= $entry['end_date'] ? htmlspecialchars($entry['end_date'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td class="px-4 py-3"><?= $entry['project_contract_cost'] !== null ? number_format((float) $entry['project_contract_cost'], 2) : '—' ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['third_party_provider'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['funding_source'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($ictProjects)): ?>
          <tr><td colspan="8" class="px-4 py-6 text-center text-ledger-muted">No entries.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>