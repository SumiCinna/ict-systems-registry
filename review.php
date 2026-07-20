<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
$stage = get_survey_stage($pdo, $_SESSION['user_id']);

$stmt = $pdo->prepare('SELECT agency_name, submitted_at FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$singleSurvey = $_GET['survey'] ?? null;

if ($singleSurvey === 'app' || $singleSurvey === 'ict') {
    if ($singleSurvey === 'app') {
        $title = 'List of Application Systems';
        $entriesStmt = $pdo->prepare('SELECT * FROM application_systems WHERE user_id = :id ORDER BY id ASC');
    } else {
        $title = 'List of ICT Projects';
        $entriesStmt = $pdo->prepare('SELECT * FROM ict_projects WHERE user_id = :id ORDER BY id ASC');
    }
    $entriesStmt->execute(['id' => $_SESSION['user_id']]);
    $entries = $entriesStmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — ICT Systems Registry</title>
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
        <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
      </div>
    </header>

    <div class="max-w-5xl mx-auto px-6 py-10">

      <div class="text-center mb-8">
        <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Your Entries</p>
        <h1 class="font-display text-2xl md:text-3xl text-ledger-navy"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
      </div>

      <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto mb-8">
        <table class="w-full text-xs">
          <thead>
            <tr class="bg-ledger-navy text-white text-left">
              <?php if ($singleSurvey === 'app'): ?>
                <th class="px-4 py-3 font-semibold">Application</th>
                <th class="px-4 py-3 font-semibold">Strategy</th>
                <th class="px-4 py-3 font-semibold">Mode</th>
                <th class="px-4 py-3 font-semibold">Users</th>
                <th class="px-4 py-3 font-semibold">Status</th>
              <?php else: ?>
                <th class="px-4 py-3 font-semibold">Project</th>
                <th class="px-4 py-3 font-semibold">Description</th>
                <th class="px-4 py-3 font-semibold">Cost</th>
                <th class="px-4 py-3 font-semibold">Provider</th>
                <th class="px-4 py-3 font-semibold">Status</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr class="border-t border-ledger-line">
              <?php if ($singleSurvey === 'app'): ?>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['application_name_version'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['development_strategy'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['mode_of_implementation'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= $entry['no_of_users'] !== null ? htmlspecialchars($entry['no_of_users'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
              <?php else: ?>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['project_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= $entry['project_contract_cost'] !== null ? number_format((float) $entry['project_contract_cost'], 2) : '—' ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['third_party_provider'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($entries)): ?>
            <tr><td colspan="5" class="px-4 py-6 text-center text-ledger-muted">No entries yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <a href="survey.php" class="block text-center w-full border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-white transition-colors">
        BACK TO DASHBOARD
      </a>

    </div>

    </body>
    </html>
    <?php
    exit;
}

if ($stage !== 'review' && $stage !== 'submitted') {
    header('Location: ' . stage_redirect_target($stage));
    exit;
}

$appSystemsStmt = $pdo->prepare('SELECT * FROM application_systems WHERE user_id = :id ORDER BY id ASC');
$appSystemsStmt->execute(['id' => $_SESSION['user_id']]);
$appSystems = $appSystemsStmt->fetchAll();

$ictProjectsStmt = $pdo->prepare('SELECT * FROM ict_projects WHERE user_id = :id ORDER BY id ASC');
$ictProjectsStmt->execute(['id' => $_SESSION['user_id']]);
$ictProjects = $ictProjectsStmt->fetchAll();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review &amp; Submit — ICT Systems Registry</title>
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
    <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
  </div>
</header>

<div class="max-w-5xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">
      <?= $stage === 'submitted' ? 'Submitted' : 'Final Review' ?>
    </p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">
      <?= $stage === 'submitted' ? 'Your survey has been submitted' : 'Review both surveys before submitting' ?>
    </h1>
    <?php if ($stage === 'submitted' && $user['submitted_at']): ?>
      <p class="text-sm text-ledger-muted mt-1">Submitted on <?= htmlspecialchars(date('F j, Y g:i A', strtotime($user['submitted_at'])), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if ($stage === 'submitted'): ?>
    <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-8" role="status">
      Thank you. Your responses for both surveys have been recorded and can no longer be edited.
    </div>
  <?php endif; ?>

  <div class="mb-10">
    <h2 class="font-display text-xl text-ledger-navy mb-4">Survey 1 — List of Application Systems (<?= count($appSystems) ?>)</h2>
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
          <tr><td colspan="5" class="px-4 py-6 text-center text-ledger-muted">No entries.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mb-10">
    <h2 class="font-display text-xl text-ledger-navy mb-4">Survey 2 — List of ICT Projects (<?= count($ictProjects) ?>)</h2>
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
          <tr><td colspan="5" class="px-4 py-6 text-center text-ledger-muted">No entries.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($stage === 'review'): ?>
    <form action="submit-confirm.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
        CONFIRM AND SUBMIT ALL
      </button>
    </form>
  <?php else: ?>
    <a href="survey.php" class="block text-center w-full border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-white transition-colors">
      BACK TO DASHBOARD
    </a>
  <?php endif; ?>

</div>

</body>
</html>