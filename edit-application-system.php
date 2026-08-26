<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validators.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_survey_access($pdo, $_SESSION['user_id'], 'systems');

$returnTo = (($_GET['return_to'] ?? $_POST['return_to'] ?? '') === 'review') ? 'review' : 'summary';
$returnUrl = $returnTo === 'review' ? 'review.php' : 'application-systems-summary.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM application_systems WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);
$entry = $stmt->fetch();

if (!$entry) {
    header('Location: ' . $returnUrl);
    exit;
}

$developmentStrategyChoices = ['In-house', 'Outsourced', 'Combination'];
$ownsIpChoices = ['Yes', 'No'];
$modeChoices = ['Stand Alone', 'LAN', 'WAN', 'Web-based'];
$typeChoices = ['External/Public', 'Internal/Agency Data'];
$scopeChoices = ['International', 'Nation-wide', 'Province', 'Municipal/City'];
$statusChoices = [
    'Fully implemented',
    'Not fully rolled out yet, but with pilot implementation',
    'Ongoing development and testing',
    'Not utilized',
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $applicationName = trim($_POST['application_name_version'] ?? '');
        $dateOfImplementation = trim($_POST['date_of_implementation'] ?? '');
        $developmentStrategy = trim($_POST['development_strategy'] ?? '');
        $ownsIp = trim($_POST['owns_ip'] ?? '');
        $modeOfImplementation = trim($_POST['mode_of_implementation'] ?? '');
        $acquisitionCost = trim($_POST['acquisition_cost'] ?? '');
        $annualMaintenanceCost = trim($_POST['annual_maintenance_cost'] ?? '');
        $annualTransactionAmount = trim($_POST['annual_transaction_amount'] ?? '');
        $noOfUsers = trim($_POST['no_of_users'] ?? '');
        $typeOfInformation = trim($_POST['type_of_information'] ?? '');
        $scopeOfOperation = trim($_POST['scope_of_operation'] ?? '');
        $status = trim($_POST['status'] ?? '');

        $entry = array_merge($entry, [
            'application_name_version' => $applicationName,
            'date_of_implementation' => $dateOfImplementation,
            'development_strategy' => $developmentStrategy,
            'owns_ip' => $ownsIp,
            'mode_of_implementation' => $modeOfImplementation,
            'acquisition_cost' => $acquisitionCost,
            'annual_maintenance_cost' => $annualMaintenanceCost,
            'annual_transaction_amount' => $annualTransactionAmount,
            'no_of_users' => $noOfUsers,
            'type_of_information' => $typeOfInformation,
            'scope_of_operation' => $scopeOfOperation,
            'status' => $status,
        ]);

        if (!validate_free_text($applicationName, 2, 191)) {
            $errors[] = 'Name of Application and Version No. is required.';
        }
        if (!validate_date($dateOfImplementation)) {
            $errors[] = 'Date of Implementation must be a valid date.';
        }
        if (!validate_choice($developmentStrategy, $developmentStrategyChoices)) {
            $errors[] = 'Development Strategy is required.';
        }
        if (!validate_choice($ownsIp, $ownsIpChoices)) {
            $errors[] = 'Own Intellectual Property is required.';
        }
        if (!validate_choice($modeOfImplementation, $modeChoices)) {
            $errors[] = 'Mode of Implementation is required.';
        }
        if (!validate_decimal($acquisitionCost)) {
            $errors[] = 'Acquisition Cost must be a valid amount.';
        }
        if (!validate_decimal($annualMaintenanceCost)) {
            $errors[] = 'Annual Maintenance Cost must be a valid amount.';
        }
        if (!validate_decimal($annualTransactionAmount)) {
            $errors[] = 'Annual Transaction Amount must be a valid amount.';
        }
        if (!validate_integer($noOfUsers)) {
            $errors[] = 'No. of Users must be a whole number.';
        }
        if (!validate_choice($typeOfInformation, $typeChoices)) {
            $errors[] = 'Type of Information Collected is required.';
        }
        if (!validate_choice($scopeOfOperation, $scopeChoices)) {
            $errors[] = 'Scope of Operation is required.';
        }
        if (!validate_choice($status, $statusChoices)) {
            $errors[] = 'Status is required.';
        }

        if (empty($errors)) {
            $updateStmt = $pdo->prepare(
                'UPDATE application_systems SET
                    application_name_version = :application_name_version,
                    date_of_implementation = :date_of_implementation,
                    development_strategy = :development_strategy,
                    owns_ip = :owns_ip,
                    mode_of_implementation = :mode_of_implementation,
                    acquisition_cost = :acquisition_cost,
                    annual_maintenance_cost = :annual_maintenance_cost,
                    annual_transaction_amount = :annual_transaction_amount,
                    no_of_users = :no_of_users,
                    type_of_information = :type_of_information,
                    scope_of_operation = :scope_of_operation,
                    status = :status
                 WHERE id = :id AND user_id = :user_id'
            );
            $updateStmt->execute([
                'application_name_version' => $applicationName,
                'date_of_implementation' => $dateOfImplementation !== '' ? $dateOfImplementation : null,
                'development_strategy' => $developmentStrategy,
                'owns_ip' => $ownsIp,
                'mode_of_implementation' => $modeOfImplementation,
                'acquisition_cost' => $acquisitionCost !== '' ? $acquisitionCost : null,
                'annual_maintenance_cost' => $annualMaintenanceCost !== '' ? $annualMaintenanceCost : null,
                'annual_transaction_amount' => $annualTransactionAmount !== '' ? $annualTransactionAmount : null,
                'no_of_users' => $noOfUsers !== '' ? $noOfUsers : null,
                'type_of_information' => $typeOfInformation,
                'scope_of_operation' => $scopeOfOperation,
                'status' => $status,
                'id' => $id,
                'user_id' => $_SESSION['user_id'],
            ]);

            $_SESSION['flash_success'] = 'Entry updated.';
            header('Location: ' . $returnUrl);
            exit;
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function ev($entry, $key)
{
    return htmlspecialchars((string) ($entry[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}
function esel($entry, $key, $option)
{
    return ($entry[$key] ?? '') === $option ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Application System — ICT Systems Registry</title>
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
  <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
    <p class="font-display text-lg">Edit Entry</p>
    <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">BACK</a>
  </div>
</header>

<div class="max-w-3xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">List of Application Systems</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Edit Entry</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 mb-6" role="alert">
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="edit-application-system.php?id=<?= $id ?>&return_to=<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="bg-white border border-ledger-line shadow-sm p-8">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

      <div class="sm:col-span-2">
        <label class="field-label">Name of Application and Version No. <span class="text-ledger-gold">*</span></label>
        <input type="text" name="application_name_version" required maxlength="191"
               value="<?= ev($entry, 'application_name_version') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Date of Implementation</label>
        <input type="date" name="date_of_implementation"
               value="<?= ev($entry, 'date_of_implementation') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Development Strategy <span class="text-ledger-gold">*</span></label>
        <select name="development_strategy" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="In-house" <?= esel($entry, 'development_strategy', 'In-house') ?>>In-house</option>
          <option value="Outsourced" <?= esel($entry, 'development_strategy', 'Outsourced') ?>>Outsourced</option>
          <option value="Combination" <?= esel($entry, 'development_strategy', 'Combination') ?>>Combination</option>
        </select>
      </div>

      <div>
        <label class="field-label">Own Intellectual Property, Yes or No <span class="text-ledger-gold">*</span></label>
        <select name="owns_ip" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="Yes" <?= esel($entry, 'owns_ip', 'Yes') ?>>Yes</option>
          <option value="No" <?= esel($entry, 'owns_ip', 'No') ?>>No</option>
        </select>
      </div>

      <div>
        <label class="field-label">Mode of Implementation <span class="text-ledger-gold">*</span></label>
        <select name="mode_of_implementation" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="Stand Alone" <?= esel($entry, 'mode_of_implementation', 'Stand Alone') ?>>Stand Alone</option>
          <option value="LAN" <?= esel($entry, 'mode_of_implementation', 'LAN') ?>>LAN</option>
          <option value="WAN" <?= esel($entry, 'mode_of_implementation', 'WAN') ?>>WAN</option>
          <option value="Web-based" <?= esel($entry, 'mode_of_implementation', 'Web-based') ?>>Web-based</option>
        </select>
      </div>

      <div>
        <label class="field-label">Acquisition Cost (Contract Cost)</label>
        <input type="number" step="0.01" min="0" name="acquisition_cost"
               value="<?= ev($entry, 'acquisition_cost') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Annual Maintenance Cost</label>
        <input type="number" step="0.01" min="0" name="annual_maintenance_cost"
               value="<?= ev($entry, 'annual_maintenance_cost') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div class="sm:col-span-2">
        <label class="field-label">Annual Transaction Amount</label>
        <input type="number" step="0.01" min="0" name="annual_transaction_amount"
               value="<?= ev($entry, 'annual_transaction_amount') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel"
               placeholder="* example: Collection system - total annual collection processed through the system">
      </div>

      <div>
        <label class="field-label">No. of Users</label>
        <input type="number" step="1" min="0" name="no_of_users"
               value="<?= ev($entry, 'no_of_users') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Type of Information Collected <span class="text-ledger-gold">*</span></label>
        <select name="type_of_information" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="External/Public" <?= esel($entry, 'type_of_information', 'External/Public') ?>>External/Public</option>
          <option value="Internal/Agency Data" <?= esel($entry, 'type_of_information', 'Internal/Agency Data') ?>>Internal/Agency Data</option>
        </select>
      </div>

      <div>
        <label class="field-label">Scope of Operation <span class="text-ledger-gold">*</span></label>
        <select name="scope_of_operation" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="International" <?= esel($entry, 'scope_of_operation', 'International') ?>>International</option>
          <option value="Nation-wide" <?= esel($entry, 'scope_of_operation', 'Nation-wide') ?>>Nation-wide</option>
          <option value="Province" <?= esel($entry, 'scope_of_operation', 'Province') ?>>Province</option>
          <option value="Municipal/City" <?= esel($entry, 'scope_of_operation', 'Municipal/City') ?>>Municipal/City</option>
        </select>
      </div>

      <div class="sm:col-span-2">
        <label class="field-label">Status <span class="text-ledger-gold">*</span></label>
        <select name="status" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="Fully implemented" <?= esel($entry, 'status', 'Fully implemented') ?>>Fully implemented</option>
          <option value="Not fully rolled out yet, but with pilot implementation" <?= esel($entry, 'status', 'Not fully rolled out yet, but with pilot implementation') ?>>Not fully rolled out yet, but with pilot implementation</option>
          <option value="Ongoing development and testing" <?= esel($entry, 'status', 'Ongoing development and testing') ?>>Ongoing development and testing</option>
          <option value="Not utilized" <?= esel($entry, 'status', 'Not utilized') ?>>Not utilized</option>
        </select>
      </div>

    </div>

    <button type="submit" class="mt-8 w-full bg-ledger-navy text-white text-sm font-semibold tracking-wide py-3 hover:bg-ledger-steel transition-colors">
      SAVE CHANGES
    </button>
  </form>

</div>

</body>
</html>