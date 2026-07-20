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

if ($targetId === (int) $_SESSION['user_id']) {
    backToIndex(['You cannot disable your own account.']);
}

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT id, agency_name, is_disabled FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $targetId]);
$target = $stmt->fetch();

if (!$target) {
    backToIndex(['Account not found.']);
}

$newStatus = (int) $target['is_disabled'] === 1 ? 0 : 1;

$updateStmt = $pdo->prepare('UPDATE users SET is_disabled = :status WHERE id = :id');
$updateStmt->execute(['status' => $newStatus, 'id' => $targetId]);

backToIndex([], $target['agency_name'] . ' has been ' . ($newStatus === 1 ? 'disabled' : 'enabled') . '.');