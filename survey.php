<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT agency_name, last_name, first_name, middle_initial, position_designation, survey_stage FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$fullName = trim($user['first_name'] . ' ' . ($user['middle_initial'] !== null && $user['middle_initial'] !== '' ? $user['middle_initial'] . '. ' : '') . $user['last_name']);
$stage = $user['survey_stage'];

$appSystemsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id');
$appSystemsStmt->execute(['id' => $_SESSION['user_id']]);
$appSystemsCount = (int) $appSystemsStmt->fetch()['total'];

$ictProjectsStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
$ictProjectsStmt->execute(['id' => $_SESSION['user_id']]);
$ictProjectsCount = (int) $ictProjectsStmt->fetch()['total'];

$steps = [
    'systems' => 'Survey 1: List of Application Systems',
    'projects' => 'Survey 2: List of ICT Projects',
    'review' => 'Final Review',
    'submitted' => 'Submitted',
];
$stageOrder = ['systems', 'projects', 'review', 'submitted'];
$currentIndex = array_search($stage, $stageOrder, true);

$ctaLabel = 'Start Survey 1';
$ctaHref = 'application-systems.php';
if ($stage === 'systems' && $appSystemsCount > 0) {
    $ctaLabel = 'Continue Survey 1';
} elseif ($stage === 'projects') {
    $ctaLabel = $ictProjectsCount > 0 ? 'Continue Survey 2' : 'Start Survey 2';
    $ctaHref = 'ict-projects.php';
} elseif ($stage === 'review') {
    $ctaLabel = 'Review & Submit';
    $ctaHref = 'review.php';
} elseif ($stage === 'submitted') {
    $ctaLabel = 'View Submitted Survey';
    $ctaHref = 'review.php';
}
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
      <div class="text-right hidden sm:block">
        <p class="text-sm font-semibold"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="text-xs text-white/60"><?= htmlspecialchars($user['position_designation'], ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
    </div>
  </div>
</header>

<div class="max-w-3xl mx-auto px-6 py-10">

  <div class="text-center mb-10">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Annual Survey</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Your progress</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <div class="bg-white border border-ledger-line shadow-sm p-8">

    <ol class="space-y-4 mb-8">
      <?php foreach ($steps as $key => $label): ?>
        <?php
          $stepIndex = array_search($key, $stageOrder, true);
          $done = $stepIndex < $currentIndex || ($key === 'submitted' && $stage === 'submitted');
          $active = $key === $stage;
        ?>
        <li class="flex items-center gap-3">
          <span class="w-6 h-6 flex items-center justify-center text-[11px] font-semibold rounded-full border
            <?= $done ? 'bg-green-600 border-green-600 text-white' : ($active ? 'border-ledger-navy text-ledger-navy' : 'border-ledger-line text-ledger-muted') ?>">
            <?= $done ? '✓' : $stepIndex + 1 ?>
          </span>
          <span class="text-sm <?= $active ? 'font-semibold text-ledger-navy' : ($done ? 'text-ledger-ink' : 'text-ledger-muted') ?>">
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ol>

    <div class="grid grid-cols-2 gap-4 mb-8 text-center">
      <div class="border border-ledger-line px-4 py-3">
        <p class="text-2xl font-display text-ledger-navy"><?= $appSystemsCount ?></p>
        <p class="text-[11px] uppercase tracking-wide text-ledger-muted mt-1">Application Systems</p>
      </div>
      <div class="border border-ledger-line px-4 py-3">
        <p class="text-2xl font-display text-ledger-navy"><?= $ictProjectsCount ?></p>
        <p class="text-[11px] uppercase tracking-wide text-ledger-muted mt-1">ICT Projects</p>
      </div>
    </div>

    <a href="<?= htmlspecialchars($ctaHref, ENT_QUOTES, 'UTF-8') ?>"
       class="block text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
      <?= htmlspecialchars(strtoupper($ctaLabel), ENT_QUOTES, 'UTF-8') ?>
    </a>

  </div>

</div>

</body>
</html>