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

$pdo = getDbConnection();
require_survey_access($pdo, $_SESSION['user_id'], 'projects');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ict-projects.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToForm(['Your session expired. Please try again.'], []);
}

$statusChoices = ['Ongoing', 'Completed'];

$projectName = trim($_POST['project_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$startDate = trim($_POST['start_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');
$projectContractCost = trim($_POST['project_contract_cost'] ?? '');
$thirdPartyProvider = trim($_POST['third_party_provider'] ?? '');
$fundingSource = trim($_POST['funding_source'] ?? '');
$status = trim($_POST['status'] ?? '');

$old = [
    'project_name' => $projectName,
    'description' => $description,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'project_contract_cost' => $projectContractCost,
    'third_party_provider' => $thirdPartyProvider,
    'funding_source' => $fundingSource,
    'status' => $status,
];

$errors = [];

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

if (!empty($errors)) {
    backToForm($errors, $old);
}

try {
    $insertStmt = $pdo->prepare(
        'INSERT INTO ict_projects
            (user_id, project_name, description, start_date, end_date,
             project_contract_cost, third_party_provider, funding_source, status)
         VALUES
            (:user_id, :project_name, :description, :start_date, :end_date,
             :project_contract_cost, :third_party_provider, :funding_source, :status)'
    );

    $insertStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'project_name' => $projectName,
        'description' => $description,
        'start_date' => $startDate !== '' ? $startDate : null,
        'end_date' => $endDate !== '' ? $endDate : null,
        'project_contract_cost' => $projectContractCost !== '' ? $projectContractCost : null,
        'third_party_provider' => $thirdPartyProvider !== '' ? $thirdPartyProvider : null,
        'funding_source' => $fundingSource !== '' ? $fundingSource : null,
        'status' => $status,
    ]);

    $_SESSION['flash_success'] = 'ICT project saved.';

    if (get_user_flow($pdo, $_SESSION['user_id'])['stage'] === 'submitted') {
        header('Location: survey.php');
        exit;
    }

    header('Location: ict-projects-summary.php');
    exit;

} catch (PDOException $e) {
    error_log('ICT projects insert failed: ' . $e->getMessage());
    backToForm(['Something went wrong while saving your entry. Please try again.'], $old);
}