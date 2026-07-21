<?php
function count_all_entries(PDO $pdo, int $userId): int
{
    $appStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id');
    $appStmt->execute(['id' => $userId]);
    $appCount = (int) $appStmt->fetch()['total'];

    $ictStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
    $ictStmt->execute(['id' => $userId]);
    $ictCount = (int) $ictStmt->fetch()['total'];

    return $appCount + $ictCount;
}

function get_user_flow(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare('SELECT survey_stage, first_survey_type FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['stage' => 'choose', 'first_survey_type' => null];
    }
    return ['stage' => $row['survey_stage'], 'first_survey_type' => $row['first_survey_type']];
}

function other_survey_type(string $type): string
{
    return $type === 'systems' ? 'projects' : 'systems';
}

function survey_page_url(string $type): string
{
    return $type === 'systems' ? 'application-systems.php' : 'ict-projects.php';
}

function survey_summary_url(string $type): string
{
    return $type === 'systems' ? 'application-systems-summary.php' : 'ict-projects-summary.php';
}

function survey_label(string $type): string
{
    return $type === 'systems' ? 'List of Application Systems' : 'List of ICT Projects';
}

function current_flow_url(PDO $pdo, int $userId): string
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];
    $first = $flow['first_survey_type'];

    if ($stage === 'choose' || $first === null) {
        return 'survey.php';
    }
    if ($stage === 'first') {
        return survey_page_url($first);
    }
    if ($stage === 'second') {
        return survey_page_url(other_survey_type($first));
    }
    return 'review.php';
}

function choose_first_survey(PDO $pdo, int $userId, string $type): void
{
    $stmt = $pdo->prepare('UPDATE users SET first_survey_type = :type, survey_stage = :stage WHERE id = :id');
    $stmt->execute(['type' => $type, 'stage' => 'first', 'id' => $userId]);
}

function require_survey_access(PDO $pdo, int $userId, string $pageType): void
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];
    $first = $flow['first_survey_type'];

    if ($stage === 'submitted') {
        return;
    }
    if ($stage === 'first' && $pageType === $first) {
        return;
    }
    if ($stage === 'second' && $first !== null && $pageType === other_survey_type($first)) {
        return;
    }
    header('Location: ' . current_flow_url($pdo, $userId));
    exit;
}

function confirm_survey_step(PDO $pdo, int $userId, string $pageType): void
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];
    $first = $flow['first_survey_type'];

    if ($stage === 'submitted') {
        header('Location: survey.php');
        exit;
    }

    if ($stage === 'first' && $pageType === $first) {
        $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage WHERE id = :id');
        $stmt->execute(['stage' => 'second', 'id' => $userId]);
        header('Location: ' . survey_page_url(other_survey_type($first)));
        exit;
    }

    if ($stage === 'second' && $first !== null && $pageType === other_survey_type($first)) {
        $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage WHERE id = :id');
        $stmt->execute(['stage' => 'review', 'id' => $userId]);
        header('Location: review.php');
        exit;
    }

    header('Location: ' . current_flow_url($pdo, $userId));
    exit;
}

function require_review_stage(PDO $pdo, int $userId): void
{
    $flow = get_user_flow($pdo, $userId);
    if ($flow['stage'] !== 'review') {
        header('Location: ' . current_flow_url($pdo, $userId));
        exit;
    }
}

function finalize_submission(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage, submitted_at = NOW() WHERE id = :id');
    $stmt->execute(['stage' => 'submitted', 'id' => $userId]);
}

function get_survey_progress(PDO $pdo, int $userId): array
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];
    $first = $flow['first_survey_type'];

    $appStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id');
    $appStmt->execute(['id' => $userId]);
    $appCount = (int) $appStmt->fetch()['total'];

    $ictStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id');
    $ictStmt->execute(['id' => $userId]);
    $ictCount = (int) $ictStmt->fetch()['total'];

    $appDone = false;
    $ictDone = false;

    if ($first !== null) {
        $firstDoneStages = ['second', 'review', 'submitted'];
        $secondDoneStages = ['review', 'submitted'];
        if ($first === 'systems') {
            $appDone = in_array($stage, $firstDoneStages, true);
            $ictDone = in_array($stage, $secondDoneStages, true);
        } else {
            $ictDone = in_array($stage, $firstDoneStages, true);
            $appDone = in_array($stage, $secondDoneStages, true);
        }
    }

    return [
        'stage' => $stage,
        'first_survey_type' => $first,
        'app_count' => $appCount,
        'ict_count' => $ictCount,
        'app_done' => $appDone,
        'ict_done' => $ictDone,
    ];
}