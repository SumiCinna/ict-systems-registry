<?php
session_start();

$authGuardPrefix = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) ? '../' : '';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . $authGuardPrefix . 'login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT is_disabled FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$guardUser = $stmt->fetch();

if (!$guardUser || (int) $guardUser['is_disabled'] === 1) {
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['login_errors'] = ['Your account has been disabled. Contact the administrator.'];
    header('Location: ' . $authGuardPrefix . 'login.php');
    exit;
}