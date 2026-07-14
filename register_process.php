<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validators.php';

function backToForm(array $errors, array $old): void
{
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old'] = $old;
    header('Location: register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// ---- CSRF check ----
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToForm(['Your session expired. Please try again.'], []);
}

// ---- Collect + trim input ----
$agencyName          = trim($_POST['agency_name'] ?? '');
$lastName            = trim($_POST['last_name'] ?? '');
$firstName           = trim($_POST['first_name'] ?? '');
$middleInitial       = trim($_POST['middle_initial'] ?? '');
$positionDesignation = trim($_POST['position_designation'] ?? '');
$telephoneNumber     = trim($_POST['telephone_number'] ?? '');
$email               = trim($_POST['email'] ?? '');
$password            = (string) ($_POST['password'] ?? '');
$confirmPassword     = (string) ($_POST['confirm_password'] ?? '');

$old = [
    'agency_name'          => $agencyName,
    'last_name'            => $lastName,
    'first_name'           => $firstName,
    'middle_initial'       => $middleInitial,
    'position_designation' => $positionDesignation,
    'telephone_number'     => $telephoneNumber,
    'email'                => $email,
];

$errors = [];

// ---- Validate ----
if (!validate_free_text($agencyName, 2, 191)) {
    $errors[] = 'Name of Agency is required.';
}
if (!validate_name($lastName)) {
    $errors[] = 'Last name is required and must contain letters only.';
}
if (!validate_name($firstName)) {
    $errors[] = 'First name is required and must contain letters only.';
}
if (!validate_middle_initial($middleInitial)) {
    $errors[] = 'Middle initial must contain letters only.';
}
if (!validate_free_text($positionDesignation, 2, 150)) {
    $errors[] = 'Position/Designation is required.';
}
if (!validate_telephone($telephoneNumber)) {
    $errors[] = 'Please provide a valid telephone number.';
}
if (!validate_email_address($email)) {
    $errors[] = 'Please provide a valid email address.';
}

$passwordFailures = password_rule_failures($password);
if (!empty($passwordFailures)) {
    $errors[] = 'Password must be 8-20 characters and include at least one uppercase letter, one lowercase letter, and one number.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Password and confirm password do not match.';
}

if (!empty($errors)) {
    backToForm($errors, $old);
}

// ---- Check for existing email, then insert ----
$pdo = getDbConnection();

try {
    $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $checkStmt->execute(['email' => $email]);

    if ($checkStmt->fetch()) {
        backToForm(['An account with this email address already exists.'], $old);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $insertStmt = $pdo->prepare(
        'INSERT INTO users (agency_name, last_name, first_name, middle_initial, position_designation, telephone_number, email, password_hash)
         VALUES (:agency_name, :last_name, :first_name, :middle_initial, :position_designation, :telephone_number, :email, :password_hash)'
    );

    $insertStmt->execute([
        'agency_name'          => $agencyName,
        'last_name'            => $lastName,
        'first_name'           => $firstName,
        'middle_initial'       => $middleInitial !== '' ? $middleInitial : null,
        'position_designation' => $positionDesignation,
        'telephone_number'     => $telephoneNumber,
        'email'                => $email,
        'password_hash'        => $passwordHash,
    ]);

    $_SESSION['user_id'] = (int) $pdo->lastInsertId();

    // Regenerate CSRF token now that the form has been used.
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $_SESSION['flash_success'] = 'Account created successfully. You can now sign in.';
    header('Location: login.php');
    exit;

} catch (PDOException $e) {
    error_log('Registration insert failed: ' . $e->getMessage());
    backToForm(['Something went wrong while creating your account. Please try again.'], $old);
}