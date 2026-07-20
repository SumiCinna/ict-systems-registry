<?php
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/../config/database.php';

$adminGuardPdo = getDbConnection();
$stmt = $adminGuardPdo->prepare('SELECT is_admin FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$adminGuardUser = $stmt->fetch();

if (!$adminGuardUser || (int) $adminGuardUser['is_admin'] !== 1) {
    header('Location: ../survey.php');
    exit;
}