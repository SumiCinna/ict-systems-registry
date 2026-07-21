<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validators.php';
require_once __DIR__ . '/includes/survey_flow.php';

function backToForm(array $errors, array $old): void
{
    $_SESSION['appsys_errors'] = $errors;
    $_SESSION['appsys_old'] = $old;
    header('Location: application-systems.php');
    exit;
}

$pdo = getDbConnection();
require_survey_access($pdo, $_SESSION['user_id'], 'systems');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: application-systems.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToForm(['Your session expired. Please try again.'], []);
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

$old = [
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
];

$errors = [];

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

if (!empty($errors)) {
    backToForm($errors, $old);
}

try {
    $stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: logout.php');
        exit;
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO application_systems
            (user_id, agency_name, application_name_version, date_of_implementation, development_strategy,
             owns_ip, mode_of_implementation, acquisition_cost, annual_maintenance_cost, annual_transaction_amount,
             no_of_users, type_of_information, scope_of_operation, status)
         VALUES
            (:user_id, :agency_name, :application_name_version, :date_of_implementation, :development_strategy,
             :owns_ip, :mode_of_implementation, :acquisition_cost, :annual_maintenance_cost, :annual_transaction_amount,
             :no_of_users, :type_of_information, :scope_of_operation, :status)'
    );

    $insertStmt->execute([
        'user_id' => $_SESSION['user_id'],
        'agency_name' => $user['agency_name'],
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
    ]);

    $_SESSION['flash_success'] = 'Application system saved.';

    if (get_user_flow($pdo, $_SESSION['user_id'])['stage'] === 'submitted') {
        header('Location: survey.php');
        exit;
    }

    header('Location: application-systems-summary.php');
    exit;

} catch (PDOException $e) {
    error_log('Application systems insert failed: ' . $e->getMessage());
    backToForm(['Something went wrong while saving your entry. Please try again.'], $old);
}