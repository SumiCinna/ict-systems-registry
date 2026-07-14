<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_stage($pdo, $_SESSION['user_id'], 'systems');

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$entriesStmt = $pdo->prepare('SELECT * FROM application_systems WHERE user_id = :id ORDER BY id ASC');
$entriesStmt->execute(['id' => $_SESSION['user_id']]);
$entries = $entriesStmt->fetchAll();

$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

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
<title>Survey 1 Summary — ICT Systems Registry</title>
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

<div class="max-w-4xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Survey 1 Summary</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">List of Application Systems</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if ($success): ?>
    <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-6" role="status">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto mb-8">
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
        <?php foreach ($entries as $entry): ?>
        <tr class="border-t border-ledger-line">
          <td class="px-4 py-3"><?= htmlspecialchars($entry['application_name_version'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['development_strategy'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['mode_of_implementation'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= $entry['no_of_users'] !== null ? htmlspecialchars($entry['no_of_users'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($entries)): ?>
        <tr>
          <td colspan="5" class="px-4 py-6 text-center text-ledger-muted">No entries yet.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-center text-sm text-ledger-muted mb-6"><?= count($entries) ?> entr<?= count($entries) === 1 ? 'y' : 'ies' ?> recorded so far.</p>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <a href="application-systems.php" class="text-center border-2 border-dashed border-ledger-line text-ledger-steel text-sm font-semibold tracking-wide py-3 hover:border-ledger-steel hover:bg-white transition-colors">
      SUBMIT ANOTHER ENTRY
    </a>
    <form action="application-systems-confirm.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors" <?= empty($entries) ? 'disabled' : '' ?>>
        CONFIRM &amp; CONTINUE TO SURVEY 2
      </button>
    </form>
  </div>

</div>

</body>
</html>