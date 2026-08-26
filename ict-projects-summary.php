<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_summary_access($pdo, $_SESSION['user_id'], 'projects');

$pageType = 'projects';
$flow = get_user_flow($pdo, $_SESSION['user_id']);
$stage = $flow['stage'];
$isSubmitted = $stage === 'submitted';

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$currentBatch = get_active_batch($pdo, $_SESSION['user_id']);
$entriesStmt = $pdo->prepare('SELECT * FROM ict_projects WHERE user_id = :id AND batch = :batch ORDER BY id ASC');
$entriesStmt->execute(['id' => $_SESSION['user_id'], 'batch' => $currentBatch]);
$entries = $entriesStmt->fetchAll();

$progress = get_survey_progress($pdo, $_SESSION['user_id']);
$isFirstSurvey = $pageType === $progress['first_survey_type'];
$surveyLabel = $isFirstSurvey ? 'Survey 1' : 'Survey 2';
$confirmLabel = $isFirstSurvey ? 'CONFIRM &amp; CONTINUE TO SURVEY 2' : 'CONFIRM AND FINALIZE ALL ANSWERS';

$success = $_SESSION['flash_success'] ?? null;
$errors = $_SESSION['ictproj_summary_errors'] ?? [];
unset($_SESSION['flash_success'], $_SESSION['ictproj_summary_errors']);

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
<title><?= htmlspecialchars($surveyLabel, ENT_QUOTES, 'UTF-8') ?> Summary — ICT Systems Registry</title>
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
  <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
    <div>
      <p class="text-[10px] tracking-[0.25em] uppercase text-white/60">ICT Systems &amp; Projects Registry</p>
      <p class="font-display text-lg"><?= htmlspecialchars($user['agency_name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="flex items-center gap-4">
      <?php if ($isSubmitted): ?>
      <a href="survey.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">
        BACK TO SURVEYS
      </a>
      <?php endif; ?>
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
    </div>
  </div>
</header>

<div class="max-w-7xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1"><?= htmlspecialchars($surveyLabel, ENT_QUOTES, 'UTF-8') ?> Summary</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">List of ICT Projects</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if ($success): ?>
    <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-6" role="status">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 mb-6" role="alert">
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto mb-8">
    <table class="w-full text-xs">
      <thead>
        <tr class="bg-ledger-navy text-white text-left">
          <th class="px-4 py-3 font-semibold">Project</th>
          <th class="px-4 py-3 font-semibold">Description</th>
          <th class="px-4 py-3 font-semibold">Start Date</th>
          <th class="px-4 py-3 font-semibold">End Date</th>
          <th class="px-4 py-3 font-semibold">Cost</th>
          <th class="px-4 py-3 font-semibold">Provider</th>
          <th class="px-4 py-3 font-semibold">Funding Source</th>
          <th class="px-4 py-3 font-semibold">Status</th>
          <th class="px-4 py-3 font-semibold">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entries as $entry): ?>
        <tr class="border-t border-ledger-line">
          <td class="px-4 py-3"><?= htmlspecialchars($entry['project_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= $entry['start_date'] ? htmlspecialchars($entry['start_date'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
          <td class="px-4 py-3"><?= $entry['end_date'] ? htmlspecialchars($entry['end_date'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
          <td class="px-4 py-3"><?= $entry['project_contract_cost'] !== null ? number_format((float) $entry['project_contract_cost'], 2) : '—' ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['third_party_provider'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['funding_source'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3 whitespace-nowrap">
            <a href="edit-ict-project.php?id=<?= (int) $entry['id'] ?>" class="text-ledger-steel font-semibold hover:text-ledger-navy hover:underline">Edit</a>
            <button type="button" class="delete-entry-btn text-red-600 font-semibold hover:underline ml-3"
                    data-id="<?= (int) $entry['id'] ?>"
                    data-name="<?= htmlspecialchars($entry['project_name'], ENT_QUOTES, 'UTF-8') ?>">
              Delete
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($entries)): ?>
        <tr>
          <td colspan="9" class="px-4 py-6 text-center text-ledger-muted">No entries yet.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <p class="text-center text-sm text-ledger-muted mb-6"><?= count($entries) ?> entr<?= count($entries) === 1 ? 'y' : 'ies' ?> recorded so far.</p>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <a href="ict-projects.php" class="text-center border-2 border-dashed border-ledger-line text-ledger-steel text-sm font-semibold tracking-wide py-3 hover:border-ledger-steel hover:bg-white transition-colors">
      SUBMIT ANOTHER ENTRY
    </a>
    <?php if ($isSubmitted): ?>
    <a href="survey.php" class="text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
      BACK TO SURVEYS
    </a>
    <?php else: ?>
    <form action="ict-projects-confirm.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors" <?= empty($entries) ? 'disabled' : '' ?>>
        <?= $confirmLabel ?>
      </button>
    </form>
    <?php endif; ?>
  </div>

</div>

<div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white border border-ledger-line shadow-lg max-w-md w-full p-6">
    <h2 class="font-display text-lg text-ledger-navy">Delete this entry?</h2>
    <p class="text-sm text-ledger-muted mt-2">
      You're about to delete <span id="deleteEntryName" class="font-semibold text-ledger-ink"></span>. This cannot be undone.
    </p>
    <div class="mt-6 flex flex-col gap-3">
      <form action="delete-ict-project.php" method="POST" id="deleteForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" id="deleteEntryId" value="">
        <button type="submit" class="w-full bg-red-600 text-white text-sm font-semibold tracking-wide py-3 hover:bg-red-700 transition-colors">
          CONFIRM DELETE
        </button>
      </form>
      <button type="button" id="cancelDelete" class="text-center w-full border border-ledger-line text-ledger-ink text-sm font-semibold tracking-wide py-2.5 hover:bg-ledger-paper transition-colors">
        CANCEL
      </button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById('deleteModal');
    const nameEl = document.getElementById('deleteEntryName');
    const idInput = document.getElementById('deleteEntryId');
    const cancelBtn = document.getElementById('cancelDelete');

    document.querySelectorAll('.delete-entry-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        idInput.value = btn.getAttribute('data-id');
        nameEl.textContent = btn.getAttribute('data-name');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
      });
    });

    if (cancelBtn) {
      cancelBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      });
    }

    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
    });
  })();
</script>

</body>
</html>