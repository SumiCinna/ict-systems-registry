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
$userId = $_SESSION['user_id'];

$progress = get_survey_progress($pdo, $userId);
if (!$progress['both_done']) {
    header('Location: survey.php');
    exit;
}

$start = $_POST['start'] ?? 'app';
if ($start !== 'app' && $start !== 'ict') {
    $start = 'app';
}

$stmt = $pdo->prepare('UPDATE users SET app_systems_done = 0, ict_projects_done = 0, survey_stage = :stage WHERE id = :id');
$stmt->execute(['stage' => 'systems', 'id' => $userId]);

header('Location: ' . ($start === 'ict' ? 'ict-projects.php' : 'application-systems.php'));
exit;
