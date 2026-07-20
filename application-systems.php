<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_not_submitted($pdo, $_SESSION['user_id'], 'systems');

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$errors = $_SESSION['appsys_errors'] ?? [];
$oldEntries = $_SESSION['appsys_old'] ?? [];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['appsys_errors'], $_SESSION['appsys_old'], $_SESSION['flash_success']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if (empty($oldEntries)) {
    $oldEntries = [[
        'application_name_version' => '',
        'date_of_implementation' => '',
        'development_strategy' => '',
        'owns_ip' => '',
        'mode_of_implementation' => '',
        'acquisition_cost' => '',
        'annual_maintenance_cost' => '',
        'annual_transaction_amount' => '',
        'no_of_users' => '',
        'type_of_information' => '',
        'scope_of_operation' => '',
        'status' => '',
    ]];
}

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
<script src="assets/js/survey-form.js" defer></script>
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

<div class="max-w-5xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Survey 1</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">List of Application Systems</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if ($success): ?>
    <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-6" role="status">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

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

  <form action="application-systems-process.php" method="POST" id="appSysForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div id="entriesContainer" class="space-y-6">
      <?php foreach ($oldEntries as $i => $entry): ?>
      <div class="entry-card bg-white border border-ledger-line shadow-sm p-8 relative">
        <div class="flex items-center justify-between mb-6">
          <span class="entry-label text-[10px] font-semibold tracking-[0.2em] uppercase text-ledger-gold">System <?= $i + 1 ?></span>
          <button type="button" class="remove-entry text-xs font-semibold text-red-600 hover:text-red-800 <?= count($oldEntries) <= 1 ? 'hidden' : '' ?>">
            REMOVE
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

          <div class="sm:col-span-2">
            <label class="field-label">Name of Application and Version No. <span class="text-ledger-gold">*</span></label>
            <input type="text" name="entries[<?= $i ?>][application_name_version]" required maxlength="191"
                   value="<?= v($entry, 'application_name_version') ?>"
                   class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                   placeholder="e.g. Payroll System v2.1">
          </div>

          <div>
            <label class="field-label">Date of Implementation</label>
            <input type="date" name="entries[<?= $i ?>][date_of_implementation]"
                   value="<?= v($entry, 'date_of_implementation') ?>"
                   class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          </div>

          <div>
            <label class="field-label">Development Strategy <span class="text-ledger-gold">*</span></label>
            <select name="entries[<?= $i ?>][development_strategy]" required
                    class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
              <option value="" disabled <?= v($entry, 'development_strategy') === '' ? 'selected' : '' ?>>Select one</option>
              <option value="In-house" <?= sel($entry, 'development_strategy', 'In-house') ?>>In-house</option>
              <option value="Outsourced" <?= sel($entry, 'development_strategy', 'Outsourced') ?>>Outsourced</option>
              <option value="Combination" <?= sel($entry, 'development_strategy', 'Combination') ?>>Combination</option>
            </select>
          </div>

          <div>
            <label class="field-label">Own Intellectual Property, Yes or No <span class="text-ledger-gold">*</span></label>
            <select name="entries[<?= $i ?>][owns_ip]" required
                    class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
              <option value="" disabled <?= v($entry, 'owns_ip') === '' ? 'selected' : '' ?>>Select one</option>
              <option value="Yes" <?= sel($entry, 'owns_ip', 'Yes') ?>>Yes</option>
              <option value="No" <?= sel($entry, 'owns_ip', 'No') ?>>No</option>
            </select>
          </div>

          <div>
            <label class="field-label">Mode of Implementation <span class="text-ledger-gold">*</span></label>
            <select name="entries[<?= $i ?>][mode_of_implementation]" required
                    class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
              <option value="" disabled <?= v($entry, 'mode_of_implementation') === '' ? 'selected' : '' ?>>Select one</option>
              <option value="Stand Alone" <?= sel($entry, 'mode_of_implementation', 'Stand Alone') ?>>Stand Alone</option>
              <option value="LAN" <?= sel($entry, 'mode_of_implementation', 'LAN') ?>>LAN</option>
              <option value="WAN" <?= sel($entry, 'mode_of_implementation', 'WAN') ?>>WAN</option>
              <option value="Web-based" <?= sel($entry, 'mode_of_implementation', 'Web-based') ?>>Web-based</option>
            </select>
          </div>

          <div>
            <label class="field-label">Acquisition Cost (Contract Cost)</label>
            <input type="number" step="0.01" min="0" name="entries[<?= $i ?>][acquisition_cost]"
                   value="<?= v($entry, 'acquisition_cost') ?>"
                   class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                   placeholder="e.g. 500000.00">
          </div>

          <div>
            <label class="field-label">Annual Maintenance Cost</label>
            <input type="number" step="0.01" min="0" name="entries[<?= $i ?>][annual_maintenance_cost]"
                   value="<?= v($entry, 'annual_maintenance_cost') ?>"
                   class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                   placeholder="e.g. 50000.00">
          </div>

          <div class="sm:col-span-2">
            <label class="field-label">Annual Transaction Amount</label>
            <input type="number" step="0.01" min="0" name="entries[<?= $i ?>][annual_transaction_amount]"
                   value="<?= v($entry, 'annual_transaction_amount') ?>"
                   class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                   placeholder="* example: Collection system - total annual collection processed through the system">
          </div>

          <div>
            <label class="field-label">No. of Users</label>
            <input type="number" step="1" min="0" name="entries[<?= $i ?>][no_of_users]"
                   value="<?= v($entry, 'no_of_users') ?>"
                   class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
                   placeholder="e.g. 150">
          </div>

          <div>
            <label class="field-label">Type of Information Collected <span class="text-ledger-gold">*</span></label>
            <select name="entries[<?= $i ?>][type_of_information]" required
                    class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
              <option value="" disabled <?= v($entry, 'type_of_information') === '' ? 'selected' : '' ?>>Select one</option>
              <option value="External/Public" <?= sel($entry, 'type_of_information', 'External/Public') ?>>External/Public</option>
              <option value="Internal/Agency Data" <?= sel($entry, 'type_of_information', 'Internal/Agency Data') ?>>Internal/Agency Data</option>
            </select>
          </div>

          <div>
            <label class="field-label">Scope of Operation <span class="text-ledger-gold">*</span></label>
            <select name="entries[<?= $i ?>][scope_of_operation]" required
                    class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
              <option value="" disabled <?= v($entry, 'scope_of_operation') === '' ? 'selected' : '' ?>>Select one</option>
              <option value="International" <?= sel($entry, 'scope_of_operation', 'International') ?>>International</option>
              <option value="Nation-wide" <?= sel($entry, 'scope_of_operation', 'Nation-wide') ?>>Nation-wide</option>
              <option value="Province" <?= sel($entry, 'scope_of_operation', 'Province') ?>>Province</option>
              <option value="Municipal/City" <?= sel($entry, 'scope_of_operation', 'Municipal/City') ?>>Municipal/City</option>
            </select>
          </div>

          <div class="sm:col-span-2">
            <label class="field-label">Status <span class="text-ledger-gold">*</span></label>
            <select name="entries[<?= $i ?>][status]" required
                    class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
              <option value="" disabled <?= v($entry, 'status') === '' ? 'selected' : '' ?>>Select one</option>
              <option value="Fully implemented" <?= sel($entry, 'status', 'Fully implemented') ?>>Fully implemented</option>
              <option value="Not fully rolled out yet, but with pilot implementation" <?= sel($entry, 'status', 'Not fully rolled out yet, but with pilot implementation') ?>>Not fully rolled out yet, but with pilot implementation</option>
              <option value="Ongoing development and testing" <?= sel($entry, 'status', 'Ongoing development and testing') ?>>Ongoing development and testing</option>
              <option value="Not utilized" <?= sel($entry, 'status', 'Not utilized') ?>>Not utilized</option>
            </select>
          </div>

        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <button type="submit"
            class="mt-8 w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
      SUBMIT
    </button>
  </form>

</div>