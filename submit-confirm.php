<?php
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/survey_flow.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php');
    exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: review.php');
    exit;
}

$pdo = getDbConnection();
$progress = get_survey_progress($pdo, $_SESSION['user_id']);

if (!$progress['both_done']) {
    header('Location: survey.php');
    exit;
}

require_stage($pdo, $_SESSION['user_id'], 'review');

set_survey_stage($pdo, $_SESSION['user_id'], 'submitted');

$_SESSION['flash_success'] = 'Your forms have been recorded. Thank you for completing the survey.';
header('Location: survey.php');
exit;