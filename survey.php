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
$totalEntries = $progress['app_count'] + $progress['ict_count'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$first_type_for_guard = $progress['first_survey_type'];
$pastBatches = get_past_batches($pdo, $_SESSION['user_id']);

// Still on survey 1 (the first survey picked, not yet confirmed done): let
// the "BACK TO SURVEY" button from the entry form land here and show the
// dashboard below, so the user can see what they've entered so far. Any
// other mid-flow state (survey 2, review) still isn't a valid stopping
// point — send the user straight back into whatever comes next instead of
// showing a dashboard that leads nowhere useful there.
$onFirstSurveyInProgress = $first_type_for_guard !== null && $stage === $first_type_for_guard;
if ($stage !== 'submitted' && $totalEntries > 0 && !$onFirstSurveyInProgress) {
    header('Location: ' . current_flow_url($pdo, $_SESSION['user_id']));
    exit;
}

// Nothing submitted yet: show the choose screen. This also covers the case
// where a survey type was already picked but never actually saved — the
// person can still freely switch their pick at this point.
if ($stage !== 'submitted' && $totalEntries === 0) {
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

    <div class="max-w-5xl mx-auto px-6 py-10">

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

      <?php if (!empty($pastBatches)): ?>
      <div class="text-center mt-8">
        <a href="submission-history.php" class="text-xs font-semibold tracking-wide text-ledger-muted hover:text-ledger-navy transition-colors">
          VIEW PAST SUBMISSIONS
        </a>
      </div>
      <?php endif; ?>

    </div>

    </body>
    </html>
    <?php
    exit;
}

// Reachable once stage === 'submitted' (the real, final dashboard) or while
// still on survey 1, in progress (via the "BACK TO SURVEY" button on the
// entry form) — the real dashboard.
$first = $progress['first_survey_type'];
$secondType = $first !== null ? other_survey_type($first) : null;
$appDone = $progress['app_done'];
$ictDone = $progress['ict_done'];
$bothDone = $appDone && $ictDone;

// Per-card figures. Survey 2 may not have been started at all yet (still
// mid survey 1), in which case there's nothing to view — no link, no
// "completed" claim.
$firstDone = $first === 'systems' ? $appDone : $ictDone;
$firstCount = $first !== null ? count_entries_by_type($pdo, $_SESSION['user_id'], $first) : 0;
$firstUrl = $first !== null ? survey_summary_url($first) : null;

$secondDone = $secondType === 'systems' ? $appDone : $ictDone;
$secondCount = $secondType !== null ? count_entries_by_type($pdo, $_SESSION['user_id'], $secondType) : 0;
$secondStarted = $secondCount > 0 || $secondDone;
$secondUrl = $secondType !== null ? survey_summary_url($secondType) : null;

$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);
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

<div class="max-w-5xl mx-auto px-6 py-10">

  <div class="text-center mb-10">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Annual Survey</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Forms</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if ($success): ?>
    <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-6" role="status">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-ledger-line shadow-sm p-10">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 mb-10">
      <div class="border border-ledger-line bg-white p-8 flex flex-col">
        <p class="text-xs tracking-[0.2em] uppercase text-ledger-gold">Survey 1</p>
        <h3 class="font-display text-xl text-ledger-navy mt-1"><?= $first !== null ? htmlspecialchars(survey_label($first), ENT_QUOTES, 'UTF-8') : 'List of Application Systems' ?></h3>
        <p class="text-sm text-ledger-muted mt-1">
          <?= $firstCount ?> entr<?= $firstCount === 1 ? 'y' : 'ies' ?> &middot; <?= $firstDone ? 'completed' : 'in progress' ?>
        </p>
        <?php if ($firstUrl !== null): ?>
        <a href="<?= htmlspecialchars($firstUrl . '?view=readonly', ENT_QUOTES, 'UTF-8') ?>"
           class="mt-6 text-center w-full border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-ledger-navy hover:text-white transition-colors">
          VIEW ENTRY
        </a>
        <?php endif; ?>
      </div>

      <div class="border border-ledger-line bg-white p-8 flex flex-col">
        <p class="text-xs tracking-[0.2em] uppercase text-ledger-gold">Survey 2</p>
        <h3 class="font-display text-xl text-ledger-navy mt-1"><?= $secondType !== null ? htmlspecialchars(survey_label($secondType), ENT_QUOTES, 'UTF-8') : 'List of ICT Projects' ?></h3>
        <?php if ($secondStarted): ?>
        <p class="text-sm text-ledger-muted mt-1">
          <?= $secondCount ?> entr<?= $secondCount === 1 ? 'y' : 'ies' ?> &middot; <?= $secondDone ? 'completed' : 'in progress' ?>
        </p>
        <a href="<?= htmlspecialchars($secondUrl . '?view=readonly', ENT_QUOTES, 'UTF-8') ?>"
           class="mt-6 text-center w-full border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-ledger-navy hover:text-white transition-colors">
          VIEW ENTRY
        </a>
        <?php else: ?>
        <p class="text-sm text-ledger-muted mt-1">Not started yet</p>
        <?php endif; ?>
      </div>
    </div>

    <button type="button" id="openSubmitAnother" class="block text-center w-full bg-ledger-steel text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-navy transition-colors">
      SUBMIT ANOTHER ENTRY
    </button>

    <?php if ($bothDone): ?>
    <?php /* This used to point at review.php, which is the pre-submit
       confirmation screen — it immediately redirects back here once
       you've actually submitted, so the button looked broken. Point it
       at the real, flat, all-cycles history instead. */ ?>
    <a href="full-submission-history.php" class="block text-center w-full mt-4 border border-ledger-navy text-ledger-navy text-sm font-semibold tracking-wide py-3 hover:bg-white transition-colors">
      VIEW FULL SUBMISSION HISTORY
    </a>
    <?php endif; ?>

    <?php if (!empty($pastBatches)): ?>
    <div class="text-center mt-4">
      <a href="submission-history.php" class="text-xs font-semibold tracking-wide text-ledger-muted hover:text-ledger-navy transition-colors">
        VIEW PAST SUBMISSIONS
      </a>
    </div>
    <?php endif; ?>

  </div>

</div>

<div id="submitAnotherModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
  <div class="bg-white border border-ledger-line shadow-lg max-w-md w-full p-6">
    <h2 class="font-display text-lg text-ledger-navy">Submit another entry</h2>
    <p class="text-sm text-ledger-muted mt-2">Choose which form you want to fill up.</p>
    <div class="mt-6 flex flex-col gap-3">
      <form action="choose-survey.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="type" value="systems">
        <button type="submit" class="text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
          Application Systems
        </button>
      </form>
      <form action="choose-survey.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="type" value="projects">
        <button type="submit" class="text-center w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
          ICT Projects
        </button>
      </form>
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