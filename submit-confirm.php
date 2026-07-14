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
require_stage($pdo, $_SESSION['user_id'], 'review');

set_survey_stage($pdo, $_SESSION['user_id'], 'submitted');

header('Location: review.php');
exit;