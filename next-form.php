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

$pdo = getDbConnection();
require_not_submitted($pdo, $_SESSION['user_id']);

$from = $_POST['from'] ?? '';
if ($from === 'app') {
    mark_survey_done($pdo, $_SESSION['user_id'], 'app');
    $progress = get_survey_progress($pdo, $_SESSION['user_id']);
    if ($progress['both_done']) {
        set_survey_stage($pdo, $_SESSION['user_id'], 'review');
        header('Location: final-review.php');
    } else {
        header('Location: ict-projects.php');
    }
    exit;
}

if ($from === 'ict') {
    mark_survey_done($pdo, $_SESSION['user_id'], 'ict');
    $progress = get_survey_progress($pdo, $_SESSION['user_id']);
    if ($progress['both_done']) {
        set_survey_stage($pdo, $_SESSION['user_id'], 'review');
        header('Location: final-review.php');
    } else {
        header('Location: application-systems.php');
    }
    exit;
}

header('Location: survey.php');
exit;
