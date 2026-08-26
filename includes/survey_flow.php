<?php
function count_all_entries(PDO $pdo, int $userId): int
{
    $batch = get_active_batch($pdo, $userId);

    $appStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id AND batch = :batch');
    $appStmt->execute(['id' => $userId, 'batch' => $batch]);
    $appCount = (int) $appStmt->fetch()['total'];

    $ictStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id AND batch = :batch');
    $ictStmt->execute(['id' => $userId, 'batch' => $batch]);
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

    $stage = $row['survey_stage'] ?: 'choose';
    $normalizedStage = normalize_survey_stage($stage, $row['first_survey_type']);
    return ['stage' => $normalizedStage, 'first_survey_type' => $row['first_survey_type']];
}

function normalize_survey_stage(string $stage, ?string $first): string
{
    if ($stage === 'first' && $first !== null) {
        return $first;
    }
    if ($stage === 'second' && $first !== null) {
        return other_survey_type($first);
    }
    if ($stage === '') {
        return 'choose';
    }
    return $stage;
}

function get_survey_stage(PDO $pdo, int $userId): string
{
    return get_user_flow($pdo, $userId)['stage'];
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
    if ($stage === 'systems' || $stage === 'projects') {
        return survey_page_url($stage);
    }
    if ($stage === 'review') {
        return 'review.php';
    }
    if ($stage === 'submitted') {
        return 'survey.php';
    }
    return 'survey.php';
}

function choose_first_survey(PDO $pdo, int $userId, string $type): void
{
    $stmt = $pdo->prepare('UPDATE users SET first_survey_type = :type, survey_stage = :stage WHERE id = :id');
    $stmt->execute(['type' => $type, 'stage' => 'first', 'id' => $userId]);
}

function set_survey_stage(PDO $pdo, int $userId, string $stage): void
{
    $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage WHERE id = :id');
    $stmt->execute(['stage' => $stage, 'id' => $userId]);
}

function require_survey_access(PDO $pdo, int $userId, string $pageType): void
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];

    if ($stage === 'submitted' || $stage === 'review') {
        return;
    }
    if ($stage === $pageType) {
        return;
    }

    header('Location: ' . current_flow_url($pdo, $userId));
    exit;
}

// Like require_survey_access, but also allows read-only access to a
// survey's summary once that survey type has already been completed —
// even after the flow has since moved on to the other survey. Without
// this, a completed survey's summary is just as locked as its entry
// form, leaving no page to "go back" to mid-flow.
function require_summary_access(PDO $pdo, int $userId, string $pageType): void
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];

    if ($stage === 'submitted' || $stage === 'review' || $stage === $pageType) {
        return;
    }

    $progress = get_survey_progress($pdo, $userId);
    $done = $pageType === 'systems' ? $progress['app_done'] : $progress['ict_done'];
    if ($done) {
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

    if ($stage !== $pageType) {
        header('Location: ' . current_flow_url($pdo, $userId));
        exit;
    }

    $nextStage = other_survey_type($stage);
    if ($first !== null && $nextStage === $first) {
        set_survey_stage($pdo, $userId, 'review');
        header('Location: review.php');
        exit;
    }

    set_survey_stage($pdo, $userId, 'second');
    header('Location: ' . survey_page_url($nextStage));
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

function require_not_submitted(PDO $pdo, int $userId): void
{
    if (get_user_flow($pdo, $userId)['stage'] === 'submitted') {
        header('Location: survey.php');
        exit;
    }
}

function mark_survey_done(PDO $pdo, int $userId, string $type): void
{
    if ($type === 'app') {
        $stmt = $pdo->prepare('UPDATE users SET app_systems_done = 1 WHERE id = :id');
    } else {
        $stmt = $pdo->prepare('UPDATE users SET ict_projects_done = 1 WHERE id = :id');
    }
    $stmt->execute(['id' => $userId]);
}

function finalize_submission(PDO $pdo, int $userId): void
{
    $stmt = $pdo->prepare('UPDATE users SET survey_stage = :stage, submitted_at = NOW(), current_batch = current_batch + 1 WHERE id = :id');
    $stmt->execute(['stage' => 'submitted', 'id' => $userId]);
}

function get_current_batch(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT current_batch FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['current_batch'] : 1;
}

// finalize_submission() bumps current_batch the instant a submission is
// finalized, so the batch that actually holds the submitted entries is
// one behind whatever current_batch now points to. Anything that needs
// to count or list "this user's real entries" — whether still in
// progress or already submitted — should resolve the batch through
// here instead of calling get_current_batch() directly.
function get_active_batch(PDO $pdo, int $userId): int
{
    $stage = get_survey_stage($pdo, $userId);
    $currentBatch = get_current_batch($pdo, $userId);
    return $stage === 'submitted' ? max(1, $currentBatch - 1) : $currentBatch;
}

function get_survey_progress(PDO $pdo, int $userId): array
{
    $flow = get_user_flow($pdo, $userId);
    $stage = $flow['stage'];
    $first = $flow['first_survey_type'];
    $batch = get_active_batch($pdo, $userId);

    // Only count entries belonging to the CURRENT submission cycle. Rows
    // from a prior completed cycle carry an older batch number and must
    // not make a fresh cycle look like it already has entries.
    $appStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM application_systems WHERE user_id = :id AND batch = :batch');
    $appStmt->execute(['id' => $userId, 'batch' => $batch]);
    $appCount = (int) $appStmt->fetch()['total'];

    $ictStmt = $pdo->prepare('SELECT COUNT(*) AS total FROM ict_projects WHERE user_id = :id AND batch = :batch');
    $ictStmt->execute(['id' => $userId, 'batch' => $batch]);
    $ictCount = (int) $ictStmt->fetch()['total'];

    $appDone = false;
    $ictDone = false;

    if ($first !== null) {
        if ($first === 'systems') {
            $appDone = in_array($stage, ['projects', 'review', 'submitted'], true);
            $ictDone = in_array($stage, ['review', 'submitted'], true);
        } else {
            $ictDone = in_array($stage, ['systems', 'review', 'submitted'], true);
            $appDone = in_array($stage, ['review', 'submitted'], true);
        }
    }

    return [
        'stage' => $stage,
        'first_survey_type' => $first,
        'app_count' => $appCount,
        'ict_count' => $ictCount,
        'app_done' => $appDone,
        'ict_done' => $ictDone,
        'both_done' => $appDone && $ictDone,
    ];
}