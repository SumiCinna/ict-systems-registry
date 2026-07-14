<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT agency_name, last_name, first_name, middle_initial, position_designation FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$fullName = trim($user['first_name'] . ' ' . ($user['middle_initial'] !== null && $user['middle_initial'] !== '' ? $user['middle_initial'] . '. ' : '') . $user['last_name']);

$appSystemsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id');
$appSystemsStmt->execute(['id' => $_SESSION['user_id']]);
$appSystemsCount = (int) $appSystemsStmt->fetch()['total'];

$ictProjectsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
$ictProjectsStmt->execute(['id' => $_SESSION['user_id']]);
$ictProjectsCount = (int) $ictProjectsStmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Survey — ICT Systems Registry</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ledger: {
            navy: '#0B2340',
            steel: '#1B4B72',
            gold: '#C9A227',
            paper: '#F7F5EF',
            line: '#D9D3C3',
            ink: '#28313A',
            muted: '#5B6B79',
          }
        },
        fontFamily: {
          display: ['Georgia', 'Cambria', 'Times New Roman', 'serif'],
          body: ['"Inter"', 'system-ui', 'sans-serif'],
        }
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
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-xs text-white/60"><?= htmlspecialchars($user['position_designation'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">
        LOG OUT
      </a>
    </div>
  </div>
</header>

<div class="max-w-5xl mx-auto px-6 py-10">

  <div class="text-center mb-10">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Annual Survey</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Select a survey to complete</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <a href="application-systems.php" class="block bg-white border border-ledger-line shadow-sm hover:border-ledger-steel hover:shadow-md transition-all p-8">
      <div class="flex items-center justify-between mb-4">
        <span class="text-[10px] font-semibold tracking-[0.2em] uppercase text-ledger-gold">Survey 1</span>
        <span class="text-xs font-semibold px-2.5 py-1 border border-ledger-line text-ledger-muted">
          <?= $appSystemsCount ?> entr<?= $appSystemsCount === 1 ? 'y' : 'ies' ?>
        </span>
      </div>
      <h2 class="font-display text-xl text-ledger-navy mb-2">List of Application Systems</h2>
      <p class="text-sm text-ledger-muted leading-relaxed mb-6">
        Record your agency's application systems, including development strategy, cost, and implementation status.
      </p>
      <span class="inline-block text-sm font-semibold text-ledger-steel">
        <?= $appSystemsCount > 0 ? 'Continue survey' : 'Start survey' ?> &rarr;
      </span>
    </a>

    <a href="ict-projects.php" class="block bg-white border border-ledger-line shadow-sm hover:border-ledger-steel hover:shadow-md transition-all p-8">
      <div class="flex items-center justify-between mb-4">
        <span class="text-[10px] font-semibold tracking-[0.2em] uppercase text-ledger-gold">Survey 2</span>
        <span class="text-xs font-semibold px-2.5 py-1 border border-ledger-line text-ledger-muted">
          <?= $ictProjectsCount ?> entr<?= $ictProjectsCount === 1 ? 'y' : 'ies' ?>
        </span>
      </div>
      <h2 class="font-display text-xl text-ledger-navy mb-2">List of ICT Projects</h2>
      <p class="text-sm text-ledger-muted leading-relaxed mb-6">
        Record ongoing and completed ICT projects, including cost, timeline, and funding source.
      </p>
      <span class="inline-block text-sm font-semibold text-ledger-steel">
        <?= $ictProjectsCount > 0 ? 'Continue survey' : 'Start survey' ?> &rarr;
      </span>
    </a>

  </div>

</div>

</body>
</html>