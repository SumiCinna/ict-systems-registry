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
    return 'review.php';
}

function require_stage(PDO $pdo, int $userId, string $requiredStage): void
{
    $stage = get_survey_stage($pdo, $userId);
    if ($stage !== $requiredStage) {
        header('Location: ' . stage_redirect_target($stage));
        exit;
    }
}