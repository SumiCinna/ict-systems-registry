<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_survey_access($pdo, $_SESSION['user_id'], 'projects');
$isSubmittedAccount = get_user_flow($pdo, $_SESSION['user_id'])['stage'] === 'submitted';

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$errors = $_SESSION['ictproj_errors'] ?? [];
$old = $_SESSION['ictproj_old'] ?? [];
unset($_SESSION['ictproj_errors'], $_SESSION['ictproj_old']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function v(array $entry, string $key): string
{
    return htmlspecialchars($entry[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

function sel(array $entry, string $key, string $option): string
{
    return ($entry[$key] ?? '') === $option ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>List of ICT Projects — ICT Systems Registry</title>
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
      <?php if ($isSubmittedAccount): ?>
      <a href="survey.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">
        BACK TO SURVEYS
      </a>
      <?php endif; ?>
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">
        LOG OUT
      </a>
    </div>
  </div>
</header>

<div class="max-w-3xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">ICT Project Entry</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">List of Information and Communications Technology (ICT) Projects</h1>
    <p class="text-sm text-ledger-muted mt-1">Ongoing and Completed</p>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <div class="border border-ledger-line bg-white text-xs text-ledger-muted px-5 py-4 mb-8">
    Illustration: If a project has multiple contracts with different service providers, submit each one as a separate entry.
  </div>

  <?php if (!empty($errors)): ?>
    <div class="border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 mb-6" role="alert">
      <p class="font-semibold mb-1">Please correct the following:</p>
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="ict-projects-process.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="bg-white border border-ledger-line shadow-sm p-8">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <div>
          <label class="field-label">Project Name <span class="text-ledger-gold">*</span></label>
          <input type="text" name="project_name" required maxlength="191"
                 value="<?= v($old, 'project_name') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. Digitalization Project">
        </div>

        <div>
          <label class="field-label">Description <span class="text-ledger-gold">*</span></label>
          <input type="text" name="description" required maxlength="255"
                 value="<?= v($old, 'description') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. Network Management">
        </div>

        <div>
          <label class="field-label">Start Date</label>
          <input type="date" name="start_date"
                 value="<?= v($old, 'start_date') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
        </div>

        <div>
          <label class="field-label">End Date</label>
          <input type="date" name="end_date"
                 value="<?= v($old, 'end_date') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
        </div>

        <div>
          <label class="field-label">Project/Contract Cost</label>
          <input type="number" step="0.01" min="0" name="project_contract_cost"
                 value="<?= v($old, 'project_contract_cost') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. 3000000.00">
        </div>

        <div>
          <label class="field-label">Third Party Service Provider</label>
          <input type="text" name="third_party_provider" maxlength="191"
                 value="<?= v($old, 'third_party_provider') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. Company A">
        </div>

        <div>
          <label class="field-label">Funding Source</label>
          <input type="text" name="funding_source" maxlength="191"
                 value="<?= v($old, 'funding_source') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. General Fund">
        </div>

        <div>
          <label class="field-label">Status <span class="text-ledger-gold">*</span></label>
          <select name="status" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'status') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="Ongoing" <?= sel($old, 'status', 'Ongoing') ?>>Ongoing</option>
            <option value="Completed" <?= sel($old, 'status', 'Completed') ?>>Completed</option>
          </select>
        </div>

      </div>
    </div>

    <button type="submit"
            class="mt-8 w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
      SUBMIT
    </button>
  </form>

</div>

</body>
</html>