<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: application-systems-summary.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: application-systems-summary.php');
    exit;
}

$pdo = getDbConnection();
require_not_submitted($pdo, $_SESSION['user_id']);
enforce_survey_lock($pdo, $_SESSION['user_id'], 'app');

$countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id');
$countStmt->execute(['id' => $_SESSION['user_id']]);
$count = (int) $countStmt->fetch()['total'];

if ($count === 0) {
    header('Location: application-systems-summary.php');
    exit;
}

mark_survey_done($pdo, $_SESSION['user_id'], 'app');

$progress = get_survey_progress($pdo, $_SESSION['user_id']);
header('Location: ' . ($progress['ict_done'] ? 'review.php' : 'ict-projects.php'));
exit;