<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: survey.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: survey.php');
    exit;
}

$type = $_POST['type'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0 || !in_array($type, ['app', 'ict'], true)) {
    header('Location: survey.php');
    exit;
}

$pdo = getDbConnection();
require_not_submitted($pdo, $_SESSION['user_id']);

$table = $type === 'app' ? 'application_systems' : 'ict_projects';
$stmt = $pdo->prepare("DELETE FROM {$table} WHERE id = :id AND user_id = :user_id");
$stmt->execute(['id' => $id, 'user_id' => $_SESSION['user_id']]);

$_SESSION['flash_success'] = 'Entry deleted successfully.';
header('Location: ' . ($type === 'app' ? 'application-systems-summary.php' : 'ict-projects-summary.php'));
exit;
