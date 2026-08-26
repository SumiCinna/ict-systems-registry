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

// Already mid-round (a survey type was picked for this round and it isn't
// finished yet) — send them back into that round instead of letting them
// switch picks mid-flow.
if (in_array($flow['stage'], ['systems', 'projects', 'review'], true)) {
    header('Location: ' . current_flow_url($pdo, $_SESSION['user_id']));
    exit;
}

// stage is 'choose' (never started) or 'submitted' (finished a previous
// round, starting a fresh one) — safe to pick a survey and begin.
choose_first_survey($pdo, $_SESSION['user_id'], $type);

header('Location: ' . survey_page_url($type));
exit;