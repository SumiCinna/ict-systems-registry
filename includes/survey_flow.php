<?php
function get_survey_stage(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare('SELECT survey_stage FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    return $row ? $row['survey_stage'] : 'systems';
}

function set_survey_stage(PDO $pdo, int $userId, string $stage): void
{
    if ($stage === 'submitted') {
        $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage, submitted_at = NOW() WHERE id = :id');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage WHERE id = :id');
    }
    $stmt->execute(['stage' => $stage, 'id' => $userId]);
}

function stage_redirect_target(string $stage): string
{
    if ($stage === 'systems') {
        return 'application-systems.php';
    }
    if ($stage === 'projects') {
        return 'ict-projects.php';
    }
    if ($stage === 'submitted') {
        return 'survey.php';
    }
    return 'final-review.php';
}

function require_stage(PDO $pdo, int $userId, string $requiredStage): void
{
    $stage = get_survey_stage($pdo, $userId);
    if ($stage !== $requiredStage) {
        header('Location: ' . stage_redirect_target($stage));
        exit;
    }
}

function require_not_submitted(PDO $pdo, int $userId, string $requiredStage): void
{
    $stage = get_survey_stage($pdo, $userId);
    if ($stage === 'submitted') {
        return;
    }
    if ($stage !== $requiredStage) {
        header('Location: ' . stage_redirect_target($stage));
        exit;
    }
}

function get_survey_progress(PDO $pdo, int $userId): array
{
    $stageStmt = $pdo->prepare('SELECT survey_stage, app_systems_done, ict_projects_done FROM users WHERE id = :id LIMIT 1');
    $stageStmt->execute(['id' => $userId]);
    $row = $stageStmt->fetch();

    $stage = $row ? $row['survey_stage'] : 'systems';
    $appDone = $row ? (bool) $row['app_systems_done'] : false;
    $ictDone = $row ? (bool) $row['ict_projects_done'] : false;

    $appStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id');
    $appStmt->execute(['id' => $userId]);
    $appCount = (int) $appStmt->fetch()['total'];

    $ictStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
    $ictStmt->execute(['id' => $userId]);
    $ictCount = (int) $ictStmt->fetch()['total'];

    return [
        'stage' => $stage,
        'app_count' => $appCount,
        'ict_count' => $ictCount,
        'app_done' => $appDone,
        'ict_done' => $ictDone,
        'both_done' => $appDone && $ictDone,
    ];
}

function mark_survey_done(PDO $pdo, int $userId, string $survey): void
{
    if ($survey === 'app') {
        $stmt = $pdo->prepare('UPDATE users SET app_systems_done = 1 WHERE id = :id');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET ict_projects_done = 1 WHERE id = :id');
    }
    $stmt->execute(['id' => $userId]);
}

function enforce_survey_lock(PDO $pdo, int $userId, string $survey): void
{
    $progress = get_survey_progress($pdo, $userId);
    if ($survey === 'app' && $progress['app_done']) {
        return;
    }
    if ($survey === 'ict' && $progress['ict_done']) {
        return;
    }
    header('Location: survey.php');
    exit;
}

function set_flow_stage(PDO $pdo, int $userId, string $stage): void
{
    set_survey_stage($pdo, $userId, $stage);
}