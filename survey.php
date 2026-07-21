<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT agency_name, last_name, first_name, middle_initial, position_designation FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$fullName = trim($user['first_name'] . ' ' . ($user['middle_initial'] !== null && $user['middle_initial'] !== '' ? $user['middle_initial'] . '. ' : '') . $user['last_name']);
$progress = get_survey_progress($pdo, $_SESSION['user_id']);
$stage = $progress['stage'];
$first = $progress['first_survey_type'];
$appDone = $progress['app_done'];
$ictDone = $progress['ict_done'];
$bothDone = $appDone && $ictDone;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($stage === 'choose') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a Survey — ICT Systems Registry</title>
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

    <div class="max-w-3xl mx-auto px-6 py-10">

      <div class="text-center mb-10">
        <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Annual Survey</p>
        <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Which survey would you like to start with?</h1>
        <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <form action="choose-survey.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="type" value="systems">
          <button type="submit" class="w-full text-left bg-white border border-ledger-line shadow-sm hover:border-ledger-steel hover:shadow-md transition-all p-8">
            <span class="text-[10px] font-semibold tracking-[0.2em] uppercase text-ledger-gold">Option</span>
            <h2 class="font-display text-xl text-ledger-navy mt-1 mb-2">List of Application Systems</h2>
            <p class="text-sm text-ledger-muted leading-relaxed">Start here to record your agency's application systems first.</p>
          </button>
        </form>

        <form action="choose-survey.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="type" value="projects">
          <button type="submit" class="w-full text-left bg-white border border-ledger-line shadow-sm hover:border-ledger-steel hover:shadow-md transition-all p-8">
            <span class="text-[10px] font-semibold tracking-[0.2em] uppercase text-ledger-gold">Option</span>
            <h2 class="font-display text-xl text-ledger-navy mt-1 mb-2">List of ICT Projects</h2>
            <p class="text-sm text-ledger-muted leading-relaxed">Start here to record your agency's ICT projects first.</p>
          </button>
        </form>
      </div>

    </div>

    </body>
    </html>
    <?php
    exit;
}

$secondType = $first !== null ? other_survey_type($first) : null;

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
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Forms</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <div class="bg-white border border-ledger-line shadow-sm p-10">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-10">
      <div class="border border-ledger-line bg-white p-8 flex flex-col">
        <p class="text-xs tracking-[0.2em] uppercase text-ledger-gold">Survey 1</p>
        <h3 class="font-display text-xl text-ledger-navy mt-1"><?= $first !== null ? htmlspecialchars(survey_label($first), ENT_QUOTES, 'UTF-8') : 'Not chosen yet' ?></h3>
        <p class="text-sm text-ledger-muted mt-1">
          <?= $first === 'systems' ? $appSystemsCount : ($first === 'projects' ? $ictProjectsCount : 0) ?> entries
          <?= $appDone || $ictDone ? '' : '' ?>
          <?= ($first === 'systems' && $appDone) || ($first === 'projects' && $ictDone) ? ' &middot; completed' : '' ?>
        </p>
        <?php if ($first !== null): ?>
        <a href="review.php?survey=<?= $first === 'systems' ? 'app' : 'ict' ?>"
           class="mt-6 text-center w-full border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-ledger-navy hover:text-white transition-colors">
          VIEW ENTRY
        </a>
        <?php endif; ?>
      </div>

      <div class="border border-ledger-line bg-white p-8 flex flex-col">
        <p class="text-xs tracking-[0.2em] uppercase text-ledger-gold">Survey 2</p>
        <h3 class="font-display text-xl text-ledger-navy mt-1"><?= $secondType !== null ? htmlspecialchars(survey_label($secondType), ENT_QUOTES, 'UTF-8') : 'Not started yet' ?></h3>
        <p class="text-sm text-ledger-muted mt-1">
          <?= $secondType === 'systems' ? $appSystemsCount : ($secondType === 'projects' ? $ictProjectsCount : 0) ?> entries
          <?= ($secondType === 'systems' && $appDone) || ($secondType === 'projects' && $ictDone) ? ' &middot; completed' : '' ?>
        </p>
        <?php if ($secondType !== null && $stage !== 'first'): ?>
        <a href="review.php?survey=<?= $secondType === 'systems' ? 'app' : 'ict' ?>"
           class="mt-6 text-center w-full border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-ledger-navy hover:text-white transition-colors">
          VIEW ENTRY
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($stage === 'submitted'): ?>
      <button type="button" id="openSubmitAnother" class="block text-center w-full bg-ledger-steel text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-navy transition-colors">
        SUBMIT ANOTHER ENTRY
      </button>
    <?php else: ?>
      <a href="<?= htmlspecialchars(current_flow_url($pdo, $_SESSION['user_id']), ENT_QUOTES, 'UTF-8') ?>" class="block text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
        SUBMIT
      </a>
    <?php endif; ?>

    <?php if ($bothDone): ?>
    <a href="review.php" class="block text-center w-full mt-4 border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-white transition-colors">
      VIEW FULL SUBMISSION HISTORY
    </a>
    <?php endif; ?>

  </div>

</div>

<div id="submitAnotherModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white border border-ledger-line shadow-lg max-w-md w-full p-6">
    <h2 class="font-display text-lg text-ledger-navy">Submit another entry</h2>
    <p class="text-sm text-ledger-muted mt-2">Choose which form you want to fill up.</p>
    <div class="mt-6 flex flex-col gap-3">
      <a href="application-systems.php" class="text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
        Application Systems
      </a>
      <a href="ict-projects.php" class="text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
        ICT Projects
      </a>
      <button type="button" id="cancelSubmitAnother" class="text-center w-full border border-ledger-line text-ledger-ink text-sm font-semibold tracking-wide py-2.5 hover:bg-ledger-paper transition-colors">
        CANCEL
      </button>
    </div>
  </div>
</div>

<script>
  (function () {
    const modal = document.getElementById('submitAnotherModal');
    const openBtn = document.getElementById('openSubmitAnother');
    const cancelBtn = document.getElementById('cancelSubmitAnother');

    function openModal() {
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }
    function closeModal() {
      modal.classList.add('hidden');
      modal.classList.remove('flex');
    }

    if (openBtn) {
      openBtn.addEventListener('click', openModal);
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', closeModal);
    }
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });
  })();
</script>

</body>
</html>