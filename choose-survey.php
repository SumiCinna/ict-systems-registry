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

if ($type !== 'systems' && $type !== 'projects') {
    header('Location: survey.php');
    exit;
}

$pdo = getDbConnection();
$flow = get_user_flow($pdo, $_SESSION['user_id']);

if ($flow['stage'] === 'submitted') {
    header('Location: survey.php');
    exit;
}

if (count_all_entries($pdo, $_SESSION['user_id']) > 0) {
    header('Location: ' . current_flow_url($pdo, $_SESSION['user_id']));
    exit;
}

choose_first_survey($pdo, $_SESSION['user_id'], $type);

header('Location: ' . survey_page_url($type));
exit;