<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ict-projects-summary.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: ict-projects-summary.php');
    exit;
}

$pdo = getDbConnection();
require_not_submitted($pdo, $_SESSION['user_id']);
enforce_survey_lock($pdo, $_SESSION['user_id'], 'ict');

$countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
$countStmt->execute(['id' => $_SESSION['user_id']]);
$count = (int) $countStmt->fetch()['total'];

if ($count === 0) {
    header('Location: ict-projects-summary.php');
    exit;
}

mark_survey_done($pdo, $_SESSION['user_id'], 'ict');

$progress = get_survey_progress($pdo, $_SESSION['user_id']);
header('Location: ' . ($progress['app_done'] ? 'review.php' : 'application-systems.php'));
exit;