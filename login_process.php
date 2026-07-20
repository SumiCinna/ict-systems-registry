<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/validators.php';

function backToForm(array $errors, array $old): void
{
    $_SESSION['login_errors'] = $errors;
    $_SESSION['login_old'] = $old;
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToForm(['Your session expired. Please try again.'], []);
}

$email    = trim($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');

$old = ['email' => $email];
$errors = [];

if ($email === '') {
    $errors[] = 'Email or username is required.';
}
if ($password === '') {
    $errors[] = 'Password is required.';
}

if (!empty($errors)) {
    backToForm($errors, $old);
}

$pdo = getDbConnection();

try {
    $stmt = $pdo->prepare('SELECT id, password_hash, is_disabled FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        backToForm(['The email or password you entered is incorrect.'], $old);
    }

    if ((int) $user['is_disabled'] === 1) {
        backToForm(['Your account has been disabled. Contact the administrator.'], $old);
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: survey.php');
    exit;

} catch (PDOException $e) {
    error_log('Login failed: ' . $e->getMessage());
    backToForm(['Something went wrong while signing you in. Please try again.'], $old);
}