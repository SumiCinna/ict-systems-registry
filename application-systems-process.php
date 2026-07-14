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

$pdoGuard = getDbConnection();
require_stage($pdoGuard, $_SESSION['user_id'], 'systems');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: application-systems.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToForm(['Your session expired. Please try again.'], []);
}

$rawEntries = $_POST['entries'] ?? [];

if (!is_array($rawEntries) || count($rawEntries) === 0) {
    backToForm(['Add at least one application system.'], []);
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
$cleanEntries = [];
$oldEntries = [];

$i = 0;
foreach ($rawEntries as $entry) {
    $i++;
    $applicationName = trim($entry['application_name_version'] ?? '');
    $dateOfImplementation = trim($entry['date_of_implementation'] ?? '');
    $developmentStrategy = trim($entry['development_strategy'] ?? '');
    $ownsIp = trim($entry['owns_ip'] ?? '');
    $modeOfImplementation = trim($entry['mode_of_implementation'] ?? '');
    $acquisitionCost = trim($entry['acquisition_cost'] ?? '');
    $annualMaintenanceCost = trim($entry['annual_maintenance_cost'] ?? '');
    $annualTransactionAmount = trim($entry['annual_transaction_amount'] ?? '');
    $noOfUsers = trim($entry['no_of_users'] ?? '');
    $typeOfInformation = trim($entry['type_of_information'] ?? '');
    $scopeOfOperation = trim($entry['scope_of_operation'] ?? '');
    $status = trim($entry['status'] ?? '');

    $oldEntries[] = [
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

    if (!validate_free_text($applicationName, 2, 191)) {
        $errors[] = "System $i: Name of Application and Version No. is required.";
    }
    if (!validate_date($dateOfImplementation)) {
        $errors[] = "System $i: Date of Implementation must be a valid date.";
    }
    if (!validate_choice($developmentStrategy, $developmentStrategyChoices)) {
        $errors[] = "System $i: Development Strategy is required.";
    }
    if (!validate_choice($ownsIp, $ownsIpChoices)) {
        $errors[] = "System $i: Own Intellectual Property is required.";
    }
    if (!validate_choice($modeOfImplementation, $modeChoices)) {
        $errors[] = "System $i: Mode of Implementation is required.";
    }
    if (!validate_decimal($acquisitionCost)) {
        $errors[] = "System $i: Acquisition Cost must be a valid amount.";
    }
    if (!validate_decimal($annualMaintenanceCost)) {
        $errors[] = "System $i: Annual Maintenance Cost must be a valid amount.";
    }
    if (!validate_decimal($annualTransactionAmount)) {
        $errors[] = "System $i: Annual Transaction Amount must be a valid amount.";
    }
    if (!validate_integer($noOfUsers)) {
        $errors[] = "System $i: No. of Users must be a whole number.";
    }
    if (!validate_choice($typeOfInformation, $typeChoices)) {
        $errors[] = "System $i: Type of Information Collected is required.";
    }
    if (!validate_choice($scopeOfOperation, $scopeChoices)) {
        $errors[] = "System $i: Scope of Operation is required.";
    }
    if (!validate_choice($status, $statusChoices)) {
        $errors[] = "System $i: Status is required.";
    }

    $cleanEntries[] = [
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
    ];
}

if (!empty($errors)) {
    backToForm($errors, $oldEntries);
}

$pdo = getDbConnection();

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

    $pdo->beginTransaction();

    foreach ($cleanEntries as $entry) {
        $insertStmt->execute([
            'user_id' => $_SESSION['user_id'],
            'agency_name' => $user['agency_name'],
            'application_name_version' => $entry['application_name_version'],
            'date_of_implementation' => $entry['date_of_implementation'],
            'development_strategy' => $entry['development_strategy'],
            'owns_ip' => $entry['owns_ip'],
            'mode_of_implementation' => $entry['mode_of_implementation'],
            'acquisition_cost' => $entry['acquisition_cost'],
            'annual_maintenance_cost' => $entry['annual_maintenance_cost'],
            'annual_transaction_amount' => $entry['annual_transaction_amount'],
            'no_of_users' => $entry['no_of_users'],
            'type_of_information' => $entry['type_of_information'],
            'scope_of_operation' => $entry['scope_of_operation'],
            'status' => $entry['status'],
        ]);
    }

    $pdo->commit();

    $_SESSION['flash_success'] = count($cleanEntries) . ' application system(s) saved.';
    header('Location: application-systems-summary.php');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Application systems insert failed: ' . $e->getMessage());
    backToForm(['Something went wrong while saving your entries. Please try again.'], $oldEntries);
}