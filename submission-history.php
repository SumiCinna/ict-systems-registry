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

$pastBatches = get_past_batches($pdo, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Past Submissions — ICT Systems Registry</title>
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
      <a href="survey.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">BACK TO SURVEY</a>
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
    </div>
  </div>
</header>

<div class="max-w-3xl mx-auto px-6 py-10">

  <div class="text-center mb-10">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Read-Only Archive</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Past Submissions</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if (empty($pastBatches)): ?>
  <div class="bg-white border border-ledger-line shadow-sm p-10 text-center text-ledger-muted">
    No past submissions yet. Once you finish a round and start a new one, it'll show up here.
  </div>
  <?php else: ?>
  <div class="flex flex-col gap-4">
    <?php foreach ($pastBatches as $batchRow): ?>
    <?php
      $firstSaved = $batchRow['first_saved'] ? date('M j, Y', strtotime($batchRow['first_saved'])) : null;
      $lastSaved = $batchRow['last_saved'] ? date('M j, Y', strtotime($batchRow['last_saved'])) : null;
      $dateRange = $firstSaved === $lastSaved ? $firstSaved : ($firstSaved . ' – ' . $lastSaved);
    ?>
    <a href="submission-history-detail.php?batch=<?= (int) $batchRow['batch'] ?>"
       class="block bg-white border border-ledger-line shadow-sm hover:border-ledger-steel hover:shadow-md transition-all p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-[10px] font-semibold tracking-[0.2em] uppercase text-ledger-gold">Submission Round</p>
          <h2 class="font-display text-lg text-ledger-navy mt-1">Cycle #<?= (int) $batchRow['batch'] ?></h2>
          <?php if ($dateRange): ?>
          <p class="text-xs text-ledger-muted mt-1">Recorded <?= htmlspecialchars($dateRange, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
        </div>
        <div class="text-right">
          <p class="text-sm font-semibold text-ledger-navy"><?= (int) $batchRow['entry_count'] ?> entr<?= (int) $batchRow['entry_count'] === 1 ? 'y' : 'ies' ?></p>
          <p class="text-xs text-ledger-steel font-semibold mt-1">VIEW &rarr;</p>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

</body>
</html>