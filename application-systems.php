<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_survey_access($pdo, $_SESSION['user_id'], 'systems');

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$errors = $_SESSION['appsys_errors'] ?? [];
$old = $_SESSION['appsys_old'] ?? [];
unset($_SESSION['appsys_errors'], $_SESSION['appsys_old']);

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
<title>List of Application Systems — ICT Systems Registry</title>
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
      <a href="logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">
        LOG OUT
      </a>
    </div>
  </div>
</header>

<div class="max-w-3xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Application System Entry</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">List of Application Systems</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
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

  <form action="application-systems-process.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="bg-white border border-ledger-line shadow-sm p-8">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

        <div class="sm:col-span-2">
          <label class="field-label">Name of Application and Version No. <span class="text-ledger-gold">*</span></label>
          <input type="text" name="application_name_version" required maxlength="191"
                 value="<?= v($old, 'application_name_version') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. Payroll System v2.1">
        </div>

        <div>
          <label class="field-label">Date of Implementation</label>
          <input type="date" name="date_of_implementation"
                 value="<?= v($old, 'date_of_implementation') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
        </div>

        <div>
          <label class="field-label">Development Strategy <span class="text-ledger-gold">*</span></label>
          <select name="development_strategy" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'development_strategy') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="In-house" <?= sel($old, 'development_strategy', 'In-house') ?>>In-house</option>
            <option value="Outsourced" <?= sel($old, 'development_strategy', 'Outsourced') ?>>Outsourced</option>
            <option value="Combination" <?= sel($old, 'development_strategy', 'Combination') ?>>Combination</option>
          </select>
        </div>

        <div>
          <label class="field-label">Own Intellectual Property, Yes or No <span class="text-ledger-gold">*</span></label>
          <select name="owns_ip" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'owns_ip') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="Yes" <?= sel($old, 'owns_ip', 'Yes') ?>>Yes</option>
            <option value="No" <?= sel($old, 'owns_ip', 'No') ?>>No</option>
          </select>
        </div>

        <div>
          <label class="field-label">Mode of Implementation <span class="text-ledger-gold">*</span></label>
          <select name="mode_of_implementation" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'mode_of_implementation') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="Stand Alone" <?= sel($old, 'mode_of_implementation', 'Stand Alone') ?>>Stand Alone</option>
            <option value="LAN" <?= sel($old, 'mode_of_implementation', 'LAN') ?>>LAN</option>
            <option value="WAN" <?= sel($old, 'mode_of_implementation', 'WAN') ?>>WAN</option>
            <option value="Web-based" <?= sel($old, 'mode_of_implementation', 'Web-based') ?>>Web-based</option>
          </select>
        </div>

        <div>
          <label class="field-label">Acquisition Cost (Contract Cost)</label>
          <input type="number" step="0.01" min="0" name="acquisition_cost"
                 value="<?= v($old, 'acquisition_cost') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. 500000.00">
        </div>

        <div>
          <label class="field-label">Annual Maintenance Cost</label>
          <input type="number" step="0.01" min="0" name="annual_maintenance_cost"
                 value="<?= v($old, 'annual_maintenance_cost') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. 50000.00">
        </div>

        <div class="sm:col-span-2">
          <label class="field-label">Annual Transaction Amount</label>
          <input type="number" step="0.01" min="0" name="annual_transaction_amount"
                 value="<?= v($old, 'annual_transaction_amount') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="* example: Collection system - total annual collection processed through the system">
        </div>

        <div>
          <label class="field-label">No. of Users</label>
          <input type="number" step="1" min="0" name="no_of_users"
                 value="<?= v($old, 'no_of_users') ?>"
                 class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                 placeholder="e.g. 150">
        </div>

        <div>
          <label class="field-label">Type of Information Collected <span class="text-ledger-gold">*</span></label>
          <select name="type_of_information" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'type_of_information') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="External/Public" <?= sel($old, 'type_of_information', 'External/Public') ?>>External/Public</option>
            <option value="Internal/Agency Data" <?= sel($old, 'type_of_information', 'Internal/Agency Data') ?>>Internal/Agency Data</option>
          </select>
        </div>

        <div>
          <label class="field-label">Scope of Operation <span class="text-ledger-gold">*</span></label>
          <select name="scope_of_operation" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'scope_of_operation') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="International" <?= sel($old, 'scope_of_operation', 'International') ?>>International</option>
            <option value="Nation-wide" <?= sel($old, 'scope_of_operation', 'Nation-wide') ?>>Nation-wide</option>
            <option value="Province" <?= sel($old, 'scope_of_operation', 'Province') ?>>Province</option>
            <option value="Municipal/City" <?= sel($old, 'scope_of_operation', 'Municipal/City') ?>>Municipal/City</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="field-label">Status <span class="text-ledger-gold">*</span></label>
          <select name="status" required
                  class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
            <option value="" disabled <?= v($old, 'status') === '' ? 'selected' : '' ?>>Select one</option>
            <option value="Fully implemented" <?= sel($old, 'status', 'Fully implemented') ?>>Fully implemented</option>
            <option value="Not fully rolled out yet, but with pilot implementation" <?= sel($old, 'status', 'Not fully rolled out yet, but with pilot implementation') ?>>Not fully rolled out yet, but with pilot implementation</option>
            <option value="Ongoing development and testing" <?= sel($old, 'status', 'Ongoing development and testing') ?>>Ongoing development and testing</option>
            <option value="Not utilized" <?= sel($old, 'status', 'Not utilized') ?>>Not utilized</option>
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