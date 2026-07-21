<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';

$returnTo = ($_POST['return_to'] ?? '') === 'review' ? 'review.php' : 'application-systems-summary.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $returnTo);
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: ' . $returnTo);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

$pdo = getDbConnection();

$stmt = $pdo->prepare('DELETE FROM application_systems WHERE id = :id AND user_id = :user_id');
$stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);

$_SESSION['flash_success'] = $stmt->rowCount() > 0 ? 'Entry deleted.' : 'Entry not found.';

header('Location: ' . $returnTo);
exit;