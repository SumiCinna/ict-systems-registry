<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validators.php';
require_once __DIR__ . '/includes/survey_flow.php';

$pdo = getDbConnection();
require_survey_access($pdo, $_SESSION['user_id'], 'projects');

$returnTo = (($_GET['return_to'] ?? $_POST['return_to'] ?? '') === 'review') ? 'review' : 'summary';
$returnUrl = $returnTo === 'review' ? 'review.php' : 'ict-projects-summary.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM ict_projects WHERE id = :id AND user_id = :user_id LIMIT 1');
$stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);
$entry = $stmt->fetch();

if (!$entry) {
    header('Location: ' . $returnUrl);
    exit;
}

$statusChoices = ['Ongoing', 'Completed'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $projectName = trim($_POST['project_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDate = trim($_POST['start_date'] ?? '');
        $endDate = trim($_POST['end_date'] ?? '');
        $projectContractCost = trim($_POST['project_contract_cost'] ?? '');
        $thirdPartyProvider = trim($_POST['third_party_provider'] ?? '');
        $fundingSource = trim($_POST['funding_source'] ?? '');
        $status = trim($_POST['status'] ?? '');

        $entry = array_merge($entry, [
            'project_name' => $projectName,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'project_contract_cost' => $projectContractCost,
            'third_party_provider' => $thirdPartyProvider,
            'funding_source' => $fundingSource,
            'status' => $status,
        ]);

        if (!validate_free_text($projectName, 2, 191)) {
            $errors[] = 'Project Name is required.';
        }
        if (!validate_free_text($description, 2, 255)) {
            $errors[] = 'Description is required.';
        }
        if (!validate_date($startDate)) {
            $errors[] = 'Start Date must be a valid date.';
        }
        if (!validate_date($endDate)) {
            $errors[] = 'End Date must be a valid date.';
        }
        if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
            $errors[] = 'End Date cannot be before Start Date.';
        }
        if (!validate_decimal($projectContractCost)) {
            $errors[] = 'Project/Contract Cost must be a valid amount.';
        }
        if ($thirdPartyProvider !== '' && !validate_free_text($thirdPartyProvider, 1, 191)) {
            $errors[] = 'Third Party Service Provider is too long.';
        }
        if ($fundingSource !== '' && !validate_free_text($fundingSource, 1, 191)) {
            $errors[] = 'Funding Source is too long.';
        }
        if (!validate_choice($status, $statusChoices)) {
            $errors[] = 'Status is required.';
        }

        if (empty($errors)) {
            $updateStmt = $pdo->prepare(
                'UPDATE ict_projects SET
                    project_name = :project_name,
                    description = :description,
                    start_date = :start_date,
                    end_date = :end_date,
                    project_contract_cost = :project_contract_cost,
                    third_party_provider = :third_party_provider,
                    funding_source = :funding_source,
                    status = :status
                 WHERE id = :id AND user_id = :user_id'
            );
            $updateStmt->execute([
                'project_name' => $projectName,
                'description' => $description,
                'start_date' => $startDate !== '' ? $startDate : null,
                'end_date' => $endDate !== '' ? $endDate : null,
                'project_contract_cost' => $projectContractCost !== '' ? $projectContractCost : null,
                'third_party_provider' => $thirdPartyProvider !== '' ? $thirdPartyProvider : null,
                'funding_source' => $fundingSource !== '' ? $fundingSource : null,
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
<title>Edit ICT Project — ICT Systems Registry</title>
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
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">List of ICT Projects</p>
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

  <form action="edit-ict-project.php?id=<?= $id ?>&return_to=<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>" method="POST" class="bg-white border border-ledger-line shadow-sm p-8">
    <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

      <div>
        <label class="field-label">Project Name <span class="text-ledger-gold">*</span></label>
        <input type="text" name="project_name" required maxlength="191"
               value="<?= ev($entry, 'project_name') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Description <span class="text-ledger-gold">*</span></label>
        <input type="text" name="description" required maxlength="255"
               value="<?= ev($entry, 'description') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Start Date</label>
        <input type="date" name="start_date"
               value="<?= ev($entry, 'start_date') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">End Date</label>
        <input type="date" name="end_date"
               value="<?= ev($entry, 'end_date') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Project/Contract Cost</label>
        <input type="number" step="0.01" min="0" name="project_contract_cost"
               value="<?= ev($entry, 'project_contract_cost') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Third Party Service Provider</label>
        <input type="text" name="third_party_provider" maxlength="191"
               value="<?= ev($entry, 'third_party_provider') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Funding Source</label>
        <input type="text" name="funding_source" maxlength="191"
               value="<?= ev($entry, 'funding_source') ?>"
               class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
      </div>

      <div>
        <label class="field-label">Status <span class="text-ledger-gold">*</span></label>
        <select name="status" required
                class="ledger-input w-full border border-ledger-line rounded-sm px-3 py-2 bg-white mt-2 focus:outline-none focus:ring-2 focus:ring-ledger-steel focus:border-ledger-steel">
          <option value="Ongoing" <?= esel($entry, 'status', 'Ongoing') ?>>Ongoing</option>
          <option value="Completed" <?= esel($entry, 'status', 'Completed') ?>>Completed</option>
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