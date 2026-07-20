<?php
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';

function backToIndex(array $errors = [], string $success = ''): void
{
    if (!empty($errors)) {
        $_SESSION['admin_errors'] = $errors;
    }
    if ($success !== '') {
        $_SESSION['admin_success'] = $success;
    }
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    backToIndex();
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    backToIndex(['Your session expired. Please try again.']);
}

$targetId = (int) ($_POST['user_id'] ?? 0);

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT id, agency_name, is_admin FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $targetId]);
$target = $stmt->fetch();

if (!$target) {
    backToIndex(['Account not found.']);
}

$isCurrentlyAdmin = (int) $target['is_admin'] === 1;

if ($isCurrentlyAdmin) {
    $countStmt = $pdo->query('SELECT COUNT(*) AS total FROM users WHERE is_admin = 1');
    $adminCount = (int) $countStmt->fetch()['total'];

    if ($adminCount <= 1) {
        backToIndex(['At least one admin account must remain.']);
    }
}

$newStatus = $isCurrentlyAdmin ? 0 : 1;

$updateStmt = $pdo->prepare('UPDATE users SET is_admin = :status WHERE id = :id');
$updateStmt->execute(['status' => $newStatus, 'id' => $targetId]);

backToIndex([], $target['agency_name'] . ' has been ' . ($newStatus === 1 ? 'granted admin access' : 'set back to a regular user') . '.');