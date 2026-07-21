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
require_survey_access($pdo, $_SESSION['user_id'], 'projects');

$countStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
$countStmt->execute(['id' => $_SESSION['user_id']]);
$count = (int) $countStmt->fetch()['total'];

if ($count === 0) {
    header('Location: ict-projects-summary.php');
    exit;
}

confirm_survey_step($pdo, $_SESSION['user_id'], 'projects');