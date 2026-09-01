<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Every batch strictly before current_batch has already been finalized by
// finalize_submission() (the only thing that bumps current_batch), so this
// pulls every entry from every submitted cycle in one flat list — no
// per-cycle grouping or navigation, unlike submission-history.php.
$currentBatch = get_current_batch($pdo, $_SESSION['user_id']);

$appStmt = $pdo->prepare('SELECT * FROM application_systems WHERE user_id = :id AND batch < :current_batch ORDER BY id ASC');
$appStmt->execute(['id' => $_SESSION['user_id'], 'current_batch' => $currentBatch]);
$appSystems = $appStmt->fetchAll();

$ictStmt = $pdo->prepare('SELECT * FROM ict_projects WHERE user_id = :id AND batch < :current_batch ORDER BY id ASC');
$ictStmt->execute(['id' => $_SESSION['user_id'], 'current_batch' => $currentBatch]);
$ictProjects = $ictStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Full Submission History — ICT Systems Registry</title>
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
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-ledger-paper font-body text-ledger-ink min-h-screen">

<header class="bg-ledger-navy text-white">
  <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
    <div>
      <p class="text-[10px] tracking-[0.25em] uppercase text-white/60">ICT Systems &amp; Projects Registry</p>
      <p class="font-display text-lg"><?= htmlspecialchars($user['agency_name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="flex items-center gap-4">
      <a href="survey.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">
        BACK TO SURVEYS
      </a>
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
    </div>
  </div>
</header>

<div class="max-w-7xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Read-Only Archive</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Full Submission History</h1>
    <p class="text-sm text-ledger-muted mt-2">Every entry you've ever submitted, across every round.</p>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <div class="mb-10">
    <h2 class="font-display text-xl text-ledger-navy mb-4">Application Systems (<?= count($appSystems) ?>)</h2>
    <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto">
      <table class="w-full text-xs">
        <thead>
          <tr class="bg-ledger-navy text-white text-left">
            <th class="px-4 py-3 font-semibold">Application</th>
            <th class="px-4 py-3 font-semibold">Strategy</th>
            <th class="px-4 py-3 font-semibold">Mode</th>
            <th class="px-4 py-3 font-semibold">Users</th>
            <th class="px-4 py-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($appSystems as $entry): ?>
          <tr class="border-t border-ledger-line">
            <td class="px-4 py-3"><?= htmlspecialchars($entry['application_name_version'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['development_strategy'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['mode_of_implementation'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= $entry['no_of_users'] !== null ? htmlspecialchars($entry['no_of_users'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($appSystems)): ?>
          <tr><td colspan="5" class="px-4 py-6 text-center text-ledger-muted">No submitted entries yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mb-4">
    <h2 class="font-display text-xl text-ledger-navy mb-4">ICT Projects (<?= count($ictProjects) ?>)</h2>
    <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto">
      <table class="w-full text-xs">
        <thead>
          <tr class="bg-ledger-navy text-white text-left">
            <th class="px-4 py-3 font-semibold">Project</th>
            <th class="px-4 py-3 font-semibold">Description</th>
            <th class="px-4 py-3 font-semibold">Cost</th>
            <th class="px-4 py-3 font-semibold">Provider</th>
            <th class="px-4 py-3 font-semibold">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ictProjects as $entry): ?>
          <tr class="border-t border-ledger-line">
            <td class="px-4 py-3"><?= htmlspecialchars($entry['project_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= $entry['project_contract_cost'] !== null ? number_format((float) $entry['project_contract_cost'], 2) : '—' ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['third_party_provider'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($ictProjects)): ?>
          <tr><td colspan="5" class="px-4 py-6 text-center text-ledger-muted">No submitted entries yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

</body>
</html>