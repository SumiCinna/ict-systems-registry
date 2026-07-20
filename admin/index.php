<?php
require_once __DIR__ . '/../includes/admin_guard.php';
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT agency_name FROM users WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$success = $_SESSION['admin_success'] ?? null;
$errors = $_SESSION['admin_errors'] ?? [];
unset($_SESSION['admin_success'], $_SESSION['admin_errors']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$usersStmt = $pdo->query(
    'SELECT u.id, u.agency_name, u.last_name, u.first_name, u.middle_initial, u.email,
            u.survey_stage, u.is_admin, u.is_disabled, u.submitted_at,
            (SELECT COUNT(*) FROM application_systems a WHERE a.user_id = u.id) AS app_count,
            (SELECT COUNT(*) FROM ict_projects p WHERE p.user_id = u.id) AS proj_count
     FROM users u
     ORDER BY u.agency_name ASC, u.last_name ASC'
);
$users = $usersStmt->fetchAll();

$adminCount = 0;
foreach ($users as $u) {
    if ((int) $u['is_admin'] === 1) {
        $adminCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — ICT Systems Registry</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          ledger: { navy: '#0B2340', steel: '#1B4B72', gold: '#C9A227', paper: '#F7F5EF', line: '#D9D3C3', ink: '#28313A', muted: '#5B6B79' }
        },
        fontFamily: { display: ['Georgia', 'Cambria', 'Times New Roman', 'serif'], body: ['"Inter"', 'system-ui', 'sans-serif'] }
      }
    }
  }
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-ledger-paper font-body text-ledger-ink min-h-screen">

<header class="bg-ledger-navy text-white">
  <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
    <div>
      <p class="text-[10px] tracking-[0.25em] uppercase text-white/60">ICT Systems &amp; Projects Registry</p>
      <p class="font-display text-lg">Admin Panel</p>
    </div>
    <div class="flex items-center gap-4">
      <a href="../survey.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">MY SURVEY</a>
      <a href="../logout.php" class="text-xs font-semibold tracking-wide border border-white/30 px-4 py-2 hover:bg-white/10 transition-colors">LOG OUT</a>
    </div>
  </div>
</header>

<div class="max-w-6xl mx-auto px-6 py-10">

  <div class="text-center mb-8">
    <p class="text-xs tracking-[0.25em] uppercase text-ledger-muted mb-1">Administration</p>
    <h1 class="font-display text-2xl md:text-3xl text-ledger-navy">Registered Accounts</h1>
    <div class="ledger-rule mt-4 mx-auto" style="max-width: 220px;"></div>
  </div>

  <?php if ($success): ?>
    <div class="border border-green-300 bg-green-50 text-green-800 text-sm px-4 py-3 mb-6" role="status">
      <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="border border-red-300 bg-red-50 text-red-800 text-sm px-4 py-3 mb-6" role="alert">
      <ul class="list-disc list-inside space-y-0.5">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="bg-white border border-ledger-line shadow-sm overflow-x-auto">
    <table class="w-full text-xs">
      <thead>
        <tr class="bg-ledger-navy text-white text-left">
          <th class="px-4 py-3 font-semibold">Agency</th>
          <th class="px-4 py-3 font-semibold">Respondent</th>
          <th class="px-4 py-3 font-semibold">Email</th>
          <th class="px-4 py-3 font-semibold">Stage</th>
          <th class="px-4 py-3 font-semibold">Entries</th>
          <th class="px-4 py-3 font-semibold">Status</th>
          <th class="px-4 py-3 font-semibold">Role</th>
          <th class="px-4 py-3 font-semibold">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <?php
          $fullName = trim($u['first_name'] . ' ' . ($u['middle_initial'] !== null && $u['middle_initial'] !== '' ? $u['middle_initial'] . '. ' : '') . $u['last_name']);
          $isSelf = (int) $u['id'] === (int) $_SESSION['user_id'];
          $isDisabled = (int) $u['is_disabled'] === 1;
          $isAdmin = (int) $u['is_admin'] === 1;
        ?>
        <tr class="border-t border-ledger-line align-top">
          <td class="px-4 py-3"><?= htmlspecialchars($u['agency_name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= htmlspecialchars(ucfirst($u['survey_stage']), ENT_QUOTES, 'UTF-8') ?></td>
          <td class="px-4 py-3"><?= (int) $u['app_count'] ?> / <?= (int) $u['proj_count'] ?></td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 text-[10px] font-semibold uppercase <?= $isDisabled ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
              <?= $isDisabled ? 'Disabled' : 'Active' ?>
            </span>
          </td>
          <td class="px-4 py-3">
            <span class="px-2 py-1 text-[10px] font-semibold uppercase <?= $isAdmin ? 'bg-ledger-gold/20 text-ledger-navy' : 'bg-gray-100 text-ledger-muted' ?>">
              <?= $isAdmin ? 'Admin' : 'User' ?>
            </span>
          </td>
          <td class="px-4 py-3 space-y-1.5">
            <a href="user-view.php?id=<?= (int) $u['id'] ?>" class="block text-ledger-steel font-semibold hover:text-ledger-navy">View Submissions</a>

            <form action="toggle-disable.php" method="POST" class="inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="block <?= $isDisabled ? 'text-green-700' : 'text-red-600' ?> font-semibold hover:underline <?= $isSelf ? 'opacity-40 cursor-not-allowed' : '' ?>" <?= $isSelf ? 'disabled' : '' ?>>
                <?= $isDisabled ? 'Enable Account' : 'Disable Account' ?>
              </button>
            </form>

            <form action="toggle-admin.php" method="POST" class="inline">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <button type="submit" class="block text-ledger-steel font-semibold hover:text-ledger-navy hover:underline <?= ($isSelf && $adminCount <= 1) ? 'opacity-40 cursor-not-allowed' : '' ?>" <?= ($isSelf && $adminCount <= 1) ? 'disabled' : '' ?>>
                <?= $isAdmin ? 'Revoke Admin' : 'Grant Admin' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="8" class="px-4 py-6 text-center text-ledger-muted">No accounts yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

</body>
</html>