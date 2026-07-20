<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validators.php';
require_once __DIR__ . '/includes/survey_flow.php';

function backToForm(array $errors, array $old): void
{
    $_SESSION['ictproj_errors'] = $errors;
    $_SESSION['ictproj_old'] = $old;
    header('Location: ict-projects.php');
    exit;
}

$pdoGuard = getDbConnection();
require_not_submitted($pdoGuard, $_SESSION['user_id'], 'projects');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ict-projects.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToForm(['Your session expired. Please try again.'], []);
}

$rawEntries = $_POST['entries'] ?? [];

if (!is_array($rawEntries) || count($rawEntries) === 0) {
    backToForm(['Add at least one project.'], []);
}

$statusChoices = ['Ongoing', 'Completed'];

$errors = [];
$cleanEntries = [];
$oldEntries = [];

$i = 0;
foreach ($rawEntries as $entry) {
    $i++;
    $projectName = trim($entry['project_name'] ?? '');
    $description = trim($entry['description'] ?? '');
    $startDate = trim($entry['start_date'] ?? '');
    $endDate = trim($entry['end_date'] ?? '');
    $projectContractCost = trim($entry['project_contract_cost'] ?? '');
    $thirdPartyProvider = trim($entry['third_party_provider'] ?? '');
    $fundingSource = trim($entry['funding_source'] ?? '');
    $status = trim($entry['status'] ?? '');

    $oldEntries[] = [
        'project_name' => $projectName,
        'description' => $description,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'project_contract_cost' => $projectContractCost,
        'third_party_provider' => $thirdPartyProvider,
        'funding_source' => $fundingSource,
        'status' => $status,
    ];

    if (!validate_free_text($projectName, 2, 191)) {
        $errors[] = "Project $i: Project Name is required.";
    }
    if (!validate_free_text($description, 2, 255)) {
        $errors[] = "Project $i: Description is required.";
    }
    if (!validate_date($startDate)) {
        $errors[] = "Project $i: Start Date must be a valid date.";
    }
    if (!validate_date($endDate)) {
        $errors[] = "Project $i: End Date must be a valid date.";
    }
    if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
        $errors[] = "Project $i: End Date cannot be before Start Date.";
    }
    if (!validate_decimal($projectContractCost)) {
        $errors[] = "Project $i: Project/Contract Cost must be a valid amount.";
    }
    if ($thirdPartyProvider !== '' && !validate_free_text($thirdPartyProvider, 1, 191)) {
        $errors[] = "Project $i: Third Party Service Provider is too long.";
    }
    if ($fundingSource !== '' && !validate_free_text($fundingSource, 1, 191)) {
        $errors[] = "Project $i: Funding Source is too long.";
    }
    if (!validate_choice($status, $statusChoices)) {
        $errors[] = "Project $i: Status is required.";
    }

    $cleanEntries[] = [
        'project_name' => $projectName,
        'description' => $description,
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
        'project_contract_cost' => $projectContractCost !== '' ? $projectContractCost : null,
        'third_party_provider' => $thirdPartyProvider !== '' ? $thirdPartyProvider : null,
        'funding_source' => $fundingSource !== '' ? $fundingSource : null,
        'status' => $status,
    ];
}

if (!empty($errors)) {
    backToForm($errors, $oldEntries);
}

$pdo = getDbConnection();

try {
    $insertStmt = $pdo->prepare(
        'INSERT INTO ict_projects
            (user_id, project_name, description, start_date, end_date,
             project_contract_cost, third_party_provider, funding_source, status)
         VALUES
            (:user_id, :project_name, :description, :start_date, :end_date,
             :project_contract_cost, :third_party_provider, :funding_source, :status)'
    );

    $pdo->beginTransaction();

    foreach ($cleanEntries as $entry) {
        $insertStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'project_name' => $entry['project_name'],
            'description' => $entry['description'],
            'start_date' => $entry['start_date'],
            'end_date' => $entry['end_date'],
            'project_contract_cost' => $entry['project_contract_cost'],
            'third_party_provider' => $entry['third_party_provider'],
            'funding_source' => $entry['funding_source'],
            'status' => $entry['status'],
        ]);
    }

    $pdo->commit();

    $_SESSION['flash_success'] = count($cleanEntries) . ' ICT project(s) saved.';

    header('Location: ict-projects-summary.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('ICT projects insert failed: ' . $e->getMessage());
    backToForm(['Something went wrong while saving your entries. Please try again.'], $oldEntries);
}